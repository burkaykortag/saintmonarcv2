<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class ShippingWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">Kargo & Lojistik Dağılımı</span>
                    <div class=\"p-2 rounded-3\" style=\"background: rgba(59, 130, 246, 0.1); color: var(--sm-info);\"><i class=\"bi bi-truck fs-5\"></i></div>
                </div>
                <ul class=\"list-unstyled d-flex flex-column gap-2 fs-8 text-muted mb-0\" style=\"font-size: 13px;\">
                    <li><strong class=\"text-white\">Kurye Bekleyen:</strong> 3 Sevkiyat</li>
                    <li><strong class=\"text-white\">Yolda/Transit:</strong> 12 Paket</li>
                    <li><strong class=\"text-white\">Teslim Edilen:</strong> 94 Paket (Bugün)</li>
                </ul>
            </div>
        ";
    }
}
