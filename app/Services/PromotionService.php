<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PromotionRepository;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use App\Services\AuditLogger;
use Exception;

class PromotionService {
    private PromotionRepository $repository;
    private DatabaseInterface $db;
    private CacheInterface $cache;
    private AuditLogger $auditLogger;

    public function __construct(
        PromotionRepository $repository,
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
        $this->cache->delete('active_promotions_list');
        $this->cache->delete('promotion_calendar');
        $this->cache->delete('promotion_stats');
    }

    /**
     * Kampanya kaydı.
     */
    public function create(array $data): int {
        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }

        try {
            $id = $this->repository->create($data);

            // Koşulları kaydet
            if (!empty($data['conditions'])) {
                $this->repository->saveConditions($id, $data['conditions']);
            }

            // Aksiyonları kaydet
            if (!empty($data['actions'])) {
                $this->repository->saveActions($id, $data['actions']);
            }

            // Hediye aksiyonlarını kaydet
            if (!empty($data['gifts'])) {
                $this->repository->saveGifts($id, $data['gifts']);
            }

            // İlişkileri sync et
            if (isset($data['product_ids'])) {
                $this->repository->syncRelations($id, 'promotion_products', 'product_id', array_map('intval', $data['product_ids']));
            }
            if (isset($data['category_ids'])) {
                $this->repository->syncRelations($id, 'promotion_categories', 'category_id', array_map('intval', $data['category_ids']));
            }
            if (isset($data['brand_ids'])) {
                $this->repository->syncRelations($id, 'promotion_brands', 'brand_id', array_map('intval', $data['brand_ids']));
            }
            if (isset($data['customer_group_ids'])) {
                $this->repository->syncRelations($id, 'promotion_customer_groups', 'customer_group_id', array_map('intval', $data['customer_group_ids']));
            }
            if (isset($data['segment_ids'])) {
                $this->repository->syncRelations($id, 'promotion_segments', 'segment_id', array_map('intval', $data['segment_ids']));
            }

            $this->auditLogger->logActivity('promotion_create', "Yeni kampanya oluşturuldu: " . trim($data['name']) . " (ID: {$id})");

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
     * Kampanya güncelleme.
     */
    public function update(int $id, array $data): void {
        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }

        try {
            $this->repository->update($id, $data);

            // Koşulları güncelle
            if (isset($data['conditions'])) {
                $this->repository->saveConditions($id, $data['conditions']);
            }

            // Aksiyonları güncelle
            if (isset($data['actions'])) {
                $this->repository->saveActions($id, $data['actions']);
            }

            // Hediye aksiyonlarını güncelle
            if (isset($data['gifts'])) {
                $this->repository->saveGifts($id, $data['gifts']);
            }

            // İlişkileri sync et
            if (isset($data['product_ids'])) {
                $this->repository->syncRelations($id, 'promotion_products', 'product_id', array_map('intval', $data['product_ids']));
            }
            if (isset($data['category_ids'])) {
                $this->repository->syncRelations($id, 'promotion_categories', 'category_id', array_map('intval', $data['category_ids']));
            }
            if (isset($data['brand_ids'])) {
                $this->repository->syncRelations($id, 'promotion_brands', 'brand_id', array_map('intval', $data['brand_ids']));
            }
            if (isset($data['customer_group_ids'])) {
                $this->repository->syncRelations($id, 'promotion_customer_groups', 'customer_group_id', array_map('intval', $data['customer_group_ids']));
            }
            if (isset($data['segment_ids'])) {
                $this->repository->syncRelations($id, 'promotion_segments', 'segment_id', array_map('intval', $data['segment_ids']));
            }

            $this->auditLogger->logActivity('promotion_update', "Kampanya güncellendi: " . trim($data['name']) . " (ID: {$id})");

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
     * Kampanyayı kopyalar (duplicate).
     */
    public function duplicate(int $id): int {
        $promo = $this->repository->getById($id);
        if (!$promo) {
            throw new Exception("Kopyalanacak kampanya bulunamadı.");
        }

        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }

        try {
            // Koşulları, aksiyonları, hediye ve ilişkileri al
            $conditions = $this->repository->getConditions($id);
            $actions = $this->repository->getActions($id);
            
            // İlişkili ID'ler
            $products = array_column($this->db->query("SELECT product_id FROM promotion_products WHERE promotion_id = :pid", [':pid' => $id]), 'product_id');
            $categories = array_column($this->db->query("SELECT category_id FROM promotion_categories WHERE promotion_id = :pid", [':pid' => $id]), 'category_id');
            $brands = array_column($this->db->query("SELECT brand_id FROM promotion_brands WHERE promotion_id = :pid", [':pid' => $id]), 'brand_id');
            $groups = array_column($this->db->query("SELECT customer_group_id FROM promotion_customer_groups WHERE promotion_id = :pid", [':pid' => $id]), 'customer_group_id');
            $segments = array_column($this->db->query("SELECT segment_id FROM promotion_segments WHERE promotion_id = :pid", [':pid' => $id]), 'segment_id');

            // Yeni kural oluşturma verisi
            $newData = [
                'type' => $promo['type'],
                'code' => $promo['code'] ? $promo['code'] . '_COPY_' . time() : null,
                'status' => 'draft',
                'priority' => (int)$promo['priority'],
                'is_exclusive' => (int)$promo['is_exclusive'],
                'start_date' => $promo['start_date'],
                'end_date' => $promo['end_date'],
                'name' => $promo['name'] . ' (Kopya)',
                'description' => $promo['description'],
                'conditions' => array_map(function($c) {
                    return ['rule_type' => $c['rule_type'], 'operator' => $c['operator'], 'value' => $c['value'], 'group_operator' => $c['group_operator']];
                }, $conditions),
                'actions' => array_map(function($a) {
                    return ['type' => $a['type'], 'amount' => $a['amount'], 'target_type' => $a['target_type'], 'target_ids' => $a['target_ids']];
                }, $actions),
                'product_ids' => $products,
                'category_ids' => $categories,
                'brand_ids' => $brands,
                'customer_group_ids' => $groups,
                'segment_ids' => $segments
            ];

            $newId = $this->create($newData);

            if ($isTransactionOwner) {
                $this->db->commit();
            }

            return $newId;
        } catch (Exception $e) {
            if ($isTransactionOwner && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Kupon kodunu doğrular.
     */
    public function validateCoupon(string $code, array $cart, ?array $customer = null): array {
        $coupon = $this->repository->getCouponByCode($code);
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Geçersiz veya pasif kupon kodu.'];
        }

        // Tarih kontrolü
        if ($coupon['start_date'] && strtotime($coupon['start_date']) > time()) {
            return ['valid' => false, 'message' => 'Kupon kampanya süresi henüz başlamadı.'];
        }
        if ($coupon['end_date'] && strtotime($coupon['end_date']) < time()) {
            return ['valid' => false, 'message' => 'Kupon kampanya süresi doldu.'];
        }

        // Limit kontrolü
        if ($coupon['total_limit'] > 0 && $coupon['used_count'] >= $coupon['total_limit']) {
            return ['valid' => false, 'message' => 'Kupon kullanım limiti tükendi.'];
        }

        // Kullanıcı bazlı limit kontrolü
        if ($customer && $coupon['user_limit'] > 0) {
            $usages = $this->db->query(
                "SELECT COUNT(*) as usages FROM promotion_coupon_usages WHERE coupon_id = :cid AND user_id = :uid",
                [':cid' => $coupon['id'], ':uid' => $customer['id']]
            );
            if ($usages[0]['usages'] >= $coupon['user_limit']) {
                return ['valid' => false, 'message' => 'Bu kuponu maksimum kullanım sınırınıza ulaştınız.'];
            }
        }

        // Sepet tutarı kontrolü
        $cartTotal = 0.0;
        foreach ($cart as $item) {
            $cartTotal += (float)$item['price'] * (int)$item['quantity'];
        }

        if ($coupon['min_cart_amount'] > 0 && $cartTotal < (float)$coupon['min_cart_amount']) {
            return ['valid' => false, 'message' => 'Bu kuponu kullanabilmek için minimum sepet tutarı: ' . number_format((float)$coupon['min_cart_amount'], 2) . ' TRY olmalıdır.'];
        }

        return ['valid' => true, 'coupon' => $coupon];
    }

    /**
     * Kampanya Hesaplama Motoru (Promotion Engine)
     */
    public function calculate(array $cart, ?array $customer = null, ?string $couponCode = null): array {
        $originalTotal = 0.0;
        foreach ($cart as $item) {
            $originalTotal += (float)$item['price'] * (int)$item['quantity'];
        }

        $appliedPromotions = [];
        $totalDiscount = 0.0;
        $freeShipping = false;
        $gifts = [];

        // Aktif kampanyaları getir
        $promotions = $this->repository->getActivePromotions();

        // Kupon doğrulaması varsa dahil et
        $validCoupon = null;
        if (!empty($couponCode)) {
            $res = $this->validateCoupon($couponCode, $cart, $customer);
            if ($res['valid']) {
                $validCoupon = $res['coupon'];
            }
        }

        // Kampanyaları sırayla işle
        foreach ($promotions as $promo) {
            $promoId = (int)$promo['id'];

            // Eğer bu kampanya kuponluysa ve girilen kupon bu kampanyaya ait değilse atla
            if ($promo['code'] !== null && $promo['code'] !== '') {
                if (!$validCoupon || (int)$validCoupon['promotion_id'] !== $promoId) {
                    continue;
                }
            }

            // Koşul kontrolü
            if (!$this->checkConditions($promoId, $cart, $customer, $originalTotal)) {
                continue;
            }

            // Aksiyon / İndirim tutarını hesapla
            $discount = $this->calculateDiscount($promoId, $cart, $originalTotal);
            if ($discount <= 0 && $promo['type'] !== 'free_shipping' && $promo['type'] !== 'gift_product') {
                continue;
            }

            // Çakışma kontrolü ve En Avantajlı İndirim Seçimi
            if ((int)$promo['is_exclusive'] === 1) {
                // Eğer bu exclusive kampanyanın indirimi şu ana kadarki toplam indirimden fazlaysa:
                // Eski uygulananları sil, sadece bunu uygula ve bitir.
                if ($discount > $totalDiscount) {
                    $totalDiscount = $discount;
                    
                    // Hediyeleri sıfırla ve sadece bu kampanyanın hediyelerini ekle
                    $gifts = [];
                    $promoGifts = $this->repository->getGifts($promoId);
                    foreach ($promoGifts as $g) {
                        $gifts[] = [
                            'promotion_id' => $promoId,
                            'gift_type' => $g['gift_type'],
                            'target_id' => $g['target_id'],
                            'quantity' => $g['quantity'],
                            'points' => $g['points']
                        ];
                    }
                    
                    $freeShipping = ($promo['type'] === 'free_shipping');
                    
                    $appliedPromotions = [[
                        'id' => $promoId,
                        'name' => $promo['name'],
                        'type' => $promo['type'],
                        'discount' => $discount,
                        'is_exclusive' => 1
                    ]];
                }
                
                // Exclusive kampanya işlendiği için artık başka kampanya birleşemez, döngüyü sonlandır.
                break;
            } else {
                // Normal birleştirme
                $totalDiscount += $discount;
                $appliedPromotions[] = [
                    'id' => $promoId,
                    'name' => $promo['name'],
                    'type' => $promo['type'],
                    'discount' => $discount,
                    'is_exclusive' => 0
                ];

                // Hediye ürünleri kontrol et
                $promoGifts = $this->repository->getGifts($promoId);
                foreach ($promoGifts as $g) {
                    $gifts[] = [
                        'promotion_id' => $promoId,
                        'gift_type' => $g['gift_type'],
                        'target_id' => $g['target_id'],
                        'quantity' => $g['quantity'],
                        'points' => $g['points']
                    ];
                }

                if ($promo['type'] === 'free_shipping') {
                    $freeShipping = true;
                }
            }
        }

        // Kupon üst sınır indirim kontrolü
        if ($validCoupon && $validCoupon['max_discount_amount'] > 0 && $totalDiscount > (float)$validCoupon['max_discount_amount']) {
            $totalDiscount = (float)$validCoupon['max_discount_amount'];
        }

        $finalTotal = max(0.0, $originalTotal - $totalDiscount);

        return [
            'original_total' => $originalTotal,
            'discount_amount' => $totalDiscount,
            'final_total' => $finalTotal,
            'applied_promotions' => $appliedPromotions,
            'gifts' => $gifts,
            'free_shipping' => $freeShipping,
            'coupon_applied' => $validCoupon ? true : false,
            'coupon_code' => $validCoupon ? $validCoupon['code'] : null
        ];
    }

    /**
     * Kampanya koşullarını kontrol eder.
     */
    private function checkConditions(int $promotionId, array $cart, ?array $customer, float $cartTotal): bool {
        $conditions = $this->repository->getConditions($promotionId);
        if (empty($conditions)) {
            return true;
        }

        // İlişkili kısıtlamaları al
        $promoProducts = array_column($this->db->query("SELECT product_id FROM promotion_products WHERE promotion_id = :pid", [':pid' => $promotionId]), 'product_id');
        $promoCategories = array_column($this->db->query("SELECT category_id FROM promotion_categories WHERE promotion_id = :pid", [':pid' => $promotionId]), 'category_id');
        $promoBrands = array_column($this->db->query("SELECT brand_id FROM promotion_brands WHERE promotion_id = :pid", [':pid' => $promotionId]), 'brand_id');
        $promoGroups = array_column($this->db->query("SELECT customer_group_id FROM promotion_customer_groups WHERE promotion_id = :pid", [':pid' => $promotionId]), 'customer_group_id');
        $promoSegments = array_column($this->db->query("SELECT segment_id FROM promotion_segments WHERE promotion_id = :pid", [':pid' => $promotionId]), 'segment_id');

        // Ürün / Kategori / Marka kontrolü
        if (!empty($promoProducts)) {
            $hasProduct = false;
            foreach ($cart as $item) {
                if (in_array((int)$item['product_id'], $promoProducts)) {
                    $hasProduct = true;
                    break;
                }
            }
            if (!$hasProduct) return false;
        }

        if (!empty($promoCategories)) {
            $hasCategory = false;
            foreach ($cart as $item) {
                if (isset($item['category_id']) && in_array((int)$item['category_id'], $promoCategories)) {
                    $hasCategory = true;
                    break;
                }
            }
            if (!$hasCategory) return false;
        }

        if (!empty($promoBrands)) {
            $hasBrand = false;
            foreach ($cart as $item) {
                if (isset($item['brand_id']) && in_array((int)$item['brand_id'], $promoBrands)) {
                    $hasBrand = true;
                    break;
                }
            }
            if (!$hasBrand) return false;
        }

        // Müşteri Grubu kontrolü
        if (!empty($promoGroups)) {
            if (!$customer || !in_array((int)($customer['customer_group_id'] ?? 1), $promoGroups)) {
                return false;
            }
        }

        // CRM Segment kontrolü
        if (!empty($promoSegments)) {
            if (!$customer) return false;
            $matched = $this->db->query(
                "SELECT COUNT(*) as count FROM customer_segment_relations WHERE customer_id = :cid AND segment_id IN (" . implode(',', array_map('intval', $promoSegments)) . ")",
                [':cid' => $customer['id']]
            );
            if ($matched[0]['count'] == 0) return false;
        }

        // Diğer Koşulları İşlet
        foreach ($conditions as $cond) {
            $val = $cond['value'];
            
            if ($cond['rule_type'] === 'min_cart' && $cartTotal < (float)$val) {
                return false;
            }
            if ($cond['rule_type'] === 'max_cart' && $cartTotal > (float)$val) {
                return false;
            }
            if ($cond['rule_type'] === 'min_items') {
                $totalQty = array_sum(array_column($cart, 'quantity'));
                if ($totalQty < (int)$val) return false;
            }
            if ($cond['rule_type'] === 'day_of_week') {
                $currentDay = (int)date('w'); // 0: Sunday, 1: Monday, etc.
                $allowedDays = array_map('intval', explode(',', $val));
                if (!in_array($currentDay, $allowedDays)) return false;
            }
        }

        return true;
    }

    /**
     * Kampanyaya ait indirim tutarını hesaplar.
     */
    private function calculateDiscount(int $promotionId, array $cart, float $cartTotal): float {
        $actions = $this->repository->getActions($promotionId);
        if (empty($actions)) {
            return 0.0;
        }

        $discount = 0.0;

        foreach ($actions as $act) {
            $type = $act['type'];
            $amount = (float)$act['amount'];

            if ($type === 'discount_percentage') {
                if ($act['target_type'] === 'cart') {
                    $discount += $cartTotal * ($amount / 100);
                } else {
                    // Ürün, marka veya kategori bazlı yüzdelik indirim
                    $targetIds = $act['target_ids'] ? array_map('intval', explode(',', $act['target_ids'])) : [];
                    foreach ($cart as $item) {
                        $match = false;
                        if ($act['target_type'] === 'product' && in_array((int)$item['product_id'], $targetIds)) $match = true;
                        if ($act['target_type'] === 'category' && isset($item['category_id']) && in_array((int)$item['category_id'], $targetIds)) $match = true;
                        if ($act['target_type'] === 'brand' && isset($item['brand_id']) && in_array((int)$item['brand_id'], $targetIds)) $match = true;

                        if ($match) {
                            $discount += ((float)$item['price'] * (int)$item['quantity']) * ($amount / 100);
                        }
                    }
                }
            } elseif ($type === 'discount_fixed') {
                if ($act['target_type'] === 'cart') {
                    $discount += $amount;
                } else {
                    // Ürün bazlı sabit indirim (adet başına)
                    $targetIds = $act['target_ids'] ? array_map('intval', explode(',', $act['target_ids'])) : [];
                    foreach ($cart as $item) {
                        $match = false;
                        if ($act['target_type'] === 'product' && in_array((int)$item['product_id'], $targetIds)) $match = true;
                        if ($act['target_type'] === 'category' && isset($item['category_id']) && in_array((int)$item['category_id'], $targetIds)) $match = true;
                        if ($act['target_type'] === 'brand' && isset($item['brand_id']) && in_array((int)$item['brand_id'], $targetIds)) $match = true;

                        if ($match) {
                            $discount += $amount * (int)$item['quantity'];
                        }
                    }
                }
            }
        }

        return $discount;
    }
}
