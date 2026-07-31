<?php
declare(strict_types=1);

namespace Resources\Views\Admin\Dashboard\Widgets;

class PaymentShippingWidget
{
    public static function render(array $data): string
    {
        return "
            <div class=\"card p-4 border-0 h-100\" role=\"region\" aria-label=\"Ödeme Yöntemleri ve Kargo Dağılımı\">
                <div class=\"d-flex align-items-center justify-content-between mb-3\">
                    <span class=\"text-white font-weight-700 fs-7\"><i class=\"bi bi-truck text-warning me-1.5\"></i> Ödeme & Kargo Dağılım Analizi</span>
                    <span class=\"badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-1 px-2 fs-9\">BI Grafikleri</span>
                </div>
                <div class=\"row\">
                    <div class=\"col-6\">
                        <span class=\"text-muted d-block text-center mb-1 fs-9 font-weight-600\">Ödeme Tipleri</span>
                        <div style=\"height: 180px; position: relative;\">
                            <canvas id=\"paymentMethodChart\" class=\"lazy-chart\"></canvas>
                        </div>
                    </div>
                    <div class=\"col-6\">
                        <span class=\"text-muted d-block text-center mb-1 fs-9 font-weight-600\">Kargo Dağıtımı</span>
                        <div style=\"height: 180px; position: relative;\">
                            <canvas id=\"carrierChart\" class=\"lazy-chart\"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
}
