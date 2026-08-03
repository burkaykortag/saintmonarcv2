<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;
use PDO;

class CustomerRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * Tüm müşterileri gelişmiş filtrelerle getirir.
     */
    public function getAll(array $filters = [], bool $onlyDeleted = false): array {
        $sql = "SELECT c.*, 
                       cg.name as group_name, cg.discount_rate as group_discount,
                       cw.balance as wallet_balance,
                       (SELECT SUM(points) FROM customer_reward_points WHERE customer_id = c.id) as total_points
                FROM customers c
                LEFT JOIN customer_groups cg ON c.customer_group_id = cg.id
                LEFT JOIN customer_wallet cw ON c.id = cw.customer_id
                WHERE 1=1";
        
        $params = [];

        if ($onlyDeleted) {
            $sql .= " AND c.deleted_at IS NOT NULL";
        } else {
            $sql .= " AND c.deleted_at IS NULL";
        }

        // Filtreler
        if (!empty($filters['search'])) {
            $sql .= " AND (c.first_name LIKE :search OR c.last_name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['customer_group_id'])) {
            $sql .= " AND c.customer_group_id = :group_id";
            $params[':group_id'] = (int)$filters['customer_group_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['kvkk_consent']) && $filters['kvkk_consent'] !== '') {
            $sql .= " AND c.kvkk_consent = :kvkk";
            $params[':kvkk'] = (int)$filters['kvkk_consent'];
        }

        // Harcama ve Sipariş Bazlı Filtreler
        if (!empty($filters['min_spent'])) {
            $sql .= " AND c.total_spent >= :min_spent";
            $params[':min_spent'] = (float)$filters['min_spent'];
        }

        if (!empty($filters['max_spent'])) {
            $sql .= " AND c.total_spent <= :max_spent";
            $params[':max_spent'] = (float)$filters['max_spent'];
        }

        if (!empty($filters['min_orders'])) {
            $sql .= " AND c.orders_count >= :min_orders";
            $params[':min_orders'] = (int)$filters['min_orders'];
        }

        // Segment Filtresi
        if (!empty($filters['segment_id'])) {
            $sql .= " AND c.id IN (SELECT customer_id FROM customer_segment_relations WHERE segment_id = :segment_id)";
            $params[':segment_id'] = (int)$filters['segment_id'];
        }

        // Etiket Filtresi
        if (!empty($filters['tag_id'])) {
            $sql .= " AND c.id IN (SELECT customer_id FROM customer_tag_relations WHERE tag_id = :tag_id)";
            $params[':tag_id'] = (int)$filters['tag_id'];
        }

        // Şehir bazlı filtre
        if (!empty($filters['city'])) {
            $sql .= " AND c.id IN (SELECT customer_id FROM customer_addresses WHERE city LIKE :city)";
            $params[':city'] = '%' . $filters['city'] . '%';
        }

        $sql .= " ORDER BY c.id DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * ID'ye göre müşteri getirir.
     */
    public function getById(int $id, bool $includeDeleted = false): ?array {
        $sql = "SELECT c.*, 
                       cg.name as group_name, cg.discount_rate as group_discount,
                       cw.balance as wallet_balance,
                       (SELECT SUM(points) FROM customer_reward_points WHERE customer_id = c.id) as total_points
                FROM customers c
                LEFT JOIN customer_groups cg ON c.customer_group_id = cg.id
                LEFT JOIN customer_wallet cw ON c.id = cw.customer_id
                WHERE c.id = :id";
        
        if (!$includeDeleted) {
            $sql .= " AND c.deleted_at IS NULL";
        }
        
        $rows = $this->db->query($sql, [':id' => $id]);
        return $rows[0] ?? null;
    }

    /**
     * E-posta adresine göre müşteri getirir (Yetkilendirme / Arama için).
     */
    public function getByEmail(string $email): ?array {
        $rows = $this->db->query(
            "SELECT * FROM customers WHERE email = :email AND deleted_at IS NULL LIMIT 1",
            [':email' => $email]
        );
        return $rows[0] ?? null;
    }

    /**
     * Yeni müşteri kaydı oluşturur.
     */
    public function create(array $data): int {
        $this->db->execute(
            "INSERT INTO customers (
                first_name, last_name, email, phone, password, avatar, customer_group_id, status, kvkk_consent, created_at, updated_at
            ) VALUES (
                :first_name, :last_name, :email, :phone, :password, :avatar, :group_id, :status, :kvkk, NOW(), NOW()
            )",
            [
                ':first_name' => trim($data['first_name']),
                ':last_name' => trim($data['last_name']),
                ':email' => trim($data['email']),
                ':phone' => $data['phone'] ?? null,
                ':password' => $data['password'] ?? null,
                ':avatar' => $data['avatar'] ?? null,
                ':group_id' => !empty($data['customer_group_id']) ? (int)$data['customer_group_id'] : 1, // 1: Perakende
                ':status' => $data['status'] ?? 'active',
                ':kvkk' => (int)($data['kvkk_consent'] ?? 0)
            ]
        );

        $id = (int)$this->db->lastInsertId();

        // Müşteri cüzdanını sıfır bakiye ile başlat
        $this->db->execute("INSERT INTO customer_wallet (customer_id, balance) VALUES (:cid, 0.0000)", [':cid' => $id]);

        return $id;
    }

    /**
     * Müşteri günceller.
     */
    public function update(int $id, array $data): bool {
        return $this->db->execute(
            "UPDATE customers 
             SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone,
                 avatar = :avatar, customer_group_id = :group_id, status = :status, kvkk_consent = :kvkk, updated_at = NOW()
             WHERE id = :id",
            [
                ':first_name' => trim($data['first_name']),
                ':last_name' => trim($data['last_name']),
                ':email' => trim($data['email']),
                ':phone' => $data['phone'] ?? null,
                ':avatar' => $data['avatar'] ?? null,
                ':group_id' => !empty($data['customer_group_id']) ? (int)$data['customer_group_id'] : 1,
                ':status' => $data['status'] ?? 'active',
                ':kvkk' => (int)($data['kvkk_consent'] ?? 0),
                ':id' => $id
            ]
        );
    }

    /**
     * Şifre günceller.
     */
    public function updatePassword(int $id, string $hashedPassword): bool {
        return $this->db->execute("UPDATE customers SET password = :pass WHERE id = :id", [':pass' => $hashedPassword, ':id' => $id]);
    }

    /**
     * KVKK Onayı günceller.
     */
    public function updateKvkkConsent(int $id, bool $consent): bool {
        return $this->db->execute("UPDATE customers SET kvkk_consent = :kvkk WHERE id = :id", [':kvkk' => $consent ? 1 : 0, ':id' => $id]);
    }

    /**
     * Müşteriyi siler (Soft Delete).
     */
    public function delete(int $id): bool {
        return $this->db->execute("UPDATE customers SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
    }

    /**
     * Müşteriyi geri yükler.
     */
    public function restore(int $id): bool {
        return $this->db->execute("UPDATE customers SET deleted_at = NULL WHERE id = :id", [':id' => $id]);
    }

    /**
     * Müşteriyi kalıcı olarak siler.
     */
    public function forceDelete(int $id): bool {
        return $this->db->execute("DELETE FROM customers WHERE id = :id", [':id' => $id]);
    }

    /**
     * Müşteriye ait adresleri getirir.
     */
    public function getAddresses(int $customerId): array {
        return $this->db->query("SELECT * FROM customer_addresses WHERE customer_id = :cid ORDER BY id DESC", [':cid' => $customerId]);
    }

    /**
     * Müşteriye ait notları getirir.
     */
    public function getNotes(int $customerId): array {
        return $this->db->query(
            "SELECT cn.*, a.username as admin_name 
             FROM customer_notes cn
             LEFT JOIN admins a ON cn.created_by_admin = a.id
             WHERE cn.customer_id = :cid ORDER BY cn.id DESC",
            [':cid' => $customerId]
        );
    }

    /**
     * Müşteri giriş geçmişini getirir.
     */
    public function getLoginHistory(int $customerId): array {
        return $this->db->query("SELECT * FROM customer_login_history WHERE customer_id = :cid ORDER BY id DESC LIMIT 50", [':cid' => $customerId]);
    }

    /**
     * Müşteri cüzdan bakiyesini getirir.
     */
    public function getWallet(int $customerId): ?array {
        $rows = $this->db->query("SELECT * FROM customer_wallet WHERE customer_id = :cid LIMIT 1", [':cid' => $customerId]);
        return $rows[0] ?? null;
    }

    /**
     * Cüzdan hareketlerini getirir.
     */
    public function getWalletTransactions(int $customerId): array {
        return $this->db->query("SELECT * FROM customer_wallet_transactions WHERE customer_id = :cid ORDER BY id DESC", [':cid' => $customerId]);
    }

    /**
     * Müşteriye ait sadakat puan geçmişini getirir.
     */
    public function getRewardPointHistory(int $customerId): array {
        return $this->db->query("SELECT * FROM customer_reward_points WHERE customer_id = :cid ORDER BY id DESC", [':cid' => $customerId]);
    }

    /**
     * Müşteri dökümanlarını getirir.
     */
    public function getDocuments(int $customerId): array {
        return $this->db->query("SELECT * FROM customer_documents WHERE customer_id = :cid ORDER BY id DESC", [':cid' => $customerId]);
    }

    /**
     * Aktivite günlüklerini getirir.
     */
    public function getActivityLogs(int $customerId): array {
        return $this->db->query("SELECT * FROM customer_activity_logs WHERE customer_id = :cid ORDER BY id DESC LIMIT 100", [':cid' => $customerId]);
    }

    /**
     * Müşteri gruplarını listeler.
     */
    public function getGroups(): array {
        return $this->db->query("SELECT * FROM customer_groups ORDER BY id ASC");
    }

    /**
     * Müşteriye atanmış etiketleri listeler.
     */
    public function getCustomerTags(int $customerId): array {
        return $this->db->query(
            "SELECT t.* FROM customer_tags t
             JOIN customer_tag_relations ctr ON t.id = ctr.tag_id
             WHERE ctr.customer_id = :cid",
            [':cid' => $customerId]
        );
    }

    /**
     * Tüm etiket tanımlarını listeler.
     */
    public function getTags(): array {
        return $this->db->query("SELECT * FROM customer_tags ORDER BY id ASC");
    }

    /**
     * Tüm segmentleri listeler.
     */
    public function getSegments(): array {
        return $this->db->query("SELECT * FROM customer_segments ORDER BY id ASC");
    }

    /**
     * ID'ye göre segment getirir.
     */
    public function getSegmentById(int $id): ?array {
        $rows = $this->db->query("SELECT * FROM customer_segments WHERE id = :id LIMIT 1", [':id' => $id]);
        return $rows[0] ?? null;
    }

    /**
     * Müşteri segment ilişkilerini yönetir.
     */
    public function syncSegments(int $customerId, array $segmentIds): void {
        $this->db->execute("DELETE FROM customer_segment_relations WHERE customer_id = :cid", [':cid' => $customerId]);
        foreach ($segmentIds as $sid) {
            $this->db->execute(
                "INSERT INTO customer_segment_relations (customer_id, segment_id) VALUES (:cid, :sid)",
                [':cid' => $customerId, ':sid' => (int)$sid]
            );
        }
    }

    /**
     * Müşteri etiket ilişkilerini yönetir.
     */
    public function syncTags(int $customerId, array $tagIds): void {
        $this->db->execute("DELETE FROM customer_tag_relations WHERE customer_id = :cid", [':cid' => $customerId]);
        foreach ($tagIds as $tid) {
            $this->db->execute(
                "INSERT INTO customer_tag_relations (customer_id, tag_id) VALUES (:cid, :tid)",
                [':cid' => $customerId, ':tid' => (int)$tid]
            );
        }
    }

    /**
     * Yeni müşteri adresi ekler.
     */
    public function addAddress(int $customerId, array $data): int {
        $isBilling = !empty($data['is_default_billing']) ? 1 : 0;
        $isShipping = !empty($data['is_default_shipping']) ? 1 : 0;

        if ($isBilling) {
            $this->db->execute("UPDATE customer_addresses SET is_default_billing = 0 WHERE customer_id = :cid", [':cid' => $customerId]);
        }
        if ($isShipping) {
            $this->db->execute("UPDATE customer_addresses SET is_default_shipping = 0 WHERE customer_id = :cid", [':cid' => $customerId]);
        }

        $district = $data['district'] ?? ($data['state'] ?? '');

        $this->db->execute(
            "INSERT INTO customer_addresses 
             (customer_id, title, first_name, last_name, phone, address_line1, address_line2, city, state, country, zip_code, is_default_billing, is_default_shipping, created_at)
             VALUES (:cid, :title, :fn, :ln, :phone, :addr1, :addr2, :city, :state, :country, :zip, :bill, :ship, NOW())",
            [
                ':cid' => $customerId,
                ':title' => $data['address_title'] ?? ($data['title'] ?? 'Adres'),
                ':fn' => $data['first_name'] ?? '',
                ':ln' => $data['last_name'] ?? '',
                ':phone' => $data['phone'] ?? '05000000000',
                ':addr1' => $data['address_line1'] ?? '',
                ':addr2' => $data['address_line2'] ?? null,
                ':city' => $data['city'] ?? '',
                ':state' => $district,
                ':country' => $data['country'] ?? 'Türkiye',
                ':zip' => $data['zip_code'] ?? '',
                ':bill' => $isBilling,
                ':ship' => $isShipping
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    /**
     * Müşteri adresini günceller (IDOR korumalı).
     */
    public function updateAddress(int $addressId, int $customerId, array $data): bool {
        $isBilling = !empty($data['is_default_billing']) ? 1 : 0;
        $isShipping = !empty($data['is_default_shipping']) ? 1 : 0;

        if ($isBilling) {
            $this->db->execute("UPDATE customer_addresses SET is_default_billing = 0 WHERE customer_id = :cid", [':cid' => $customerId]);
        }
        if ($isShipping) {
            $this->db->execute("UPDATE customer_addresses SET is_default_shipping = 0 WHERE customer_id = :cid", [':cid' => $customerId]);
        }

        $district = $data['district'] ?? ($data['state'] ?? '');

        return $this->db->execute(
            "UPDATE customer_addresses
             SET title = :title, first_name = :fn, last_name = :ln, phone = :phone,
                 address_line1 = :addr1, address_line2 = :addr2, city = :city, state = :state,
                 country = :country, zip_code = :zip, is_default_billing = :bill, is_default_shipping = :ship, updated_at = NOW()
             WHERE id = :id AND customer_id = :cid",
            [
                ':title' => $data['address_title'] ?? ($data['title'] ?? 'Adres'),
                ':fn' => $data['first_name'] ?? '',
                ':ln' => $data['last_name'] ?? '',
                ':phone' => $data['phone'] ?? '05000000000',
                ':addr1' => $data['address_line1'] ?? '',
                ':addr2' => $data['address_line2'] ?? null,
                ':city' => $data['city'] ?? '',
                ':state' => $district,
                ':country' => $data['country'] ?? 'Türkiye',
                ':zip' => $data['zip_code'] ?? '',
                ':bill' => $isBilling,
                ':ship' => $isShipping,
                ':id' => $addressId,
                ':cid' => $customerId
            ]
        );
    }

    /**
     * Müşteri adresini siler (IDOR korumalı).
     */
    public function deleteAddress(int $addressId, int $customerId): bool {
        return $this->db->execute(
            "DELETE FROM customer_addresses WHERE id = :id AND customer_id = :cid",
            [':id' => $addressId, ':cid' => $customerId]
        );
    }

    /**
     * Varsayılan fatura adresi olarak ayarlar.
     */
    public function setDefaultBillingAddress(int $addressId, int $customerId): bool {
        $this->db->execute("UPDATE customer_addresses SET is_default_billing = 0 WHERE customer_id = :cid", [':cid' => $customerId]);
        return $this->db->execute(
            "UPDATE customer_addresses SET is_default_billing = 1 WHERE id = :id AND customer_id = :cid",
            [':id' => $addressId, ':cid' => $customerId]
        );
    }

    /**
     * Varsayılan teslimat adresi olarak ayarlar.
     */
    public function setDefaultShippingAddress(int $addressId, int $customerId): bool {
        $this->db->execute("UPDATE customer_addresses SET is_default_shipping = 0 WHERE customer_id = :cid", [':cid' => $customerId]);
        return $this->db->execute(
            "UPDATE customer_addresses SET is_default_shipping = 1 WHERE id = :id AND customer_id = :cid",
            [':id' => $addressId, ':cid' => $customerId]
        );
    }
}
