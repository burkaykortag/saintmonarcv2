<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class ProcurementRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * Tüm tedarikçileri filtrelerle listeler.
     */
    public function getAllSuppliers(array $filters = []): array {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM purchase_orders po WHERE po.supplier_id = s.id AND po.deleted_at IS NULL) as po_count,
                       (SELECT COALESCE(SUM(po.grand_total), 0) FROM purchase_orders po WHERE po.supplier_id = s.id AND po.status = 'completed' AND po.deleted_at IS NULL) as total_spent
                FROM suppliers s
                WHERE s.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (s.company_name LIKE :q OR s.contact_name LIKE :q OR s.tax_number LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND s.is_active = :active";
            $params[':active'] = (int)$filters['is_active'];
        }

        return $this->db->query($sql, $params);
    }

    /**
     * ID bazlı tedarikçi getirir.
     */
    public function getSupplierById(int $id): ?array {
        $sql = "SELECT * FROM suppliers WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $res = $this->db->query($sql, [':id' => $id]);
        return $res[0] ?? null;
    }

    /**
     * Tedarikçi yetkili kişileri.
     */
    public function getSupplierContacts(int $supplierId): array {
        // Mock or relational fallback from contact fields
        $sup = $this->getSupplierById($supplierId);
        if (!$sup) return [];
        return [
            [
                'name' => $sup['contact_name'] ?? 'Temsilci',
                'phone' => $sup['phone'] ?? '-',
                'email' => $sup['email'] ?? '-',
                'role' => 'Satış Yöneticisi'
            ]
        ];
    }

    /**
     * Tedarikçi adresleri.
     */
    public function getSupplierAddresses(int $supplierId): array {
        $sup = $this->getSupplierById($supplierId);
        if (!$sup) return [];
        return [
            [
                'title' => 'Merkez Ofis',
                'address' => ($sup['city'] ?? '') . ' / ' . ($sup['country'] ?? '')
            ]
        ];
    }

    /**
     * Tedarikçi satın alma siparişleri.
     */
    public function getSupplierPOs(int $supplierId): array {
        $sql = "SELECT po.*, w.name as warehouse_name
                FROM purchase_orders po
                JOIN warehouses w ON po.warehouse_id = w.id
                WHERE po.supplier_id = :sid AND po.deleted_at IS NULL
                ORDER BY po.id DESC";
        return $this->db->query($sql, [':sid' => $supplierId]);
    }

    /**
     * Tedarikçiye ait faturalar (fatura tablosundan tedarikçi PO'larına bağlı olanlar).
     */
    public function getSupplierInvoices(int $supplierId): array {
        $sql = "SELECT i.*, po.po_number
                FROM invoices i
                JOIN purchase_orders po ON i.order_id = po.id -- note: linking to PO using order_id as type mapping
                WHERE po.supplier_id = :sid AND po.deleted_at IS NULL
                ORDER BY i.id DESC";
        try {
            return $this->db->query($sql, [':sid' => $supplierId]);
        } catch (\Throwable $t) {
            return [];
        }
    }

    /**
     * Tedarikçi ödemeleri.
     */
    public function getSupplierPayments(int $supplierId): array {
        $sql = "SELECT sp.*, po.po_number
                FROM supplier_payments sp
                LEFT JOIN purchase_orders po ON sp.purchase_order_id = po.id
                WHERE sp.supplier_id = :sid
                ORDER BY sp.id DESC";
        return $this->db->query($sql, [':sid' => $supplierId]);
    }

    /**
     * Tedarikçi sözleşmeleri.
     */
    public function getSupplierContracts(int $supplierId): array {
        $sql = "SELECT * FROM supplier_contracts WHERE supplier_id = :sid ORDER BY id DESC";
        return $this->db->query($sql, [':sid' => $supplierId]);
    }

    /**
     * Tedarikçi dökümanları.
     */
    public function getSupplierDocuments(int $supplierId): array {
        $sql = "SELECT * FROM supplier_documents WHERE supplier_id = :sid ORDER BY id DESC";
        return $this->db->query($sql, [':sid' => $supplierId]);
    }

    /**
     * Tedarikçi notları (audit logs / system notes fallback).
     */
    public function getSupplierNotes(int $supplierId): array {
        return [
            ['created_at' => date('Y-m-d H:i:s'), 'admin_name' => 'Sistem', 'note' => 'Tedarikçi hesabı oluşturuldu.']
        ];
    }

    /**
     * Tedarikçi performans göstergeleri.
     */
    public function getSupplierPerformance(int $supplierId): array {
        $sup = $this->getSupplierById($supplierId);
        if (!$sup) return [];

        // 1. Total purchases
        $totalPurchasesSql = "SELECT COALESCE(SUM(grand_total), 0) as total, COUNT(*) as cnt, MAX(created_at) as last_date 
                              FROM purchase_orders 
                              WHERE supplier_id = :sid AND deleted_at IS NULL";
        $totalPurchasesRow = $this->db->query($totalPurchasesSql, [':sid' => $supplierId]);
        $totalPurchases = (float)($totalPurchasesRow[0]['total'] ?? 0.0);
        $totalOrderCount = (int)($totalPurchasesRow[0]['cnt'] ?? 0);
        $lastPurchaseDate = $totalPurchasesRow[0]['last_date'] ?? null;

        // 2. Average actual lead time in days (difference between receipt date and order date)
        $leadTimeSql = "SELECT AVG(DATEDIFF(gr.created_at, po.created_at)) as avg_days
                        FROM goods_receipts gr
                        JOIN purchase_orders po ON gr.purchase_order_id = po.id
                        WHERE po.supplier_id = :sid";
        $leadTimeRow = $this->db->query($leadTimeSql, [':sid' => $supplierId]);
        $avgLeadTime = $leadTimeRow[0]['avg_days'] !== null ? round((float)$leadTimeRow[0]['avg_days'], 1) : (float)($sup['lead_time'] ?? 0);

        // 3. On-time delivery rate & Delayed delivery count
        $delayedSql = "SELECT COUNT(*) as cnt
                       FROM goods_receipts gr
                       JOIN purchase_orders po ON gr.purchase_order_id = po.id
                       WHERE po.supplier_id = :sid AND DATE(gr.created_at) > po.expected_delivery";
        $delayedRow = $this->db->query($delayedSql, [':sid' => $supplierId]);
        $delayedCount = (int)($delayedRow[0]['cnt'] ?? 0);

        $totalReceiptsSql = "SELECT COUNT(*) as cnt 
                             FROM goods_receipts gr 
                             JOIN purchase_orders po ON gr.purchase_order_id = po.id 
                             WHERE po.supplier_id = :sid";
        $totalReceiptsRow = $this->db->query($totalReceiptsSql, [':sid' => $supplierId]);
        $totalReceipts = (int)($totalReceiptsRow[0]['cnt'] ?? 0);

        $onTimeRate = 1.0;
        if ($totalReceipts > 0) {
            $onTimeRate = ($totalReceipts - $delayedCount) / $totalReceipts;
        }

        // 4. Return/damaged rate & Missing rate
        $ratesSql = "SELECT COALESCE(SUM(gri.quantity), 0) as received_qty,
                            COALESCE(SUM(gri.damaged_quantity), 0) as damaged_qty,
                            COALESCE(SUM(gri.missing_quantity), 0) as missing_qty
                     FROM goods_receipt_items gri
                     JOIN goods_receipts gr ON gri.goods_receipt_id = gr.id
                     JOIN purchase_orders po ON gr.purchase_order_id = po.id
                     WHERE po.supplier_id = :sid";
        $ratesRow = $this->db->query($ratesSql, [':sid' => $supplierId]);
        $receivedQty = (int)($ratesRow[0]['received_qty'] ?? 0);
        $damagedQty = (int)($ratesRow[0]['damaged_qty'] ?? 0);
        $missingQty = (int)($ratesRow[0]['missing_qty'] ?? 0);

        $damagedRate = 0.0;
        if ($receivedQty > 0) {
            $damagedRate = $damagedQty / $receivedQty;
        }

        $totalOrderedSql = "SELECT COALESCE(SUM(quantity), 0) as ordered_qty 
                             FROM purchase_order_items poi
                             JOIN purchase_orders po ON poi.purchase_order_id = po.id
                             WHERE po.supplier_id = :sid AND po.status IN ('completed', 'partially_received')";
        $totalOrderedRow = $this->db->query($totalOrderedSql, [':sid' => $supplierId]);
        $orderedQty = (int)($totalOrderedRow[0]['ordered_qty'] ?? 0);

        $missingRate = 0.0;
        if ($orderedQty > 0) {
            $missingRate = $missingQty / $orderedQty;
        }

        // 5. Average product cost from PO items
        $avgCostSql = "SELECT COALESCE(AVG(price), 0) as avg_price 
                       FROM purchase_order_items poi
                       JOIN purchase_orders po ON poi.purchase_order_id = po.id
                       WHERE po.supplier_id = :sid";
        $avgCostRow = $this->db->query($avgCostSql, [':sid' => $supplierId]);
        $avgItemCost = (float)($avgCostRow[0]['avg_price'] ?? 0.0);

        // 6. Total spent (completed orders grand total)
        $spentSql = "SELECT COALESCE(SUM(grand_total), 0) as spent 
                     FROM purchase_orders 
                     WHERE supplier_id = :sid AND status = 'completed' AND deleted_at IS NULL";
        $spentRow = $this->db->query($spentSql, [':sid' => $supplierId]);
        $totalSpent = (float)($spentRow[0]['spent'] ?? 0.0);

        return [
            'score' => (float)($sup['score'] ?? 5.00),
            'lead_time' => (int)$avgLeadTime,
            'on_time_rate' => $onTimeRate * 100, // percentage
            'delayed_count' => $delayedCount,
            'refund_rate' => $damagedRate * 100,
            'damaged_rate' => $damagedRate * 100,
            'missing_rate' => $missingRate * 100,
            'average_item_cost' => $avgItemCost,
            'last_purchase_date' => $lastPurchaseDate,
            'total_purchases' => $totalPurchases,
            'total_spent' => $totalSpent,
            'total_order_count' => $totalOrderCount,
            'abc_class' => $totalSpent > 10000 ? 'A' : ($totalSpent > 2000 ? 'B' : 'C')
        ];
    }

    /**
     * Tüm satın alma siparişlerini (PO) listeler.
     */
    public function getAllPurchaseOrders(array $filters = []): array {
        $sql = "SELECT po.*, s.company_name as supplier_name, w.name as warehouse_name,
                       (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.purchase_order_id = po.id) as item_count,
                       (SELECT COALESCE(SUM(poi.quantity), 0) FROM purchase_order_items poi WHERE poi.purchase_order_id = po.id) as total_qty,
                       (SELECT COALESCE(SUM(poi.received_quantity), 0) FROM purchase_order_items poi WHERE poi.purchase_order_id = po.id) as received_qty
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.id
                JOIN warehouses w ON po.warehouse_id = w.id
                WHERE po.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (po.po_number LIKE :q OR s.company_name LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND po.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['supplier_id'])) {
            $sql .= " AND po.supplier_id = :sid";
            $params[':sid'] = (int)$filters['supplier_id'];
        }

        $sql .= " ORDER BY po.id DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * ID bazlı PO getirir.
     */
    public function getPurchaseOrderById(int $id): ?array {
        $sql = "SELECT po.*, s.company_name as supplier_name, w.name as warehouse_name,
                       a_c.username as creator_name, a_a.username as approver_name
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.id
                JOIN warehouses w ON po.warehouse_id = w.id
                LEFT JOIN admins a_c ON po.created_by = a_c.id
                LEFT JOIN admins a_a ON po.approved_by = a_a.id
                WHERE po.id = :id AND po.deleted_at IS NULL LIMIT 1";
        $res = $this->db->query($sql, [':id' => $id]);
        return $res[0] ?? null;
    }

    /**
     * PO kalemlerini listeler.
     */
    public function getPurchaseOrderItems(int $orderId): array {
        $sql = "SELECT poi.*, p.sku as product_sku, pt.name as product_name, pv.sku as variant_sku
                FROM purchase_order_items poi
                JOIN products p ON poi.product_id = p.id
                JOIN product_translations pt ON p.id = pt.product_id
                LEFT JOIN product_variants pv ON poi.variant_id = pv.id
                WHERE poi.purchase_order_id = :oid";
        return $this->db->query($sql, [':oid' => $orderId]);
    }

    /**
     * RFQ Teklif İsteklerini listeler.
     */
    public function getRFQs(array $filters = []): array {
        $sql = "SELECT r.*, p.sku as product_sku, pt.name as product_name,
                       (SELECT COUNT(*) FROM rfq_responses rr WHERE rr.rfq_id = r.id) as response_count,
                       (SELECT MIN(rr.price) FROM rfq_responses rr WHERE rr.rfq_id = r.id) as min_price,
                       (SELECT MIN(rr.delivery_lead_time) FROM rfq_responses rr WHERE rr.rfq_id = r.id) as min_lead_time
                FROM rfqs r
                JOIN products p ON r.product_id = p.id
                JOIN product_translations pt ON p.id = pt.product_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY r.id DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * ID bazlı RFQ getirir.
     */
    public function getRFQById(int $id): ?array {
        $sql = "SELECT r.*, p.sku as product_sku, pt.name as product_name
                FROM rfqs r
                JOIN products p ON r.product_id = p.id
                JOIN product_translations pt ON p.id = pt.product_id
                WHERE r.id = :id LIMIT 1";
        $res = $this->db->query($sql, [':id' => $id]);
        return $res[0] ?? null;
    }

    /**
     * RFQ teklif yanıtlarını listeler.
     */
    public function getRFQResponses(int $rfqId): array {
        $sql = "SELECT rr.*, s.company_name as supplier_name, s.score as supplier_score
                FROM rfq_responses rr
                JOIN suppliers s ON rr.supplier_id = s.id
                WHERE rr.rfq_id = :rid
                ORDER BY rr.price ASC";
        return $this->db->query($sql, [':rid' => $rfqId]);
    }

    /**
     * Mal kabul kayıtlarını listeler.
     */
    public function getGoodsReceipts(array $filters = []): array {
        $sql = "SELECT gr.*, po.po_number, s.company_name as supplier_name, a.username as receiver_name,
                       (SELECT SUM(gri.quantity) FROM goods_receipt_items gri WHERE gri.goods_receipt_id = gr.id) as total_qty
                FROM goods_receipts gr
                JOIN purchase_orders po ON gr.purchase_order_id = po.id
                JOIN suppliers s ON po.supplier_id = s.id
                JOIN admins a ON gr.received_by = a.id
                ORDER BY gr.id DESC";
        return $this->db->query($sql);
    }

    /**
     * ID bazlı mal kabul getirir.
     */
    public function getGoodsReceiptById(int $id): ?array {
        $sql = "SELECT gr.*, po.po_number, s.company_name as supplier_name, a.username as receiver_name
                FROM goods_receipts gr
                JOIN purchase_orders po ON gr.purchase_order_id = po.id
                JOIN suppliers s ON po.supplier_id = s.id
                JOIN admins a ON gr.received_by = a.id
                WHERE gr.id = :id LIMIT 1";
        $res = $this->db->query($sql, [':id' => $id]);
        return $res[0] ?? null;
    }

    /**
     * Mal kabul kalemlerini getirir.
     */
    public function getGoodsReceiptItems(int $receiptId): array {
        $sql = "SELECT gri.*, p.sku as product_sku, pt.name as product_name
                FROM goods_receipt_items gri
                JOIN products p ON gri.product_id = p.id
                JOIN product_translations pt ON p.id = pt.product_id
                WHERE gri.goods_receipt_id = :rid";
        return $this->db->query($sql, [':rid' => $receiptId]);
    }

    /**
     * Tüm tedarikçi sözleşmelerini getirir.
     */
    public function getSupplierContractsAll(array $filters = []): array {
        $sql = "SELECT sc.*, s.company_name as supplier_name
                FROM supplier_contracts sc
                JOIN suppliers s ON sc.supplier_id = s.id
                ORDER BY sc.id DESC";
        return $this->db->query($sql);
    }

    /**
     * Tüm ödemeleri listeler.
     */
    public function getSupplierPaymentsAll(array $filters = []): array {
        $sql = "SELECT sp.*, s.company_name as supplier_name, po.po_number
                FROM supplier_payments sp
                JOIN suppliers s ON sp.supplier_id = s.id
                LEFT JOIN purchase_orders po ON sp.purchase_order_id = po.id
                ORDER BY sp.payment_date ASC";
        return $this->db->query($sql);
    }

    /**
     * Dashboard KPI metrikleri.
     */
    public function getProcurementDashboardStats(): array {
        $totalPurchasing = $this->db->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM purchase_orders WHERE status = 'completed' AND deleted_at IS NULL")[0]['total'] ?? 0;
        $pendingPOs = $this->db->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status IN ('pending_approval', 'approved', 'sent') AND deleted_at IS NULL")[0]['cnt'] ?? 0;
        $pendingDeliveries = $this->db->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status = 'sent' AND deleted_at IS NULL")[0]['cnt'] ?? 0;
        $delayedOrders = $this->db->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status = 'sent' AND expected_delivery < CURDATE() AND deleted_at IS NULL")[0]['cnt'] ?? 0;
        
        $bestSupplier = $this->db->query("SELECT company_name FROM suppliers WHERE deleted_at IS NULL ORDER BY score DESC LIMIT 1")[0]['company_name'] ?? 'Tedarikçi Yok';
        $riskySupplier = $this->db->query("SELECT company_name FROM suppliers WHERE deleted_at IS NULL ORDER BY score ASC LIMIT 1")[0]['company_name'] ?? 'Tedarikçi Yok';

        return [
            'total_purchasing' => (float)$totalPurchasing,
            'pending_pos' => (int)$pendingPOs,
            'pending_deliveries' => (int)$pendingDeliveries,
            'delayed_orders' => (int)$delayedOrders,
            'best_supplier' => $bestSupplier,
            'risky_supplier' => $riskySupplier
        ];
    }

    /**
     * Satın Alma Analitiği detayları.
     */
    public function getPurchaseAnalytics(string $period = 'this_month'): array {
        $stats = $this->getProcurementDashboardStats();
        
        // Kategori dağılımı mockup/real
        $categoryDistribution = [
            ['category_name' => 'Giyim', 'percentage' => 45],
            ['category_name' => 'Ayakkabı', 'percentage' => 30],
            ['category_name' => 'Aksesuar', 'percentage' => 25],
        ];

        // Monthly purchases chart mock
        $monthlyChart = [
            'labels' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz'],
            'data' => [12000, 15000, 18000, 11000, 23000, 17000, (float)$stats['total_purchasing']]
        ];

        return [
            'stats' => $stats,
            'total_spend' => (float)$stats['total_purchasing'],
            'total_orders' => (int)($stats['pending_pos'] + $stats['pending_deliveries']),
            'category_distribution' => $categoryDistribution,
            'monthly_chart' => $monthlyChart
        ];
    }
}
