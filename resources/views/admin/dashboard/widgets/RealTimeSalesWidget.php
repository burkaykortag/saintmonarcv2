<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class RealTimeSalesWidget
{
    public static function render(array $data): string
    {
        $html = "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Gerçek Zamanlı Satış Akışı\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-broadcast text-danger me-1.5 pulse-icon\"></i> Gerçek Zamanlı Sipariş Akışı</span>
                    <span class=\"badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2 fs-9\">Canlı DB</span>
                </div>
                <div class=\"realtime-feed-container overflow-y-auto\" style=\"max-height: 250px; scrollbar-width: none;\" id=\"realtimeSalesFeed\">
        ";

        if (empty($data)) {
            $html .= "
                <div class=\"text-center py-4 text-muted fs-8\">
                    <i class=\"bi bi-cart-x fs-4 d-block mb-1 opacity-50\"></i>
                    Henüz kayıtlı bir sipariş bulunmuyor.
                </div>
            ";
        } else {
            foreach ($data as $order) {
                $customerName = htmlspecialchars((string)($order['customer_name'] ?? 'Müşteri'), ENT_QUOTES, 'UTF-8');
                $orderNumber  = htmlspecialchars((string)($order['order_number'] ?? '#' . ($order['id'] ?? '')), ENT_QUOTES, 'UTF-8');
                $total        = number_format((float)($order['grand_total'] ?? 0.0), 2, ',', '.');
                $payment      = htmlspecialchars((string)($order['payment_method'] ?? 'Kredi Kartı'), ENT_QUOTES, 'UTF-8');
                $status       = strtolower((string)($order['status'] ?? 'pending'));
                $timeAgo      = isset($order['created_at']) ? date('d.m H:i', strtotime((string)$order['created_at'])) : 'Şimdi';

                $statusBadge = 'bg-secondary';
                if ($status === 'delivered') $statusBadge = 'bg-success';
                elseif ($status === 'shipped') $statusBadge = 'bg-info';
                elseif ($status === 'processing') $statusBadge = 'bg-primary';
                elseif ($status === 'cancelled') $statusBadge = 'bg-danger';

                $html .= "
                    <div class=\"p-2.5 rounded-3 mb-2 d-flex justify-content-between align-items-center bg-white bg-opacity-2 border border-white border-opacity-5 fs-8 text-white\">
                        <div>
                            <strong class=\"d-block\">{$customerName}</strong>
                            <small class=\"text-muted\">Sipariş {$orderNumber} - {$payment}</small>
                        </div>
                        <div class=\"text-end\">
                            <strong class=\"text-warning d-block\">₺{$total}</strong>
                            <small class=\"badge {$statusBadge} bg-opacity-20 text-white font-size-10 px-1.5 py-0.5\">{$timeAgo}</small>
                        </div>
                    </div>
                ";
            }
        }

        $html .= "
                </div>
            </div>
        ";

        return $html;
    }
}
