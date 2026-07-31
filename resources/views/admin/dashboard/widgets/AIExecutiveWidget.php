<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class AIExecutiveWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"AI Executive Karar Destek Paneli\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-cpu text-warning me-1.5\"></i> AI Executive Karar Destek Paneli</span>
                    <span class=\"badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2 fs-9\">SaintMonarc AI v2</span>
                </div>
                <div class=\"row g-3 fs-8 text-muted\" style=\"font-size: 13px;\">
                    <div class=\"col-md-6\">
                        <div class=\"p-3 rounded-3\" style=\"background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);\">
                            <strong class=\"text-white d-block mb-1.5\"><i class=\"bi bi-check2-circle text-success me-1\"></i> Bugün Yapılması Gerekenler</strong>
                            <ul class=\"list-unstyled d-flex flex-column gap-1.5 mb-0\">
                                <li>• Riskli 3 sepetin kurtarılması için SMS tetikleyin.</li>
                                <li>• Stokta kritik seviyedeki 5 ürünü sipariş edin.</li>
                            </ul>
                        </div>
                    </div>
                    <div class=\"col-md-6\">
                        <div class=\"p-3 rounded-3\" style=\"background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);\">
                            <strong class=\"text-white d-block mb-1.5\"><i class=\"bi bi-tags text-warning me-1\"></i> Kar & Fiyat Optimizasyonu</strong>
                            <ul class=\"list-unstyled d-flex flex-column gap-1.5 mb-0\">
                                <li>• Dyson V15 fiyatını %3 artırın (Dönüşüm kaybı olmaksızın ₺12.000 ekstra kâr).</li>
                                <li>• Sepette kargo ücretsiz limitini ₺750 yapın.</li>
                            </ul>
                        </div>
                    </div>
                    <div class=\"col-md-12\">
                        <div class=\"p-3 rounded-3\" style=\"background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);\">
                            <strong class=\"text-white d-block mb-1.5\"><i class=\"bi bi-graph-up-arrow text-info me-1\"></i> AI Hasılat Tahmin Trendi</strong>
                            <div style=\"height: 120px; position: relative;\">
                                <canvas id=\"aiForecastChart\" class=\"lazy-chart\"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
}
