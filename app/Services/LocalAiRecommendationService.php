<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AiRecommendationRepository;
use App\Services\PromotionService;
use Exception;

class LocalAiRecommendationService implements AiRecommendationServiceInterface {
    private AiRecommendationRepository $repository;
    private PromotionService $promotionService;

    public function __construct(AiRecommendationRepository $repository, PromotionService $promotionService) {
        $this->repository = $repository;
        $this->promotionService = $promotionService;
    }

    /**
     * Verileri yerel algoritmalarla analiz edip önerileri üretir.
     */
    public function generateRecommendations(): array {
        // Öncelikle bekleyen eski önerileri temizleyelim (çakışma ve şişmeyi önlemek için)
        // (Gerçek hayatta sadece dismiss edilmemiş veya yeni olanları temizleyebiliriz)
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->execute("DELETE FROM ai_recommendations WHERE status = 'pending'");

        $recommendations = [];

        // 1. En Çok Birlikte Satılan Ürün İkilileri (FBT Bundle)
        $fbt = $this->repository->getFrequentlyBoughtTogether(5);
        foreach ($fbt as $pair) {
            $title = "Çapraz Satış Önerisi: " . $pair['prod1_sku'] . " & " . $pair['prod2_sku'];
            $desc = "Bu iki ürün son 30 günde toplam " . $pair['pair_count'] . " kez birlikte satın alındı. " .
                    "Müşterilerinize sepette birlikte almaları durumunda ikincisinde veya sepette ekstra indirim sunan bir çapraz satış kampanyası tanımlayabilirsiniz.";
            
            $payload = [
                'type' => 'cross_sell_bundle',
                'products' => [
                    ['id' => (int)$pair['prod1_id'], 'sku' => $pair['prod1_sku'], 'name' => $pair['prod1_name']],
                    ['id' => (int)$pair['prod2_id'], 'sku' => $pair['prod2_sku'], 'name' => $pair['prod2_name']]
                ],
                'proposed_discount' => 15.00, // Önerilen indirim %15
                'action_type' => 'discount_percentage'
            ];

            $this->repository->save([
                'type' => 'cross_sell_bundle',
                'title' => $title,
                'description' => $desc,
                'payload' => $payload,
                'status' => 'pending'
            ]);
        }

        // 2. Bekleyen Stoklar (Aging Stock)
        $aging = $this->repository->getAgingStockProducts(60, 10, 5);
        foreach ($aging as $prod) {
            $title = "Stok Eritme Önerisi: " . $prod['sku'];
            $desc = "Bu ürünün stok miktarı yüksek (" . $prod['total_stock'] . " adet) fakat son 60 gündür hiç satışı gerçekleşmedi. " .
                    "Depo maliyetlerini azaltmak amacıyla bu ürüne özel indirim tanımlanması önerilir.";

            $payload = [
                'type' => 'aging_stock',
                'product_id' => (int)$prod['id'],
                'sku' => $prod['sku'],
                'name' => $prod['product_name'],
                'proposed_discount' => 20.00,
                'action_type' => 'discount_percentage'
            ];

            $this->repository->save([
                'type' => 'aging_stock',
                'title' => $title,
                'description' => $desc,
                'payload' => $payload,
                'status' => 'pending'
            ]);
        }

        // 3. Yüksek Ziyaret / Düşük Dönüşüm (Low Conversion Rate)
        $lowConv = $this->repository->getHighViewsLowSalesProducts(50, 1.5, 5);
        foreach ($lowConv as $prod) {
            $title = "Satış Dönüşüm İyileştirmesi: " . $prod['sku'];
            $desc = "Bu ürün " . $prod['view_count'] . " kez görüntülendi ancak satış dönüşüm oranı oldukça düşük (%" . number_format((float)$prod['conversion_rate'], 2) . "). " .
                    "Müşterileri satın almaya teşvik etmek için sepette ekstra indirim veya kargo bedava kampanyası yapılması önerilmektedir.";

            $payload = [
                'type' => 'product_campaign',
                'product_id' => (int)$prod['id'],
                'sku' => $prod['sku'],
                'name' => $prod['product_name'],
                'proposed_discount' => 10.00,
                'action_type' => 'discount_percentage'
            ];

            $this->repository->save([
                'type' => 'product_campaign',
                'title' => $title,
                'description' => $desc,
                'payload' => $payload,
                'status' => 'pending'
            ]);
        }

        // 4. Stoğu Yüksek, Ciro Payı Düşük Kategoriler
        $categories = $this->repository->getAgingCategories(3);
        foreach ($categories as $cat) {
            $title = "Kategori Bazlı İndirim Önerisi: " . $cat['category_name'];
            $desc = "Bu kategoride toplam " . $cat['total_category_stock'] . " adet ürün stoğu bulunuyor. " .
                    "Ancak son 30 gündeki ciro payı (" . number_format((float)$cat['total_revenue'], 2) . " TRY) oldukça geride kaldı. " .
                    "Kategori genelinde indirim kampanyası hareketlilik sağlayacaktır.";

            $payload = [
                'type' => 'category_discount',
                'category_id' => (int)$cat['id'],
                'category_name' => $cat['category_name'],
                'proposed_discount' => 15.00,
                'action_type' => 'discount_percentage'
            ];

            $this->repository->save([
                'type' => 'category_discount',
                'title' => $title,
                'description' => $desc,
                'payload' => $payload,
                'status' => 'pending'
            ]);
        }

        return $this->repository->getAll('pending');
    }

    /**
     * Öneriyi otomatik kampanyaya dönüştürerek onaylar ve uygular.
     */
    public function applyRecommendation(int $id): bool {
        $rec = $this->repository->getById($id);
        if (!$rec || $rec['status'] !== 'pending') {
            return false;
        }

        $payload = json_decode($rec['payload'] ?? '', true);
        if (empty($payload)) {
            return false;
        }

        // Kampanyayı veritabanında oluşturmak için PromotionService çağrısı yapalım
        $promoData = [
            'type' => 'percentage',
            'code' => null, // Sepette otomatik uygula
            'name' => "AI Önerisi: " . $rec['title'],
            'description' => "AI Recommendation Engine tarafından otomatik üretilen kampanya.",
            'status' => 'active',
            'priority' => 5,
            'is_exclusive' => 0,
            'max_total_usage' => 1000,
            'max_user_usage' => 1,
            'conditions' => [
                ['rule_type' => 'min_cart', 'operator' => '>=', 'value' => '100.00']
            ],
            'actions' => [
                [
                    'type' => $payload['action_type'] ?? 'discount_percentage',
                    'amount' => (float)($payload['proposed_discount'] ?? 10.00),
                    'target_type' => 'cart'
                ]
            ]
        ];

        // Eğer belirli bir ürüne indirip uygulanacaksa
        if (isset($payload['product_id'])) {
            $promoData['actions'][0]['target_type'] = 'product';
            $promoData['actions'][0]['target_ids'] = (string)$payload['product_id'];
        }

        // Eğer kategori genelinde indirim ise
        if (isset($payload['category_id'])) {
            $promoData['actions'][0]['target_type'] = 'category';
            $promoData['actions'][0]['target_ids'] = (string)$payload['category_id'];
        }

        $promoId = $this->promotionService->create($promoData);
        
        if ($promoId > 0) {
            // Öneri durumunu 'applied' olarak güncelle
            return $this->repository->updateStatus($id, 'applied');
        }

        return false;
    }

    /**
     * Öneriyi yoksayar.
     */
    public function dismissRecommendation(int $id): bool {
        return $this->repository->updateStatus($id, 'dismissed');
    }
}
