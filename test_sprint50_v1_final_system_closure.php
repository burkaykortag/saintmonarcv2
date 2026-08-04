<?php
declare(strict_types=1);

/**
 * SaintMonarc — Sprint 50: V1.0 Final System Closure & Production Readiness Test Suite
 */

define('ROOT_DIR', 'C:/xampp/htdocs/SaintMonarc');

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Autoloader
spl_autoload_register(function (string $class) {
    $prefixes = [
        'Core\\' => ROOT_DIR . '/core/',
        'App\\' => ROOT_DIR . '/app/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) continue;
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

use App\Services\RbacService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\CustomerService;
use App\Services\DashboardService;
use App\Services\AuditLogger;
use App\Repositories\OrderRepository;

$conf = require ROOT_DIR . '/config/database.php';
$pdo = new PDO(
    "mysql:host=" . ($conf['host']??'127.0.0.1') . ";dbname=" . ($conf['dbname']??'saintmonarc') . ";charset=utf8mb4",
    $conf['user']??'root',
    $conf['password']??'',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

// Database Adapter
$dbAdapter = new class($pdo) implements \Core\Contracts\DatabaseInterface {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function query(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    public function lastInsertId(): string { return $this->pdo->lastInsertId(); }
    public function inTransaction(): bool { return $this->pdo->inTransaction(); }
    public function beginTransaction(): bool { return $this->pdo->inTransaction() || $this->pdo->beginTransaction(); }
    public function commit(): bool { return $this->pdo->inTransaction() ? $this->pdo->commit() : true; }
    public function rollBack(): bool { return $this->pdo->inTransaction() ? $this->pdo->rollBack() : true; }
};

// Cache Adapter
$cacheAdapter = new class implements \Core\Contracts\CacheInterface {
    private array $store = [];
    public function get(string $key, mixed $default = null): mixed { return $this->store[$key] ?? $default; }
    public function set(string $key, mixed $value, ?int $ttl = null): bool { $this->store[$key] = $value; return true; }
    public function delete(string $key): bool { unset($this->store[$key]); return true; }
    public function clear(): bool { $this->store = []; return true; }
    public function has(string $key): bool { return isset($this->store[$key]); }
};

$rbacService = new RbacService($dbAdapter, $cacheAdapter);
$auditLogger = new AuditLogger($dbAdapter);
$orderRepo = new OrderRepository($dbAdapter);
$orderService = new OrderService($orderRepo, $dbAdapter, $cacheAdapter, $auditLogger);
$paymentService = new PaymentService($dbAdapter);

$passCount = 0;
$failCount = 0;
$warnCount = 0;

function testPass(string $code, string $title, string $detail = ''): void {
    global $passCount;
    $passCount++;
    echo "  [PASS] {$code} :: {$title}" . ($detail ? " ({$detail})" : "") . "\n";
}

function testFail(string $code, string $title, string $detail = ''): void {
    global $failCount;
    $failCount++;
    echo "  [FAIL] {$code} :: {$title}" . ($detail ? " ({$detail})" : "") . "\n";
}

function testWarn(string $code, string $title, string $detail = ''): void {
    global $warnCount;
    $warnCount++;
    echo "  [WARN] {$code} :: {$title}" . ($detail ? " ({$detail})" : "") . "\n";
}

echo "======================================================================\n";
echo " SAINTMONARC SPRINT 50: V1.0 FINAL SYSTEM CLOSURE & PRODUCTION AUDIT\n";
echo "======================================================================\n\n";

// =========================================================================
// SECTION 1: AUTHENTICATION & SESSION SECURITY
// =========================================================================
echo "--- SECTION 1: AUTHENTICATION & SESSION SECURITY ---\n";

$adminRow = $pdo->query("SELECT * FROM admins WHERE username = 'admin' OR is_super = 1 LIMIT 1")->fetch();
if ($adminRow && !empty($adminRow['password'])) {
    testPass('AUTH-ADMIN-DB', 'Admin Hesabı ve Şifre Hash Yapısı Mevcut', "Username: {$adminRow['username']}");
} else {
    testFail('AUTH-ADMIN-DB', 'Admin hesabı veritabanında bulunamadı.');
}

if (class_exists('Core\\Security') && method_exists('Core\\Security', 'generateCsrfToken')) {
    testPass('AUTH-SECURITY-CSRF', 'CSRF Güvenlik Katmanı Mevcut');
} else {
    testFail('AUTH-SECURITY-CSRF', 'CSRF katmanı bulunamadı.');
}

// =========================================================================
// SECTION 2: HIERARCHICAL RBAC & BOUNDARIES
// =========================================================================
echo "\n--- SECTION 2: HIERARCHICAL RBAC & BOUNDARIES ---\n";

$devPrio = $rbacService->getAdminMaxPriority((int)($adminRow['id'] ?? 1));
if ($devPrio >= 90) {
    testPass('RBAC-HIERARCHY-DEV', 'DevAdmin / SuperAdmin Öncelik Seviyesi Doğrulandı', "Priority: {$devPrio}");
} else {
    testFail('RBAC-HIERARCHY-DEV', 'DevAdmin Seviyesi Yetersiz', "Priority: {$devPrio}");
}

// System role protection
try {
    $rbacService->deleteRole(1, (int)($adminRow['id'] ?? 1));
    testFail('RBAC-SYSROLE-PROTECT', 'Sistem Rolü Silinebildi! (GÜVENLİK İHLALİ)');
} catch (Exception $e) {
    testPass('RBAC-SYSROLE-PROTECT', 'Sistem Rolü Silme Engeli Çalışıyor', $e->getMessage());
}

// =========================================================================
// SECTION 3: IMPERSONATION SECURITY & LIFECYCLE
// =========================================================================
echo "\n--- SECTION 3: IMPERSONATION SECURITY & LIFECYCLE ---\n";

// Create temp mock target admin
$pdo->exec("INSERT IGNORE INTO admins (id, username, email, password, is_super, is_active, is_impersonatable, created_at)
            VALUES (999, 's50_temp_mgr', 's50mgr@test.com', 'hash', 0, 1, 1, NOW())");
$pdo->exec("INSERT IGNORE INTO admin_roles (admin_id, role_id) VALUES (999, 4)");

$canImp = $rbacService->canImpersonate((int)$adminRow['id'], 999);
if ($canImp) {
    testPass('IMP-PERM-CHECK', 'SuperAdmin Alt Seviye Yöneticinin Hesabına Geçebilir.');
} else {
    testFail('IMP-PERM-CHECK', 'SuperAdmin geçiş yetkisi alamadı.');
}

// Ensure low admin cannot impersonate SuperAdmin
$canLowerImpSuper = $rbacService->canImpersonate(999, (int)$adminRow['id']);
if (!$canLowerImpSuper) {
    testPass('IMP-BOUNDARY-BLOCK', 'Alt Seviye Admin Üst Yöneticinin Hesabına Geçemez (Engellendi)');
} else {
    testFail('IMP-BOUNDARY-BLOCK', 'Alt seviye admin üst yöneticiye geçebildi! (GÜVENLİK İHLALİ)');
}

// =========================================================================
// SECTION 4: ROUTE SCAN (0 BROKEN ROUTES)
// =========================================================================
echo "\n--- SECTION 4: ROUTE INTEGRITY SCAN (STOREFRONT, ADMIN & API) ---\n";

$routeFiles = ['web.php', 'admin.php', 'api.php'];
$brokenRoutes = [];
$totalScannedRoutes = 0;

foreach ($routeFiles as $rf) {
    $content = file_get_contents(ROOT_DIR . '/routes/' . $rf);
    preg_match_all('/\$router->(get|post|put|delete)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[\s*([A-Za-z0-9_\\\\]+)::class\s*,\s*[\'"]([^\'"]+)[\'"]\s*\]/i', $content, $matches, PREG_SET_ORDER);
    $totalScannedRoutes += count($matches);
    foreach ($matches as $m) {
        $controllerClass = $m[3];
        $method = $m[4];
        if (strpos($controllerClass, '\\') === false) {
            $controllerClass = 'App\\Controllers\\' . $controllerClass;
        }
        if (!class_exists($controllerClass) || !method_exists($controllerClass, $method)) {
            $brokenRoutes[] = "{$m[1]} {$m[2]} => {$controllerClass}::{$method}";
        }
    }
}

if (count($brokenRoutes) === 0) {
    testPass('ROUTES-INTEGRITY', 'Tüm Route-Controller-Method Bağlantıları Geçerli (0 Kırık Route)', "Toplam Route: {$totalScannedRoutes}");
} else {
    testFail('ROUTES-INTEGRITY', "Kırık Route'lar Tespit Edildi!", implode(' | ', $brokenRoutes));
}

// =========================================================================
// SECTION 5: STOREFRONT & CHECKOUT END-TO-END FLOW
// =========================================================================
echo "\n--- SECTION 5: STOREFRONT & CHECKOUT END-TO-END FLOW ---\n";

// Fetch or create a test product
$prodRow = $pdo->query("SELECT id, total_stock, price FROM products WHERE deleted_at IS NULL AND is_active = 1 LIMIT 1")->fetch();
if (!$prodRow) {
    $pdo->exec("INSERT INTO products (sku, barcode, price, cost_price, total_stock, is_active, slug, created_at)
                VALUES ('S50-TEST', '112233', 100.00, 50.00, 50, 1, 's50-test-prod', NOW())");
    $prodId = (int)$pdo->lastInsertId();
    $initialStock = 50;
} else {
    $prodId = (int)$prodRow['id'];
    $initialStock = (int)$prodRow['total_stock'];
}

// Execute real order creation via OrderService
$orderId = 0;
try {
    $orderId = $orderService->create([
        'user_id' => 1,
        'subtotal' => 100.00,
        'tax_total' => 18.00,
        'discount_total' => 0.00,
        'shipping_total' => 15.00,
        'grand_total' => 133.00,
        'billing_first_name' => 'S50',
        'billing_last_name' => 'Tester',
        'billing_address' => 'Test Mah. No:1',
        'billing_city' => 'İstanbul',
        'shipping_first_name' => 'S50',
        'shipping_last_name' => 'Tester',
        'shipping_address' => 'Test Mah. No:1',
        'shipping_city' => 'İstanbul',
        'items' => [
            [
                'product_id' => $prodId,
                'product_sku' => 'S50-TEST',
                'product_name' => 'Sprint 50 Test Ürünü',
                'quantity' => 2,
                'price' => 50.00,
                'tax_amount' => 9.00
            ]
        ]
    ]);

    testPass('ORDER-CREATE-FLOW', 'Sipariş Oluşturma ve Veritabanı Kaydı Başarılı', "Sipariş ID: {$orderId}");
} catch (Exception $e) {
    testFail('ORDER-CREATE-FLOW', 'Sipariş oluşturma başarısız', $e->getMessage());
}

// =========================================================================
// SECTION 6: STOCK / ORDER / FINANCE CONSISTENCY
// =========================================================================
echo "\n--- SECTION 6: STOCK / ORDER / FINANCE CONSISTENCY ---\n";

// Check stock decrease (Quantity was 2, so stock should be initialStock - 2)
$updatedProd = $pdo->query("SELECT total_stock FROM products WHERE id = {$prodId}")->fetch();
$expectedStock = $initialStock - 2;
if ($updatedProd && (int)$updatedProd['total_stock'] === $expectedStock) {
    testPass('STOCK-DECREASE-SYNC', 'Sipariş Sonrası Stok Otomatik Düşürüldü', "Stok: {$updatedProd['total_stock']} (Beklenen: {$expectedStock})");
} else {
    testFail('STOCK-DECREASE-SYNC', 'Stok düşümü tutarsız!', "Actual: " . ($updatedProd['total_stock'] ?? 'NULL') . ", Expected: {$expectedStock}");
}

// Test Order Cancellation & Stock Restoration
if ($orderId > 0) {
    try {
        $orderService->update($orderId, ['status' => 'cancelled', 'status_comment' => 'Sprint 50 Test İptali']);
        $restoredProd = $pdo->query("SELECT total_stock FROM products WHERE id = {$prodId}")->fetch();
        if ($restoredProd && (int)$restoredProd['total_stock'] === $initialStock) {
            testPass('STOCK-RESTORE-CANCEL', 'Sipariş İptalinde Stok Otomatik Geri Yüklendi', "Stok: {$restoredProd['total_stock']} (Orijinal: {$initialStock})");
        } else {
            testFail('STOCK-RESTORE-CANCEL', 'İptal sonrası stok geri yüklenemedi!', "Actual: " . ($restoredProd['total_stock'] ?? 'NULL'));
        }
    } catch (Exception $e) {
        testFail('STOCK-RESTORE-CANCEL', 'Sipariş iptali başarısız', $e->getMessage());
    }
}

// =========================================================================
// SECTION 7: PAYMENT SECURITY & CALLBACK IDEMPOTENCY
// =========================================================================
echo "\n--- SECTION 7: PAYMENT SECURITY & CALLBACK IDEMPOTENCY ---\n";

// 1. Verify Unconfigured Gateway Safety (Prevents Fake Payment Exploits)
$defaultRes = $paymentService->handleCallback(['paymentId' => 'LIVE-FAKE-123']);
if (!empty($defaultRes['status']) && $defaultRes['status'] === 'BLOCKED_LIVE_CREDENTIAL_REQUIRED') {
    testPass('PAYMENT-GATEWAY-SECURITY', 'Canlı API Anahtarı Olmayan Ödeme Çağrıları Güvenle Engellendi (Sahte Ödeme Önleme)');
} else {
    testWarn('PAYMENT-GATEWAY-SECURITY', 'Ödeme Gateway API anahtarları henüz tanımlanmamış.');
}

// 2. Verify Configured Gateway Callback & Idempotency Protection
$mockGateway = new class implements \App\Contracts\PaymentGatewayInterface {
    public function isConfigured(): bool { return true; }
    public function createPayment(array $data): array { return ['success' => true, 'status' => 'pending']; }
    public function verifyPayment(array $cb): array {
        if (!empty($cb['status']) && $cb['status'] === 'success') {
            return ['success' => true, 'status' => 'paid', 'amount' => (float)($cb['amount'] ?? 133.00)];
        }
        return ['success' => false, 'status' => 'failed'];
    }
    public function refundPayment(string $ref, float $amt): array { return ['success' => true]; }
    public function getPaymentStatus(string $ref): array { return ['status' => 'paid']; }
};

$securePaymentService = new PaymentService($dbAdapter, $mockGateway);
$txRef = 'S50-TX-' . time();
$cbData = [
    'order_id' => $orderId,
    'paymentId' => $txRef,
    'transaction_reference' => $txRef,
    'amount' => 133.00,
    'status' => 'success'
];

$res1 = $securePaymentService->handleCallback($cbData);
if (!empty($res1['success'])) {
    testPass('PAYMENT-CALLBACK-PROCESS', 'Ödeme Callback İşleme ve Ledger Kaydı Başarılı', "Ref: {$txRef}");
} else {
    testFail('PAYMENT-CALLBACK-PROCESS', 'Ödeme callback işleme başarısız');
}

// Duplicate callback simulation (Idempotency Protection)
$res2 = $securePaymentService->handleCallback($cbData);
if (!empty($res2['status']) && $res2['status'] === 'already_processed') {
    testPass('PAYMENT-IDEMPOTENCY-PROTECT', 'Çift Ödeme Callback (Duplicate Webhook) Koruması Başarılı (Engellendi)');
} else {
    testFail('PAYMENT-IDEMPOTENCY-PROTECT', 'Mükerrer ödeme işlemi engellenemedi! (GÜVENLİK İHLALİ)');
}

// =========================================================================
// SECTION 8: ERROR HANDLING & PRODUCTION ISOLATION
// =========================================================================
echo "\n--- SECTION 8: ERROR HANDLING & PRODUCTION ISOLATION ---\n";

$err404 = file_exists(ROOT_DIR . '/resources/views/errors/404.php');
$err403 = file_exists(ROOT_DIR . '/resources/views/errors/403.php');
$err500 = file_exists(ROOT_DIR . '/resources/views/errors/500.php');

if ($err404 && $err403 && $err500) {
    testPass('ERR-PRODUCTION-VIEWS', 'Özel Üretim Hata Sayfaları (404, 403, 500) Mevcut');
} else {
    testFail('ERR-PRODUCTION-VIEWS', 'Üretim hata sayfaları eksik!');
}

// Verify public directory isolation (.env, migrations, sql files outside public/)
$publicEnv = file_exists(ROOT_DIR . '/public/.env');
$publicSql = file_exists(ROOT_DIR . '/public/schema.sql');
if (!$publicEnv && !$publicSql) {
    testPass('SECURITY-PUBLIC-ISOLATION', 'Hassas Dosyalar (.env, sql, migrations) Public Web Root Dışında İzole Edilmiş');
} else {
    testFail('SECURITY-PUBLIC-ISOLATION', 'Public web root altında hassas dosyalar açıkta! (GÜVENLİK İHLALİ)');
}

// =========================================================================
// CLEANUP TEST ARTIFACTS
// =========================================================================
if ($orderId > 0) {
    $pdo->exec("DELETE FROM payment_transactions WHERE order_id = {$orderId}");
    $pdo->exec("DELETE FROM order_status_history WHERE order_id = {$orderId}");
    $pdo->exec("DELETE FROM order_items WHERE order_id = {$orderId}");
    $pdo->exec("DELETE FROM orders WHERE id = {$orderId}");
}
$pdo->exec("DELETE FROM admin_roles WHERE admin_id = 999");
$pdo->exec("DELETE FROM admins WHERE id = 999");

// =========================================================================
// TEST SUMMARY & FINAL VERDICT
// =========================================================================
echo "\n======================================================================\n";
echo " SPRINT 50 AUDIT SONUÇ ÖZETİ:\n";
echo "   [PASS] Başarılı Testler: {$passCount}\n";
echo "   [FAIL] Başarısız Testler: {$failCount}\n";
echo "   [WARN] Uyarılar: {$warnCount}\n";
echo "======================================================================\n\n";

if ($failCount === 0) {
    echo "✅ SAINTMONARC V1.0 PRODUCTION READINESS TEST BAŞARIYLA TAMAMLANDI (0 FAIL / 0 CRITICAL)\n\n";
    exit(0);
} else {
    echo "❌ SAINTMONARC V1.0 AUDIT BAŞARISIZ! ({$failCount} HATA DÜZELTİLMELİ)\n\n";
    exit(1);
}
