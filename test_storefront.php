<?php
declare(strict_types=1);

/**
 * Sprint 24 - Enterprise Storefront CLI Tests
 */

define('ROOT_DIR', __DIR__);

if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
}

// Development and dynamic fallback autoloader to resolve newly created files
spl_autoload_register(function (string $class) {
    $prefixMap = [
        'Core\\' => 'core/',
        'App\\' => 'app/',
        'Modules\\' => 'modules/',
        'Admin\\' => 'admin/',
        'Resources\\' => 'resources/'
    ];

    foreach ($prefixMap as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

use Core\Config\EnvParser;
use Core\Application;

EnvParser::parse(ROOT_DIR . '/.env');

$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$db = $container->get(\Core\Contracts\DatabaseInterface::class);

$passed = 0;
$failed = 0;

function test(string $name, callable $fn) {
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "  [PASS] {$name}\n";
            $passed++;
        } else {
            echo "  [FAIL] {$name}: " . (is_string($result) ? $result : json_encode($result)) . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "  [FAIL] {$name}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo " SPRINT 24 - ENTERPRISE STOREFRONT (VITRIN) UNIT TESTLERİ\n";
echo str_repeat('=', 60) . "\n\n";

// --- SECTION 1: ROUTING & CONTROLLERS ---
echo "📂 [BÖLÜM 1] Rota ve Kontrolör Metot Kontrolleri\n";

test("StoreController sınıfının varlığı", function() {
    return class_exists(\App\Controllers\StoreController::class);
});

test("StoreController::home metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'home');
});

test("StoreController::products metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'products');
});

test("StoreController::category metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'category');
});

test("StoreController::brand metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'brand');
});

test("StoreController::productDetail metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'productDetail');
});

test("StoreController::cart metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'cart');
});

test("StoreController::checkout metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'checkout');
});

test("StoreController::account metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'account');
});

test("StoreController::blog metot varlığı", function() {
    return method_exists(\App\Controllers\StoreController::class, 'blog');
});

// --- SECTION 2: VIEW TEMPLATES ---
echo "\n🎯 [BÖLÜM 2] Vitrin Arayüz Görünümleri Varlık Kontrolleri\n";

$templates = [
    'store/layouts/header.php', 'store/layouts/footer.php',
    'store/home/index.php', 'store/product/detail.php',
    'store/category/list.php', 'store/brand/list.php',
    'store/search/results.php', 'store/cart/index.php',
    'store/checkout/index.php', 'store/customer/dashboard.php',
    'store/blog/index.php'
];

foreach ($templates as $t) {
    test("Görünüm dosyası varlığı: {$t}", function() use ($t) {
        $path = ROOT_DIR . '/resources/views/' . $t;
        return file_exists($path) ? true : "Dosya bulunamadı: {$path}";
    });
}

// --- SECTION 3: SEO & WEB METRICS ---
echo "\n🔎 [BÖLÜM 3] SEO, Performans ve Semantik Kuralları\n";

test("Storefront HTML yapısı Outfit/Inter tipografisini barındırıyor", function() {
    $headerContent = file_get_contents(ROOT_DIR . '/resources/views/store/layouts/header.php');
    return str_contains($headerContent, 'fonts.googleapis.com/css2?family=Inter') ? true : 'Inter font çağrılmamış';
});

test("Sticky Header yapısı mega menü ve arama kutusunu barındırıyor", function() {
    $headerContent = file_get_contents(ROOT_DIR . '/resources/views/store/layouts/header.php');
    return str_contains($headerContent, 'store-header') && str_contains($headerContent, 'q') ? true : 'Header yapısı eksik';
});

test("Kurumsal Footer e-bülten ve KVKK bağlantısını barındırıyor", function() {
    $footerContent = file_get_contents(ROOT_DIR . '/resources/views/store/layouts/footer.php');
    return str_contains($footerContent, 'E-Bülten') && str_contains($footerContent, 'KVKK') ? true : 'Footer yapısı eksik';
});

test("Tek Sayfa Ödeme (Checkout) kart ve kargo formunu barındırıyor", function() {
    $checkoutContent = file_get_contents(ROOT_DIR . '/resources/views/store/checkout/index.php');
    return str_contains($checkoutContent, 'shipping') && str_contains($checkoutContent, 'payment') ? true : 'Checkout yapısı eksik';
});

// --- SECTION 4: PREMIUM COMPONENTS & PRODUCT PAGE ---
echo "\n💎 [BÖLÜM 4] Premium Bileşenler ve Ürün Sayfası Denetimleri\n";

test("UiStore sınıfı tanımlı", function() {
    require_once ROOT_DIR . '/resources/views/store/components/UiStore.php';
    return class_exists(\Resources\Views\Store\Components\UiStore::class);
});

test("UiStore::badge metot varlığı ve çıktı doğruluğu", function() {
    $html = \Resources\Views\Store\Components\UiStore::badge('ai', 'Yapay Zekâ');
    return str_contains($html, 'bi-stars') && str_contains($html, 'Yapay Zekâ');
});

test("UiStore::deliveryCard kargo firmalarını ve tahmini teslimatı listeliyor", function() {
    $html = \Resources\Views\Store\Components\UiStore::deliveryCard(['company' => 'SM Express', 'date' => 'Yarın Teslim']);
    return str_contains($html, 'SM Express') && str_contains($html, 'Yarın Teslim');
});

test("UiStore::installmentCalculator taksit tablosunu üretiyor", function() {
    $html = \Resources\Views\Store\Components\UiStore::installmentCalculator(1000.00);
    return str_contains($html, '3 Taksit') && str_contains($html, '12 Taksit');
});

test("UiStore::bundleCard paket indirimini ve kazancı gösteriyor", function() {
    $html = \Resources\Views\Store\Components\UiStore::bundleCard(['old_price' => 1250, 'price' => 1000, 'desc' => 'Tasarruf paketi']);
    return str_contains($html, 'Tasarruf paketi') && str_contains($html, '1.250');
});

test("Ürün Detay Sayfası variant-btn düğmelerini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'variant-btn');
});

test("Ürün Detay Sayfası 360 derece ve video oynatma tetikleyicilerini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'trigger360()') && str_contains($content, 'playVideo()');
});

test("Ürün Detay Sayfası akıllı AI yorum özetini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'AI Akıllı Yorum Özeti');
});

test("Ürün Detay Sayfası Soru & Cevap (Q&A) form yapısını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'Soru & Cevap');
});

test("Ürün Detay Sayfası sosyal kanıt (social proof) metriklerini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'Bugün') && str_contains($content, 'satıldı') && str_contains($content, 'gördü');
});

test("Ürün Detay Sayfası indirilebilir teknik doküman ve kılavuzları içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'CE Uyumluluk Belgesi') || str_contains($content, 'Teknik Doküman');
});

test("Ürün Detay Sayfası blog entegrasyon alanını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'Rehberler & Bloglar');
});

test("Ürün Detay Sayfası sosyal paylaşım (Share Modal) bileşenini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'id="shareModal"');
});

test("Ürün Detay Sayfası varyant tetiklendiğinde fiyat güncelleyen JS metodunu içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'function selectVariant');
});

test("Ürün Detay Sayfası slider resmini güncelleyen JS metodunu içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/store/product/detail.php');
    return str_contains($content, 'function changeImage');
});

// --- SECTION 5: EXECUTIVE DASHBOARD WIDGETS ---
echo "\n📊 [BÖLÜM 5] Executive Dashboard ve Widget Sistemi Denetimleri\n";

test("SalesWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/SalesWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\SalesWidget::render(['total_sales' => 5000]);
    return str_contains($html, 'Satış Cirosu') && str_contains($html, '5.000');
});

test("RevenueWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/RevenueWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\RevenueWidget::render([]);
    return str_contains($html, 'Net Ciro') && str_contains($html, 'Hasılat');
});

test("OrdersWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/OrdersWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\OrdersWidget::render(['order_count' => 18]);
    return str_contains($html, 'Sipariş Adeti') && str_contains($html, '18');
});

test("CustomersWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/CustomersWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\CustomersWidget::render(['aov' => 350.25]);
    return str_contains($html, 'Ortalama Sepet') && str_contains($html, '350,25');
});

test("ProductsWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/ProductsWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\ProductsWidget::render(['total_products' => 84, 'critical_stock' => 2]);
    return str_contains($html, 'Envanter Dağılımı') && str_contains($html, '84 Ürün');
});

test("AIWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/AIWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\AIWidget::render([]);
    return str_contains($html, 'AI Öneri') && str_contains($html, 'Satış Tahmini');
});

test("ActivityWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/ActivityWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\ActivityWidget::render([]);
    return str_contains($html, 'Son Sistem Aktiviteleri');
});

test("WorkflowWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/WorkflowWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\WorkflowWidget::render([]);
    return str_contains($html, 'İş Akışı') && str_contains($html, 'İstatistikleri');
});

test("ShippingWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    require_once ROOT_DIR . '/resources/views/admin/dashboard/widgets/ShippingWidget.php';
    $html = \Resources\Views\Admin\Dashboard\Widgets\ShippingWidget::render([]);
    return str_contains($html, 'Kargo & Lojistik Dağılımı');
});

test("Dashboard V2 ana görünüm dosyası tüm widget render çağrılarını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'SalesWidget::render') && str_contains($content, 'AIWidget::render');
});

test("Dashboard V2 hızlı aksiyon butonlarını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'quick-action-btn') && str_contains($content, 'Yeni Ürün');
});

test("Dashboard V2 drag-and-drop sıralama scriptlerini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'dragstart') && str_contains($content, 'saveWidgetPositions()');
});

test("Dashboard V2 localStorage entegrasyonunu içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'sm_widget_layout') && str_contains($content, 'loadWidgetLayout()');
});

test("Dashboard V2 Türkiye haritası SVG görselini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'Turkey outline mockup') || str_contains($content, 'Sipariş Dağılım Haritası');
});

test("Dashboard V2 Chart.js kütüphane bağlantısını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'cdn.jsdelivr.net/npm/chart.js');
});

test("AnalyticsWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\AnalyticsWidget::render([]);
    return str_contains($html, 'Saatlik') && str_contains($html, 'biAnalyticsChart');
});

test("CategoryBrandWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\CategoryBrandWidget::render([]);
    return str_contains($html, 'categoryChart') && str_contains($html, 'brandChart');
});

test("AIExecutiveWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\AIExecutiveWidget::render([]);
    return str_contains($html, 'aiForecastChart') && str_contains($html, 'Karar Destek');
});

test("RealTimeSalesWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\RealTimeSalesWidget::render([]);
    return str_contains($html, 'realtimeSalesFeed') && str_contains($html, 'Sipariş Akışı');
});

test("ActivityLogWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\ActivityLogWidget::render([]);
    return str_contains($html, 'realtimeActivityFeed') && str_contains($html, 'Sistem Aktivite');
});

test("PaymentShippingWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\PaymentShippingWidget::render([]);
    return str_contains($html, 'paymentMethodChart') && str_contains($html, 'carrierChart');
});

test("IadeTrendWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\IadeTrendWidget::render([]);
    return str_contains($html, 'returnReasonChart') && str_contains($html, 'stockTrendChart');
});

test("WidgetMarketWidget sınıfı tanımlı ve çıktı üretiyor", function() {
    $html = \Resources\Views\Admin\Dashboard\Widgets\WidgetMarketWidget::render([]);
    return str_contains($html, 'openWidgetMarketModal()') && str_contains($html, 'Widget Market');
});

test("Dashboard V3 üst filtreleme alanlarını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'dashboardDateFilter') && str_contains($content, 'dashboardCityFilter');
});

test("Dashboard V3 profil ve şablon seçicilerini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'dashboardProfileSelect') && str_contains($content, 'dashboardTemplateSelect');
});

test("Dashboard V3 animasyonlu sayaç yapılarını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'kpi-counter') && str_contains($content, 'animateKpiCounters()');
});

test("Dashboard V3 widget market modali ve veri kataloğunu içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'widgetMarketModal') && str_contains($content, 'widgetMarketCatalog');
});

test("Dashboard V3 gerçek zamanlı sipariş akışını içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'realtimeSalesFeed') && str_contains($content, 'new-live-item');
});

test("Dashboard V3 harita bölge koordinatlarını ve tooltip sistemini içeriyor", function() {
    $content = file_get_contents(ROOT_DIR . '/resources/views/admin/dashboard.php');
    return str_contains($content, 'region-path') && str_contains($content, 'mapTooltip');
});

// --- SONUÇ --
$dummy = 1; // placeholder dummy
echo "\n" . str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo " SPRINT 27 STOREFRONT SONUÇ: {$passed}/{$total} test BAŞARILI\n";
echo str_repeat('=', 60) . "\n\n";

if ($failed === 0) {
    echo "✅ TÜM SPRINT 27 EXECUTIVE DASHBOARD TESTLERİ BAŞARIYLA TAMAMLANDI!\n\n";
} else {
    echo "⚠️ Bazı testler başarısız. Lütfen hataları inceleyin.\n\n";
}
