<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class ProductsWidget
{
    public static function render(array $data): string
    {
        $total = $data['total_products'] ?? 142;
        $critical = $data['critical_stock'] ?? 3;

        return "
            <div class=\"card p-4 border-0 h-100\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-muted fs-7 text-uppercase font-weight-600\">Envanter Dağılımı</span>
                    <div class=\"p-2 rounded-3\" style=\"background: rgba(239, 68, 68, 0.1); color: var(--sm-error);\"><i class=\"bi bi-box-seam fs-5\"></i></div>
                </div>
                <h3 class=\"font-weight-800 m-0 text-white\" style=\"font-size: 28px;\">{$total} Ürün</h3>
                <div class=\"d-flex align-items-center justify-content-between mt-3 fs-8 text-muted\" style=\"font-size: 13px;\">
                    <span class=\"text-danger font-weight-600\"><i class=\"bi bi-exclamation-triangle-fill\"></i> {$critical} Kritik Stok</span>
                    <span>Tüm Depolar</span>
                </div>
            </div>
        ";
    }
}
