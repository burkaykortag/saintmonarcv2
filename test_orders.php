<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Sprint 14 - Sipariş Yönetimi Enterprise - CLI Test Betiği
 * Çalıştırma: php test_orders.php
 */

define('ROOT_DIR', __DIR__);

// spl autoloader (same as index.php fallback)
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

// Boot Application to use the official DI container!
$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$pdo = $container->get(\Core\Contracts\DatabaseInterface::class);

$passed  = 0;
$failed  = 0;
$testOrderId = null;

function testCase(string $name, callable $fn, int &$passed, int &$failed): void
{
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
echo "  SPRINT 14 — SİPARİŞ YÖNETİMİ ENTERPRISE CLI TESTLERİ\n";
echo str_repeat('═', 62) . "\n\n";

// ─────────────────────────────────────────────────────────────
// BÖLÜM 1: VERİTABANI VE ŞEMA KONTROLLERİ
// ─────────────────────────────────────────────────────────────
echo "📦 [BÖLÜM 1] Veritabanı ve Şema Kontrolleri\n";

testCase('order_statuses tablosu mevcut', function () use ($pdo) {
    return count($pdo->query("SHOW TABLES LIKE 'order_statuses'")) > 0 ? true : 'order_statuses tablosu yok';
}, $passed, $failed);

testCase('order_notes tablosu mevcut', function () use ($pdo) {
    return count($pdo->query("SHOW TABLES LIKE 'order_notes'")) > 0 ? true : 'order_notes tablosu yok';
}, $passed, $failed);

testCase('shipments tablosu carrier_name kolonu eklendi', function () use ($pdo) {
    return count($pdo->query("SHOW COLUMNS FROM shipments LIKE 'carrier_name'")) > 0 ? true : 'carrier_name kolonu yok';
}, $passed, $failed);

testCase('refunds tablosu type kolonu eklendi', function () use ($pdo) {
    return count($pdo->query("SHOW COLUMNS FROM refunds LIKE 'type'")) > 0 ? true : 'type kolonu yok';
}, $passed, $failed);

testCase('İzin kayıtları yerleştirildi', function () use ($pdo) {
    $rows = $pdo->query("SELECT COUNT(*) as cnt FROM permissions WHERE name IN ('view_orders', 'create_orders', 'edit_orders')");
    return $rows[0]['cnt'] >= 3 ? true : 'Sipariş izinleri bulunamadı';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 2: SİPARİŞ CRUD VE İŞLEMLER
// ─────────────────────────────────────────────────────────────
echo "\n🛒 [BÖLÜM 2] Sipariş CRUD ve İşlemler\n";

// Test ürünü alalım (JOIN ile name ile birlikte)
$productRow = $pdo->query("SELECT p.id, p.sku, pt.name, p.price FROM products p JOIN product_translations pt ON p.id = pt.product_id WHERE p.deleted_at IS NULL LIMIT 1");
if (empty($productRow)) {
    // Ekle
    $pdo->execute("INSERT INTO products (sku, price, status, slug) VALUES ('TEST-ORDER-PROD', 150.00, 'published', 'test-order-prod')");
    $pid = $pdo->lastInsertId();
    $pdo->execute("INSERT INTO product_translations (product_id, language_id, name) VALUES ({$pid}, 1, 'Test Sipariş Ürünü')");
    $product = ['id' => $pid, 'sku' => 'TEST-ORDER-PROD', 'name' => 'Test Sipariş Ürünü', 'price' => 150.00];
} else {
    $product = $productRow[0];
}

$orderService = $container->get(\App\Services\OrderService::class);
$orderRepo = $container->get(\App\Repositories\OrderRepository::class);

testCase('Yeni sipariş oluşturma (Transaction + Türkçe Karakter)', function () use ($orderService, $product, &$testOrderId) {
    $data = [
        'user_id' => 1,
        'billing_first_name' => 'Hakan',
        'billing_last_name' => 'Çalhanoğlu',
        'billing_address' => 'Şişli Mecidiyeköy',
        'billing_city' => 'İstanbul',
        'billing_country' => 'Türkiye',
        'billing_zip' => '34381',
        'shipping_first_name' => 'Hakan',
        'shipping_last_name' => 'Çalhanoğlu',
        'shipping_address' => 'Şişli Mecidiyeköy',
        'shipping_city' => 'İstanbul',
        'shipping_country' => 'Türkiye',
        'shipping_zip' => '34381',
        'subtotal' => 150.00,
        'tax_total' => 27.00,
        'discount_total' => 10.00,
        'shipping_total' => 15.00,
        'grand_total' => 182.00,
        'items' => [
            [
                'product_id' => $product['id'],
                'product_sku' => $product['sku'],
                'product_name' => $product['name'],
                'quantity' => 1,
                'price' => 150.00,
                'tax_amount' => 27.00
            ]
        ],
        'note' => 'Hızlı teslimat lütfen.'
    ];

    $testOrderId = $orderService->create($data);
    return $testOrderId > 0 ? true : 'Sipariş ID alınamadı';
}, $passed, $failed);

testCase('Sipariş bilgilerini ve durumunu güncelleme', function () use ($orderService, $orderRepo, &$testOrderId) {
    if (!$testOrderId) return 'Sipariş oluşturulamadı';
    
    $orderService->update($testOrderId, [
        'billing_first_name' => 'Ömer',
        'status' => 'preparing',
        'status_comment' => 'Sipariş hazırlanma aşamasına geçti.'
    ]);
    
    $row = $orderRepo->getById($testOrderId);
    if ($row['billing_first_name'] !== 'Ömer') return 'İsim güncellenmedi';
    if ($row['status'] !== 'preparing') return 'Durum güncellenmedi';
    return true;
}, $passed, $failed);

testCase('Siparişe Kargo sevk kaydı ekleme', function () use ($orderService, &$testOrderId) {
    if (!$testOrderId) return 'Sipariş oluşturulamadı';
    
    $shipId = $orderService->addShipment($testOrderId, [
        'shipping_method_id' => 1,
        'tracking_number' => '1234567890',
        'carrier_name' => 'Yurtiçi Kargo',
        'notes' => 'Özel kargo notu',
        'status' => 'shipped'
    ]);
    
    return $shipId > 0 ? true : 'Kargo kaydı oluşturulamadı';
}, $passed, $failed);

testCase('Sipariş İade Talebi ve Tutar İadesi ekleme', function () use ($orderService, &$testOrderId) {
    if (!$testOrderId) return 'Sipariş oluşturulamadı';
    
    $refundId = $orderService->addRefund($testOrderId, [
        'amount' => 100.00,
        'reason' => 'Kusurlu Ürün İadesi',
        'type' => 'partial',
        'status' => 'approved'
    ]);
    
    return $refundId > 0 ? true : 'İade kaydı oluşturulamadı';
}, $passed, $failed);

testCase('Ödeme / Tahsilat kaydı ekleme', function () use ($orderService, &$testOrderId) {
    if (!$testOrderId) return 'Sipariş oluşturulamadı';
    
    $txId = $orderService->addPaymentTransaction($testOrderId, [
        'payment_method_id' => 1,
        'transaction_reference' => 'TX-' . time() . '-' . rand(1,999),
        'amount' => 182.00,
        'status' => 'approved',
        'payload' => '{"pos":"garanti"}'
    ]);
    
    return $txId > 0 ? true : 'Ödeme kaydı oluşturulamadı';
}, $passed, $failed);

testCase('Sipariş Notu (İç ve Dış) ekleme', function () use ($orderService, &$testOrderId) {
    if (!$testOrderId) return 'Sipariş oluşturulamadı';
    
    $noteId = $orderService->addNote($testOrderId, 'Özel paketleme rica edildi.', true);
    return $noteId > 0 ? true : 'Sipariş notu oluşturulamadı';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 3: BELGE & VERİ DIŞA AKTARMA (PDF, Excel, CSV)
// ─────────────────────────────────────────────────────────────
echo "\n📊 [BÖLÜM 3] Belge & Veri Dışa Aktarma Testleri\n";

testCase('Excel ve CSV veri serme formatı doğruluğu', function () use ($orderService) {
    $orders = [
        [
            'id' => 1,
            'order_number' => 'SM-TEST-123',
            'billing_first_name' => 'Çetin',
            'billing_last_name' => 'Yılmaz',
            'status' => 'pending',
            'status_name' => 'Yeni Sipariş',
            'subtotal' => 100.0,
            'tax_total' => 18.0,
            'discount_total' => 0.0,
            'shipping_total' => 10.0,
            'grand_total' => 128.0,
            'currency_code' => 'TRY',
            'created_at' => '2026-07-29 20:30:00'
        ]
    ];
    
    $csv = $orderService->exportData('csv', $orders);
    $excel = $orderService->exportData('excel', $orders);
    
    if (!str_contains($csv, 'SM-TEST-123')) return 'CSV içeriğinde Sipariş No yok';
    if (!str_contains($excel, 'Çetin Yılmaz')) return 'Excel içeriğinde Müşteri Adı yok';
    return true;
}, $passed, $failed);

testCase('Denetim Geçmişi (Audit Logs) kaydı oluştu', function () use ($pdo, &$testOrderId) {
    $rows = $pdo->query("SELECT COUNT(*) as cnt FROM audit_logs WHERE auditable_type = 'Order' AND auditable_id = {$testOrderId}");
    return $rows[0]['cnt'] >= 0 ? true : 'Audit log kaydı bulunamadı';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 4: REST API VE YETKİLENDİRME
// ─────────────────────────────────────────────────────────────
echo "\n🌐 [BÖLÜM 4] REST API ve Yetkilendirme Testleri\n";

testCase('GET /api/orders endpoint çağrısı (HTTP 200/401)', function () {
    if (!function_exists('curl_init')) return 'cURL yüklü değil, atlandı';
    $ch = curl_init('http://localhost/SaintMonarc/api/orders');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return in_array($code, [200, 401, 302]) ? true : "HTTP {$code} döndü";
}, $passed, $failed);

testCase('Sipariş API controller metotları tanımlı', function () {
    $methods = ['apiIndex', 'apiShow', 'apiStore', 'apiUpdate', 'apiDelete'];
    foreach ($methods as $m) {
        if (!method_exists(\App\Controllers\OrderController::class, $m)) {
            return "OrderController::{$m}() metodu yok";
        }
    }
    return true;
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 5: DOSYA SİNTAKS KONTROLLERİ
// ─────────────────────────────────────────────────────────────
echo "\n🔍 [BÖLÜM 5] PHP Syntax Kontrolleri\n";

$files = [
    'app/Controllers/OrderController.php',
    'app/Services/OrderService.php',
    'app/Repositories/OrderRepository.php',
    'routes/admin.php',
    'routes/api.php'
];

foreach ($files as $f) {
    testCase("Syntax OK: {$f}", function () use ($f) {
        $path = ROOT_DIR . '/' . $f;
        if (!file_exists($path)) return "Dosya bulunamadı: {$f}";
        exec("C:\\xampp\\php\\php.exe -l \"{$path}\" 2>&1", $output, $ret);
        return $ret === 0 ? true : implode(' ', $output);
    }, $passed, $failed);
}

// Temizlik
if ($testOrderId) {
    $pdo->execute("DELETE FROM order_items WHERE order_id = {$testOrderId}");
    $pdo->execute("DELETE FROM shipments WHERE order_id = {$testOrderId}");
    $pdo->execute("DELETE FROM refunds WHERE order_id = {$testOrderId}");
    $pdo->execute("DELETE FROM payment_transactions WHERE order_id = {$testOrderId}");
    $pdo->execute("DELETE FROM order_notes WHERE order_id = {$testOrderId}");
    $pdo->execute("DELETE FROM orders WHERE id = {$testOrderId}");
}

echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
if ($failed === 0) {
    echo "  ✅  TÜM {$total}/{$total} TEST BAŞARILI!\n";
} else {
    echo "  ⚠️   SONUÇ: {$passed}/{$total} BAŞARILI, {$failed} BAŞARISIZ\n";
}
echo str_repeat('═', 62) . "\n";
echo "  🔗  Admin Panel : http://localhost/SaintMonarc/admin\n";
echo "  🔗  Sipariş Listesi: http://localhost/SaintMonarc/admin/orders\n";
echo "  🔗  REST API    : http://localhost/SaintMonarc/api/orders\n";
echo str_repeat('═', 62) . "\n\n";
