<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class WorkflowWidget
{
    public static function render(array $data): string
    {
        $total     = (int)($data['total']            ?? 0);
        $active    = (int)($data['active']           ?? 0);
        $execs     = (int)($data['total_executions'] ?? 0);
        $todayExec = (int)($data['today_executions'] ?? 0);
        $rate      = (float)($data['success_rate']   ?? 0.0);
        $errors    = (int)($data['error_count']      ?? 0);

        $rateColor = $rate >= 95 ? 'success' : ($rate >= 80 ? 'warning' : 'danger');

        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">İş Akışı (Workflow) İstatistikleri</span>
                    <div class=\"p-2 rounded-3\" style=\"background: rgba(168, 85, 247, 0.1); color: #c084fc;\"><i class=\"bi bi-diagram-3 fs-5\"></i></div>
                </div>
                <ul class=\"list-unstyled d-flex flex-column gap-2 fs-8 text-muted mb-0\" style=\"font-size: 13px;\">
                    <li><strong class=\"text-white\">Kayıtlı Akışlar:</strong> {$total} Adet
                        <span class=\"badge bg-success bg-opacity-10 text-success ms-1 px-2 py-0\" style=\"font-size:10px;\">{$active} Aktif</span>
                    </li>
                    <li><strong class=\"text-white\">Toplam Tetiklenmeler:</strong> {$execs} Sefer
                        <span class=\"text-muted ms-1\" style=\"font-size:11px;\">(Bugün: {$todayExec})</span>
                    </li>
                    <li><strong class=\"text-white\">Başarı Oranı:</strong> <span class=\"text-{$rateColor}\">{$rate}%</span>
                        " . ($errors > 0 ? "<span class=\"text-danger ms-1\" style=\"font-size:11px;\">({$errors} Hata)</span>" : "<span class=\"text-success ms-1\" style=\"font-size:11px;\">(Hatasız)</span>") . "
                    </li>
                </ul>
                <div class=\"mt-3 pt-2 border-top border-white border-opacity-10\">
                    <a href=\"/admin/workflows\" class=\"btn btn-sm btn-outline-secondary rounded-pill px-3\" style=\"font-size:11px;\">
                        <i class=\"bi bi-arrow-right me-1\"></i>Workflow Yönetimi
                    </a>
                </div>
            </div>
        ";
    }
}
