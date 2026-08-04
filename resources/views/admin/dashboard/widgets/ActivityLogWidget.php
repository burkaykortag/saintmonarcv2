<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class ActivityLogWidget
{
    public static function render(array $data): string
    {
        $html = "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Canlı Sistem Aktiviteleri\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-activity text-warning me-1.5\"></i> Canlı Sistem Aktivite Logu</span>
                    <span class=\"badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-1 px-2 fs-9\">Veritabanı Logu</span>
                </div>
                <div class=\"activity-feed-container overflow-y-auto\" style=\"max-height: 250px; scrollbar-width: none;\" id=\"realtimeActivityFeed\">
        ";

        if (empty($data)) {
            $html .= "
                <div class=\"text-center py-4 text-muted fs-8\">
                    <i class=\"bi bi-clock-history fs-4 d-block mb-1 opacity-50\"></i>
                    Henüz kayıtlı bir sistem aktivitesi bulunmuyor.
                </div>
            ";
        } else {
            foreach ($data as $log) {
                $event     = htmlspecialchars((string)($log['event'] ?? 'Aktivite'), ENT_QUOTES, 'UTF-8');
                $type      = htmlspecialchars((string)($log['auditable_type'] ?? 'Sistem'), ENT_QUOTES, 'UTF-8');
                $id        = htmlspecialchars((string)($log['auditable_id'] ?? ''), ENT_QUOTES, 'UTF-8');
                $ip        = htmlspecialchars((string)($log['ip_address'] ?? '-'), ENT_QUOTES, 'UTF-8');
                $createdAt = isset($log['created_at']) ? date('d.m.Y H:i', strtotime((string)$log['created_at'])) : 'Şimdi';

                // Color coding based on event type
                $dotColor = 'bg-info';
                if (stripos($event, 'create') !== false || stripos($event, 'add') !== false || stripos($event, 'store') !== false) {
                    $dotColor = 'bg-success';
                } elseif (stripos($event, 'delete') !== false || stripos($event, 'remove') !== false) {
                    $dotColor = 'bg-danger';
                } elseif (stripos($event, 'update') !== false || stripos($event, 'edit') !== false) {
                    $dotColor = 'bg-warning';
                }

                $html .= "
                    <div class=\"activity-item border-start border-secondary border-opacity-20 ps-3 pb-2.5 position-relative fs-8 text-muted\" style=\"font-size: 12.5px;\">
                        <span class=\"position-absolute start-0 top-0 translate-middle-x {$dotColor} rounded-circle d-inline-block\" style=\"width: 8px; height: 8px; margin-left: -1px;\"></span>
                        <strong class=\"text-white d-block\">{$event} - {$type} " . ($id ? "#{$id}" : "") . "</strong>
                        <span>IP: {$ip}</span>
                        <small class=\"text-muted d-block mt-0.5\">{$createdAt}</small>
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
