<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class WorkflowWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">İş Akışı (Workflow) İstatistikleri</span>
                    <div class=\"p-2 rounded-3\" style=\"background: rgba(168, 85, 247, 0.1); color: #c084fc;\"><i class=\"bi bi-diagram-3 fs-5\"></i></div>
                </div>
                <ul class=\"list-unstyled d-flex flex-column gap-2 fs-8 text-muted mb-0\" style=\"font-size: 13px;\">
                    <li><strong class=\"text-white\">Kayıtlı Akışlar:</strong> 8 Adet</li>
                    <li><strong class=\"text-white\">Aktif Tetiklenenler:</strong> 184 Sefer</li>
                    <li><strong class=\"text-white\">Başarı Oranı:</strong> %98,9 (2 Hata)</li>
                </ul>
            </div>
        ";
    }
}
