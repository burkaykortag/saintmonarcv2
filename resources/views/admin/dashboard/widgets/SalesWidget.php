<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class SalesWidget
{
    public static function render(array $data): string
    {
        $totalSales = number_format($data['total_sales'] ?? 0.00, 2, ',', '.');
        $change = $data['total_sales_change'] ?? 0.0;
        $class = $change >= 0 ? 'text-success' : 'text-danger';
        $icon = $change >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short';

        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">Satış Cirosu</span>
                    <div class=\"p-2 rounded-3\" style=\"background: var(--sm-gold-glow); color: var(--sm-gold);\"><i class=\"bi bi-banknote fs-5\"></i></div>
                </div>
                <h3 class=\"font-weight-800 m-0 gradient-text-gold\" style=\"font-size: 28px;\">₺{$totalSales}</h3>
                <div class=\"d-flex align-items-center justify-content-between mt-3\">
                    <span class=\"{$class} font-weight-600 fs-7\">
                        <i class=\"bi {$icon}\"></i> " . number_format(abs($change), 1) . "%
                    </span>
                    <svg width=\"120\" height=\"30\" style=\"overflow: visible;\">
                        <path d=\"M 0 25 Q 20 5, 40 18 T 80 2 T 120 10\" fill=\"none\" stroke=\"var(--sm-gold)\" stroke-width=\"2.5\" />
                    </svg>
                </div>
            </div>
        ";
    }
}
