<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class IadeTrendWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"İade ve Stok Trend Analizi\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-arrow-left-right text-warning me-1.5\"></i> İade Analizi & Stok Trendleri</span>
                    <span class=\"badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2 fs-9\">Hassas Analiz</span>
                </div>
                <div class=\"row\">
                    <div class=\"col-6\">
                        <span class=\"text-muted d-block text-center mb-1 fs-9 font-weight-600\">İade Sebepleri</span>
                        <div style=\"height: 180px; position: relative;\">
                            <canvas id=\"returnReasonChart\" class=\"lazy-chart\"></canvas>
                        </div>
                    </div>
                    <div class=\"col-6\">
                        <span class=\"text-muted d-block text-center mb-1 fs-9 font-weight-600\">Stok Seviyeleri</span>
                        <div style=\"height: 180px; position: relative;\">
                            <canvas id=\"stockTrendChart\" class=\"lazy-chart\"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
}
