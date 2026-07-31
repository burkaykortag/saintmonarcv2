<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class AIWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-stars text-warning me-1\"></i> AI Öneri & Satış Tahmini</span>
                    <span class=\"badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 py-1 px-2 fs-9\" style=\"font-size: 11px;\">Gelişmiş</span>
                </div>
                <ul class=\"list-unstyled d-flex flex-column gap-2 fs-8 text-muted mb-0\" style=\"font-size: 13px; line-height: 1.5;\">
                    <li><i class=\"bi bi-lightbulb-fill text-warning me-1.5\"></i> <strong class=\"text-white\">Kampanya Önerisi:</strong> Hafta sonu elektronik kategorisine %10 indirim ciroyu %14 artırabilir.</li>
                    <li><i class=\"bi bi-exclamation-octagon-fill text-danger me-1.5\"></i> <strong class=\"text-white\">Kritik Risk:</strong> 3 üründe stok bitmek üzere, tedarik siparişi açın.</li>
                    <li><i class=\"bi bi-graph-up text-success me-1.5\"></i> <strong class=\"text-white\">Tahmini Hasılat:</strong> Önümüzdeki 30 gün tahmini ciro: ₺482.000 (Hata Payı: %3)</li>
                </ul>
            </div>
        ";
    }
}
