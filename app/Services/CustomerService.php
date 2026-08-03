<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Services\AuditLogger;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Exception;

class CustomerService {
    private CustomerRepository $repository;
    private DatabaseInterface $db;
    private CacheInterface $cache;
    private AuditLogger $auditLogger;

    public function __construct(
        CustomerRepository $repository,
        DatabaseInterface $db,
        CacheInterface $cache,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->db = $db;
        $this->cache = $cache;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Cache temizleme mekanizması.
     */
    public function clearCache(): void {
        $this->cache->delete('active_customers_list');
        $this->cache->delete('customer_stats');
        $this->cache->delete('segment_list');
        $this->cache->delete('wallet_stats');
        $this->cache->delete('reward_stats');
    }

    /**
     * ID'ye göre müşteri getirir.
     */
    public function getById(int $id, bool $withGroup = false): ?array {
        return $this->repository->getById($id, $withGroup);
    }

    /**
     * Müşteriye ait adresleri getirir.
     */
    public function getAddresses(int $customerId): array {
        return $this->repository->getAddresses($customerId);
    }

    /**
     * Yeni müşteri oluşturur.
     */
    public function create(array $data): int {
        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }

        try {
            // Şifre hashle
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
            }

            $id = $this->repository->create($data);

            // Varsayılan puan hediye et (Yeni üyelik hediyesi: 50 puan)
            $this->addRewardPoints($id, 50, 'Yeni üyelik hoş geldin puanı.');

            // Etiketleri sync et
            if (!empty($data['tag_ids'])) {
                $this->repository->syncTags($id, $data['tag_ids']);
            } else {
                // Varsayılan etiket ekle: Yeni Üye (ID: 1)
                $this->repository->syncTags($id, [1]);
            }

            // KVKK onayı varsa logla
            if (!empty($data['kvkk_consent'])) {
                $this->logActivity($id, 'kvkk_approve', 'KVKK Aydınlatma Metni onaylandı.');
            }

            $this->auditLogger->logActivity('customer_create', "Yeni Müşteri Eklendi: " . trim($data['first_name'] . ' ' . $data['last_name']) . " (ID: {$id})");

            // Segmentleri otomatik ata
            $this->runRfmAndSegmentationForCustomer($id);

            if ($isTransactionOwner) {
                $this->db->commit();
            }

            $this->clearCache();
            return $id;
        } catch (Exception $e) {
            if ($isTransactionOwner && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Müşteri günceller.
     */
    public function update(int $id, array $data): void {
        $current = $this->repository->getById($id);
        if (!$current) {
            throw new Exception("Müşteri bulunamadı.");
        }

        $data['first_name'] = $data['first_name'] ?? $current['first_name'] ?? '';
        $data['last_name'] = $data['last_name'] ?? $current['last_name'] ?? '';
        $data['email'] = $data['email'] ?? $current['email'] ?? '';
        $data['phone'] = $data['phone'] ?? $current['phone'] ?? '';

        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }

        try {
            // Şifre güncellenmişse hashle
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
                $this->repository->updatePassword($id, $data['password']);
            }

            $this->repository->update($id, $data);

            // Etiketleri sync et
            if (isset($data['tag_ids'])) {
                $this->repository->syncTags($id, $data['tag_ids']);
            }

            $this->auditLogger->logActivity('customer_update', "Müşteri güncellendi: " . trim($data['first_name'] . ' ' . $data['last_name']) . " (ID: {$id})");

            $this->runRfmAndSegmentationForCustomer($id);

            if ($isTransactionOwner) {
                $this->db->commit();
            }

            $this->clearCache();
        } catch (Exception $e) {
            if ($isTransactionOwner && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Müşteri adres kaydı ekler.
     */
    public function addAddress(int $customerId, array $data): int {
        $city = trim($data['city'] ?? '');
        $district = trim($data['district'] ?? ($data['state'] ?? ''));
        $country = trim($data['country'] ?? 'Türkiye');

        if (in_array(mb_strtolower($country, 'UTF-8'), ['türkiye', 'turkey', 'tr'], true)) {
            if (!empty($city) && !empty($district)) {
                if (!\App\Helpers\AddressHelper::isValid($city, $district)) {
                    throw new Exception("Geçersiz İl ({$city}) / İlçe ({$district}) haritalaması.");
                }
            }
        }

        if (!empty($data['zip_code']) && in_array(mb_strtolower($country, 'UTF-8'), ['türkiye', 'turkey', 'tr'], true)) {
            if (!preg_match('/^\d{5}$/', trim($data['zip_code']))) {
                throw new Exception("Türkiye posta kodu 5 haneli rakamlardan oluşmalıdır.");
            }
        }

        $id = $this->repository->addAddress($customerId, $data);
        $this->logActivity($customerId, 'address_add', "Yeni adres eklendi: " . trim($data['address_title'] ?? ($data['title'] ?? 'Ev')));
        $this->clearCache();

        return $id;
    }

    /**
     * Cüzdana para yükleme (Deposit)
     */
    public function depositWallet(int $customerId, float $amount, string $description): void {
        if ($amount <= 0) {
            throw new Exception("Yüklenecek tutar 0'dan büyük olmalıdır.");
        }

        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }

        try {
            // Cüzdanı kontrol et
            $wallet = $this->repository->getWallet($customerId);
            if (!$wallet) {
                $this->db->execute("INSERT INTO customer_wallet (customer_id, balance) VALUES (:cid, 0.0000)", [':cid' => $customerId]);
            }

            // Bakiye ekle
            $this->db->execute(
                "UPDATE customer_wallet SET balance = balance + :amount WHERE customer_id = :cid",
                [':amount' => $amount, ':cid' => $customerId]
            );

            // Hareket ekle
            $this->db->execute(
                "INSERT INTO customer_wallet_transactions (customer_id, amount, type, description, created_at) 
                 VALUES (:cid, :amount, 'deposit', :desc, NOW())",
                [':cid' => $customerId, ':amount' => $amount, ':desc' => $description]
            );

            $this->logActivity($customerId, 'wallet_deposit', "Cüzdana {$amount} TRY yüklendi: {$description}");
            $this->auditLogger->logActivity('customer_wallet_deposit', "Müşteri cüzdanına bakiye eklendi (ID: {$customerId}, Tutar: {$amount} TRY)");

            if ($isTransactionOwner) {
                $this->db->commit();
            }

            $this->clearCache();
        } catch (Exception $e) {
            if ($isTransactionOwner && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cüzdandan para düşme (Withdraw)
     */
    public function withdrawWallet(int $customerId, float $amount, string $description): void {
        if ($amount <= 0) {
            throw new Exception("Düşülecek tutar 0'dan büyük olmalıdır.");
        }

        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }

        try {
            $wallet = $this->repository->getWallet($customerId);
            $balance = $wallet ? (float)$wallet['balance'] : 0.0;

            if ($balance < $amount) {
                throw new Exception("Cüzdan bakiyesi yetersiz. Mevcut bakiye: {$balance} TRY");
            }

            // Bakiye düş
            $this->db->execute(
                "UPDATE customer_wallet SET balance = balance - :amount WHERE customer_id = :cid",
                [':amount' => $amount, ':cid' => $customerId]
            );

            // Hareket ekle
            $this->db->execute(
                "INSERT INTO customer_wallet_transactions (customer_id, amount, type, description, created_at) 
                 VALUES (:cid, :amount, 'withdraw', :desc, NOW())",
                [':cid' => $customerId, ':amount' => -$amount, ':desc' => $description]
            );

            $this->logActivity($customerId, 'wallet_withdraw', "Cüzdandan {$amount} TRY düşüldü: {$description}");
            $this->auditLogger->logActivity('customer_wallet_withdraw', "Müşteri cüzdanından bakiye düşüldü (ID: {$customerId}, Tutar: {$amount} TRY)");

            if ($isTransactionOwner) {
                $this->db->commit();
            }

            $this->clearCache();
        } catch (Exception $e) {
            if ($isTransactionOwner && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Sadakat puanı ekler.
     */
    public function addRewardPoints(int $customerId, int $points, string $description): void {
        if ($points <= 0) return;
        
        $this->db->execute(
            "INSERT INTO customer_reward_points (customer_id, points, description, created_at) 
             VALUES (:cid, :pts, :desc, NOW())",
            [':cid' => $customerId, ':pts' => $points, ':desc' => $description]
        );
        $this->logActivity($customerId, 'reward_points_add', "{$points} sadakat puanı kazanıldı: {$description}");
        $this->clearCache();
    }

    /**
     * Sadakat puanı harcar.
     */
    public function spendRewardPoints(int $customerId, int $points, string $description): void {
        if ($points <= 0) return;

        // Toplam puanı sorgula
        $rows = $this->db->query("SELECT SUM(points) as total FROM customer_reward_points WHERE customer_id = :cid", [':cid' => $customerId]);
        $total = $rows[0]['total'] ? (int)$rows[0]['total'] : 0;

        if ($total < $points) {
            throw new Exception("Yetersiz sadakat puanı. Mevcut puanınız: {$total}");
        }

        $this->db->execute(
            "INSERT INTO customer_reward_points (customer_id, points, description, created_at) 
             VALUES (:cid, :pts, :desc, NOW())",
            [':cid' => $customerId, ':pts' => -$points, ':desc' => $description]
        );
        $this->logActivity($customerId, 'reward_points_spend', "{$points} sadakat puanı harcandı: {$description}");
        $this->clearCache();
    }

    /**
     * Müşteri notu ekler.
     */
    public function addNote(int $customerId, string $note): int {
        $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
        $this->db->execute(
            "INSERT INTO customer_notes (customer_id, note, created_by_admin, created_at) 
             VALUES (:cid, :note, :admin, NOW())",
            [':cid' => $customerId, ':note' => trim($note), ':admin' => $adminId]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Müşteri aktivite logu kaydeder.
     */
    public function logActivity(int $customerId, string $action, string $description): void {
        $this->db->execute(
            "INSERT INTO customer_activity_logs (customer_id, action, description, created_at) 
             VALUES (:cid, :action, :desc, NOW())",
            [':cid' => $customerId, ':action' => $action, ':desc' => $description]
        );
    }

    /**
     * Giriş kaydı loglar.
     */
    public function logLogin(int $customerId, string $ip, string $userAgent, string $status = 'success'): void {
        $this->db->execute(
            "INSERT INTO customer_login_history (customer_id, ip_address, user_agent, status, created_at) 
             VALUES (:cid, :ip, :ua, :status, NOW())",
            [':cid' => $customerId, ':ip' => $ip, ':ua' => $userAgent, ':status' => $status]
        );
        if ($status === 'success') {
            $this->db->execute("UPDATE customers SET last_login_at = NOW() WHERE id = :id", [':id' => $customerId]);
            $this->logActivity($customerId, 'login', "Sisteme giriş yapıldı (IP: {$ip}).");
        }
    }

    /**
     * RFM Analizi ve Dinamik Segmentasyonu tüm sistemde çalıştırır.
     */
    public function runSegmentationEngine(): void {
        $customers = $this->repository->getAll();
        foreach ($customers as $c) {
            $this->runRfmAndSegmentationForCustomer((int)$c['id']);
        }
        $this->clearCache();
    }

    /**
     * Tek bir müşteri için RFM Analizini ve Dinamik Segment kurallarını işletir.
     */
    public function runRfmAndSegmentationForCustomer(int $customerId): void {
        // Müşterinin finansal özetini güncelle
        // Toplam harcama (total_spent), sipariş adedi (orders_count) ve ortalama sepet (average_basket) değerlerini orders tablosundan topla.
        $stats = $this->db->query(
            "SELECT COUNT(id) as cnt, SUM(grand_total) as total, AVG(grand_total) as avg_val 
             FROM orders 
             WHERE user_id = (SELECT id FROM users WHERE email = (SELECT email FROM customers WHERE id = :cid) LIMIT 1) 
               AND status NOT IN ('cancelled') AND deleted_at IS NULL",
            [':cid' => $customerId]
        );

        $ordersCount = $stats[0]['cnt'] ? (int)$stats[0]['cnt'] : 0;
        $totalSpent = $stats[0]['total'] ? (float)$stats[0]['total'] : 0.0;
        $averageBasket = $stats[0]['avg_val'] ? (float)$stats[0]['avg_val'] : 0.0;

        $this->db->execute(
            "UPDATE customers 
             SET total_spent = :spent, orders_count = :cnt, average_basket = :avg 
             WHERE id = :cid",
            [':spent' => $totalSpent, ':cnt' => $ordersCount, ':avg' => $averageBasket, ':cid' => $customerId]
        );

        // RFM Hesaplama
        // Recency: Son sipariş tarihi gün farkı
        $lastOrder = $this->db->query(
            "SELECT created_at FROM orders 
             WHERE user_id = (SELECT id FROM users WHERE email = (SELECT email FROM customers WHERE id = :cid) LIMIT 1)
               AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
            [':cid' => $customerId]
        );
        
        $recencyDays = 999;
        if (!empty($lastOrder)) {
            $diff = time() - strtotime($lastOrder[0]['created_at']);
            $recencyDays = (int)floor($diff / (60 * 60 * 24));
        }

        // RFM Skorları (1-5 arası)
        // R (Recency): Düşük gün = Yüksek skor
        $rScore = 1;
        if ($recencyDays <= 30) $rScore = 5;
        elseif ($recencyDays <= 90) $rScore = 4;
        elseif ($recencyDays <= 180) $rScore = 3;
        elseif ($recencyDays <= 360) $rScore = 2;

        // F (Frequency): Yüksek sipariş = Yüksek skor
        $fScore = 1;
        if ($ordersCount >= 10) $fScore = 5;
        elseif ($ordersCount >= 5) $fScore = 4;
        elseif ($ordersCount >= 3) $fScore = 3;
        elseif ($ordersCount >= 2) $fScore = 2;

        // M (Monetary): Yüksek harcama = Yüksek skor
        $mScore = 1;
        if ($totalSpent >= 10000) $mScore = 5;
        elseif ($totalSpent >= 5000) $mScore = 4;
        elseif ($totalSpent >= 2000) $mScore = 3;
        elseif ($totalSpent >= 500) $mScore = 2;

        $rfmScore = "{$rScore}{$fScore}{$mScore}";
        $this->db->execute("UPDATE customers SET rfm_score = :rfm WHERE id = :cid", [':rfm' => $rfmScore, ':cid' => $customerId]);

        // Dinamik Segment Kural Kontrolleri
        // Kuralları `customer_segments` tablosundan al ve eşleşenleri `customer_segment_relations` tablosuna ekle
        $segments = $this->repository->getSegments();
        $matchedSegments = [];

        // Son giriş kontrolü için
        $cust = $this->repository->getById($customerId);
        $lastLoginDays = 999;
        if ($cust && $cust['last_login_at']) {
            $diff = time() - strtotime($cust['last_login_at']);
            $lastLoginDays = (int)floor($diff / (60 * 60 * 24));
        }

        foreach ($segments as $seg) {
            $rules = json_decode($seg['rules'], true);
            $match = true;

            if (isset($rules['days_since_last_order']) && $recencyDays > $rules['days_since_last_order']) {
                $match = false;
            }
            if (isset($rules['min_total_spent']) && $totalSpent < $rules['min_total_spent']) {
                $match = false;
            }
            if (isset($rules['min_orders_count']) && $ordersCount < $rules['min_orders_count']) {
                $match = false;
            }
            if (isset($rules['orders_count']) && $ordersCount !== $rules['orders_count']) {
                $match = false;
            }
            if (isset($rules['days_since_last_login']) && $lastLoginDays < $rules['days_since_last_login']) {
                $match = false;
            }

            if ($match) {
                $matchedSegments[] = (int)$seg['id'];
            }
        }

        $this->repository->syncSegments($customerId, $matchedSegments);

        // VIP ve Etiket Otomatik Yönetimi
        $tags = array_column($this->repository->getCustomerTags($customerId), 'id');
        
        // VIP Müşteri: Harcama >= 100000 ise
        if ($totalSpent >= 100000) {
            $this->db->execute("UPDATE customers SET status = 'VIP', customer_group_id = 2 WHERE id = :cid", [':cid' => $customerId]); // 2: VIP Grubu
            if (!in_array(2, $tags)) {
                $tags[] = 2; // VIP Müşteri Etiketi (ID: 2)
            }
        }
        
        // Riskli Müşteri: Harcama yok ve 180 gündür sipariş vermediyse
        if ($recencyDays > 180 && $totalSpent > 0) {
            $this->db->execute("UPDATE customers SET status = 'risky' WHERE id = :cid", [':cid' => $customerId]);
            if (!in_array(4, $tags)) {
                $tags[] = 4; // Riskli Üye Etiketi (ID: 4)
            }
        }

        // Pasif Müşteri: 90 gündür giriş yapmadıysa
        if ($lastLoginDays >= 90) {
            $this->db->execute("UPDATE customers SET status = 'passive' WHERE id = :cid", [':cid' => $customerId]);
            if (!in_array(5, $tags)) {
                $tags[] = 5; // Pasif Müşteri Etiketi (ID: 5)
            }
        }

        $this->repository->syncTags($customerId, array_map('intval', $tags));
    }

    /**
     * Müşteri adresini günceller (IDOR ve İl/İlçe doğrulaması ile).
     */
    public function updateAddress(int $addressId, int $customerId, array $data): bool {
        $city = trim($data['city'] ?? '');
        $district = trim($data['district'] ?? ($data['state'] ?? ''));
        $country = trim($data['country'] ?? 'Türkiye');

        if (in_array(mb_strtolower($country, 'UTF-8'), ['türkiye', 'turkey', 'tr'], true)) {
            if (!empty($city) && !empty($district)) {
                if (!\App\Helpers\AddressHelper::isValid($city, $district)) {
                    throw new Exception("Geçersiz İl ({$city}) / İlçe ({$district}) haritalaması.");
                }
            }
        }

        if (!empty($data['zip_code']) && in_array(mb_strtolower($country, 'UTF-8'), ['türkiye', 'turkey', 'tr'], true)) {
            if (!preg_match('/^\d{5}$/', trim($data['zip_code']))) {
                throw new Exception("Türkiye posta kodu 5 haneli rakamlardan oluşmalıdır.");
            }
        }

        $res = $this->repository->updateAddress($addressId, $customerId, $data);
        if (!$res) {
            throw new Exception("Adres bulunamadı veya yetkisiz erişim denemesi (IDOR).");
        }

        $this->logActivity($customerId, 'address_update', "Adres güncellendi (ID: {$addressId})");
        $this->clearCache();
        return $res;
    }

    /**
     * Müşteri adresini siler (IDOR korumalı).
     */
    public function deleteAddress(int $addressId, int $customerId): bool {
        $res = $this->repository->deleteAddress($addressId, $customerId);
        if (!$res) {
            throw new Exception("Adres bulunamadı veya yetkisiz erişim denemesi (IDOR).");
        }
        $this->logActivity($customerId, 'address_delete', "Adres silindi (ID: {$addressId})");
        $this->clearCache();
        return $res;
    }
}
