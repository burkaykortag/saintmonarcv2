<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class CustomersWidget
{
    public static function render(array $data): string
    {
        $aovVal = (float)($data['aov'] ?? 0.00);
        $aov = number_format($aovVal, 2, ',', '.');
        $change = (float)($data['aov_change'] ?? 0.0);
        $class = $change >= 0 ? 'text-success' : 'text-danger';
        $icon = $change >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short';

        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">Ortalama Sepet (AOV)</span>
                    <div class=\"p-2 rounded-3\" style=\"background: rgba(168, 85, 247, 0.1); color: #c084fc;\"><i class=\"bi bi-people fs-5\"></i></div>
                </div>
                <h3 class=\"font-weight-800 m-0 text-white\" style=\"font-size: 28px;\">₺{$aov}</h3>
                <div class=\"d-flex align-items-center justify-content-between mt-3\">
                    <span class=\"{$class} font-weight-600 fs-7\"><i class=\"bi {$icon}\"></i> " . ($change >= 0 ? '+' : '') . number_format($change, 1) . "% Değişim</span>
                    <span class=\"text-muted fs-8\">Filtreli Dönem</span>
                </div>
            </div>
        ";
    }
}
