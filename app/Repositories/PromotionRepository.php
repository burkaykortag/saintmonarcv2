<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;
use PDO;

class PromotionRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * Tüm kampanyaları gelişmiş filtrelerle getirir.
     */
    public function getAll(array $filters = [], bool $onlyDeleted = false): array {
        $sql = "SELECT p.*, pt.name, pt.description,
                       (SELECT COUNT(*) FROM promotion_coupons WHERE promotion_id = p.id) as coupon_count
                FROM promotions p
                JOIN promotion_translations pt ON p.id = pt.promotion_id AND pt.language_id = 1
                WHERE 1=1";
        
        $params = [];

        if ($onlyDeleted) {
            $sql .= " AND p.deleted_at IS NOT NULL";
        } else {
            $sql .= " AND p.deleted_at IS NULL";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (pt.name LIKE :search OR pt.description LIKE :search OR p.code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND p.type = :type";
            $params[':type'] = $filters['type'];
        }

        $sql .= " ORDER BY p.priority DESC, p.id DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * ID'ye göre kampanya çeker.
     */
    public function getById(int $id, bool $includeDeleted = false): ?array {
        $sql = "SELECT p.*, pt.name, pt.description
                FROM promotions p
                JOIN promotion_translations pt ON p.id = pt.promotion_id AND pt.language_id = 1
                WHERE p.id = :id";
        
        if (!$includeDeleted) {
            $sql .= " AND p.deleted_at IS NULL";
        }

        $rows = $this->db->query($sql, [':id' => $id]);
        return $rows[0] ?? null;
    }

    /**
     * Yeni kampanya oluşturur.
     */
    public function create(array $data): int {
        $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

        $this->db->execute(
            "INSERT INTO promotions (type, code, status, priority, is_exclusive, start_date, end_date, created_by, created_at, updated_at)
             VALUES (:type, :code, :status, :priority, :is_exclusive, :start_date, :end_date, :created_by, NOW(), NOW())",
            [
                ':type' => $data['type'],
                ':code' => !empty($data['code']) ? trim($data['code']) : null,
                ':status' => $data['status'] ?? 'draft',
                ':priority' => (int)($data['priority'] ?? 0),
                ':is_exclusive' => (int)($data['is_exclusive'] ?? 1),
                ':start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                ':end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
                ':created_by' => $adminId
            ]
        );

        $id = (int)$this->db->lastInsertId();

        // Çeviriyi kaydet (Language 1 = TR)
        $this->db->execute(
            "INSERT INTO promotion_translations (promotion_id, language_id, name, description, created_at, updated_at)
             VALUES (:pid, 1, :name, :description, NOW(), NOW())",
            [
                ':pid' => $id,
                ':name' => trim($data['name']),
                ':description' => $data['description'] ?? null
            ]
        );

        // İstatistik başlat
        $this->db->execute(
            "INSERT INTO promotion_statistics (promotion_id, views, clicks, conversions, total_discount_given, total_revenue_generated, roi, updated_at)
             VALUES (:pid, 0, 0, 0, 0.0000, 0.0000, 0.00, NOW())",
            [':pid' => $id]
        );

        // Kullanım sınırı başlat
        $this->db->execute(
            "INSERT INTO promotion_usage_limits (promotion_id, max_total_usage, max_user_usage, current_usage, created_at)
             VALUES (:pid, :max_total, :max_user, 0, NOW())",
            [
                ':pid' => $id,
                ':max_total' => (int)($data['max_total_usage'] ?? 0),
                ':max_user' => (int)($data['max_user_usage'] ?? 1)
            ]
        );

        return $id;
    }

    /**
     * Kampanya günceller.
     */
    public function update(int $id, array $data): bool {
        $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

        $ok = $this->db->execute(
            "UPDATE promotions 
             SET type = :type, code = :code, status = :status, priority = :priority, is_exclusive = :is_exclusive, 
                 start_date = :start_date, end_date = :end_date, updated_by = :updated_by, updated_at = NOW()
             WHERE id = :id",
            [
                ':type' => $data['type'],
                ':code' => !empty($data['code']) ? trim($data['code']) : null,
                ':status' => $data['status'] ?? 'draft',
                ':priority' => (int)($data['priority'] ?? 0),
                ':is_exclusive' => (int)($data['is_exclusive'] ?? 1),
                ':start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                ':end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
                ':updated_by' => $adminId,
                ':id' => $id
            ]
        );

        // Çeviriyi güncelle
        $this->db->execute(
            "INSERT INTO promotion_translations (promotion_id, language_id, name, description, created_at, updated_at)
             VALUES (:pid, 1, :name, :description, NOW(), NOW())
             ON DUPLICATE KEY UPDATE name = :name_up, description = :desc_up, updated_at = NOW()",
            [
                ':pid' => $id,
                ':name' => trim($data['name']),
                ':description' => $data['description'] ?? null,
                ':name_up' => trim($data['name']),
                ':desc_up' => $data['description'] ?? null
            ]
        );

        // Kullanım sınırı güncelle
        $this->db->execute(
            "UPDATE promotion_usage_limits 
             SET max_total_usage = :max_total, max_user_usage = :max_user
             WHERE promotion_id = :pid",
            [
                ':pid' => $id,
                ':max_total' => (int)($data['max_total_usage'] ?? 0),
                ':max_user' => (int)($data['max_user_usage'] ?? 1)
            ]
        );

        return $ok;
    }

    /**
     * Kampanyayı siler (Soft Delete).
     */
    public function delete(int $id): bool {
        $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
        return $this->db->execute(
            "UPDATE promotions SET deleted_at = NOW(), deleted_by = :admin WHERE id = :id",
            [':admin' => $adminId, ':id' => $id]
        );
    }

    /**
     * Geri yükler.
     */
    public function restore(int $id): bool {
        return $this->db->execute("UPDATE promotions SET deleted_at = NULL, deleted_by = NULL WHERE id = :id", [':id' => $id]);
    }

    /**
     * Kalıcı siler.
     */
    public function forceDelete(int $id): bool {
        return $this->db->execute("DELETE FROM promotions WHERE id = :id", [':id' => $id]);
    }

    /**
     * Koşulları çeker.
     */
    public function getConditions(int $promotionId): array {
        return $this->db->query("SELECT * FROM promotion_conditions WHERE promotion_id = :pid", [':pid' => $promotionId]);
    }

    /**
     * Aksiyonları çeker.
     */
    public function getActions(int $promotionId): array {
        return $this->db->query("SELECT * FROM promotion_actions WHERE promotion_id = :pid", [':pid' => $promotionId]);
    }

    /**
     * Hediyeleri çeker.
     */
    public function getGifts(int $promotionId): array {
        return $this->db->query("SELECT * FROM promotion_gifts WHERE promotion_id = :pid", [':pid' => $promotionId]);
    }

    /**
     * İndirim koşullarını sıfırlayıp kaydeder.
     */
    public function saveConditions(int $promotionId, array $conditions): void {
        $this->db->execute("DELETE FROM promotion_conditions WHERE promotion_id = :pid", [':pid' => $promotionId]);
        foreach ($conditions as $c) {
            $this->db->execute(
                "INSERT INTO promotion_conditions (promotion_id, rule_type, operator, value, group_operator, created_at)
                 VALUES (:pid, :type, :op, :val, :grp, NOW())",
                [
                    ':pid' => $promotionId,
                    ':type' => $c['rule_type'],
                    ':op' => $c['operator'] ?? '=',
                    ':val' => $c['value'],
                    ':grp' => $c['group_operator'] ?? 'AND'
                ]
            );
        }
    }

    /**
     * İndirim aksiyonlarını sıfırlayıp kaydeder.
     */
    public function saveActions(int $promotionId, array $actions): void {
        $this->db->execute("DELETE FROM promotion_actions WHERE promotion_id = :pid", [':pid' => $promotionId]);
        foreach ($actions as $a) {
            $this->db->execute(
                "INSERT INTO promotion_actions (promotion_id, type, amount, target_type, target_ids, created_at)
                 VALUES (:pid, :type, :amount, :target, :ids, NOW())",
                [
                    ':pid' => $promotionId,
                    ':type' => $a['type'],
                    ':amount' => (float)$a['amount'],
                    ':target' => $a['target_type'] ?? 'cart',
                    ':ids' => $a['target_ids'] ?? null
                ]
            );
        }
    }

    /**
     * Hediye aksiyonlarını kaydeder.
     */
    public function saveGifts(int $promotionId, array $gifts): void {
        $this->db->execute("DELETE FROM promotion_gifts WHERE promotion_id = :pid", [':pid' => $promotionId]);
        foreach ($gifts as $g) {
            $this->db->execute(
                "INSERT INTO promotion_gifts (promotion_id, gift_type, target_id, quantity, points, created_at)
                 VALUES (:pid, :type, :target, :qty, :pts, NOW())",
                [
                    ':pid' => $promotionId,
                    ':type' => $g['gift_type'],
                    ':target' => !empty($g['target_id']) ? (int)$g['target_id'] : null,
                    ':qty' => (int)($g['quantity'] ?? 1),
                    ':pts' => !empty($g['points']) ? (int)$g['points'] : null
                ]
            );
        }
    }

    /**
     * Çoklu ilişkileri yönetir.
     */
    public function syncRelations(int $promotionId, string $relationTable, string $foreignKey, array $ids): void {
        $this->db->execute("DELETE FROM `{$relationTable}` WHERE promotion_id = :pid", [':pid' => $promotionId]);
        foreach ($ids as $id) {
            $this->db->execute(
                "INSERT INTO `{$relationTable}` (promotion_id, `{$foreignKey}`) VALUES (:pid, :fid)",
                [':pid' => $promotionId, ':fid' => (int)$id]
            );
        }
    }

    /**
     * Aktif ve zaman dilimindeki kampanyaları getirir.
     */
    public function getActivePromotions(): array {
        $sql = "SELECT p.*, pt.name, pt.description
                FROM promotions p
                JOIN promotion_translations pt ON p.id = pt.promotion_id AND pt.language_id = 1
                WHERE p.status = 'active' AND p.deleted_at IS NULL
                  AND (p.start_date IS NULL OR p.start_date <= NOW())
                  AND (p.end_date IS NULL OR p.end_date >= NOW())
                ORDER BY p.priority DESC, p.id DESC";
        return $this->db->query($sql);
    }

    /**
     * Kupon koduna göre kupon getirir.
     */
    public function getCouponByCode(string $code): ?array {
        $rows = $this->db->query(
            "SELECT pc.*, p.status as promo_status, p.deleted_at as promo_deleted
             FROM promotion_coupons pc
             JOIN promotions p ON pc.promotion_id = p.id
             WHERE pc.code = :code AND pc.is_active = 1 AND pc.deleted_at IS NULL",
            [':code' => trim($code)]
        );
        return $rows[0] ?? null;
    }

    /**
     * Kupon oluşturur.
     */
    public function createCoupon(array $data): int {
        $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

        $this->db->execute(
            "INSERT INTO promotion_coupons (promotion_id, code, usage_type, total_limit, user_limit, min_cart_amount, max_discount_amount, start_date, end_date, is_active, created_by, created_at, updated_at)
             VALUES (:pid, :code, :usage_type, :total_limit, :user_limit, :min_cart, :max_discount, :start, :end, 1, :created_by, NOW(), NOW())",
            [
                ':pid' => (int)$data['promotion_id'],
                ':code' => trim($data['code']),
                ':usage_type' => $data['usage_type'] ?? 'multiple',
                ':total_limit' => (int)($data['total_limit'] ?? 0),
                ':user_limit' => (int)($data['user_limit'] ?? 1),
                ':min_cart' => (float)($data['min_cart_amount'] ?? 0.0),
                ':max_discount' => (float)($data['max_discount_amount'] ?? 0.0),
                ':start' => !empty($data['start_date']) ? $data['start_date'] : null,
                ':end' => !empty($data['end_date']) ? $data['end_date'] : null,
                ':created_by' => $adminId
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    /**
     * Kupon kullanım sayacını artırır.
     */
    public function incrementCouponUsage(int $couponId): void {
        $this->db->execute("UPDATE promotion_coupons SET used_count = used_count + 1 WHERE id = :id", [':id' => $couponId]);
    }

    /**
     * Kupon kullanım kaydı oluşturur.
     */
    public function logCouponUsage(int $couponId, int $userId, int $orderId, float $discount): void {
        $this->db->execute(
            "INSERT INTO promotion_coupon_usages (coupon_id, user_id, order_id, discount_amount, created_at)
             VALUES (:cid, :uid, :oid, :amount, NOW())",
            [
                ':cid' => $couponId,
                ':uid' => $userId,
                ':oid' => $orderId,
                ':amount' => $discount
            ]
        );
    }

    /**
     * Kampanya kullanım geçmişini loglar.
     */
    public function logUsage(int $promotionId, ?int $userId, ?int $orderId, float $discountAmount, string $description): void {
        // Log kaydet
        $this->db->execute(
            "INSERT INTO promotion_logs (promotion_id, user_id, order_id, discount_amount, description, created_at)
             VALUES (:pid, :uid, :oid, :amount, :desc, NOW())",
            [
                ':pid' => $promotionId,
                ':uid' => $userId,
                ':oid' => $orderId,
                ':amount' => $discountAmount,
                ':desc' => $description
            ]
        );

        // Kullanım limit sayacını artır
        $this->db->execute(
            "UPDATE promotion_usage_limits SET current_usage = current_usage + 1 WHERE promotion_id = :pid",
            [':pid' => $promotionId]
        );

        // İstatistikleri güncelle
        $this->db->execute(
            "UPDATE promotion_statistics 
             SET conversions = conversions + 1, 
                 total_discount_given = total_discount_given + :amount 
             WHERE promotion_id = :pid",
            [':amount' => $discountAmount, ':pid' => $promotionId]
        );
    }

    /**
     * İstatistikleri getirir (Raporlar için).
     */
    public function getReports(): array {
        return $this->db->query(
            "SELECT p.id, pt.name, p.type, p.status,
                    ps.views, ps.clicks, ps.conversions, ps.total_discount_given, ps.total_revenue_generated, ps.roi
             FROM promotions p
             JOIN promotion_translations pt ON p.id = pt.promotion_id AND pt.language_id = 1
             LEFT JOIN promotion_statistics ps ON p.id = ps.promotion_id
             WHERE p.deleted_at IS NULL"
        );
    }
}
