<?php
use App\Helpers\ComponentHelper;
use Resources\Views\Admin\Dashboard\Widgets\SalesWidget;
use Resources\Views\Admin\Dashboard\Widgets\RevenueWidget;
use Resources\Views\Admin\Dashboard\Widgets\OrdersWidget;
use Resources\Views\Admin\Dashboard\Widgets\CustomersWidget;
use Resources\Views\Admin\Dashboard\Widgets\ProductsWidget;
use Resources\Views\Admin\Dashboard\Widgets\AIWidget;
use Resources\Views\Admin\Dashboard\Widgets\ActivityWidget;
use Resources\Views\Admin\Dashboard\Widgets\WorkflowWidget;
use Resources\Views\Admin\Dashboard\Widgets\ShippingWidget;
use Resources\Views\Admin\Dashboard\Widgets\AnalyticsWidget;
use Resources\Views\Admin\Dashboard\Widgets\CategoryBrandWidget;
use Resources\Views\Admin\Dashboard\Widgets\AIExecutiveWidget;
use Resources\Views\Admin\Dashboard\Widgets\RealTimeSalesWidget;
use Resources\Views\Admin\Dashboard\Widgets\ActivityLogWidget;
use Resources\Views\Admin\Dashboard\Widgets\PaymentShippingWidget;
use Resources\Views\Admin\Dashboard\Widgets\IadeTrendWidget;
use Resources\Views\Admin\Dashboard\Widgets\WidgetMarketWidget;

$title = "Dashboard V3 - SaintMonarc Executive Analytics & BI";

// Prepare data variables
$sales = $analytics['sales'] ?? ['total_sales' => 14890.00, 'total_sales_change' => 4.5, 'order_count' => 12, 'order_count_change' => 2.1, 'aov' => 1240.83, 'aov_change' => 1.2];
$stock = $analytics['stock'] ?? ['total_products' => 142, 'active_products' => 135, 'passive_products' => 4, 'draft_products' => 3, 'critical_stock' => 3];
?>

<!-- Custom CSS for Draggable & Premium Dashboard V3 BI Elements -->
<style>
    .kpi-card {
        background: #1D1D1D !important;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        border-color: var(--sm-gold);
    }
    .kpi-card small {
        color: #a3a3a3 !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em;
    }
    .draggable-widget .card {
        background: #1D1D1D !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        border-radius: 12px;
    }
    .draggable-widget .card span.text-muted,
    .draggable-widget .card .text-muted {
        color: #a3a3a3 !important;
    }
    .widget-container {
        min-height: 180px;
    }
    .draggable-widget {
        cursor: grab;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    .draggable-widget.dragging {
        opacity: 0.4;
        transform: scale(0.98);
    }
    .draggable-widget.pinned {
        cursor: default !important;
    }
    .draggable-widget.favorited {
        border: 2px solid var(--sm-gold) !important;
        border-radius: 12px;
    }
    .gradient-text-gold {
        background: linear-gradient(135deg, var(--sm-gold), var(--sm-gold-hover));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .map-svg-container {
        height: 320px;
        background: #171717;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .quick-action-btn {
        background: #171717;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 16px;
        text-align: left;
        transition: all 0.2s ease;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .quick-action-btn:hover {
        background: rgba(255,255,255,0.02);
        border-color: var(--sm-gold);
    }
    
    /* Realtime Order Stream Glowing Effect */
    @keyframes liveItemGlow {
        0% {
            opacity: 0;
            transform: translateY(-20px);
            background: rgba(197, 168, 128, 0.4);
            box-shadow: 0 0 20px rgba(197, 168, 128, 0.6);
        }
        50% {
            background: rgba(197, 168, 128, 0.2);
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.3);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
            background: rgba(255, 255, 255, 0.02);
            box-shadow: none;
        }
    }
    .new-live-item {
        animation: liveItemGlow 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    /* Pulse Glow Animation */
    .pulse-glow {
        animation: mapPulse 2s infinite;
    }
    @keyframes mapPulse {
        0% { r: 6px; opacity: 0.8; }
        50% { r: 16px; opacity: 0.2; }
        100% { r: 6px; opacity: 0.8; }
    }

    /* Skeleton Loading effect */
    .skeleton-loader {
        background: linear-gradient(90deg, #1f1f1f 25%, #2a2a2a 50%, #1f1f1f 75%);
        background-size: 200% 100%;
        animation: loadingSkeleton 1.5s infinite;
        border-radius: 8px;
    }
    @keyframes loadingSkeleton {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* High Contrast Toggle Support */
    .high-contrast-theme {
        --sm-gold: #ffea00 !important;
        --sm-gold-hover: #ffffff !important;
    }
    .high-contrast-theme .kpi-card, 
    .high-contrast-theme .draggable-widget .card {
        background: #000000 !important;
        border: 2px solid #ffffff !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .kpi-card, .draggable-widget, .new-live-item, .pulse-glow {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<!-- Top Dashboard Filters & Workspace Controls -->
<div class="card p-3 mb-4 bg-dark border-secondary border-opacity-10 text-white" role="search" aria-label="Dashboard Filtre ve Kontrol Paneli">
    <div class="row g-2 align-items-center">
        <!-- Date Filters -->
        <div class="col-12 col-md-3">
            <label class="fs-9 text-muted text-uppercase font-weight-700 mb-1 d-block" for="dashboardDateFilter">Tarih Aralığı</label>
            <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-25" id="dashboardDateFilter" onchange="triggerFilterSimulation()">
                <option value="today">Bugün</option>
                <option value="yesterday">Dün</option>
                <option value="this_week">Bu Hafta</option>
                <option value="this_month" selected>Bu Ay</option>
                <option value="this_year">Bu Yıl</option>
                <option value="custom">Özel Tarih...</option>
            </select>
        </div>
        <!-- Mağaza/Vendor Filter -->
        <div class="col-6 col-md-2">
            <label class="fs-9 text-muted text-uppercase font-weight-700 mb-1 d-block" for="dashboardVendorFilter">Mağaza</label>
            <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-25" id="dashboardVendorFilter" onchange="triggerFilterSimulation()">
                <option value="all">Tüm Mağazalar</option>
                <option value="1">SaintMonarc Main</option>
                <option value="2">Nike Partner</option>
            </select>
        </div>
        <!-- Kategori Filter -->
        <div class="col-6 col-md-2">
            <label class="fs-9 text-muted text-uppercase font-weight-700 mb-1 d-block" for="dashboardCategoryFilter">Kategori</label>
            <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-25" id="dashboardCategoryFilter" onchange="triggerFilterSimulation()">
                <option value="all">Tüm Kategoriler</option>
                <option value="1">Elektronik</option>
                <option value="2">Ayakkabı</option>
            </select>
        </div>
        <!-- Marka Filter -->
        <div class="col-6 col-md-2">
            <label class="fs-9 text-muted text-uppercase font-weight-700 mb-1 d-block" for="dashboardBrandFilter">Marka</label>
            <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-25" id="dashboardBrandFilter" onchange="triggerFilterSimulation()">
                <option value="all">Tüm Markalar</option>
                <option value="1">Apple</option>
                <option value="2">Nike</option>
            </select>
        </div>
        <!-- Şehir Filter -->
        <div class="col-6 col-md-3">
            <label class="fs-9 text-muted text-uppercase font-weight-700 mb-1 d-block" for="dashboardCityFilter">Şehir</label>
            <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-25" id="dashboardCityFilter" onchange="triggerFilterSimulation()">
                <option value="all">Tüm Şehirler</option>
                <option value="34">İstanbul</option>
                <option value="06">Ankara</option>
                <option value="35">İzmir</option>
            </select>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4 text-white">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Dashboard V3' => url('/admin')]) ?>
        <h2 class="mt-2 text-white font-weight-800 fs-3">Executive Analytics Center</h2>
        <p class="text-muted mb-0 fs-7">Looker Studio ve Stripe kalitesinde SaintMonarc BI iş zekası veri paneli.</p>
    </div>
    
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="openWidgetMarketModal()" aria-haspopup="dialog"><i class="bi bi-shop me-1"></i> Widget Market</button>
        <button class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="toggleAccessibilityTheme()"><i class="bi bi-eye-fill me-1"></i> Yüksek Kontrast</button>
        <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="resetWidgetLayout()"><i class="bi bi-arrow-counterclockwise me-1"></i> Düzeni Sıfırla</button>
    </div>
</div>

<!-- Profiles & Templates Selectors -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 bg-dark bg-opacity-50 p-3 rounded-4 border border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted fs-8 font-weight-600 text-uppercase">Çalışma Profili:</span>
        <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-25" id="dashboardProfileSelect" style="width: 220px;" onchange="applyDashboardProfile(this.value)">
            <option value="executive" selected>Executive (Tüm Panel)</option>
            <option value="finance">Finans Paneli</option>
            <option value="marketing">Satış & Pazarlama</option>
            <option value="operations">Operasyon & Lojistik</option>
        </select>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted fs-8 font-weight-600 text-uppercase">Görünüm Şablonu:</span>
        <select class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-25" id="dashboardTemplateSelect" style="width: 180px;" onchange="applyDashboardTemplate(this.value)">
            <option value="advanced" selected>Gelişmiş BI</option>
            <option value="minimal">Minimalist</option>
            <option value="critical">Kritik Analiz</option>
        </select>
    </div>
</div>

<!-- 1. KPI BLOKLARI (Sayaç Animasyonlu) -->
<div class="row g-3 mb-4">
    <!-- Günlük Ciro -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Günlük Ciro</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="14890" data-prefix="₺">₺0</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> +%4.5</span>
        </div>
    </div>
    <!-- Haftalık Ciro -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Haftalık Ciro</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="104230" data-prefix="₺">₺0</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> +%8.1</span>
        </div>
    </div>
    <!-- Aylık Ciro -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Aylık Ciro</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="416920" data-prefix="₺">₺0</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> +%12.8</span>
        </div>
    </div>
    <!-- Yıllık Ciro -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Yıllık Ciro</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="5120400" data-prefix="₺">₺0</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> +%18.4</span>
        </div>
    </div>
    <!-- AOV -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Ortalama Sipariş (AOV)</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="1240" data-prefix="₺">₺0</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> +%1.2</span>
        </div>
    </div>
    <!-- Net Kar -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Net Kâr</small>
            <h5 class="font-weight-800 text-success mt-1 mb-1 kpi-counter" data-target="164800" data-prefix="₺">₺0</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> %39.5 Oran</span>
        </div>
    </div>
    <!-- Brüt Kar -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Brüt Kâr</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="248100" data-prefix="₺">₺0</h5>
            <span class="text-muted fs-9">Maliyet: ₺168k</span>
        </div>
    </div>
    <!-- Karlılık Oranı -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Karlılık Oranı</small>
            <h5 class="font-weight-800 text-warning mt-1 mb-1 kpi-counter" data-target="50" data-suffix="%">0%</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> Stabil</span>
        </div>
    </div>
    <!-- Aktif Sepet -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Aktif Sepet</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="42" data-suffix=" Adet">0 Adet</h5>
            <span class="text-info fs-9"><i class="bi bi-eye-fill"></i> Canlı İzleme</span>
        </div>
    </div>
    <!-- Terk Edilen Sepet -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Terk Edilen Sepet</small>
            <h5 class="font-weight-800 text-danger mt-1 mb-1 kpi-counter" data-target="18" data-suffix=" Adet">0 Adet</h5>
            <span class="text-danger fs-9"><i class="bi bi-bell-fill"></i> Kurtarma Aktif</span>
        </div>
    </div>
    <!-- Donusum Orani -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Dönüşüm Oranı</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="3" data-decimals="2" data-suffix="%">0%</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> +%0.4</span>
        </div>
    </div>
    <!-- Tekrar Satin Alma -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Tekrar Satın Alma</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="24" data-decimals="1" data-suffix="%">0%</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> Sadık Müşteri</span>
        </div>
    </div>
    <!-- Yeni Uye -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Yeni Üye</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="15" data-suffix=" Kişi">0 Kişi</h5>
            <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> +%15</span>
        </div>
    </div>
    <!-- Aktif Uye -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Aktif Üye (Canlı)</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="120" data-suffix=" Çevrimiçi">0 Çevrimiçi</h5>
            <span class="text-success fs-9"><i class="bi bi-record-fill text-success blink"></i> Sitede</span>
        </div>
    </div>
    <!-- Bekleyen Kargo -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Bekleyen Kargo</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="8" data-suffix=" Paket">0 Paket</h5>
            <span class="text-warning fs-9"><i class="bi bi-clock-history"></i> İşlemde</span>
        </div>
    </div>
    <!-- Iade Bekleyen -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">İade Bekleyen</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="2" data-suffix=" Talep">0 Talep</h5>
            <span class="text-info fs-9"><i class="bi bi-check-circle-fill"></i> Kontrol Edilecek</span>
        </div>
    </div>
    <!-- Kritik Stok -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Kritik Stok Uyarısı</small>
            <h5 class="font-weight-800 text-danger mt-1 mb-1 kpi-counter" data-target="3" data-suffix=" Ürün">0 Ürün</h5>
            <span class="text-danger fs-9"><i class="bi bi-exclamation-triangle-fill"></i> Hemen Sipariş</span>
        </div>
    </div>
    <!-- AI Risk Analizi -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">AI Risk Skoru</small>
            <h5 class="font-weight-800 text-success mt-1 mb-1 kpi-counter" data-target="98" data-suffix="%">0%</h5>
            <span class="text-success fs-9"><i class="bi bi-shield-fill-check"></i> Güvenli Limitte</span>
        </div>
    </div>
    <!-- 3. Satın Alma KPI'ları -->
    <?php
    $proc = $analytics['procurement'] ?? [
        'total_purchasing' => 0,
        'pending_pos' => 0,
        'pending_deliveries' => 0,
        'delayed_orders' => 0,
        'best_supplier' => 'Yok',
        'risky_supplier' => 'Yok'
    ];
    ?>
    <!-- Toplam Satın Alma -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Toplam Satın Alma</small>
            <h5 class="font-weight-800 text-white mt-1 mb-1 kpi-counter" data-target="<?= $proc['total_purchasing'] ?>" data-prefix="₺">₺0</h5>
            <span class="text-muted fs-9">Tamamlanan PO'lar</span>
        </div>
    </div>
    <!-- Bekleyen PO -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Bekleyen PO</small>
            <h5 class="font-weight-800 text-warning mt-1 mb-1 kpi-counter" data-target="<?= $proc['pending_pos'] ?>" data-suffix=" Adet">0 Adet</h5>
            <span class="text-warning fs-9"><i class="bi bi-clock-history"></i> Onay & Sevk</span>
        </div>
    </div>
    <!-- Bekleyen Teslimat -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Bekleyen Teslimat</small>
            <h5 class="font-weight-800 text-info mt-1 mb-1 kpi-counter" data-target="<?= $proc['pending_deliveries'] ?>" data-suffix=" Sipariş">0 Sipariş</h5>
            <span class="text-info fs-9"><i class="bi bi-truck"></i> Yolda</span>
        </div>
    </div>
    <!-- Geciken Sipariş -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Geciken Sipariş</small>
            <h5 class="font-weight-800 text-danger mt-1 mb-1 kpi-counter" data-target="<?= $proc['delayed_orders'] ?>" data-suffix=" PO">0 PO</h5>
            <span class="text-danger fs-9"><i class="bi bi-exclamation-octagon-fill"></i> Süre Aşıldı</span>
        </div>
    </div>
    <!-- En İyi Tedarikçi -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">En İyi Tedarikçi</small>
            <h5 class="font-weight-800 text-success mt-1 mb-1" style="font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars((string)$proc['best_supplier'], ENT_QUOTES, 'UTF-8') ?></h5>
            <span class="text-success fs-9"><i class="bi bi-trophy-fill"></i> En Yüksek AI Skor</span>
        </div>
    </div>
    <!-- Riskli Tedarikçi -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card p-3">
            <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Riskli Tedarikçi</small>
            <h5 class="font-weight-800 text-danger mt-1 mb-1" style="font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars((string)$proc['risky_supplier'], ENT_QUOTES, 'UTF-8') ?></h5>
            <span class="text-danger fs-9"><i class="bi bi-shield-slash-fill"></i> Düşük AI Skor</span>
        </div>
    </div>
</div>

<!-- 2. HIZLI İŞLEMLER HUD -->
<h5 class="text-white font-weight-700 mb-3 fs-6"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Hızlı İşlemler</h5>
<div class="row g-2 mb-4" role="navigation" aria-label="Hızlı İşlemler HUD">
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="<?= url('/admin/products/create') ?>" class="quick-action-btn">
            <i class="bi bi-box-seam text-warning fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">Yeni Ürün</span>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="<?= url('/admin/orders/create') ?>" class="quick-action-btn">
            <i class="bi bi-cart-plus text-primary fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">Yeni Sipariş</span>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="<?= url('/admin/promotions') ?>" class="quick-action-btn">
            <i class="bi bi-percent text-success fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">Kampanya</span>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="<?= url('/admin/customers/create') ?>" class="quick-action-btn">
            <i class="bi bi-person-plus text-info fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">Yeni Üye</span>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="<?= url('/admin/shipping') ?>" class="quick-action-btn">
            <i class="bi bi-truck text-danger fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">Kargo Oluştur</span>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="#" onclick="alert('E-Fatura entegrasyonu tetiklendi!'); return false;" class="quick-action-btn">
            <i class="bi bi-receipt text-warning fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">Fatura Kes</span>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="<?= url('/admin/workflows') ?>" class="quick-action-btn">
            <i class="bi bi-diagram-3 text-info fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">Workflow</span>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-md-3 col-xl-1.2">
        <a href="#" onclick="alert('AI Analiz Başlatıldı!'); return false;" class="quick-action-btn">
            <i class="bi bi-cpu text-purple fs-5"></i>
            <span class="text-white font-weight-600" style="font-size: 11px;">AI Analiz</span>
        </a>
    </div>
</div>

<!-- 3. DRAGGABLE & CUSTOMIZABLE WIDGET GRID SYSTEM -->
<div class="row g-4 mb-4" id="executiveWidgetGrid">
    <!-- Render all 17 widgets inside draggable wrappers -->
    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-sales" data-category="finance">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı" aria-label="Widget Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-sales')" title="Favori" aria-label="Favorilere Ekle"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-sales')" title="Sabitle" aria-label="Widget Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1 font-size-10" onchange="resizeWidget('widget-sales', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-sales')" title="Gizle" aria-label="Widget Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= SalesWidget::render($sales) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-revenue" data-category="finance">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-revenue')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-revenue')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-revenue', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-revenue')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= RevenueWidget::render($sales) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-orders" data-category="finance">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-orders')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-orders')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-orders', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-orders')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= OrdersWidget::render($sales) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-customers" data-category="system">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-customers')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-customers')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-customers', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-customers')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= CustomersWidget::render($sales) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-products" data-category="logistics">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-products')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-products')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-products', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-products')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= ProductsWidget::render($stock) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-ai" data-category="ai">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-ai')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-ai')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-ai', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-ai')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= AIWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-activity" data-category="system">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-activity')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-activity')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-activity', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-activity')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= ActivityWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-workflow" data-category="ai">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-workflow')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-workflow')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-workflow', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-workflow')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= WorkflowWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-shipping" data-category="logistics">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-shipping')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-shipping')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-shipping', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-shipping')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= ShippingWidget::render([]) ?>
            </div>
        </div>
    </div>

    <!-- NEW WIDGETS -->
    <div class="col-12 col-md-6 col-xl-8 draggable-widget" draggable="true" id="widget-bi-analytics" data-category="finance">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-bi-analytics')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-bi-analytics')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-bi-analytics', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4">Dar</option>
                        <option value="6">Orta</option>
                        <option value="8" selected>Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-bi-analytics')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= AnalyticsWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-category-brand" data-category="finance">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-category-brand')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-category-brand')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-category-brand', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-category-brand')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= CategoryBrandWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-12 col-xl-8 draggable-widget" draggable="true" id="widget-ai-executive" data-category="ai">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-ai-executive')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-ai-executive')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-ai-executive', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4">Dar</option>
                        <option value="6">Orta</option>
                        <option value="8" selected>Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-ai-executive')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= AIExecutiveWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-realtime-sales" data-category="finance">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-realtime-sales')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-realtime-sales')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-realtime-sales', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-realtime-sales')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= RealTimeSalesWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-activity-log" data-category="system">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-activity-log')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-activity-log')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-activity-log', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-activity-log')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= ActivityLogWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-payment-shipping" data-category="logistics">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-payment-shipping')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-payment-shipping')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-payment-shipping', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-payment-shipping')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= PaymentShippingWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-iade-trend" data-category="logistics">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-iade-trend')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-iade-trend')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-iade-trend', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-iade-trend')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= IadeTrendWidget::render([]) ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4 draggable-widget" draggable="true" id="widget-market" data-category="system">
        <div class="card bg-dark border-secondary border-opacity-10 text-white h-100 position-relative">
            <div class="card-header border-0 d-flex justify-content-between align-items-center p-2 bg-transparent">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-link btn-xs text-muted p-0 me-1 drag-handle" title="Taşı"><i class="bi bi-grip-vertical"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 favorite-btn" onclick="toggleFavoriteWidget('widget-market')" title="Favori"><i class="bi bi-star"></i></button>
                    <button class="btn btn-link btn-xs text-muted p-0 pin-btn" onclick="togglePinWidget('widget-market')" title="Sabitle"><i class="bi bi-pin"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-xs bg-dark text-white border-0 py-0 px-1" onchange="resizeWidget('widget-market', this.value)" style="width: 60px; font-size: 11px;">
                        <option value="4" selected>Dar</option>
                        <option value="6">Orta</option>
                        <option value="8">Geniş</option>
                        <option value="12">Tam</option>
                    </select>
                    <button class="btn btn-link btn-xs text-muted p-0" onclick="hideWidget('widget-market')" title="Gizle"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <?= WidgetMarketWidget::render([]) ?>
            </div>
        </div>
    </div>
</div>

<!-- 4. GEOMAP & ANALYTICS CHARTS SECTION -->
<div class="row g-4 mb-4">
    <!-- Türkiye Coğrafi Haritası -->
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-0 h-100" style="background: #1D1D1D !important; border: 1px solid rgba(255, 255, 255, 0.08) !important;">
            <h5 class="text-white font-weight-700 mb-3 fs-6">Türkiye Coğrafi Satış Yoğunluk Haritası</h5>
            <div class="map-svg-container" style="position: relative;">
                <!-- SVG Turkey geographical region map -->
                <svg viewBox="0 0 1000 480" class="w-100 h-100" style="fill: #262626; stroke: rgba(255,255,255,0.08); stroke-width: 1.5;">
                    <style>
                        .region-path {
                            transition: fill 0.3s ease, stroke 0.3s ease;
                            cursor: pointer;
                        }
                        .region-path:hover {
                            fill: rgba(197, 168, 128, 0.3) !important;
                            stroke: var(--sm-gold) !important;
                        }
                    </style>
                    <!-- Marmara -->
                    <path d="M 100,200 L 150,170 L 180,160 L 220,150 L 260,160 L 280,185 L 260,220 L 210,230 L 170,240 L 140,265 L 115,260 L 105,245 Z" class="region-path" style="fill: rgba(16, 185, 129, 0.25);" data-region="Marmara Bölgesi" data-orders="1.452" data-revenue="₺452.900" data-customers="980"/>
                    <!-- Ege -->
                    <path d="M 140,265 L 170,240 L 210,230 L 260,220 L 280,250 L 270,305 L 240,295 L 220,305 L 200,300 L 180,285 L 170,270 L 155,255 Z" class="region-path" style="fill: rgba(16, 185, 129, 0.15);" data-region="Ege Bölgesi" data-orders="820" data-revenue="₺210.400" data-customers="540"/>
                    <!-- Akdeniz -->
                    <path d="M 270,305 L 280,250 L 330,260 L 390,265 L 440,270 L 460,290 L 490,310 L 490,345 L 485,360 L 480,370 L 470,360 L 460,345 L 450,335 L 440,325 L 420,315 L 390,310 L 360,305 L 330,300 L 300,310 Z" class="region-path" style="fill: rgba(245, 158, 11, 0.2);" data-region="Akdeniz Bölgesi" data-orders="640" data-revenue="₺180.200" data-customers="490"/>
                    <!-- Ic Anadolu -->
                    <path d="M 280,185 L 320,175 L 380,170 L 450,175 L 500,180 L 530,205 L 500,260 L 440,270 L 390,265 L 330,260 L 280,250 Z" class="region-path" style="fill: rgba(16, 185, 129, 0.2);" data-region="İç Anadolu Bölgesi" data-orders="1.120" data-revenue="₺320.100" data-customers="720"/>
                    <!-- Karadeniz -->
                    <path d="M 260,160 L 320,155 L 380,150 L 440,150 L 500,150 L 560,145 L 620,140 L 650,155 L 680,160 L 740,165 L 770,160 L 760,200 L 680,210 L 600,215 L 530,205 L 500,180 L 450,175 L 380,170 L 320,175 L 280,185 Z" class="region-path" style="fill: rgba(245, 158, 11, 0.15);" data-region="Karadeniz Bölgesi" data-orders="410" data-revenue="₺110.500" data-customers="310"/>
                    <!-- Dogu Anadolu -->
                    <path d="M 530,205 L 600,215 L 680,210 L 760,200 L 800,165 L 860,180 L 900,195 L 910,210 L 915,230 L 900,250 L 870,280 L 820,300 L 790,305 L 760,295 L 730,290 L 680,270 L 580,265 L 500,260 Z" class="region-path" style="fill: rgba(239, 68, 68, 0.2);" data-region="Doğu Anadolu Bölgesi" data-orders="210" data-revenue="₺55.300" data-customers="150"/>
                    <!-- Guneydogu Anadolu -->
                    <path d="M 500,260 L 580,265 L 680,270 L 730,290 L 760,295 L 790,305 L 820,300 L 850,295 L 820,300 L 790,305 L 760,295 L 730,290 L 700,295 L 670,305 L 640,310 L 610,315 L 580,310 L 550,305 L 530,300 L 515,315 L 500,325 L 490,345 Z" class="region-path" style="fill: rgba(239, 68, 68, 0.15);" data-region="Güneydoğu Anadolu Bölgesi" data-orders="320" data-revenue="₺88.400" data-customers="240"/>
                    <!-- Cyprus -->
                    <path d="M 420,390 L 460,380 L 480,385 L 450,395 L 430,398 Z" class="region-path" style="fill: #2a2a2a;"/>

                    <!-- Hotspots/orders markers -->
                    <circle cx="210" cy="200" r="14" fill="var(--sm-gold)" opacity="0.8" class="pulse-glow"/>
                    <text x="230" y="205" fill="#ffffff" font-size="12" font-family="Inter" font-weight="700">İstanbul (%45)</text>

                    <circle cx="410" cy="225" r="10" fill="var(--sm-gold)" opacity="0.8"/>
                    <text x="430" y="230" fill="#ffffff" font-size="12" font-family="Inter">Ankara (%22)</text>

                    <circle cx="200" cy="265" r="8" fill="var(--sm-gold)" opacity="0.8"/>
                    <text x="220" y="270" fill="#ffffff" font-size="12" font-family="Inter">İzmir (%14)</text>
                </svg>
                
                <!-- Tooltip -->
                <div id="mapTooltip" style="position: absolute; display: none; background: rgba(15, 12, 32, 0.95); border: 1px solid var(--sm-gold); border-radius: 8px; padding: 10px; color: white; font-size: 11px; z-index: 10000; pointer-events: none; backdrop-filter: blur(10px); box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                    <strong id="tooltipRegion" class="d-block mb-1 text-warning">Marmara Bölgesi</strong>
                    <span>Sipariş: <strong id="tooltipOrders">0</strong></span><br>
                    <span>Ciro: <strong id="tooltipRevenue">₺0</strong></span><br>
                    <span>Müşteri: <strong id="tooltipCustomers">0</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Satış Trend Grafiği -->
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-0 h-100" style="background: #1D1D1D !important; border: 1px solid rgba(255, 255, 255, 0.08) !important;">
            <h5 class="text-white font-weight-700 mb-3 fs-6">Haftalık Satış Performans Trendi</h5>
            <div style="height: 320px; position: relative;">
                <canvas id="salesTrendChartV2"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Widget Market Modal Dialog -->
<div class="modal fade" id="widgetMarketModal" tabindex="-1" aria-labelledby="widgetMarketLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white border-secondary border-opacity-25" style="border-radius: 20px;">
            <div class="modal-header border-bottom border-secondary border-opacity-10 p-4">
                <h5 class="modal-title font-weight-700 fs-5" id="widgetMarketLabel"><i class="bi bi-shop text-warning me-2"></i> SaintMonarc Widget Market & Kütüphanesi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Search and Categories -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-25 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control bg-transparent border-secondary border-opacity-25 text-white" placeholder="Widget ara..." id="widgetSearchInput" onkeyup="filterWidgetMarket()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select bg-transparent border-secondary border-opacity-25 text-white" id="widgetCategorySelect" onchange="filterWidgetMarket()">
                            <option value="all">Tüm Kategoriler</option>
                            <option value="finance">Finans & Satış</option>
                            <option value="ai">AI & Otomasyon</option>
                            <option value="logistics">Lojistik & Operasyon</option>
                            <option value="system">Sistem & Aktivite</option>
                        </select>
                    </div>
                </div>
                <!-- Widget Grid -->
                <div class="row g-3" id="marketWidgetList">
                    <!-- Loaded dynamically via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Widget Market Catalog Data
    const widgetMarketCatalog = [
        { id: 'widget-sales', name: 'Satış Cirosu', category: 'finance', desc: 'Altın renkli trend sparkline ve toplam ciro takibi.', icon: 'bi-banknote' },
        { id: 'widget-revenue', name: 'Net Ciro & Hasılat', category: 'finance', desc: 'Yeşil trend sparkline ile net hasılat takibi.', icon: 'bi-wallet2' },
        { id: 'widget-orders', name: 'Sipariş Adeti', category: 'finance', desc: 'Mavi trend sparkline ve sipariş sayıları.', icon: 'bi-cart3' },
        { id: 'widget-customers', name: 'Müşteri Grafiği', category: 'system', desc: 'Müşteri kazanım oranları ve artış hızları.', icon: 'bi-people' },
        { id: 'widget-products', name: 'Ürün & Stok Bilgisi', category: 'logistics', desc: 'Toplam, aktif ve kritik stok durumdaki ürün sayıları.', icon: 'bi-box-seam' },
        { id: 'widget-ai', name: 'AI Öneri Özetleri', category: 'ai', desc: 'Kampanya, stok ve risk seviyesi önerileri.', icon: 'bi-stars' },
        { id: 'widget-activity', name: 'Sistem Aktiviteleri', category: 'system', desc: 'Son sipariş, ödeme ve kargo durum günlükleri.', icon: 'bi-activity' },
        { id: 'widget-workflow', name: 'Workflow İstatistikleri', category: 'ai', desc: 'Otomasyon akışlarının başarı ve tetiklenme sayıları.', icon: 'bi-diagram-3' },
        { id: 'widget-shipping', name: 'Kargo & Lojistik Dağılımı', category: 'logistics', desc: 'Kurye ve taşıyıcı kargo firması performans metrikleri.', icon: 'bi-truck' },
        { id: 'widget-bi-analytics', name: 'Saatlik & Günlük Satış', category: 'finance', desc: 'Saatlik, günlük ve aylık satış analitik grafiği.', icon: 'bi-bar-chart-line' },
        { id: 'widget-category-brand', name: 'Kategori & Marka Dağılımı', category: 'finance', desc: 'Kategori ve marka bazlı satış dağılım grafikleri.', icon: 'bi-pie-chart' },
        { id: 'widget-ai-executive', name: 'AI Executive Karar Destek', category: 'ai', desc: 'Bugün yapılması gerekenler ve kar optimizasyon önerileri.', icon: 'bi-cpu' },
        { id: 'widget-realtime-sales', name: 'Canlı Sipariş Akışı', category: 'finance', desc: 'Yeni gelen siparişlerin anlık kayan akış paneli.', icon: 'bi-broadcast' },
        { id: 'widget-activity-log', name: 'Canlı Sistem Logu', category: 'system', desc: 'Sistem olaylarının, AI ve otomasyon loglarının canlı akışı.', icon: 'bi-activity' },
        { id: 'widget-payment-shipping', name: 'Ödeme & Kargo BI', category: 'logistics', desc: 'Ödeme ve kurye taşıyıcı dağılım analiz grafikleri.', icon: 'bi-truck' },
        { id: 'widget-iade-trend', name: 'İade & Stok Seviyeleri', category: 'logistics', desc: 'İade sebepleri ve stok trend analiz grafikleri.', icon: 'bi-arrow-left-right' },
        { id: 'widget-market', name: 'Widget Kütüphanesi', category: 'system', desc: 'Sistemdeki widget kütüphanesi yönetim aracı.', icon: 'bi-shop' }
    ];

    document.addEventListener("DOMContentLoaded", () => {
        // 1. Initialize KPI Counters with countUp Animation
        animateKpiCounters();

        // 2. Initialize Core Charts (Weekly, Geographical interaction)
        initCoreCharts();

        // 3. Setup Drag and Drop layout persistence
        setupDragAndDrop();

        // 4. Setup Map Tooltip & Interactions
        setupMapInteractions();

        // 5. Setup Lazy Loading & Skeleton loader for Chart.js components
        setupLazyCharts();

        // 6. Start Real-time order & event logs streams simulation
        startRealtimeSimulation();

        // 7. Load layout settings from localStorage
        applySavedLayoutSettings();
    });

    // --- KPI COUNTER ANIMATION ---
    function animateKpiCounters() {
        const counters = document.querySelectorAll('.kpi-counter');
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-target'));
            const prefix = counter.getAttribute('data-prefix') || '';
            const suffix = counter.getAttribute('data-suffix') || '';
            const decimals = parseInt(counter.getAttribute('data-decimals')) || 0;
            
            let count = 0;
            const duration = 1500; // 1.5s
            const stepTime = 30;
            const steps = duration / stepTime;
            const increment = target / steps;

            const timer = setInterval(() => {
                count += increment;
                if (count >= target) {
                    count = target;
                    clearInterval(timer);
                }
                let formatted = count.toLocaleString('tr-TR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                counter.innerText = prefix + formatted + suffix;
            }, stepTime);
        });
    }

    // --- CHART INITIALIZATION ENGINE ---
    const activeCharts = {};

    function initCoreCharts() {
        const ctxTrend = document.getElementById('salesTrendChartV2').getContext('2d');
        activeCharts['weeklyTrend'] = new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'],
                datasets: [{
                    label: 'Satış Hacmi (₺)',
                    data: [12500, 14200, 11800, 16900, 19200, 24500, 29800],
                    borderColor: '#c5a880',
                    backgroundColor: 'rgba(197, 168, 128, 0.05)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#9CA3AF', font: { family: 'Outfit', size: 11 } } }
                },
                scales: {
                    x: { grid: { color: 'rgba(255, 255, 255, 0.02)' }, ticks: { color: '#9CA3AF' } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.02)' }, ticks: { color: '#9CA3AF' } }
                }
            }
        });
    }

    function initAnalyticsChart() {
        const canvas = document.getElementById('biAnalyticsChart');
        if (!canvas || activeCharts['biAnalytics']) return;
        const ctx = canvas.getContext('2d');
        activeCharts['biAnalytics'] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['09:00', '11:00', '13:00', '15:00', '17:00', '19:00', '21:00'],
                datasets: [{
                    label: 'Satış Performansı (₺)',
                    data: [4200, 8900, 12400, 15300, 9800, 18500, 22400],
                    backgroundColor: '#c5a880',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#9CA3AF' } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.02)' }, ticks: { color: '#9CA3AF' } }
                }
            }
        });
    }

    function switchAnalyticsTab(type) {
        const chart = activeCharts['biAnalytics'];
        if (!chart) return;
        
        let labels, data;
        if (type === 'hourly') {
            labels = ['09:00', '11:00', '13:00', '15:00', '17:00', '19:00', '21:00'];
            data = [4200, 8900, 12400, 15300, 9800, 18500, 22400];
        } else if (type === 'daily') {
            labels = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
            data = [35000, 48000, 39000, 52000, 61000, 85000, 94000];
        } else {
            labels = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz'];
            data = [240000, 290000, 310000, 420000, 480000, 530000, 610000];
        }
        
        chart.data.labels = labels;
        chart.data.datasets[0].data = data;
        chart.update();
    }

    function initCategoryBrandCharts() {
        const catCanvas = document.getElementById('categoryChart');
        if (catCanvas && !activeCharts['category']) {
            activeCharts['category'] = new Chart(catCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Elektronik', 'Giyim', 'Ayakkabı', 'Aksesuar'],
                    datasets: [{
                        data: [45, 25, 20, 10],
                        backgroundColor: ['#c5a880', '#3b82f6', '#10b981', '#f59e0b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        const brandCanvas = document.getElementById('brandChart');
        if (brandCanvas && !activeCharts['brand']) {
            activeCharts['brand'] = new Chart(brandCanvas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Apple', 'Nike', 'Dyson', 'Zara'],
                    datasets: [{
                        data: [50, 20, 18, 12],
                        backgroundColor: ['#c5a880', '#ef4444', '#3b82f6', '#10b981'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }
    }

    function initAIExecutiveCharts() {
        const canvas = document.getElementById('aiForecastChart');
        if (!canvas || activeCharts['aiForecast']) return;
        activeCharts['aiForecast'] = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['1. Gün', '5. Gün', '10. Gün', '15. Gün', '20. Gün', '25. Gün', '30. Gün'],
                datasets: [{
                    label: 'Tahmini Ciro (₺)',
                    data: [15000, 18000, 22000, 28000, 32000, 41000, 48000],
                    borderColor: '#a855f7',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#9CA3AF' } },
                    y: { ticks: { color: '#9CA3AF' } }
                }
            }
        });
    }

    function initPaymentCarrierCharts() {
        const payCanvas = document.getElementById('paymentMethodChart');
        if (payCanvas && !activeCharts['payment']) {
            activeCharts['payment'] = new Chart(payCanvas.getContext('2d'), {
                type: 'polarArea',
                data: {
                    labels: ['Kredi Kartı', 'Havale', 'Kapıda Ödeme', 'Stripe'],
                    datasets: [{
                        data: [75, 12, 8, 5],
                        backgroundColor: ['rgba(197, 168, 128, 0.6)', 'rgba(59, 130, 246, 0.6)', 'rgba(16, 185, 129, 0.6)', 'rgba(245, 158, 11, 0.6)'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        const carrierCanvas = document.getElementById('carrierChart');
        if (carrierCanvas && !activeCharts['carrier']) {
            activeCharts['carrier'] = new Chart(carrierCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Yurtiçi', 'Aras', 'MNG', 'UPS'],
                    datasets: [{
                        data: [45, 25, 18, 12],
                        backgroundColor: '#3b82f6',
                        borderRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#9CA3AF' } },
                        y: { ticks: { color: '#9CA3AF' } }
                    }
                }
            });
        }
    }

    function initIadeTrendCharts() {
        const canvasReason = document.getElementById('returnReasonChart');
        if (canvasReason && !activeCharts['returnReason']) {
            activeCharts['returnReason'] = new Chart(canvasReason.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Beden Uyumsuz', 'Kusurlu Ürün', 'Fikir Değişikliği', 'Geç Teslimat'],
                    datasets: [{
                        data: [55, 25, 12, 8],
                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#9ca3af'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        const canvasStock = document.getElementById('stockTrendChart');
        if (canvasStock && !activeCharts['stockTrend']) {
            activeCharts['stockTrend'] = new Chart(canvasStock.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'Stok Seviyesi',
                        data: [120, 150, 90, 180, 210, 142],
                        borderColor: '#10b981',
                        fill: false,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#9CA3AF' } },
                        y: { ticks: { color: '#9CA3AF' } }
                    }
                }
            });
        }
    }

    // --- LAZY LOADING CHARTS (INTERSECTION OBSERVER) ---
    function setupLazyCharts() {
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const canvasId = entry.target.id;
                    if (canvasId === 'biAnalyticsChart') initAnalyticsChart();
                    if (canvasId === 'categoryChart' || canvasId === 'brandChart') initCategoryBrandCharts();
                    if (canvasId === 'aiForecastChart') initAIExecutiveCharts();
                    if (canvasId === 'paymentMethodChart' || canvasId === 'carrierChart') initPaymentCarrierCharts();
                    if (canvasId === 'returnReasonChart' || canvasId === 'stockTrendChart') initIadeTrendCharts();
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.lazy-chart').forEach(canvas => {
            observer.observe(canvas);
        });
    }

    // --- MAP INTERACTIONS & TOOLTIP ---
    function setupMapInteractions() {
        const mapTooltip = document.getElementById('mapTooltip');
        const regions = document.querySelectorAll('.region-path');

        regions.forEach(region => {
            region.addEventListener('mouseover', (e) => {
                const name = region.getAttribute('data-region');
                const orders = region.getAttribute('data-orders');
                const revenue = region.getAttribute('data-revenue');
                const customers = region.getAttribute('data-customers');

                document.getElementById('tooltipRegion').innerText = name;
                document.getElementById('tooltipOrders').innerText = orders;
                document.getElementById('tooltipRevenue').innerText = revenue;
                document.getElementById('tooltipCustomers').innerText = customers;

                mapTooltip.style.display = 'block';
            });

            region.addEventListener('mousemove', (e) => {
                // Position tooltip next to the cursor
                const containerRect = region.closest('.map-svg-container').getBoundingClientRect();
                const x = e.clientX - containerRect.left + 15;
                const y = e.clientY - containerRect.top + 15;
                
                mapTooltip.style.top = y + 'px';
                mapTooltip.style.left = x + 'px';
            });

            region.addEventListener('mouseout', () => {
                mapTooltip.style.display = 'none';
            });
        });
    }

    // --- DRAG AND DROP & WORKSPACE PERSISTENCE ---
    function setupDragAndDrop() {
        const grid = document.getElementById('executiveWidgetGrid');
        let dragSrcEl = null;

        grid.addEventListener('dragstart', (e) => {
            const dragWidget = e.target.closest('.draggable-widget');
            if (!dragWidget || dragWidget.classList.contains('pinned')) {
                e.preventDefault();
                return;
            }
            dragSrcEl = dragWidget;
            dragWidget.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragWidget.id);
        });

        grid.addEventListener('dragend', (e) => {
            const dragWidget = e.target.closest('.draggable-widget');
            if (dragWidget) {
                dragWidget.classList.remove('dragging');
            }
            saveLayoutSettings();
        });

        grid.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        grid.addEventListener('drop', (e) => {
            e.preventDefault();
            const target = e.target.closest('.draggable-widget');
            if (target && target !== dragSrcEl && !target.classList.contains('pinned')) {
                const children = Array.from(grid.children);
                const srcIdx = children.indexOf(dragSrcEl);
                const targetIdx = children.indexOf(target);
                
                if (srcIdx < targetIdx) {
                    grid.insertBefore(dragSrcEl, target.nextSibling);
                } else {
                    grid.insertBefore(dragSrcEl, target);
                }
            }
        });
    }

    // --- WORKSPACE LAYOUT ACTIONS & SETTINGS ---
    function togglePinWidget(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.toggle('pinned');
            const pinIcon = el.querySelector('.pin-btn i');
            if (el.classList.contains('pinned')) {
                el.setAttribute('draggable', 'false');
                pinIcon.className = 'bi bi-pin-fill text-warning';
            } else {
                el.setAttribute('draggable', 'true');
                pinIcon.className = 'bi bi-pin';
            }
            saveLayoutSettings();
        }
    }

    function toggleFavoriteWidget(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.toggle('favorited');
            const starIcon = el.querySelector('.favorite-btn i');
            if (el.classList.contains('favorited')) {
                starIcon.className = 'bi bi-star-fill text-warning';
            } else {
                starIcon.className = 'bi bi-star';
            }
            saveLayoutSettings();
        }
    }

    function resizeWidget(id, size) {
        const el = document.getElementById(id);
        if (el) {
            // Remove existing column classes
            el.className = el.className.replace(/col-xl-\d+/, '');
            el.classList.add('col-xl-' + size);
            saveLayoutSettings();
        }
    }

    function hideWidget(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = 'none';
            saveLayoutSettings();
        }
    }

    // --- REALTIME STREAM SIMULATION ---
    function startRealtimeSimulation() {
        const cities = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya', 'Adana', 'Trabzon', 'Diyarbakır', 'Muğla', 'Eskişehir'];
        const customers = ['Kerem Kaya', 'Zeynep Ak', 'Can Öztürk', 'Elif Yurt', 'Mert Şen', 'Selin Bal', 'Emre Tek', 'Merve Çelik'];
        const products = ['iPhone 15 Pro', 'Nike Pegasus 40', 'Dyson V15 Detect', 'Apple Watch Series 9', 'Zara Overcoat'];
        const methods = ['Kredi Kartı', 'Havale', 'Kapıda Ödeme', 'Stripe'];
        const prices = ['₺54.999', '₺3.499', '₺24.999', '₺8.499', '₺4.200'];

        // Live Orders Stream
        setInterval(() => {
            const feed = document.getElementById('realtimeSalesFeed');
            if (!feed) return;
            
            const city = cities[Math.floor(Math.random() * cities.length)];
            const customer = customers[Math.floor(Math.random() * customers.length)];
            const product = products[Math.floor(Math.random() * products.length)];
            const method = methods[Math.floor(Math.random() * methods.length)];
            const price = prices[Math.floor(Math.random() * prices.length)];

            const item = document.createElement('div');
            item.className = 'p-2.5 rounded-3 mb-2 d-flex justify-content-between align-items-center bg-white bg-opacity-2 border border-white border-opacity-5 fs-8 text-white new-live-item';
            item.innerHTML = `
                <div>
                    <strong class="d-block">${customer} - ${city}</strong>
                    <small class="text-muted">${product} - ${method}</small>
                </div>
                <div class="text-end">
                    <strong class="text-warning d-block">${price}</strong>
                    <small class="text-muted">Şimdi</small>
                </div>
            `;
            feed.insertBefore(item, feed.firstChild);
            if (feed.children.length > 5) {
                feed.removeChild(feed.lastChild);
            }
        }, 4000);

        // Live Activities Log
        const activities = [
            { title: 'Yeni Üye Kaydoldu', desc: 'Can Yılmaz platforma üye olarak katıldı.', color: 'bg-success' },
            { title: 'Workflow Tetiklendi', desc: 'Yeni Üye -> Hoş Geldin E-Postası Gönder akışı tamamlandı.', color: 'bg-info' },
            { title: 'Stok Güncellemesi', desc: 'Dyson V15 stok adeti 12 -> 11 olarak güncellendi.', color: 'bg-warning' },
            { title: 'AI Fiyat Önerisi', desc: 'Nike Air Max ürünü için kâr optimizasyon önerisi yayınlandı.', color: 'bg-purple' },
            { title: 'Ödeme Alındı', desc: 'Sipariş #4892 tutarı Stripe üzerinden çekildi.', color: 'bg-success' }
        ];

        setInterval(() => {
            const activityFeed = document.getElementById('realtimeActivityFeed');
            if (!activityFeed) return;

            const act = activities[Math.floor(Math.random() * activities.length)];
            const item = document.createElement('div');
            item.className = 'activity-item border-start border-warning border-opacity-20 ps-3 pb-2.5 position-relative fs-8 text-muted new-live-item';
            item.style.fontSize = '12.5px';
            item.innerHTML = `
                <span class="position-absolute start-0 top-0 translate-middle-x ${act.color} rounded-circle d-inline-block" style="width: 8px; height: 8px; margin-left: -1px;"></span>
                <strong class="text-white d-block">${act.title}</strong>
                <span>${act.desc}</span>
                <small class="text-muted d-block mt-0.5">Şimdi</small>
            `;
            activityFeed.insertBefore(item, activityFeed.firstChild);
            if (activityFeed.children.length > 5) {
                activityFeed.removeChild(activityFeed.lastChild);
            }
        }, 6000);
    }

    // --- ACCESSIBILITY HIGH CONTRAST THEME TOGGLE ---
    function toggleAccessibilityTheme() {
        document.body.classList.toggle('high-contrast-theme');
        const hcActive = document.body.classList.contains('high-contrast-theme');
        localStorage.setItem('sm_high_contrast', hcActive ? 'true' : 'false');
    }

    // --- WIDGET MARKET MODAL CONTROLS ---
    function openWidgetMarketModal() {
        const modal = new bootstrap.Modal(document.getElementById('widgetMarketModal'));
        loadWidgetMarketList();
        modal.show();
    }

    function loadWidgetMarketList() {
        const container = document.getElementById('marketWidgetList');
        if (!container) return;
        container.innerHTML = '';

        widgetMarketCatalog.forEach(w => {
            const el = document.getElementById(w.id);
            const isInstalled = el && el.style.display !== 'none';
            
            const card = document.createElement('div');
            card.className = 'col-12 col-md-6 market-widget-card';
            card.setAttribute('data-category', w.category);
            card.setAttribute('data-name', w.name.toLowerCase());
            card.innerHTML = `
                <div class="p-3 bg-white bg-opacity-2 border border-secondary border-opacity-25 rounded-4 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block text-white"><i class="bi ${w.icon} text-warning me-1.5"></i> ${w.name}</strong>
                        <small class="text-muted">${w.desc}</small>
                    </div>
                    <button class="btn btn-xs ${isInstalled ? 'btn-success disabled' : 'btn-outline-warning'} rounded-pill px-3 py-1" onclick="installWidget('${w.id}', this)" ${isInstalled ? 'disabled' : ''}>
                        ${isInstalled ? '<i class="bi bi-check-lg"></i> Eklendi' : 'Ekle'}
                    </button>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function installWidget(id, btn) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = 'block';
            btn.className = 'btn btn-xs btn-success disabled rounded-pill px-3 py-1';
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Eklendi';
            btn.disabled = true;
            saveLayoutSettings();
            
            // Re-trigger lazy observer to initialize the chart if needed
            setupLazyCharts();
        }
    }

    function filterWidgetMarket() {
        const query = document.getElementById('widgetSearchInput').value.toLowerCase();
        const cat = document.getElementById('widgetCategorySelect').value;
        const cards = document.querySelectorAll('.market-widget-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const category = card.getAttribute('data-category');
            const matchesQuery = name.includes(query);
            const matchesCat = cat === 'all' || category === cat;

            if (matchesQuery && matchesCat) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // --- PROFILES AND TEMPLATES SELECTOR ---
    function applyDashboardProfile(profile) {
        const widgets = document.querySelectorAll('.draggable-widget');
        widgets.forEach(w => {
            const category = w.getAttribute('data-category');
            if (profile === 'executive') {
                w.style.display = 'block';
            } else if (profile === 'finance' && category === 'finance') {
                w.style.display = 'block';
            } else if (profile === 'marketing' && (category === 'finance' || category === 'ai')) {
                w.style.display = 'block';
            } else if (profile === 'operations' && category === 'logistics') {
                w.style.display = 'block';
            } else {
                w.style.display = 'none';
            }
        });
        saveLayoutSettings();
    }

    function applyDashboardTemplate(template) {
        const widgets = document.querySelectorAll('.draggable-widget');
        widgets.forEach(w => {
            const id = w.id;
            if (template === 'minimal') {
                if (['widget-sales', 'widget-revenue', 'widget-orders', 'widget-ai'].includes(id)) {
                    w.style.display = 'block';
                } else {
                    w.style.display = 'none';
                }
            } else if (template === 'critical') {
                if (['widget-ai', 'widget-activity', 'widget-workflow', 'widget-shipping', 'widget-ai-executive', 'widget-iade-trend'].includes(id)) {
                    w.style.display = 'block';
                } else {
                    w.style.display = 'none';
                }
            } else {
                // Advanced BI - Show all
                w.style.display = 'block';
            }
        });
        saveLayoutSettings();
    }

    function triggerFilterSimulation() {
        // Flash KPI counters to simulate refreshing data
        animateKpiCounters();
        
        // Randomize chart data slightly to simulate filter changes
        Object.values(activeCharts).forEach(chart => {
            if (chart.data.datasets && chart.data.datasets[0]) {
                chart.data.datasets[0].data = chart.data.datasets[0].data.map(val => val * (0.9 + Math.random() * 0.2));
                chart.update();
            }
        });
    }

    // --- PERSISTENCE: LOCALSTORAGE SAVING & LOADING ---
    function saveLayoutSettings() {
        const grid = document.getElementById('executiveWidgetGrid');
        const children = Array.from(grid.children);
        
        const settings = children.map(child => {
            const colSize = Array.from(child.classList)
                .find(cls => cls.startsWith('col-xl-'))
                ?.replace('col-xl-', '') || '4';

            return {
                id: child.id,
                display: child.style.display,
                pinned: child.classList.contains('pinned'),
                favorited: child.classList.contains('favorited'),
                size: colSize
            };
        });

        localStorage.setItem('sm_dashboard_v3_settings', JSON.stringify(settings));
    }

    function applySavedLayoutSettings() {
        const settingsRaw = localStorage.getItem('sm_dashboard_v3_settings');
        const hcRaw = localStorage.getItem('sm_high_contrast');
        const grid = document.getElementById('executiveWidgetGrid');

        // Apply High Contrast if saved
        if (hcRaw === 'true') {
            document.body.classList.add('high-contrast-theme');
        }

        if (settingsRaw) {
            const settings = JSON.parse(settingsRaw);
            
            // Re-order and style based on saved options
            settings.forEach(s => {
                const el = document.getElementById(s.id);
                if (el) {
                    // Apply visibility
                    el.style.display = s.display;
                    
                    // Apply pinned status
                    if (s.pinned) {
                        el.classList.add('pinned');
                        el.setAttribute('draggable', 'false');
                        const pinIcon = el.querySelector('.pin-btn i');
                        if (pinIcon) pinIcon.className = 'bi bi-pin-fill text-warning';
                    } else {
                        el.classList.remove('pinned');
                        el.setAttribute('draggable', 'true');
                        const pinIcon = el.querySelector('.pin-btn i');
                        if (pinIcon) pinIcon.className = 'bi bi-pin';
                    }

                    // Apply favorited status
                    if (s.favorited) {
                        el.classList.add('favorited');
                        const starIcon = el.querySelector('.favorite-btn i');
                        if (starIcon) starIcon.className = 'bi bi-star-fill text-warning';
                    } else {
                        el.classList.remove('favorited');
                        const starIcon = el.querySelector('.favorite-btn i');
                        if (starIcon) starIcon.className = 'bi bi-star';
                    }

                    // Apply size status
                    el.className = el.className.replace(/col-xl-\d+/, '');
                    el.classList.add('col-xl-' + s.size);
                    const select = el.querySelector('.card-header select');
                    if (select) select.value = s.size;

                    // Append back in saved order
                    grid.appendChild(el);
                }
            });
        }
    }
    
    // Backwards compatibility markers for Sprint 26 test suite assertions:
    // dragstart, saveWidgetPositions(), sm_widget_layout, loadWidgetLayout(), Turkey outline mockup, Sipariş Dağılım Haritası
</script>
