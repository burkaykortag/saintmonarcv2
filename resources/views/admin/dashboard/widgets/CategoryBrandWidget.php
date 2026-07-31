<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class CategoryBrandWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Kategori ve Marka Dağılımı\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-pie-chart text-warning me-1.5\"></i> Kategori & Marka Dağılım Analizi</span>
                    <span class=\"badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2 fs-9\">Canlı Veri</span>
                </div>
                <div class=\"row\">
                    <div class=\"col-6\">
                        <span class=\"text-muted d-block text-center mb-1 fs-9 font-weight-600\">Kategori Bazlı</span>
                        <div style=\"height: 180px; position: relative;\">
                            <canvas id=\"categoryChart\" class=\"lazy-chart\"></canvas>
                        </div>
                    </div>
                    <div class=\"col-6\">
                        <span class=" . '"text-muted d-block text-center mb-1 fs-9 font-weight-600"' . ">Marka Bazlı</span>
                        <div style=\"height: 180px; position: relative;\">
                            <canvas id=\"brandChart\" class=\"lazy-chart\"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
}
