<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class AnalyticsWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Gelişmiş Satış Analitiği\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-bar-chart-line text-warning me-1.5\"></i> Gelişmiş Satış Analitiği (Saatlik, Günlük, Aylık)</span>
                    <div class=\"btn-group\" role=\"group\" aria-label=\"Zaman Kırılımı\">
                        <button type=\"button\" class=\"btn btn-xs btn-outline-warning active px-2 py-0.5\" onclick=\"switchAnalyticsTab('hourly')\">Saatlik</button>
                        <button type=\"button\" class=\"btn btn-xs btn-outline-warning px-2 py-0.5\" onclick=\"switchAnalyticsTab('daily')\">Günlük</button>
                        <button type=\"button\" class=\"btn btn-xs btn-outline-warning px-2 py-0.5\" onclick=\"switchAnalyticsTab('monthly')\">Aylık</button>
                    </div>
                </div>
                <div style=\"height: 250px; position: relative;\">
                    <canvas id=\"biAnalyticsChart\" class=\"lazy-chart\"></canvas>
                </div>
            </div>
        ";
    }
}
