<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class ActivityWidget
{
    public static function render(array $data): string
    {
        $html = "
            <div class=\"card p-4 border-0 h-100\">
                <h4 class=\"text-white font-weight-600 mb-3 fs-6\"><i class=\"bi bi-clock-history me-2 text-warning\"></i>Son Sistem Aktiviteleri</h4>
                <div class=\"d-flex flex-column gap-2 fs-8\" style=\"font-size: 13px;\">
        ";

        if (empty($data)) {
            $html .= "
                <div class=\"p-3 rounded-3 text-center text-muted border border-secondary border-opacity-10\">
                    Kayıtlı sistem aktivitesi bulunmuyor.
                </div>
            ";
        } else {
            $count = 0;
            foreach ($data as $log) {
                if ($count >= 4) break;
                $event     = htmlspecialchars((string)($log['event'] ?? 'İşlem'), ENT_QUOTES, 'UTF-8');
                $type      = htmlspecialchars((string)($log['auditable_type'] ?? 'Sistem'), ENT_QUOTES, 'UTF-8');
                $id        = htmlspecialchars((string)($log['auditable_id'] ?? ''), ENT_QUOTES, 'UTF-8');
                $createdAt = isset($log['created_at']) ? date('H:i d.m', strtotime((string)$log['created_at'])) : 'Şimdi';

                $html .= "
                    <div class=\"p-2 rounded-3 border d-flex justify-content-between align-items-center\" style=\"background: rgba(255,255,255,0.01); border-color: rgba(255,255,255,0.05) !important;\">
                        <span><i class=\"bi bi-record-circle text-warning me-2\"></i> {$event} ({$type}" . ($id ? " #{$id}" : "") . ")</span>
                        <span class=\"text-muted\">{$createdAt}</span>
                    </div>
                ";
                $count++;
            }
        }

        $html .= "
                </div>
            </div>
        ";

        return $html;
    }
}
