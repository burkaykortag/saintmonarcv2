<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class WarehouseRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * Tüm depoları listeler.
     */
    public function getAll(array $filters = []): array {
        $sql = "SELECT w.*,
                       (SELECT COUNT(DISTINCT i.product_id) FROM inventories i WHERE i.warehouse_id = w.id) as total_products,
                       (SELECT COALESCE(SUM(i.stock), 0) FROM inventories i WHERE i.warehouse_id = w.id) as total_stock,
                       (SELECT COUNT(*) FROM warehouse_locations wl WHERE wl.warehouse_id = w.id) as location_count
                FROM warehouses w
                WHERE 1=1";
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND w.is_active = :active";
            $params[':active'] = (int)$filters['is_active'];
        }

        return $this->db->query($sql, $params);
    }

    /**
     * ID bazlı depo getirir.
     */
    public function getById(int $id): ?array {
        $sql = "SELECT * FROM warehouses WHERE id = :id LIMIT 1";
        $res = $this->db->query($sql, [':id' => $id]);
        return $res[0] ?? null;
    }

    /**
     * Depo lokasyonlarını (Raf/Lokasyon) listeler.
     */
    public function getLocations(?int $warehouseId = null): array {
        $sql = "SELECT wl.*, w.name as warehouse_name,
                       (SELECT COUNT(*) FROM inventory_locations il WHERE il.location_id = wl.id) as product_count,
                       (SELECT COALESCE(SUM(il.quantity), 0) FROM inventory_locations il WHERE il.location_id = wl.id) as current_qty
                FROM warehouse_locations wl
                JOIN warehouses w ON wl.warehouse_id = w.id
                WHERE 1=1";
        $params = [];

        if ($warehouseId !== null) {
            $sql .= " AND wl.warehouse_id = :wid";
            $params[':wid'] = $warehouseId;
        }

        $sql .= " ORDER BY wl.location_code ASC";
        return $this->db->query($sql, $params);
    }

    /**
     * Stok hareketlerini getirir (Stock Movement Center).
     */
    public function getStockMovements(array $filters = []): array {
        $sql = "SELECT im.*, i.product_id, i.variant_id, i.warehouse_id,
                       p.sku as product_sku, pt.name as product_name,
                       w.name as warehouse_name
                FROM inventory_movements im
                JOIN inventories i ON im.inventory_id = i.id
                JOIN products p ON i.product_id = p.id
                JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                LEFT JOIN warehouses w ON i.warehouse_id = w.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND im.type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND i.warehouse_id = :wid";
            $params[':wid'] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND im.created_at >= :start";
            $params[':start'] = $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND im.created_at <= :end";
            $params[':end'] = $filters['end_date'] . ' 23:59:59';
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (pt.name LIKE :q OR p.sku LIKE :q OR im.description LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $sql .= " ORDER BY im.id DESC LIMIT 100";
        return $this->db->query($sql, $params);
    }

    /**
     * Depolar arası transferleri getirir.
     */
    public function getTransfers(array $filters = []): array {
        $sql = "SELECT t.*, 
                       wf.name as from_warehouse_name,
                       wt.name as to_warehouse_name,
                       (SELECT COUNT(*) FROM warehouse_transfer_items WHERE transfer_id = t.id) as item_count,
                       (SELECT SUM(quantity) FROM warehouse_transfer_items WHERE transfer_id = t.id) as total_qty
                FROM warehouse_transfers t
                JOIN warehouses wf ON t.from_warehouse_id = wf.id
                JOIN warehouses wt ON t.to_warehouse_id = wt.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY t.id DESC";
        return $this->db->query($sql, $params);
    }

    /**
     * Sayım kayıtlarını listeler.
     */
    public function getCounts(array $filters = []): array {
        $sql = "SELECT c.*, w.name as warehouse_name,
                       (SELECT COUNT(*) FROM inventory_count_items WHERE count_id = c.id) as item_count,
                       (SELECT SUM(difference_quantity) FROM inventory_count_items WHERE count_id = c.id) as total_difference
                FROM inventory_counts c
                JOIN warehouses w ON c.warehouse_id = w.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY c.id DESC";
        return $this->db->query($sql, $params);
    }

    /**
     * Depo Dashboard KPI verilerini çeker.
     */
    public function getDashboardStats(?int $warehouseId = null): array {
        $wid = $warehouseId ?? 1; // varsayılan merkez depo

        $totalProducts = $this->db->query(
            "SELECT COUNT(DISTINCT product_id) as cnt FROM inventories WHERE warehouse_id = :wid",
            [':wid' => $wid]
        )[0]['cnt'] ?? 0;

        $totalStock = $this->db->query(
            "SELECT COALESCE(SUM(stock), 0) as cnt FROM inventories WHERE warehouse_id = :wid",
            [':wid' => $wid]
        )[0]['cnt'] ?? 0;

        $criticalStock = $this->db->query(
            "SELECT COUNT(*) as cnt FROM inventories i 
             JOIN products p ON i.product_id = p.id 
             WHERE i.warehouse_id = :wid AND i.stock <= p.critical_stock",
            [':wid' => $wid]
        )[0]['cnt'] ?? 0;

        $totalLocations = $this->db->query(
            "SELECT COUNT(*) as cnt FROM warehouse_locations WHERE warehouse_id = :wid",
            [':wid' => $wid]
        )[0]['cnt'] ?? 0;

        $occupiedLocations = $this->db->query(
            "SELECT COUNT(DISTINCT location_id) as cnt FROM inventory_locations il
             JOIN warehouse_locations wl ON il.location_id = wl.id
             WHERE wl.warehouse_id = :wid AND il.quantity > 0",
            [':wid' => $wid]
        )[0]['cnt'] ?? 0;

        $emptyLocations = max(0, $totalLocations - $occupiedLocations);

        $dailyIn = $this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) as cnt FROM inventory_movements im
             JOIN inventories i ON im.inventory_id = i.id
             WHERE i.warehouse_id = :wid AND im.quantity > 0 AND DATE(im.created_at) = CURDATE()",
            [':wid' => $wid]
        )[0]['cnt'] ?? 0;

        $dailyOut = $this->db->query(
            "SELECT COALESCE(ABS(SUM(quantity)), 0) as cnt FROM inventory_movements im
             JOIN inventories i ON im.inventory_id = i.id
             WHERE i.warehouse_id = :wid AND im.quantity < 0 AND DATE(im.created_at) = CURDATE()",
            [':wid' => $wid]
        )[0]['cnt'] ?? 0;

        // Pending picking & shipping orders
        $pendingPicking = $this->db->query(
            "SELECT COUNT(*) as cnt FROM orders WHERE status = 'preparing' AND deleted_at IS NULL"
        )[0]['cnt'] ?? 0;

        $pendingShipping = $this->db->query(
            "SELECT COUNT(*) as cnt FROM orders WHERE status = 'packing' AND deleted_at IS NULL"
        )[0]['cnt'] ?? 0;

        $todayTransfers = $this->db->query(
            "SELECT COUNT(*) as cnt FROM warehouse_transfers 
             WHERE (from_warehouse_id = :wid OR to_warehouse_id = :wid2) AND DATE(created_at) = CURDATE()",
            [':wid' => $wid, ':wid2' => $wid]
        )[0]['cnt'] ?? 0;

        return [
            'total_products'     => $totalProducts,
            'total_stock'        => $totalStock,
            'critical_stock'     => $criticalStock,
            'empty_locations'    => $emptyLocations,
            'occupied_locations' => $occupiedLocations,
            'daily_in'           => $dailyIn,
            'daily_out'          => $dailyOut,
            'pending_picking'    => $pendingPicking,
            'pending_shipping'   => $pendingShipping,
            'today_transfers'    => $todayTransfers,
        ];
    }
}
