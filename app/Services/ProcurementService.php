<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use App\Repositories\ProcurementRepository;
use App\Services\WarehouseService;
use App\Services\AuditLogger;
use App\Services\RbacService;
use Exception;

class ProcurementService {
    private ProcurementRepository $repository;
    private DatabaseInterface $db;
    private WarehouseService $warehouseService;
    private AuditLogger $auditLogger;
    private ?RbacService $rbacService = null;

    public function __construct(
        ProcurementRepository $repository,
        DatabaseInterface $db,
        WarehouseService $warehouseService,
        AuditLogger $auditLogger,
        ?RbacService $rbacService = null
    ) {
        $this->repository = $repository;
        $this->db = $db;
        $this->warehouseService = $warehouseService;
        $this->auditLogger = $auditLogger;
        $this->rbacService = $rbacService;
    }

    /**
     * Tedarikçi oluşturma.
     */
    public function createSupplier(array $data): int {
        if (empty($data['company_name'])) {
            throw new Exception("Şirket adı zorunludur.");
        }

        $status = $data['status'] ?? 'active';
        $isActive = ($status === 'active') ? 1 : 0;

        $sql = "INSERT INTO suppliers (company_name, tax_number, tax_office, contact_name, phone, email, country, city, district, address, zip_code, iban, notes, status, currency, payment_terms, lead_time, is_active, score)
                VALUES (:company_name, :tax_number, :tax_office, :contact_name, :phone, :email, :country, :city, :district, :address, :zip_code, :iban, :notes, :status, :currency, :payment_terms, :lead_time, :is_active, :score)";
        
        $this->db->execute($sql, [
            ':company_name' => $data['company_name'],
            ':tax_number' => $data['tax_number'] ?? null,
            ':tax_office' => $data['tax_office'] ?? null,
            ':contact_name' => $data['contact_name'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':country' => $data['country'] ?? null,
            ':city' => $data['city'] ?? null,
            ':district' => $data['district'] ?? null,
            ':address' => $data['address'] ?? null,
            ':zip_code' => $data['zip_code'] ?? null,
            ':iban' => $data['iban'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':status' => $status,
            ':currency' => $data['currency'] ?? 'TRY',
            ':payment_terms' => $data['payment_terms'] ?? null,
            ':lead_time' => (int)($data['lead_time'] ?? 0),
            ':is_active' => $isActive,
            ':score' => (float)($data['score'] ?? 5.00)
        ]);

        $id = (int)$this->db->lastInsertId();
        $this->auditLogger->logActivity('supplier_create', "Tedarikçi hesabı oluşturuldu (ID: {$id})");
        $this->auditLogger->logAudit('create', 'Supplier', $id, null, $data);

        return $id;
    }

    /**
     * Tedarikçi güncelleme.
     */
    public function updateSupplier(int $id, array $data): bool {
        if (empty($data['company_name'])) {
            throw new Exception("Şirket adı zorunludur.");
        }

        $status = $data['status'] ?? 'active';
        $isActive = ($status === 'active') ? 1 : 0;

        $sql = "UPDATE suppliers SET 
                    company_name = :company_name, tax_number = :tax_number, tax_office = :tax_office, contact_name = :contact_name, 
                    phone = :phone, email = :email, country = :country, city = :city, district = :district, 
                    address = :address, zip_code = :zip_code, iban = :iban, notes = :notes, status = :status, 
                    currency = :currency, payment_terms = :payment_terms, lead_time = :lead_time, is_active = :is_active, score = :score
                WHERE id = :id";
        
        $res = $this->db->execute($sql, [
            ':id' => $id,
            ':company_name' => $data['company_name'],
            ':tax_number' => $data['tax_number'] ?? null,
            ':tax_office' => $data['tax_office'] ?? null,
            ':contact_name' => $data['contact_name'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':country' => $data['country'] ?? null,
            ':city' => $data['city'] ?? null,
            ':district' => $data['district'] ?? null,
            ':address' => $data['address'] ?? null,
            ':zip_code' => $data['zip_code'] ?? null,
            ':iban' => $data['iban'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':status' => $status,
            ':currency' => $data['currency'] ?? 'TRY',
            ':payment_terms' => $data['payment_terms'] ?? null,
            ':lead_time' => (int)($data['lead_time'] ?? 0),
            ':is_active' => $isActive,
            ':score' => (float)($data['score'] ?? 5.00)
        ]);

        $this->auditLogger->logActivity('supplier_update', "Tedarikçi hesabı güncellendi (ID: {$id})");
        $this->auditLogger->logAudit('update', 'Supplier', $id, null, $data);
        return $res;
    }

    /**
     * Tedarikçi silme (Soft Delete).
     */
    public function deleteSupplier(int $id): bool {
        $sql = "UPDATE suppliers SET deleted_at = NOW(), status = 'passive', is_active = 0 WHERE id = :id";
        $res = $this->db->execute($sql, [':id' => $id]);
        $this->auditLogger->logActivity('supplier_delete', "Tedarikçi hesabı silindi (ID: {$id})");
        return $res;
    }

    /**
     * Satın Alma Siparişi (PO) oluşturma.
     */
    public function createPurchaseOrder(array $data, int $adminId): int {
        if (empty($data['supplier_id']) || empty($data['warehouse_id']) || empty($data['items'])) {
            throw new Exception("Tedarikçi, depo ve en az bir ürün kalemi zorunludur.");
        }

        $this->db->beginTransaction();
        try {
            // Sequential PO numbering: PO-YYYY-XXXXXX
            $year = date('Y');
            $lastPoRow = $this->db->query(
                "SELECT po_number FROM purchase_orders WHERE po_number LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE",
                [':prefix' => "PO-{$year}-%"]
            );
            if (!empty($lastPoRow) && preg_match('/PO-\d{4}-(\d+)/', $lastPoRow[0]['po_number'], $m)) {
                $nextSeq = (int)$m[1] + 1;
            } else {
                $nextSeq = 1;
            }
            $poNumber = sprintf('PO-%s-%06d', $year, $nextSeq);
            
            // Calculate totals
            $taxTotal = 0;
            $discountTotal = 0;
            $grandTotal = 0;

            foreach ($data['items'] as $item) {
                $qty = (int)$item['quantity'];
                $price = (float)$item['price'];
                $taxRate = (float)($item['tax_rate'] ?? 20.00);
                $disc = (float)($item['discount_amount'] ?? 0);
                
                $rowSubtotal = $qty * $price;
                $rowDiscount = $qty * $disc;
                $rowTax = ($rowSubtotal - $rowDiscount) * ($taxRate / 100);
                
                $taxTotal += $rowTax;
                $discountTotal += $rowDiscount;
                $grandTotal += ($rowSubtotal - $rowDiscount + $rowTax);
            }

            $sql = "INSERT INTO purchase_orders (po_number, supplier_id, warehouse_id, currency, status, expected_delivery, tax_total, discount_total, grand_total, created_by)
                    VALUES (:po_number, :supplier_id, :warehouse_id, :currency, 'draft', :expected_delivery, :tax_total, :discount_total, :grand_total, :created_by)";
            
            $this->db->execute($sql, [
                ':po_number' => $poNumber,
                ':supplier_id' => (int)$data['supplier_id'],
                ':warehouse_id' => (int)$data['warehouse_id'],
                ':currency' => $data['currency'] ?? 'TRY',
                ':expected_delivery' => $data['expected_delivery'] ?? null,
                ':tax_total' => $taxTotal,
                ':discount_total' => $discountTotal,
                ':grand_total' => $grandTotal,
                ':created_by' => $adminId
            ]);

            $poId = (int)$this->db->lastInsertId();

            // Insert items
            foreach ($data['items'] as $item) {
                $qty = (int)$item['quantity'];
                $price = (float)$item['price'];
                $taxRate = (float)($item['tax_rate'] ?? 20.00);
                $disc = (float)($item['discount_amount'] ?? 0);
                $rowTotal = ($qty * $price) - ($qty * $disc) + (($qty * $price) - ($qty * $disc)) * ($taxRate / 100);

                $sqlItem = "INSERT INTO purchase_order_items (purchase_order_id, product_id, variant_id, quantity, received_quantity, price, tax_rate, discount_amount, total)
                            VALUES (:po_id, :product_id, :variant_id, :quantity, 0, :price, :tax_rate, :discount_amount, :total)";
                
                $this->db->execute($sqlItem, [
                    ':po_id' => $poId,
                    ':product_id' => (int)$item['product_id'],
                    ':variant_id' => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    ':quantity' => $qty,
                    ':price' => $price,
                    ':tax_rate' => $taxRate,
                    ':discount_amount' => $disc,
                    ':total' => $rowTotal
                ]);
            }

            // Create pending payment record
            $sqlPayment = "INSERT INTO supplier_payments (purchase_order_id, supplier_id, amount, payment_date, status)
                           VALUES (:po_id, :supplier_id, :amount, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'pending')";
            $this->db->execute($sqlPayment, [
                ':po_id' => $poId,
                ':supplier_id' => (int)$data['supplier_id'],
                ':amount' => $grandTotal
            ]);

            $this->db->commit();
            $this->auditLogger->logActivity('purchase_order_create', "Satın alma siparişi oluşturuldu: {$poNumber} (ID: {$poId})");
            $this->auditLogger->logAudit('create', 'PurchaseOrder', $poId, null, $data);
            
            return $poId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Sipariş Durum Güncelleme ve Onay Akışı (RBAC Destekli).
     */
    public function updatePurchaseOrderStatus(int $id, string $status, ?int $adminId = null): bool {
        $allowed = ['draft', 'pending_approval', 'approved', 'sent', 'partially_received', 'completed', 'cancelled', 'closed'];
        if (!in_array($status, $allowed)) {
            throw new Exception("Geçersiz satın alma durumu: {$status}");
        }

        // RBAC: Approval requires approve_purchase_order or approve_purchase_orders permission
        if ($status === 'approved' && $adminId && $this->rbacService !== null) {
            $hasApprove = $this->rbacService->adminHasPermission($adminId, 'approve_purchase_order')
                       || $this->rbacService->adminHasPermission($adminId, 'approve_purchase_orders');
            if (!$hasApprove) {
                throw new Exception('Satın alma siparişini onaylama yetkiniz bulunmamaktadır.');
            }
        }

        // Fetch old status for audit log
        $currentPoRow = $this->db->query("SELECT status, po_number FROM purchase_orders WHERE id = :id LIMIT 1", [':id' => $id]);
        $oldStatus = $currentPoRow[0]['status'] ?? 'unknown';
        $poNumber = $currentPoRow[0]['po_number'] ?? 'N/A';

        $params = [
            ':id' => $id,
            ':status' => $status
        ];

        $approvedSql = '';
        if ($status === 'approved' && $adminId) {
            $approvedSql = ', approved_by = :approved_by';
            $params[':approved_by'] = $adminId;
        }

        $sql = "UPDATE purchase_orders SET status = :status {$approvedSql} WHERE id = :id";
        $res = $this->db->execute($sql, $params);
        
        $this->auditLogger->logActivity('purchase_order_status_update', "Sipariş durumu güncellendi: {$oldStatus} → {$status} ({$poNumber} ID: {$id})");
        $this->auditLogger->logAudit('status_change', 'PurchaseOrder', $id, ['status' => $oldStatus], ['status' => $status]);
        return $res;
    }

    /**
     * Goods Receipt (Mal Kabul) Kaydı ve WMS Stok Entegrasyonu.
     */
    public function receiveGoods(int $poId, array $items, int $adminId, ?string $notes = null): int {
        $po = $this->repository->getPurchaseOrderById($poId);
        if (!$po) {
            throw new Exception("Mal kabul için sipariş bulunamadı.");
        }

        $this->db->beginTransaction();
        try {
            // Create Goods Receipt header
            $sqlReceipt = "INSERT INTO goods_receipts (purchase_order_id, received_by, notes) VALUES (:po_id, :admin_id, :notes)";
            $this->db->execute($sqlReceipt, [
                ':po_id' => $poId,
                ':admin_id' => $adminId,
                ':notes' => $notes
            ]);
            $receiptId = (int)$this->db->lastInsertId();

            // Fetch current PO items to compare quantities
            $poItems = $this->repository->getPurchaseOrderItems($poId);
            $poItemsMap = [];
            foreach ($poItems as $pi) {
                $key = $pi['product_id'] . '-' . ($pi['variant_id'] ?? 'null');
                $poItemsMap[$key] = $pi;
            }

            $allCompleted = true;

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
                $qty = (int)$item['quantity'];
                $damaged = (int)($item['damaged_quantity'] ?? 0);
                $missing = (int)($item['missing_quantity'] ?? 0);

                // Insert Goods Receipt Item details
                $sqlItem = "INSERT INTO goods_receipt_items (goods_receipt_id, product_id, variant_id, quantity, damaged_quantity, missing_quantity, lot_number, serial_number, batch_number, expire_date, photo_path)
                            VALUES (:gr_id, :pid, :vid, :qty, :dmg, :msg, :lot, :sn, :batch, :exp, :photo)";
                
                $this->db->execute($sqlItem, [
                    ':gr_id' => $receiptId,
                    ':pid' => $productId,
                    ':vid' => $variantId,
                    ':qty' => $qty,
                    ':dmg' => $damaged,
                    ':msg' => $missing,
                    ':lot' => $item['lot_number'] ?? null,
                    ':sn' => $item['serial_number'] ?? null,
                    ':batch' => $item['batch_number'] ?? null,
                    ':exp' => $item['expire_date'] ?? null,
                    ':photo' => $item['photo_path'] ?? null
                ]);

                // Update PO Item received count
                $key = $productId . '-' . ($variantId ?? 'null');
                if (isset($poItemsMap[$key])) {
                    $poItem = $poItemsMap[$key];
                    $newReceived = (int)$poItem['received_quantity'] + $qty;
                    
                    $this->db->execute(
                        "UPDATE purchase_order_items SET received_quantity = :rec WHERE id = :id",
                        [':rec' => $newReceived, ':id' => $poItem['id']]
                    );

                    if ($newReceived < (int)$poItem['quantity']) {
                        $allCompleted = false;
                    }
                }

                // WMS Stok Entegrasyonu: adjust stock inside target warehouse
                // We calculate net good quantity received to put into stock
                $goodQty = $qty - $damaged;
                if ($goodQty > 0) {
                    $this->warehouseService->adjustStock(
                        $productId,
                        $variantId,
                        (int)$po['warehouse_id'],
                        $goodQty,
                        'giriş',
                        "Satın alma mal kabulü: {$po['po_number']}, GR-{$receiptId}"
                    );
                }
            }

            // Update PO Status based on received counts
            $newStatus = $allCompleted ? 'completed' : 'partially_received';
            $this->updatePurchaseOrderStatus($poId, $newStatus, $adminId);

            $this->db->commit();
            $this->auditLogger->logActivity('goods_receipt_receive', "Mal kabul başarıyla işlendi (ID: {$receiptId}). PO: {$po['po_number']}");
            
            return $receiptId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * RFQ Teklif Talebi oluşturma.
     */
    public function createRFQ(array $data): int {
        if (empty($data['product_id']) || empty($data['quantity']) || empty($data['title'])) {
            throw new Exception("Ürün, miktar ve başlık zorunludur.");
        }

        $sql = "INSERT INTO rfqs (product_id, variant_id, quantity, title, description, status)
                VALUES (:pid, :vid, :qty, :title, :desc, 'active')";
        
        $this->db->execute($sql, [
            ':pid' => (int)$data['product_id'],
            ':vid' => !empty($data['variant_id']) ? (int)$data['variant_id'] : null,
            ':qty' => (int)$data['quantity'],
            ':title' => $data['title'],
            ':desc' => $data['description'] ?? null
        ]);

        $id = (int)$this->db->lastInsertId();
        $this->auditLogger->logActivity('rfq_create', "Yeni RFQ teklif talebi oluşturuldu: {$data['title']} (ID: {$id})");
        
        return $id;
    }

    /**
     * RFQ Teklif Yanıtı girme.
     */
    public function submitRFQResponse(array $data): int {
        if (empty($data['rfq_id']) || empty($data['supplier_id']) || empty($data['price']) || empty($data['delivery_lead_time'])) {
            throw new Exception("RFQ, tedarikçi, fiyat ve teslim süresi zorunludur.");
        }

        $sql = "INSERT INTO rfq_responses (rfq_id, supplier_id, price, delivery_lead_time, is_recommended)
                VALUES (:rid, :sid, :price, :lead, :rec)";
        
        $this->db->execute($sql, [
            ':rid' => (int)$data['rfq_id'],
            ':sid' => (int)$data['supplier_id'],
            ':price' => (float)$data['price'],
            ':lead' => (int)$data['delivery_lead_time'],
            ':rec' => isset($data['is_recommended']) ? (int)$data['is_recommended'] : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * RFQ Teklifleri Karşılaştırma ve AI Önerisi.
     */
    public function compareRFQ(int $rfqId): array {
        $rfq = $this->repository->getRFQById($rfqId);
        if (!$rfq) {
            throw new Exception("Karşılaştırma için RFQ bulunamadı.");
        }

        $responses = $this->repository->getRFQResponses($rfqId);
        if (empty($responses)) {
            return [
                'rfq' => $rfq,
                'responses' => [],
                'cheapest' => null,
                'fastest' => null,
                'ai_recommended' => null
            ];
        }

        $cheapest = $responses[0];
        $fastest = $responses[0];

        foreach ($responses as $r) {
            if ((float)$r['price'] < (float)$cheapest['price']) {
                $cheapest = $r;
            }
            if ((int)$r['delivery_lead_time'] < (int)$fastest['delivery_lead_time']) {
                $fastest = $r;
            }
        }

        // AI recommendation weight formula: 60% price score + 40% delivery speed score
        // We set is_recommended flag on database
        $bestScore = -1;
        $recommended = null;
        
        foreach ($responses as $r) {
            // Simple mock weight calculation
            $score = (1 / (float)$r['price']) * 60 + (1 / (int)$r['delivery_lead_time']) * 40;
            if ($score > $bestScore) {
                $bestScore = $score;
                $recommended = $r;
            }
        }

        if ($recommended) {
            $this->db->execute("UPDATE rfq_responses SET is_recommended = 0 WHERE rfq_id = :rid", [':rid' => $rfqId]);
            $this->db->execute("UPDATE rfq_responses SET is_recommended = 1 WHERE id = :id", [':id' => $recommended['id']]);
        }

        $this->db->execute("UPDATE rfqs SET status = 'compared' WHERE id = :id", [':id' => $rfqId]);

        return [
            'rfq' => $rfq,
            'responses' => $responses,
            'cheapest' => $cheapest,
            'fastest' => $fastest,
            'ai_recommended' => $recommended
        ];
    }

    /**
     * Tedarikçi Sözleşmesi oluşturma.
     */
    public function createContract(array $data): int {
        if (empty($data['supplier_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['end_date'])) {
            throw new Exception("Tedarikçi, sözleşme başlığı ve başlangıç/bitiş tarihleri zorunludur.");
        }

        $sql = "INSERT INTO supplier_contracts (supplier_id, title, start_date, end_date, renewal_date, file_path, status)
                VALUES (:sid, :title, :start, :end, :renewal, :file, 'active')";
        
        $this->db->execute($sql, [
            ':sid' => (int)$data['supplier_id'],
            ':title' => $data['title'],
            ':start' => $data['start_date'],
            ':end' => $data['end_date'],
            ':renewal' => $data['renewal_date'] ?? null,
            ':file' => $data['file_path'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Tedarikçi Ödemesi oluşturma.
     */
    public function createPayment(array $data): int {
        if (empty($data['supplier_id']) || empty($data['amount']) || empty($data['payment_date'])) {
            throw new Exception("Tedarikçi, tutar ve vade tarihi zorunludur.");
        }

        $sql = "INSERT INTO supplier_payments (purchase_order_id, supplier_id, amount, payment_date, status)
                VALUES (:po_id, :sid, :amount, :pdate, 'pending')";
        
        $this->db->execute($sql, [
            ':po_id' => !empty($data['purchase_order_id']) ? (int)$data['purchase_order_id'] : null,
            ':sid' => (int)$data['supplier_id'],
            ':amount' => (float)$data['amount'],
            ':pdate' => $data['payment_date']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Tedarikçi Ödeme Durumu Güncelleme.
     */
    public function updatePaymentStatus(int $id, string $status): bool {
        $allowed = ['pending', 'paid', 'partial', 'overdue'];
        if (!in_array($status, $allowed)) {
            throw new Exception("Geçersiz ödeme durumu: {$status}");
        }

        $sql = "UPDATE supplier_payments SET status = :status WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id, ':status' => $status]);
    }

    /**
     * AI Satın Alma Asistanı Önerileri.
     */
    public function getAiPurchasingAssistantSuggestions(): array {
        // Find products with stock less than critical level
        $criticalProducts = $this->db->query(
            "SELECT p.id, pt.name, p.sku, p.total_stock, p.critical_stock
             FROM products p
             JOIN product_translations pt ON p.id = pt.product_id
             WHERE p.total_stock <= p.critical_stock AND p.deleted_at IS NULL
             ORDER BY p.total_stock ASC"
        );

        $suggestions = [];

        foreach ($criticalProducts as $p) {
            // Find recommended supplier for this product if any (using RFQ historical low or vendor tables)
            $suggestions[] = [
                'type' => 'low_stock',
                'title' => 'Düşük Stok Seviyesi: ' . $p['name'],
                'description' => "Ürün stoğu ({$p['total_stock']} adet), kritik seviyenin ({$p['critical_stock']} adet) altına düştü. Sipariş verilmesi önerilir.",
                'recommended_qty' => (int)$p['critical_stock'] * 3,
                'product_id' => $p['id']
            ];
        }

        // Mock delay warnings for suppliers with score < 4.0
        $riskySuppliers = $this->db->query("SELECT id, company_name, lead_time, score FROM suppliers WHERE score < 4.00 AND deleted_at IS NULL");
        foreach ($riskySuppliers as $s) {
            $suggestions[] = [
                'type' => 'lead_time_delay',
                'title' => 'Tedarikçi Gecikme Riski: ' . $s['company_name'],
                'description' => "Bu tedarikçinin teslim süresi taahhüdü aşılıyor. Sipariş vermeden önce alternatif firmaları değerlendirin. Performans Skoru: {$s['score']}/5.00",
                'supplier_id' => $s['id']
            ];
        }

        return $suggestions;
    }

    /**
     * Recalculates and saves the overall performance score for a supplier.
     */
    public function recalculateSupplierScore(int $supplierId): float {
        $perf = $this->repository->getSupplierPerformance($supplierId);
        if (empty($perf)) {
            return 5.00;
        }

        // On-time factor: max 5 points, minus penalty for late delivery
        $onTimeFactor = ($perf['on_time_rate'] / 100.0) * 5.0;
        // Damaged/refund rate penalty
        $damagePenalty = ($perf['refund_rate'] / 100.0) * 5.0;
        // Missing rate penalty
        $missingPenalty = ($perf['missing_rate'] / 100.0) * 5.0;

        $score = $onTimeFactor - $damagePenalty - $missingPenalty;
        $score = max(1.00, min(5.00, $score));
        $score = round($score, 2);

        $sql = "UPDATE suppliers SET score = :score WHERE id = :id";
        $this->db->execute($sql, [':score' => $score, ':id' => $supplierId]);

        return $score;
    }

    /**
     * Düşük stok önerilerini getirir (Alias for getAiPurchasingAssistantSuggestions)
     */
    public function getLowStockSuggestions(): array {
        return $this->getAiPurchasingAssistantSuggestions();
    }
}
