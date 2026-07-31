<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class RealTimeSalesWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Gerçek Zamanlı Satış Akışı\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-broadcast text-danger me-1.5 pulse-icon\"></i> Gerçek Zamanlı Sipariş Akışı</span>
                    <span class=\"badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2 fs-9\">Canlı</span>
                </div>
                <div class=\"realtime-feed-container overflow-y-auto\" style=\"max-height: 250px; scrollbar-width: none;\" id=\"realtimeSalesFeed\">
                    <!-- JavaScript will dynamically stream new orders here -->
                    <div class=\"p-2.5 rounded-3 mb-2 d-flex justify-content-between align-items-center bg-white bg-opacity-2 border border-white border-opacity-5 fs-8 text-white\">
                        <div>
                            <strong class=\"d-block\">Ali Yılmaz - İstanbul</strong>
                            <small class=\"text-muted\">Dyson V15 - Kredi Kartı</small>
                        </div>
                        <div class=\"text-end\">
                            <strong class=\"text-warning d-block\">₺24.999</strong>
                            <small class=\"text-muted\">Şimdi</small>
                        </div>
                    </div>
                    <div class=\"p-2.5 rounded-3 mb-2 d-flex justify-content-between align-items-center bg-white bg-opacity-2 border border-white border-opacity-5 fs-8 text-white\">
                        <div>
                            <strong class=\"d-block\">Ayşe Demir - Ankara</strong>
                            <small class=\"text-muted\">Nike Pegasus 40 - Havale</small>
                        </div>
                        <div class=\"text-end\">
                            <strong class=\"text-warning d-block\">₺3.499</strong>
                            <small class=\"text-muted\">2 dk önce</small>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
}
