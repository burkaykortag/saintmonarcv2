<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AiRecommendationRepository;
use App\Services\PromotionService;
use Exception;

class OpenAiRecommendationService implements AiRecommendationServiceInterface {
    private AiRecommendationRepository $repository;
    private PromotionService $promotionService;
    private ?string $apiKey;

    public function __construct(AiRecommendationRepository $repository, PromotionService $promotionService, ?string $apiKey = null) {
        $this->repository = $repository;
        $this->promotionService = $promotionService;
        $envKey = getenv('OPENAI_API_KEY');
        $this->apiKey = $apiKey ?: ($envKey !== false ? $envKey : null);
    }

    /**
     * OpenAI API'sine satış ve stok özet verilerini prompt olarak gönderip yapay zekadan öneri alır.
     */
    public function generateRecommendations(): array {
        if (empty($this->apiKey)) {
            // Eğer API anahtarı tanımlı değilse, Local servis davranışına geri dön (fallback)
            $localService = new LocalAiRecommendationService($this->repository, $this->promotionService);
            return $localService->generateRecommendations();
        }

        try {
            // 1. Veritabanından ham analiz özet verilerini topla
            $fbt = $this->repository->getFrequentlyBoughtTogether(5);
            $aging = $this->repository->getAgingStockProducts(60, 10, 5);
            $lowConv = $this->repository->getHighViewsLowSalesProducts(50, 1.5, 5);
            $categories = $this->repository->getAgingCategories(3);

            // 2. OpenAI API'si için zengin bir prompt oluştur
            $prompt = "Aşağıdaki e-ticaret verilerini incele ve mağaza yöneticisi için satış artırıcı, stok eritici akıllı kampanya önerileri sun. JSON formatında dön.\n\n";
            $prompt .= "Birlikte satılan ürünler:\n" . json_encode($fbt) . "\n\n";
            $prompt .= "Bekleyen yüksek stoklu ürünler:\n" . json_encode($aging) . "\n\n";
            $prompt .= "Ziyaret edilip az satılan ürünler:\n" . json_encode($lowConv) . "\n\n";
            $prompt .= "Düşük cirolu kategoriler:\n" . json_encode($categories) . "\n\n";

            // 3. OpenAI cURL/HTTP çağrısı yapısı (Gelecekte aktif edilecek)
            /*
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'Sen kurumsal bir e-ticaret kampanya danışmanısın.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7
            ]));
            $response = curl_exec($ch);
            curl_close($ch);
            // Sonuçları parse et ve veritabanına ($this->repository->save) kaydet...
            */

            // OpenAI API entegrasyonu tamamlanana kadar yerel motoru çağır
            $localService = new LocalAiRecommendationService($this->repository, $this->promotionService);
            return $localService->generateRecommendations();

        } catch (Exception $e) {
            // Hata durumunda yerel servise fallback yap
            $localService = new LocalAiRecommendationService($this->repository, $this->promotionService);
            return $localService->generateRecommendations();
        }
    }

    /**
     * Öneriyi otomatik kampanyaya dönüştürerek onaylar ve uygular.
     */
    public function applyRecommendation(int $id): bool {
        $localService = new LocalAiRecommendationService($this->repository, $this->promotionService);
        return $localService->applyRecommendation($id);
    }

    /**
     * Öneriyi yoksayar.
     */
    public function dismissRecommendation(int $id): bool {
        return $this->repository->updateStatus($id, 'dismissed');
    }
}
