<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class OrdersWidget
{
    public static function render(array $data): string
    {
        $ordersCount = $data['order_count'] ?? 0;
        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">Sipariş Adeti</span>
                    <div class=\"p-2 rounded-3\" style=\"background: rgba(59, 130, 246, 0.1); color: var(--sm-info);\"><i class=\"bi bi-cart3 fs-5\"></i></div>
                </div>
                <h3 class=\"font-weight-800 m-0 text-white\" style=\"font-size: 28px;\">{$ordersCount} Adet</h3>
                <div class=\"d-flex align-items-center justify-content-between mt-3\">
                    <span class=\"text-info font-weight-600 fs-7\"><i class=\"bi bi-arrow-up-short\"></i> %4.8 artış</span>
                    <svg width=\"120\" height=\"30\" style=\"overflow: visible;\">
                        <path d=\"M 0 28 Q 30 2, 60 20 T 120 8\" fill=\"none\" stroke=\"#3b82f6\" stroke-width=\"2.5\" />
                    </svg>
                </div>
            </div>
        ";
    }
}
