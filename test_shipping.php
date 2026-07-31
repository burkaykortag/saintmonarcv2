<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Sprint 21 - Enterprise Logistics & Shipping CLI Test Betiği
 * Çalıştırma: php test_shipping.php
 */

define('ROOT_DIR', __DIR__);

// spl autoloader
spl_autoload_register(function (string $class) {
    $prefixMap = [
        'Core\\' => 'core/',
        'App\\' => 'app/',
        'Modules\\' => 'modules/',
        'Admin\\' => 'admin/'
    ];

    foreach ($prefixMap as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

use Core\Config\EnvParser;
use Core\Application;

EnvParser::parse(ROOT_DIR . '/.env');

$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$pdo = $container->get(\Core\Contracts\DatabaseInterface::class);

$passed = 0;
$failed = 0;

function testCase(string $name, callable $fn, int &$passed, int &$failed): void {
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "  ✅  {$name}\n";
            $passed++;
        } else {
            echo "  ❌  {$name}: " . (is_string($result) ? $result : json_encode($result)) . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "  ❌  {$name}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat('═', 62) . "\n";
echo "  SPRINT 21 — LOGISTICS & SHIPPING CLI TESTLERİ\n";
echo str_repeat('═', 62) . "\n\n";

// --- BÖLÜM 1: 26 ADET TABLO VARLIK KONTROLLERİ (1-26) ---
echo "📦 [BÖLÜM 1] Veritabanı Tablo Varlık Kontrolleri\n";

$tables = [
    'shipping_companies', 'shipping_services', 'shipping_regions', 'shipping_zones',
    'shipping_zone_prices', 'shipping_rules', 'shipping_methods', 'shipping_labels',
    'shipping_packages', 'shipping_package_items', 'shipping_pickups', 'shipping_tracking',
    'shipping_tracking_events', 'shipping_statuses', 'shipping_returns', 'shipping_return_items',
    'shipping_claims', 'shipping_insurances', 'shipping_documents', 'shipping_notifications',
    'shipping_api_logs', 'shipping_statistics', 'shipping_reports', 'shipping_cache',
    'shipping_translations', 'shipping_integrations'
];

$idx = 1;
foreach ($tables as $t) {
    testCase("{$idx}. Tablo varlığı: {$t}", function() use ($pdo, $t) {
        return count($pdo->query("SHOW TABLES LIKE '{$t}'")) > 0 ? true : "{$t} tablosu bulunamadı";
    }, $passed, $failed);
    $idx++;
}

// --- BÖLÜM 2: 9 ADET RBAC YETKİLERİ KONTROLLERİ (27-35) ---
echo "\n🔐 [BÖLÜM 2] RBAC Yetkilendirme İzin Kontrolleri\n";

$permissions = [
    'view_shipping', 'manage_shipping', 'manage_shipping_rules', 'manage_shipping_companies',
    'manage_returns', 'manage_labels', 'shipping_reports', 'shipping_statistics', 'shipping_integrations'
];

$permIdx = 27;
foreach ($permissions as $p) {
    testCase("{$permIdx}. RBAC Yetki varlığı: {$p}", function() use ($pdo, $p) {
        $rows = $pdo->query("SELECT id FROM permissions WHERE name = :name", [':name' => $p]);
        return count($rows) > 0 ? true : "{$p} yetkisi bulunamadı";
    }, $passed, $failed);
    $permIdx++;
}

// Ön Temizlik (Önceki başarısız testlerden kalan verileri siler)
$pdo->execute("DELETE FROM shipping_insurances");
$pdo->execute("DELETE FROM shipping_documents");
$pdo->execute("DELETE FROM shipping_notifications");
$pdo->execute("DELETE FROM shipping_claims");
$pdo->execute("DELETE FROM shipping_pickups");
$pdo->execute("DELETE FROM shipping_integrations");
$pdo->execute("DELETE FROM shipping_tracking_events");
$pdo->execute("DELETE FROM shipping_tracking");
$pdo->execute("DELETE FROM shipping_package_items");
$pdo->execute("DELETE FROM shipping_packages");
$pdo->execute("DELETE FROM shipping_services");
$pdo->execute("DELETE FROM shipping_companies WHERE code LIKE 'yurtici_test%' OR code LIKE 'fedex_global%' OR code LIKE 'yurtici_tr_%'");
$pdo->execute("DELETE FROM shipping_zone_prices");
$pdo->execute("DELETE FROM shipping_zones WHERE name = 'Ege Bölgesi'");
$pdo->execute("DELETE FROM shipping_rules WHERE name = '500 TL Üzeri Kargo Bedava'");

// --- BÖLÜM 3: REPOSITORY & CRUD FONKSİYONLARI (36-64) ---
echo "\n🎯 [BÖLÜM 3] Lojistik Deposu (ShippingRepository) & CRUD İşlemleri\n";

$repo = $container->get(\App\Repositories\ShippingRepository::class);
$service = $container->get(\App\Services\ShippingService::class);

$testCompanyId = null;
$testServiceId = null;
$testZoneId = null;
$testPackageId = null;
$testReturnId = null;

testCase('36. Kargo Firması Ekleme (createCompany)', function() use ($repo, &$testCompanyId) {
    $testCompanyId = $repo->createCompany([
        'name' => 'Yurtiçi Kargo Test',
        'code' => 'yurtici_test',
        'tax_number' => '9999999999',
        'is_active' => 1
    ]);
    return $testCompanyId > 0 ? true : 'Firma oluşturulamadı';
}, $passed, $failed);

testCase('37. Kargo Firması Çekme (getCompany)', function() use ($repo, &$testCompanyId) {
    $comp = $repo->getCompany($testCompanyId);
    return $comp['code'] === 'yurtici_test' ? true : 'Firma kodu eşleşmedi';
}, $passed, $failed);

testCase('38. Kargo Firması Güncelleme (updateCompany)', function() use ($repo, &$testCompanyId) {
    return $repo->updateCompany($testCompanyId, [
        'name' => 'Yurtiçi Kargo Test Güncel',
        'code' => 'yurtici_test',
        'tax_number' => '1111111111',
        'is_active' => 1
    ]);
}, $passed, $failed);

testCase('39. Kargo Firması Silme (deleteCompany - soft delete)', function() use ($repo, &$testCompanyId, $pdo) {
    $repo->deleteCompany($testCompanyId);
    $rows = $pdo->query("SELECT deleted_at FROM shipping_companies WHERE id = {$testCompanyId}");
    return !empty($rows[0]['deleted_at']) ? true : 'deleted_at boş kaldı';
}, $passed, $failed);

testCase('40. Kargo Firması Geri Yükleme (restoreCompany)', function() use ($repo, &$testCompanyId, $pdo) {
    $repo->restoreCompany($testCompanyId);
    $rows = $pdo->query("SELECT deleted_at FROM shipping_companies WHERE id = {$testCompanyId}");
    return empty($rows[0]['deleted_at']) ? true : 'deleted_at temizlenemedi';
}, $passed, $failed);

testCase('41. Kargo Servis Tipi Oluşturma (createService)', function() use ($repo, $testCompanyId, &$testServiceId) {
    $testServiceId = $repo->createService([
        'company_id' => $testCompanyId,
        'name' => 'Standart Gönderi',
        'code' => 'standart',
        'is_active' => 1
    ]);
    return $testServiceId > 0 ? true : 'Servis oluşturulamadı';
}, $passed, $failed);

testCase('42. Kargo Servis Tipi Silme', function() use ($repo, $testServiceId) {
    return $repo->deleteService($testServiceId);
}, $passed, $failed);

testCase('43. Kargo Teslimat Bölgesi (Zone) Oluşturma (createZone)', function() use ($repo, &$testZoneId) {
    $testZoneId = $repo->createZone([
        'name' => 'Ege Bölgesi',
        'country_code' => 'TR',
        'city_name' => 'İzmir',
        'is_active' => 1
    ]);
    return $testZoneId > 0 ? true : 'Zone oluşturulamadı';
}, $passed, $failed);

testCase('44. Kargo Bölge Desi Fiyat Matrisi Ekleme (createZonePrice)', function() use ($repo, $testZoneId, $testServiceId) {
    $id = $repo->createZonePrice([
        'zone_id' => $testZoneId,
        'service_id' => $testServiceId,
        'min_desi' => 0.00,
        'max_desi' => 10.00,
        'price' => 75.00
    ]);
    return $id > 0 ? true : 'Fiyat matrisi eklenemedi';
}, $passed, $failed);

testCase('45. Bölge Desi Fiyat Eşleşme Kontrolü (getMatchingZonePrice)', function() use ($repo, $testServiceId) {
    $row = $repo->getMatchingZonePrice($testServiceId, 'TR', 'İzmir', 5.0);
    return (float)($row['price'] ?? 0) === 75.00 ? true : 'Fiyat eşleşmesi hatalı';
}, $passed, $failed);

testCase('46. Kargo Hesaplama Kuralı Ekleme (createRule)', function() use ($repo) {
    $id = $repo->createRule([
        'name' => '500 TL Üzeri Kargo Bedava',
        'free_shipping_limit' => 500.00,
        'is_active' => 1
    ]);
    return $id > 0 ? true : 'Kural eklenemedi';
}, $passed, $failed);

testCase('47. Kargo Hesaplama Kurallarını Listeleme', function() use ($repo) {
    $list = $repo->listRules();
    return count($list) > 0 ? true : 'Kurallar listelenemedi';
}, $passed, $failed);

testCase('48. Hacimsel Desi Hesaplaması (calculateDesi)', function() use ($service) {
    $desi = $service->calculateDesi(30.0, 40.0, 50.0);
    return $desi === 20.0 ? true : 'Desi hesabı hatalı: ' . $desi;
}, $passed, $failed);

testCase('49. Kargo Fiyat Hesaplaması (calculateShippingCost)', function() use ($service, $testServiceId) {
    $cost = $service->calculateShippingCost($testServiceId, 'TR', 'İzmir', 5.0, 100.00);
    return $cost === 75.00 ? true : 'Fiyat hesaplaması hatalı: ' . $cost;
}, $passed, $failed);

testCase('50. Eşsiz Kargo Takip No Üretimi (generateTrackingNumber)', function() use ($service) {
    $track = $service->generateTrackingNumber();
    return str_starts_with($track, 'SM-TRK-') ? true : 'Takip numarası formatı geçersiz';
}, $passed, $failed);

testCase('51. Yeni Sevkiyat/Gönderi Paketi Oluşturma (createShipment)', function() use ($service, $testServiceId, &$testPackageId) {
    $testPackageId = $service->createShipment([
        'order_id' => 9991,
        'service_id' => $testServiceId,
        'desi' => 5.0,
        'weight' => 3.0,
        'shipping_cost' => 75.00
    ], [
        ['product_id' => 1, 'quantity' => 2]
    ]);
    return $testPackageId > 0 ? true : 'Gönderi paketi oluşturulamadı';
}, $passed, $failed);

testCase('52. Kargo Takip Durumu Güncelleme (updateTracking)', function() use ($service, $repo, $testPackageId) {
    $pkg = $repo->getPackage($testPackageId);
    $res = $service->updateTracking($pkg['tracking_number'], 'delivered', 'İzmir Dağıtım Şubesi', 'Alıcıya teslim edildi.');
    return $res === true ? true : 'Takip durumu güncellenemedi';
}, $passed, $failed);

testCase('53. Kargo Takip Durum Detay Geçmişini Çekme (getTrackingHistory)', function() use ($repo, $testPackageId) {
    $pkg = $repo->getPackage($testPackageId);
    $hist = $repo->getTrackingHistory($pkg['tracking_number']);
    return count($hist) > 0 ? true : 'Takip geçmişi boş';
}, $passed, $failed);

testCase('54. Mock Kargo Barkod Etiket PDF Oluşturma (generateLabel)', function() use ($service, $testPackageId) {
    $path = $service->generateLabel($testPackageId);
    return file_exists(ROOT_DIR . '/' . $path) ? true : 'Etiket dosyası oluşturulamadı';
}, $passed, $failed);

testCase('55. Kargo İade Talebi Oluşturma (createReturnRequest)', function() use ($service, &$testReturnId) {
    $testReturnId = $service->createReturnRequest([
        'order_id' => 9991,
        'reason' => 'Beden uymadı'
    ], [
        ['product_id' => 1, 'quantity' => 1]
    ]);
    return $testReturnId > 0 ? true : 'İade talebi oluşturulamadı';
}, $passed, $failed);

testCase('56. İade Durumunu Güncelleme (updateReturn)', function() use ($service, $testReturnId) {
    return $service->updateReturn($testReturnId, 'completed');
}, $passed, $failed);

testCase('57. Toplu Kargo Gönderisi Sevkiyatı Başlatma (bulkShip)', function() use ($service, $testServiceId) {
    $ids = $service->bulkShip([
        [
            'order_id' => 9992,
            'service_id' => $testServiceId,
            'desi' => 2.0,
            'weight' => 1.5,
            'shipping_cost' => 75.00
        ]
    ]);
    return count($ids) > 0 ? true : 'Toplu sevkiyat başarısız';
}, $passed, $failed);

testCase('58. Kargo Taşıyıcı Firmaları Listeleme (listCompanies)', function() use ($repo) {
    $list = $repo->listCompanies();
    return count($list) > 0 ? true : 'Firmalar listelenemedi';
}, $passed, $failed);

testCase('59. Sevkiyat Paketlerini Listeleme (listPackages)', function() use ($repo) {
    $list = $repo->listPackages();
    return count($list) > 0 ? true : 'Paketler listelenemedi';
}, $passed, $failed);

testCase('60. İadeleri Listeleme (listReturns)', function() use ($repo) {
    $list = $repo->listReturns();
    return count($list) > 0 ? true : 'İadeler listelenemedi';
}, $passed, $failed);

testCase('61. Toplu Gönderi Durum Güncellemesi (bulkUpdatePackageStatus)', function() use ($repo, $testPackageId) {
    return $repo->bulkUpdatePackageStatus([$testPackageId], 'shipped');
}, $passed, $failed);

testCase('62. Kargo API Entegrasyonu Ekleme/Güncelleme (upsertIntegration)', function() use ($repo, $testCompanyId) {
    $id = $repo->upsertIntegration([
        'company_id' => $testCompanyId,
        'api_url' => 'https://api.yurtici.com/v1',
        'username' => 'yurtici_user',
        'password' => 'yurtici_pass'
    ]);
    return $id > 0 ? true : 'Entegrasyon eklenemedi';
}, $passed, $failed);

testCase('63. Kargo API Entegrasyonunu Çekme (getIntegration)', function() use ($repo, $testCompanyId) {
    $int = $repo->getIntegration($testCompanyId);
    return $int['username'] === 'yurtici_user' ? true : 'Entegrasyon verisi hatalı';
}, $passed, $failed);

testCase('64. Lojistik Cache Temizliği', function() use ($service) {
    $service->clearShippingCache();
    return true;
}, $passed, $failed);


// --- BÖLÜM 4: REST API ENDPOINTS KONTROLLERİ (65-71) ---
echo "\n🌐 [BÖLÜM 4] REST API Uç Noktaları\n";

$endpoints = [
    '65. GET /api/shipping' => '/api/shipping',
    '66. GET /api/shipping/calculate' => '/api/shipping/calculate?service_id=1&desi=3.5',
    '67. GET /api/shipping/track' => '/api/shipping/track?tracking_number=SM-TRK-TEST',
    '68. GET /api/shipping/returns' => '/api/shipping/returns',
    '69. GET /api/shipping/companies' => '/api/shipping/companies',
    '70. GET /api/shipping/labels' => '/api/shipping/labels?package_id=1',
    '71. GET /api/shipping/statistics' => '/api/shipping/statistics'
];

foreach ($endpoints as $name => $uri) {
    testCase($name, function() use ($uri) {
        $ch = curl_init('http://localhost/SaintMonarc' . $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 0) return 'Sunucu bağlantısı kurulamadı (cURL error)';
        return ($httpCode === 200 || $httpCode === 400 || $httpCode === 401) ? true : "HTTP {$httpCode} döndü";
    }, $passed, $failed);
}


// --- BÖLÜM 5: SYNTAX & STANDARTS (72-80) ---
echo "\n🔎 [BÖLÜM 5] PHP Syntax ve Kod Standartları Kontrolleri\n";

$files = [
    'app/Repositories/ShippingRepository.php',
    'app/Services/ShippingService.php',
    'app/Controllers/ShippingController.php',
    'routes/admin.php',
    'routes/api.php'
];

$fileIdx = 72;
foreach ($files as $f) {
    testCase("{$fileIdx}. Syntax OK: {$f}", function () use ($f) {
        $path = ROOT_DIR . '/' . $f;
        if (!file_exists($path)) return "Dosya bulunamadı: {$f}";
        exec("C:\\xampp\\php\\php.exe -l \"{$path}\" 2>&1", $output, $ret);
        return $ret === 0 ? true : implode(' ', $output);
    }, $passed, $failed);
    $fileIdx++;
}


// --- BÖLÜM 6: EKSTRA SENARYO VE İŞ MANTIĞI DOĞRULAMALARI (81-95) ---
echo "\n🧪 [BÖLÜM 6] Ekstra İş Mantığı Senaryoları\n";

testCase('81. Desi aralığı dışı fiyat araması durumunda default kargo bedeli (49.90 TL) kontrolü', function() use ($service) {
    $cost = $service->calculateShippingCost(999, 'TR', 'Yok', 999.0, 0.0);
    return $cost === 49.90 ? true : 'Hatalı default kargo bedeli: ' . $cost;
}, $passed, $failed);

testCase('82. Ücretsiz kargo limitine ulaşıldığında kargo tutarının 0 TL olması', function() use ($service, $testServiceId) {
    $cost = $service->calculateShippingCost($testServiceId, 'TR', 'İzmir', 5.0, 600.00);
    return $cost === 0.00 ? true : 'Ücretsiz kargo uygulanmadı: ' . $cost;
}, $passed, $failed);

testCase('83. Negatif desi veya sıfır durumunda calculateDesi default 1.0 dönmeli', function() use ($service) {
    $desi = $service->calculateDesi(0, -1, 3);
    return $desi === 1.0 ? true : 'Hatalı desi: ' . $desi;
}, $passed, $failed);

testCase('84. Kargo firmalarında Türkçe özel karakter (şığüşöç İĞÜŞÖÇ) uyumluluk doğrulaması', function() use ($repo, $pdo) {
    $companyName = 'Yurtiçi Kargo Şubesi Gürültülü';
    $id = $repo->createCompany([
        'name' => $companyName,
        'code' => 'yurtici_tr_' . time(),
        'tax_number' => '123'
    ]);
    $row = $repo->getCompany($id);
    $pdo->execute("DELETE FROM shipping_companies WHERE id = {$id}");
    return $row['name'] === $companyName ? true : 'Türkçe karakter bozuldu: ' . $row['name'];
}, $passed, $failed);

testCase('85. Kargo sigorta bedeli (shipping_insurances) insert doğrulaması', function() use ($pdo, $testPackageId) {
    if (!$testPackageId) return 'Gönderi paketi oluşturulamadı';
    $pdo->execute("INSERT INTO shipping_insurances (package_id, insurance_value, insurance_fee) VALUES ({$testPackageId}, 1000.00, 20.00)");
    $row = $pdo->query("SELECT insurance_fee FROM shipping_insurances WHERE package_id = {$testPackageId}")[0] ?? null;
    return (float)($row['insurance_fee'] ?? 0) === 20.00 ? true : 'Sigorta kaydı başarısız';
}, $passed, $failed);

testCase('86. Sevkiyat evrakları (shipping_documents) veri yolu kaydı', function() use ($pdo, $testPackageId) {
    if (!$testPackageId) return 'Gönderi paketi oluşturulamadı';
    $pdo->execute("INSERT INTO shipping_documents (package_id, name, file_path) VALUES ({$testPackageId}, 'İrsaliye', 'public/docs/irsaliye.pdf')");
    $row = $pdo->query("SELECT file_path FROM shipping_documents WHERE package_id = {$testPackageId}")[0] ?? null;
    return $row['file_path'] === 'public/docs/irsaliye.pdf' ? true : 'Belge kaydı başarısız';
}, $passed, $failed);

testCase('87. Sevkiyat bildirimleri (shipping_notifications) tipi ve mesaj doğrulaması', function() use ($pdo, $testPackageId) {
    if (!$testPackageId) return 'Gönderi paketi oluşturulamadı';
    $pdo->execute("INSERT INTO shipping_notifications (package_id, type, message) VALUES ({$testPackageId}, 'email', 'Kargonuz yola çıktı')");
    $row = $pdo->query("SELECT type FROM shipping_notifications WHERE package_id = {$testPackageId}")[0] ?? null;
    return $row['type'] === 'email' ? true : 'Bildirim kaydı başarısız';
}, $passed, $failed);

testCase('88. Kurye pickup talebi (shipping_pickups) durumu', function() use ($pdo, $testCompanyId) {
    if (!$testCompanyId) return 'Kargo firması oluşturulamadı';
    $pdo->execute("INSERT INTO shipping_pickups (company_id, pickup_date, status) VALUES ({$testCompanyId}, NOW(), 'requested')");
    $row = $pdo->query("SELECT status FROM shipping_pickups WHERE company_id = {$testCompanyId}")[0] ?? null;
    return $row['status'] === 'requested' ? true : 'Pickup kaydı başarısız';
}, $passed, $failed);

testCase('89. Teslim Edilen gönderilerde Finans Gider entegrasyon doğrulaması', function() use ($pdo) {
    $row = $pdo->query("SELECT id FROM expenses WHERE description LIKE 'Kargo Teslimat%Maliyeti%' LIMIT 1");
    return count($row) > 0 ? true : 'Finansal entegrasyon gideri kaydedilmedi';
}, $passed, $failed);

testCase('90. İade Tamamlandığında Finans Gelir Düzeltme entegrasyon doğrulaması', function() use ($pdo) {
    $row = $pdo->query("SELECT id FROM revenues WHERE description LIKE 'İade Gelir Düzeltmesi%' LIMIT 1");
    return count($row) > 0 ? true : 'Finansal entegrasyon gelir düzeltmesi kaydedilmedi';
}, $passed, $failed);

testCase('91. RBAC izinlerinin rol ilişkileri varlık kontrolü', function() use ($pdo) {
    $rows = $pdo->query("SELECT COUNT(*) as cnt FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE p.name LIKE 'manage_shipping%'");
    return $rows[0]['cnt'] > 0 ? true : 'Rol yetkileri atanmamış';
}, $passed, $failed);

testCase('92. shipping_statistics tablosu metrik ekleme', function() use ($pdo) {
    $pdo->execute("INSERT IGNORE INTO shipping_statistics (metric_name, metric_value, recorded_date) VALUES ('delivered_ratio', 98.5, NOW())");
    $row = $pdo->query("SELECT metric_value FROM shipping_statistics WHERE metric_name = 'delivered_ratio' LIMIT 1")[0] ?? null;
    return (float)($row['metric_value'] ?? 0) === 98.5 ? true : 'İstatistik kaydı başarısız';
}, $passed, $failed);

testCase('93. shipping_reports tablosu dosya kaydı', function() use ($pdo) {
    $pdo->execute("INSERT INTO shipping_reports (name, file_path) VALUES ('Yıllık Lojistik Raporu', 'public/reports/logistics_2026.pdf')");
    $row = $pdo->query("SELECT file_path FROM shipping_reports WHERE name = 'Yıllık Lojistik Raporu' LIMIT 1")[0] ?? null;
    return $row['file_path'] === 'public/reports/logistics_2026.pdf' ? true : 'Rapor kaydı başarısız';
}, $passed, $failed);

testCase('94. Kargo firmalarında UPS, DHL, FedEx dinamik kod doğrulaması', function() use ($repo, $pdo) {
    $id = $repo->createCompany(['name' => 'FedEx International', 'code' => 'fedex_global', 'tax_number' => '12']);
    $row = $repo->getCompanyByCode('fedex_global');
    $pdo->execute("DELETE FROM shipping_companies WHERE id = {$id}");
    return $row['name'] === 'FedEx International' ? true : 'Firma kodu doğrulaması başarısız';
}, $passed, $failed);

testCase('95. shipping_claims tazmin kaydı durum kontrolü', function() use ($pdo, $testPackageId) {
    if (!$testPackageId) return 'Gönderi paketi oluşturulamadı';
    $pdo->execute("INSERT INTO shipping_claims (package_id, claim_type, amount, status) VALUES ({$testPackageId}, 'damaged', 250.00, 'pending')");
    $row = $pdo->query("SELECT amount FROM shipping_claims WHERE package_id = {$testPackageId} LIMIT 1")[0] ?? null;
    return (float)($row['amount'] ?? 0) === 250.00 ? true : 'Tazmin kaydı başarısız';
}, $passed, $failed);


// Temizlik
if ($testCompanyId) {
    if ($testPackageId) {
        $pdo->execute("DELETE FROM shipping_insurances WHERE package_id = {$testPackageId}");
        $pdo->execute("DELETE FROM shipping_documents WHERE package_id = {$testPackageId}");
        $pdo->execute("DELETE FROM shipping_notifications WHERE package_id = {$testPackageId}");
        $pdo->execute("DELETE FROM shipping_claims WHERE package_id = {$testPackageId}");
        $pdo->execute("DELETE FROM shipping_tracking_events WHERE tracking_id IN (SELECT id FROM shipping_tracking WHERE package_id = {$testPackageId})");
        $pdo->execute("DELETE FROM shipping_tracking WHERE package_id = {$testPackageId}");
        $pdo->execute("DELETE FROM shipping_package_items WHERE package_id = {$testPackageId}");
    }
    $pdo->execute("DELETE FROM shipping_pickups WHERE company_id = {$testCompanyId}");
    $pdo->execute("DELETE FROM shipping_integrations WHERE company_id = {$testCompanyId}");
    $pdo->execute("DELETE FROM shipping_packages WHERE service_id IN (SELECT id FROM shipping_services WHERE company_id = {$testCompanyId})");
    $pdo->execute("DELETE FROM shipping_services WHERE company_id = {$testCompanyId}");
    $pdo->execute("DELETE FROM shipping_companies WHERE id = {$testCompanyId}");
}
if ($testZoneId) {
    $pdo->execute("DELETE FROM shipping_zone_prices WHERE zone_id = {$testZoneId}");
    $pdo->execute("DELETE FROM shipping_zones WHERE id = {$testZoneId}");
}
$pdo->execute("DELETE FROM shipping_rules WHERE name = '500 TL Üzeri Kargo Bedava'");
$pdo->execute("DELETE FROM expenses WHERE description LIKE 'Kargo Teslimat Maliyeti%'");
$pdo->execute("DELETE FROM revenues WHERE description LIKE 'İade Gelir Düzeltmesi%'");
$pdo->execute("DELETE FROM shipping_statistics WHERE metric_name = 'delivered_ratio'");
$pdo->execute("DELETE FROM shipping_reports WHERE name = 'Yıllık Lojistik Raporu'");
$pdo->execute("DELETE FROM shipping_returns WHERE order_id = 9991");
if ($testPackageId) {
    $labelFile = ROOT_DIR . "/public/uploads/labels/label_{$testPackageId}_*.pdf";
    foreach (glob($labelFile) as $file) {
        @unlink($file);
    }
}

echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
if ($failed === 0) {
    echo "  ✅  TÜM {$total}/{$total} TEST BAŞARILI!\n";
} else {
    echo "  ⚠️   SONUÇ: {$passed}/{$total} BAŞARILI, {$failed} BAŞARISIZ\n";
}
echo str_repeat('═', 62) . "\n";
echo "  🔗  Lojistik Paneli : http://localhost/SaintMonarc/admin/shipping\n";
echo "  🔗  Firmalar        : http://localhost/SaintMonarc/admin/shipping/companies\n";
echo "  🔗  Gönderiler      : http://localhost/SaintMonarc/admin/shipping/shipments\n";
echo "  🔗  İadeler         : http://localhost/SaintMonarc/admin/shipping/returns\n";
echo "  🔗  Raporlar        : http://localhost/SaintMonarc/admin/shipping/reports\n";
echo "  🔗  REST API        : http://localhost/SaintMonarc/api/shipping\n";
echo str_repeat('═', 62) . "\n\n";
