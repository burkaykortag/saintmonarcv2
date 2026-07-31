<?php
declare(strict_types=1);

namespace Resources\Views\Store\Components;

class UiStore
{
    public static function badge(string $type, string $text): string
    {
        $class = '';
        $icon = '';
        switch ($type) {
            case 'ai':
                $class = 'bg-purple bg-opacity-10 text-purple border-purple';
                $icon = '<i class="bi bi-stars me-1"></i>';
                break;
            case 'discount':
                $class = 'bg-danger bg-opacity-10 text-danger border-danger';
                $icon = '<i class="bi bi-percent me-1"></i>';
                break;
            case 'stock':
                $class = 'bg-success bg-opacity-10 text-success border-success';
                $icon = '<i class="bi bi-check-circle me-1"></i>';
                break;
            case 'hot':
                $class = 'bg-warning bg-opacity-10 text-warning border-warning';
                $icon = '<i class="bi bi-fire me-1"></i>';
                break;
        }
        return "
            <span class=\"badge {$class} border py-1 px-2.5 rounded-pill fs-9 align-middle\" style=\"font-size: 11px;\">
                {$icon}{$text}
            </span>
        ";
    }

    public static function deliveryCard(array $info): string
    {
        return "
            <div class=\"bg-light p-3 rounded-3 border border-secondary border-opacity-10 mb-3\">
                <div class=\"d-flex align-items-center gap-2 mb-2\">
                    <i class=\"bi bi-truck text-dark fs-5\"></i>
                    <span class=\"font-weight-700 fs-7 text-dark\">Kargo & Teslimat Bilgisi</span>
                </div>
                <ul class=\"list-unstyled d-flex flex-column gap-1 fs-8 text-muted mb-0\" style=\"font-size: 13px;\">
                    <li><strong class=\"text-dark\">Kargo Firması:</strong> " . htmlspecialchars($info['company'] ?? 'SM Express') . "</li>
                    <li><strong class=\"text-dark\">Tahmini Teslimat:</strong> " . htmlspecialchars($info['date'] ?? 'Aynı Gün Kargo') . "</li>
                    <li><strong class=\"text-dark\">Kargo Ücreti:</strong> " . htmlspecialchars($info['price'] ?? 'Ücretsiz') . "</li>
                </ul>
            </div>
        ";
    }

    public static function installmentCalculator(float $price): string
    {
        $monthly3 = number_format($price / 3, 2, ',', '.');
        $monthly6 = number_format(($price * 1.05) / 6, 2, ',', '.');
        $monthly12 = number_format(($price * 1.10) / 12, 2, ',', '.');

        return "
            <div class=\"accordion border rounded-3 mb-3 overflow-hidden bg-white shadow-sm\" id=\"installmentAccordion\">
                <div class=\"accordion-item border-0\">
                    <h2 class=\"accordion-header\" id=\"headingInstallment\">
                        <button class=\"accordion-button collapsed bg-light text-dark font-weight-700 fs-7\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#collapseInstallment\" aria-expanded=\"false\" aria-controls=\"collapseInstallment\">
                            <i class=\"bi bi-credit-card-2-front me-2\"></i> Taksit Seçenekleri
                        </button>
                    </h2>
                    <div id=\"collapseInstallment\" class=\"accordion-collapse collapse\" aria-labelledby=\"headingInstallment\" data-bs-parent=\"#installmentAccordion\">
                        <div class=\"accordion-body p-0\">
                            <table class=\"table table-sm table-striped fs-8 mb-0\" style=\"font-size: 12px;\">
                                <thead>
                                    <tr class=\"bg-light text-muted\">
                                        <th class=\"ps-3\">Taksit</th>
                                        <th>Aylık Ödeme</th>
                                        <th class=\"pe-3 text-end\">Toplam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class=\"ps-3\">Peşin</td>
                                        <td>" . number_format($price, 2, ',', '.') . " TL</td>
                                        <td class=\"pe-3 text-end\">" . number_format($price, 2, ',', '.') . " TL</td>
                                    </tr>
                                    <tr>
                                        <td class=\"ps-3\">3 Taksit</td>
                                        <td>{$monthly3} TL</td>
                                        <td class=\"pe-3 text-end\">" . number_format($price, 2, ',', '.') . " TL</td>
                                    </tr>
                                    <tr>
                                        <td class=\"ps-3\">6 Taksit</td>
                                        <td>{$monthly6} TL</td>
                                        <td class=\"pe-3 text-end\">" . number_format($price * 1.05, 2, ',', '.') . " TL</td>
                                    </tr>
                                    <tr>
                                        <td class=\"ps-3\">12 Taksit</td>
                                        <td>{$monthly12} TL</td>
                                        <td class=\"pe-3 text-end\">" . number_format($price * 1.10, 2, ',', '.') . " TL</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    public static function bundleCard(array $bundle): string
    {
        return "
            <div class=\"bg-light p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-center gap-3\">
                <div class=\"d-flex align-items-center gap-3\">
                    <div class=\"bg-white p-2 rounded-3 border\"><i class=\"bi bi-box-seam fs-4 text-muted\"></i></div>
                    <div class=\"fs-5 font-weight-700 text-dark\">+</div>
                    <div class=\"bg-white p-2 rounded-3 border\"><i class=\"bi bi-image fs-4 text-muted\"></i></div>
                    <div>
                        <h6 class=\"font-weight-700 m-0 text-dark\">Birlikte Alın, Tasarruf Edin!</h6>
                        <small class=\"text-muted fs-8\">" . htmlspecialchars($bundle['desc'] ?? '') . "</small>
                    </div>
                </div>
                <div class=\"text-md-end\">
                    <span class=\"text-muted text-decoration-line-through fs-8 d-block\">" . number_format($bundle['old_price'], 2, ',', '.') . " TL</span>
                    <span class=\"fs-4 font-weight-800 text-dark d-block\">" . number_format($bundle['price'], 2, ',', '.') . " TL</span>
                    <button class=\"btn btn-sm btn-dark rounded-pill px-4 mt-2\" onclick=\"addBundleToCart()\">Paketi Ekle</button>
                </div>
            </div>
        ";
    }
}
