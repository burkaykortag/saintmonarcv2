<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class ActivityWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\">
                <h4 class=\"text-white font-weight-600 mb-3 fs-6\"><i class=\"bi bi-clock-history me-2 text-warning\"></i>Son Sistem Aktiviteleri</h4>
                <div class=\"d-flex flex-column gap-2 fs-8\" style=\"font-size: 13px;\">
                    <div class=\"p-2 rounded-3 border d-flex justify-content-between align-items-center\" style=\"background: rgba(255,255,255,0.01);\">
                        <span><i class=\"bi bi-cart-fill text-success me-2\"></i> Yeni sipariş #SM-90812 alındı.</span>
                        <span class=\"text-muted\">2 dk önce</span>
                    </div>
                    <div class=\"p-2 rounded-3 border d-flex justify-content-between align-items-center\" style=\"background: rgba(255,255,255,0.01);\">
                        <span><i class=\"bi bi-person-fill-add text-primary me-2\"></i> Yeni müşteri Ahmet Y. kayıt oldu.</span>
                        <span class=\"text-muted\">10 dk önce</span>
                    </div>
                    <div class=\"p-2 rounded-3 border d-flex justify-content-between align-items-center\" style=\"background: rgba(255,255,255,0.01);\">
                        <span><i class=\"bi bi-diagram-3-fill text-warning me-2\"></i> Fatura kesim iş akışı başarıyla çalıştı.</span>
                        <span class=\"text-muted\">15 dk önce</span>
                    </div>
                </div>
            </div>
        ";
    }
}
