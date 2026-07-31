<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class WidgetMarketWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Widget Kütüphanesi\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-shop text-warning me-1.5\"></i> Widget Market & Kütüphanesi</span>
                    <button class=\"btn btn-xs btn-outline-warning rounded-pill px-2.5\" onclick=\"openWidgetMarketModal()\" aria-haspopup=\"dialog\"><i class=\"bi bi-plus-circle me-1\"></i> Yeni Ekle</button>
                </div>
                <p class=\"text-muted fs-8 mb-3\" style=\"font-size: 13px;\">SaintMonarc BI panelinize yeni finansal tablolar, AI risk grafikleri ve lojistik panelleri ekleyin.</p>
                <div class=\"d-flex flex-wrap gap-1.5\">
                    <span class=\"badge bg-secondary bg-opacity-20 text-white font-weight-500 fs-9 py-1 px-2\"><i class=\"bi bi-shield-check text-success me-1\"></i> 9 Aktif</span>
                    <span class=\"badge bg-secondary bg-opacity-20 text-white font-weight-500 fs-9 py-1 px-2\"><i class=\"bi bi-grid-fill text-warning me-1\"></i> 15 Toplam</span>
                </div>
            </div>
        ";
    }
}
