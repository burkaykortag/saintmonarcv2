<?php
declare(strict_types=1);

namespace App\Services;

interface AiRecommendationServiceInterface {
    /**
     * Satış, stok ve sepet verilerini inceleyerek öneriler üretir ve kaydeder.
     */
    public function generateRecommendations(): array;

    /**
     * Bir öneriyi onaylayıp sisteme (kampanya olarak vb.) yansıtır.
     */
    public function applyRecommendation(int $id): bool;

    /**
     * Bir öneriyi yoksayar / pasife çeker.
     */
    public function dismissRecommendation(int $id): bool;
}
