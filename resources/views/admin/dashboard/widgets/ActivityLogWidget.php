<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class ActivityLogWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Canlı Sistem Aktiviteleri\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-activity text-warning me-1.5\"></i> Canlı Sistem Aktivite Logu</span>
                    <span class=\"badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-1 px-2 fs-9\">Otomatik</span>
                </div>
                <div class=\"activity-feed-container overflow-y-auto\" style=\"max-height: 250px; scrollbar-width: none;\" id=\"realtimeActivityFeed\">
                    <div class=\"activity-item border-start border-warning border-opacity-20 ps-3 pb-2.5 position-relative fs-8 text-muted\" style=\"font-size: 12.5px;\">
                        <span class=\"position-absolute start-0 top-0 translate-middle-x bg-warning rounded-circle d-inline-block\" style=\"width: 8px; height: 8px; margin-left: -1px;\"></span>
                        <strong class=\"text-white d-block\">AI Analiz Analizi</strong>
                        <span>SaintMonarc AI bugünkü satış ve kampanya öneri raporunu oluşturdu.</span>
                        <small class=\"text-muted d-block mt-0.5\">Şimdi</small>
                    </div>
                    <div class=\"activity-item border-start border-warning border-opacity-20 ps-3 pb-2.5 position-relative fs-8 text-muted\" style=\"font-size: 12.5px;\">
                        <span class=\"position-absolute start-0 top-0 translate-middle-x bg-info rounded-circle d-inline-block\" style=\"width: 8px; height: 8px; margin-left: -1px;\"></span>
                        <strong class=\"text-white d-block\">Workflow Tetiklendi</strong>
                        <span>Yeni Sipariş -> Fatura Oluştur -> SMS Gönder iş akışı başarıyla çalıştı.</span>
                        <small class=\"text-muted d-block mt-0.5\">5 dk önce</small>
                    </div>
                </div>
            </div>
        ";
    }
}
