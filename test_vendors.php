<?php
declare(strict_types=1);

/**
 * Sprint 23 - Enterprise Marketplace & Vendor Management System CLI Tests
 */

define('ROOT_DIR', __DIR__);

// Autoload
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class) {
        $prefixMap = ['Core\\' => 'core/', 'App\\' => 'app/'];
        foreach ($prefixMap as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) continue;
            $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) { require $file; return; }
        }
    });
}

use Core\Config\EnvParser;
use Core\Application;
use App\Repositories\VendorRepository;
use App\Services\VendorService;

EnvParser::parse(ROOT_DIR . '/.env');

$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$db = $container->get(\Core\Contracts\DatabaseInterface::class);
$repository = new VendorRepository($db);

// Simple Cache Mock implementation for tests
$cacheMock = new class implements \Core\Contracts\CacheInterface {
    private array $data = [];
    public function get(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
    public function set(string $key, mixed $value, int $ttl = null): bool { $this->data[$key] = $value; return true; }
    public function has(string $key): bool { return isset($this->data[$key]); }
    public function delete(string $key): bool { unset($this->data[$key]); return true; }
    public function clear(): bool { $this->data = []; return true; }
};

$service = new VendorService($repository, $cacheMock);

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
echo " SPRINT 23 - ENTERPRISE VENDOR MANAGEMENT SYSTEM TESTS\n";
echo str_repeat('=', 60) . "\n\n";

// --- SECTION 1: DATABASE SCHEMA ---
echo "📂 [BÖLÜM 1] Veritabanı Tablo Varlık Kontrolleri\n";

$pdo = $db;

$tables = [
    'vendors', 'vendor_users', 'vendor_addresses', 'vendor_contacts',
    'vendor_documents', 'vendor_bank_accounts', 'vendor_products', 'vendor_orders',
    'vendor_commissions', 'vendor_payments', 'vendor_wallet', 'vendor_wallet_transactions',
    'vendor_shipping_settings', 'vendor_returns', 'vendor_statistics', 'vendor_activity_logs',
    'vendor_notifications', 'vendor_contracts', 'vendor_api_keys', 'vendor_settings',
    'vendor_reviews', 'vendor_messages', 'vendor_files', 'vendor_logs'
];

foreach ($tables as $t) {
    test("Tablo varlığı: {$t}", function() use ($pdo, $t) {
        $res = $pdo->query("SHOW TABLES LIKE '{$t}'");
        return count($res) > 0 ? true : "Tablo bulunamadı: {$t}";
    });
}

// --- SECTION 2: CRUD & BUSINESS LOGIC ---
echo "\n🎯 [BÖLÜM 2] Satıcı Deposu & CRUD İş Mantığı Testleri\n";

$testVendorId = null;
$testBankAccountId = null;

test("Yeni Satıcı Ekleme ve Slug üretimi", function() use ($service, &$testVendorId) {
    $vendorId = $service->createVendor([
        'name' => 'Test Satıcı Anonim Şirketi',
        'email' => 'satici_' . time() . '@test.com',
        'phone' => '05554443322',
        'commission_type' => 'percentage',
        'commission_rate' => 12.50
    ]);
    $testVendorId = $vendorId;
    return $vendorId > 0 ? true : 'Satıcı eklenemedi';
});

test("Satıcıyı ID ile Çekme ve Cache doğrulaması", function() use ($service, $cacheMock, &$testVendorId) {
    $vendor = $service->getVendor($testVendorId);
    if (!$vendor) return 'Satıcı bulunamadı';
    return $cacheMock->has("vendor_{$testVendorId}") ? true : 'Cache kaydedilmedi';
});

test("Satıcı Bilgilerini Güncelleme ve Cache Temizliği", function() use ($service, $cacheMock, &$testVendorId) {
    $service->updateVendor($testVendorId, ['phone' => '02128887766']);
    return !$cacheMock->has("vendor_{$testVendorId}") ? true : 'Önbellek temizlenemedi';
});

test("Satıcı Banka Hesabı Ekleme", function() use ($repository, &$testVendorId, &$testBankAccountId) {
    $bankId = $repository->createBankAccount([
        'vendor_id' => $testVendorId,
        'bank_name' => 'Garanti BBVA',
        'account_holder' => 'Test Satıcı A.Ş.',
        'iban' => 'TR900000111222333444555666'
    ]);
    $testBankAccountId = $bankId;
    return $bankId > 0 ? true : 'Banka hesabı eklenemedi';
});

test("Kategori/Marka Bazlı Yüzdelik Komisyon Hesaplaması", function() use ($service, &$testVendorId) {
    $price = 1000.00;
    // Commision rate is 12.5%, expected is 125.00
    $commission = $service->calculateCommission($testVendorId, $price);
    return abs($commission - 125.00) < 0.001 ? true : 'Komisyon hatalı: ' . $commission;
});

test("Cüzdana Satış Geliri Aktarımı (Deposit)", function() use ($service, &$testVendorId) {
    $service->deposit($testVendorId, 875.00, 'order', 1, 'Sipariş No: #1');
    $wallet = $service->getWallet($testVendorId);
    return (float)$wallet['balance'] === 875.00 ? true : 'Bakiye hatalı: ' . $wallet['balance'];
});

test("Cüzdandan Para Çekme (Withdraw)", function() use ($service, &$testVendorId) {
    $service->withdraw($testVendorId, 200.00, 'payment', 1, 'Hakediş çekim');
    $wallet = $service->getWallet($testVendorId);
    return (float)$wallet['balance'] === 675.00 ? true : 'Bakiye hatalı: ' . $wallet['balance'];
});

test("Cüzdan Yetersiz Bakiye Hata Fırlatma Kontrolü", function() use ($service, &$testVendorId) {
    try {
        $service->withdraw($testVendorId, 1000.00, 'payment', 1, 'Hatalı çekim');
        return 'Yetersiz bakiye kontrolü başarısız';
    } catch (Exception $e) {
        return true;
    }
});

test("Hak Ediş Ödeme Talebi Oluşturma", function() use ($service, &$testVendorId, &$testBankAccountId) {
    $paymentId = $service->requestPayout($testVendorId, $testBankAccountId, 300.00);
    $wallet = $service->getWallet($testVendorId);
    return ($paymentId > 0 && (float)$wallet['balance'] === 375.00) ? true : 'Ödeme talebi başarısız';
});

test("Satıcı İstatistik Kaydı Çekme", function() use ($service, &$testVendorId) {
    $stats = $service->getStatistics($testVendorId);
    return isset($stats['total_sales']) ? true : 'İstatistik bilgisi eksik';
});

// --- SECTION 3: REST API & CONTROLLER ROUTING ---
echo "\n🌐 [BÖLÜM 3] REST API & Controller Rota Erişim Testleri\n";

test("API /api/vendors endpoint erişim metodu", function() {
    return method_exists(\App\Controllers\VendorController::class, 'apiList') ? true : 'apiList metodu yok';
});

test("API /api/vendors/{id} endpoint erişim metodu", function() {
    return method_exists(\App\Controllers\VendorController::class, 'apiShow') ? true : 'apiShow metodu yok';
});

test("API /api/vendors/products endpoint erişim metodu", function() {
    return method_exists(\App\Controllers\VendorController::class, 'apiProducts') ? true : 'apiProducts metodu yok';
});

test("API /api/vendors/orders endpoint erişim metodu", function() {
    return method_exists(\App\Controllers\VendorController::class, 'apiOrders') ? true : 'apiOrders metodu yok';
});

test("API /api/vendors/statistics endpoint erişim metodu", function() {
    return method_exists(\App\Controllers\VendorController::class, 'apiStatistics') ? true : 'apiStatistics metodu yok';
});

// --- CLEANUP ---
test("Test verilerini force delete ile temizleme", function() use ($pdo, &$testVendorId) {
    if (!$testVendorId) return true;
    $pdo->execute("DELETE FROM vendor_bank_accounts WHERE vendor_id = :vid", [':vid' => $testVendorId]);
    $pdo->execute("DELETE FROM vendor_payments WHERE vendor_id = :vid", [':vid' => $testVendorId]);
    $pdo->execute("DELETE FROM vendor_wallet_transactions WHERE vendor_id = :vid", [':vid' => $testVendorId]);
    $pdo->execute("DELETE FROM vendor_wallet WHERE vendor_id = :vid", [':vid' => $testVendorId]);
    $pdo->execute("DELETE FROM vendor_statistics WHERE vendor_id = :vid", [':vid' => $testVendorId]);
    $pdo->execute("DELETE FROM vendors WHERE id = :vid", [':vid' => $testVendorId]);
    return true;
});

echo "\n" . str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo " SPRINT 23 SONUÇ: {$passed}/{$total} test BAŞARILI\n";
echo str_repeat('=', 60) . "\n\n";

if ($failed === 0) {
    echo "✅ TÜM SPRINT 23 PAZARYERİ TESTLERİ BAŞARIYLA TAMAMLANDI!\n\n";
} else {
    echo "⚠️ Bazı testler başarısız. Lütfen hataları inceleyin.\n\n";
}
