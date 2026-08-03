<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;
use PDO;

class OrderRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * Tüm siparişleri filtreleriyle getirir.
     */
    public function getAll(array $filters = [], bool $onlyDeleted = false): array {
        $sql = "SELECT o.*, 
                       u.email as customer_email, up.phone as customer_phone,
                       os.name as status_name, os.color as status_color, os.icon as status_icon
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN user_profiles up ON o.user_id = up.user_id
                LEFT JOIN order_statuses os ON o.status = os.code
                WHERE 1=1";
        
        $params = [];

        if ($onlyDeleted) {
            $sql .= " AND o.deleted_at IS NOT NULL";
        } else {
            $sql .= " AND o.deleted_at IS NULL";
        }

        // Filtreler
        if (!empty($filters['order_number'])) {
            $sql .= " AND o.order_number LIKE :order_number";
            $params[':order_number'] = '%' . $filters['order_number'] . '%';
        }

        if (!empty($filters['customer'])) {
            $sql .= " AND (o.billing_first_name LIKE :customer OR o.billing_last_name LIKE :customer OR o.shipping_first_name LIKE :customer OR o.shipping_last_name LIKE :customer)";
            $params[':customer'] = '%' . $filters['customer'] . '%';
        }

        if (!empty($filters['phone'])) {
            $sql .= " AND (u.phone LIKE :phone OR o.billing_address LIKE :phone)"; // basit telefon araması
            $params[':phone'] = '%' . $filters['phone'] . '%';
        }

        if (!empty($filters['email'])) {
            $sql .= " AND u.email LIKE :email";
            $params[':email'] = '%' . $filters['email'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['city'])) {
            $sql .= " AND (o.billing_city LIKE :city OR o.shipping_city LIKE :city)";
            $params[':city'] = '%' . $filters['city'] . '%';
        }

        if (!empty($filters['payment_method_id'])) {
            $sql .= " AND o.payment_method_id = :payment_method_id";
            $params[':payment_method_id'] = (int)$filters['payment_method_id'];
        }

        if (!empty($filters['shipping_method_id'])) {
            $sql .= " AND o.shipping_method_id = :shipping_method_id";
            $params[':shipping_method_id'] = (int)$filters['shipping_method_id'];
        }

        if (!empty($filters['min_amount'])) {
            $sql .= " AND o.grand_total >= :min_amount";
            $params[':min_amount'] = (float)$filters['min_amount'];
        }

        if (!empty($filters['max_amount'])) {
            $sql .= " AND o.grand_total <= :max_amount";
            $params[':max_amount'] = (float)$filters['max_amount'];
        }

        // Tarih Filtreleri
        if (!empty($filters['date_start'])) {
            $sql .= " AND o.created_at >= :date_start";
            $params[':date_start'] = $filters['date_start'] . ' 00:00:00';
        }

        if (!empty($filters['date_end'])) {
            $sql .= " AND o.created_at <= :date_end";
            $params[':date_end'] = $filters['date_end'] . ' 23:59:59';
        }

        // Ürün, Kategori ve Marka Bazlı Arama
        if (!empty($filters['product_id']) || !empty($filters['category_id']) || !empty($filters['brand_id'])) {
            $subSql = "SELECT DISTINCT oi.order_id FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       LEFT JOIN product_category_relations pcr ON p.id = pcr.product_id 
                       WHERE 1=1";
            
            $subParams = [];
            if (!empty($filters['product_id'])) {
                $subSql .= " AND p.id = :sub_pid";
                $subParams[':sub_pid'] = (int)$filters['product_id'];
            }
            if (!empty($filters['brand_id'])) {
                $subSql .= " AND p.brand_id = :sub_bid";
                $subParams[':sub_bid'] = (int)$filters['brand_id'];
            }
            if (!empty($filters['category_id'])) {
                $subSql .= " AND pcr.category_id = :sub_cid";
                $subParams[':sub_cid'] = (int)$filters['category_id'];
            }

            $orderIds = $this->db->query($subSql, $subParams);
            if (!empty($orderIds)) {
                $ids = array_column($orderIds, 'order_id');
                $sql .= " AND o.id IN (" . implode(',', array_map('intval', $ids)) . ")";
            } else {
                // Eğer eşleşen sipariş yoksa boş dönelim
                return [];
            }
        }

        $sql .= " ORDER BY o.id DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * ID'ye göre sipariş getirir.
     */
    public function getById(int $id, bool $includeDeleted = false): ?array {
        $sql = "SELECT o.*, 
                       u.email as customer_email, up.phone as customer_phone,
                       os.name as status_name, os.color as status_color, os.icon as status_icon
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN user_profiles up ON o.user_id = up.user_id
                LEFT JOIN order_statuses os ON o.status = os.code
                WHERE o.id = :id";
        
        if (!$includeDeleted) {
            $sql .= " AND o.deleted_at IS NULL";
        }
        
        $rows = $this->db->query($sql, [':id' => $id]);
        return $rows[0] ?? null;
    }

    /**
     * Siparişe ait kalemleri getirir.
     */
    public function getItems(int $orderId): array {
        return $this->db->query(
            "SELECT oi.*, p.cover_image_id, ml.filepath as product_image
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             LEFT JOIN media_library ml ON p.cover_image_id = ml.id
             WHERE oi.order_id = :oid ORDER BY oi.id ASC",
            [':oid' => $orderId]
        );
    }

    /**
     * Sipariş notlarını getirir.
     */
    public function getNotes(int $orderId): array {
        return $this->db->query(
            "SELECT onot.*, a.username as admin_name
             FROM order_notes onot
             LEFT JOIN admins a ON onot.created_by_admin = a.id
             WHERE onot.order_id = :oid ORDER BY onot.id DESC",
            [':oid' => $orderId]
        );
    }

    /**
     * Sipariş durum geçmişini getirir.
     */
    public function getStatusHistory(int $orderId): array {
        return $this->db->query(
            "SELECT osh.*, a.username as admin_name, os.name as status_name, os.color as status_color
             FROM order_status_history osh
             LEFT JOIN admins a ON osh.created_by_admin = a.id
             LEFT JOIN order_statuses os ON osh.status = os.code
             WHERE osh.order_id = :oid ORDER BY osh.id DESC",
            [':oid' => $orderId]
        );
    }

    /**
     * Siparişe ait kargoları getirir.
     */
    public function getShipments(int $orderId): array {
        return $this->db->query(
            "SELECT s.*, sm.id as shipping_method_id
             FROM shipments s
             LEFT JOIN shipping_methods sm ON s.shipping_method_id = sm.id
             WHERE s.order_id = :oid ORDER BY s.id DESC",
            [':oid' => $orderId]
        );
    }

    /**
     * Siparişe ait iadeleri getirir.
     */
    public function getRefunds(int $orderId): array {
        return $this->db->query(
            "SELECT * FROM refunds WHERE order_id = :oid ORDER BY id DESC",
            [':oid' => $orderId]
        );
    }

    /**
     * Siparişe ait ödeme geçmişini getirir.
     */
    public function getPaymentTransactions(int $orderId): array {
        return $this->db->query(
            "SELECT pt.*, pm.name as payment_method_name
             FROM payment_transactions pt
             LEFT JOIN payment_methods pm ON pt.payment_method_id = pm.id
             WHERE pt.order_id = :oid ORDER BY pt.id DESC",
            [':oid' => $orderId]
        );
    }

    /**
     * Tüm sipariş durum tanımlarını getirir.
     */
    public function getStatuses(): array {
        return $this->db->query("SELECT * FROM order_statuses ORDER BY sort_order ASC");
    }

    /**
     * Durum tanımını koduna göre getirir.
     */
    public function getStatusByCode(string $code): ?array {
        $rows = $this->db->query("SELECT * FROM order_statuses WHERE code = :code LIMIT 1", [':code' => $code]);
        return $rows[0] ?? null;
    }

    /**
     * Yeni sipariş durumu ekler.
     */
    public function createStatus(array $data): bool {
        return $this->db->execute(
            "INSERT INTO order_statuses (code, name, color, icon, sort_order, is_system) 
             VALUES (:code, :name, :color, :icon, :sort_order, 0)",
            [
                ':code' => $data['code'],
                ':name' => $data['name'],
                ':color' => $data['color'] ?? '#c5a880',
                ':icon' => $data['icon'] ?? 'bi-circle',
                ':sort_order' => (int)($data['sort_order'] ?? 0)
            ]
        );
    }

    /**
     * Sipariş durumunu günceller.
     */
    public function updateStatus(string $code, array $data): bool {
        return $this->db->execute(
            "UPDATE order_statuses 
             SET name = :name, color = :color, icon = :icon, sort_order = :sort_order 
             WHERE code = :code",
            [
                ':name' => $data['name'],
                ':color' => $data['color'] ?? '#c5a880',
                ':icon' => $data['icon'] ?? 'bi-circle',
                ':sort_order' => (int)($data['sort_order'] ?? 0),
                ':code' => $code
            ]
        );
    }

    /**
     * Sipariş durumunu siler.
     */
    public function deleteStatus(string $code): bool {
        // Sistem durumu silinemez
        $status = $this->getStatusByCode($code);
        if (!$status || $status['is_system']) {
            return false;
        }
        return $this->db->execute("DELETE FROM order_statuses WHERE code = :code AND is_system = 0", [':code' => $code]);
    }

    /**
     * Siparişin durumunu günceller.
     */
    public function updateOrderStatus(int $id, string $status): bool {
        return $this->db->execute("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id", [':status' => $status, ':id' => $id]);
    }
}
