<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class RevenueWidget
{
    public static function render(array $data): string
    {
        $revenue = number_format($data['revenue'] ?? 482500.00, 2, ',', '.');
        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">Net Ciro & Hasılat</span>
                    <div class=\"p-2 rounded-3\" style=\"background: rgba(34, 197, 94, 0.1); color: var(--sm-success);\"><i class=\"bi bi-wallet2 fs-5\"></i></div>
                </div>
                <h3 class=\"font-weight-800 m-0 text-white\" style=\"font-size: 28px;\">₺{$revenue}</h3>
                <div class=\"d-flex align-items-center justify-content-between mt-3\">
                    <span class=\"text-success font-weight-600 fs-7\"><i class=\"bi bi-graph-up-arrow\"></i> %12.4 artış</span>
                    <svg width=\"120\" height=\"30\" style=\"overflow: visible;\">
                        <path d=\"M 0 25 Q 30 10, 60 15 T 120 2\" fill=\"none\" stroke=\"#22c55e\" stroke-width=\"2.5\" />
                    </svg>
                </div>
            </div>
        ";
    }
}
