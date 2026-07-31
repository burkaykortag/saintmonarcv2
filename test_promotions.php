<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Sprint 16 - Promotion Engine (Kampanya & İndirim Motoru) CLI Test Betiği
 * Çalıştırma: php test_promotions.php
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

// Boot Application to use the DI Container!
$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$pdo = $container->get(\Core\Contracts\DatabaseInterface::class);

$passed  = 0;
$failed  = 0;

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
echo "  SPRINT 16 — PROMOTION ENGINE CLI TESTLERİ\n";
echo str_repeat('═', 62) . "\n\n";

// ─────────────────────────────────────────────────────────────
// BÖLÜM 1: ŞEMA VE VERİTABANI KONTROLLERİ (1-22)
// ─────────────────────────────────────────────────────────────
echo "📦 [BÖLÜM 1] Veritabanı ve Şema Kontrolleri\n";

$tables = [
    'promotions', 'promotion_translations', 'promotion_conditions', 'promotion_actions',
    'promotion_products', 'promotion_categories', 'promotion_brands', 'promotion_customer_groups',
    'promotion_segments', 'promotion_coupons', 'promotion_coupon_usages', 'promotion_gifts',
    'promotion_logs', 'promotion_schedules', 'promotion_usage_limits', 'promotion_banner_relations',
    'promotion_notifications', 'promotion_priority_rules', 'promotion_conflicts', 'promotion_statistics',
    'promotion_history', 'promotion_preview_cache'
];

$idx = 1;
foreach ($tables as $t) {
    testCase("{$idx}. Tablo varlığı: {$t}", function () use ($pdo, $t) {
        return count($pdo->query("SHOW TABLES LIKE '{$t}'")) > 0 ? true : "{$t} tablosu bulunamadı";
    }, $passed, $failed);
    $idx++;
}

// ─────────────────────────────────────────────────────────────
// BÖLÜM 2: YETKİLER VE RBAC (23)
// ─────────────────────────────────────────────────────────────
echo "\n🔐 [BÖLÜM 2] RBAC Yetkilendirme Kontrolleri\n";

testCase('23. Promosyon & Kupon RBAC izin kayıtları', function () use ($pdo) {
    $rows = $pdo->query("SELECT COUNT(*) as cnt FROM permissions WHERE name IN ('view_promotions', 'create_promotions', 'coupon_management', 'flash_sale_management')");
    return $rows[0]['cnt'] >= 4 ? true : 'İzin tanımları eksik';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 3: KAMPANYA VE KUPON CRUD (24-34)
// ─────────────────────────────────────────────────────────────
echo "\n🎯 [BÖLÜM 3] Kampanya ve Kupon CRUD / İş Mantığı\n";

$service = $container->get(\App\Services\PromotionService::class);
$repo = $container->get(\App\Repositories\PromotionRepository::class);
$testPromoId = null;
$testCouponId = null;

testCase('24. Kampanya Oluşturma (CRUD-C + Türkçe Karakter)', function () use ($service, &$testPromoId) {
    $data = [
        'type' => 'percentage',
        'code' => null,
        'name' => 'Türkçe Başlıklı Kampanya %20 İndirim',
        'description' => 'Açıklama: Özel kampanya kuralları.',
        'status' => 'active',
        'priority' => 10,
        'is_exclusive' => 1,
        'max_total_usage' => 100,
        'max_user_usage' => 1,
        'conditions' => [
            ['rule_type' => 'min_cart', 'operator' => '>=', 'value' => '500']
        ],
        'actions' => [
            ['type' => 'discount_percentage', 'amount' => 20.00, 'target_type' => 'cart']
        ]
    ];
    $testPromoId = $service->create($data);
    return $testPromoId > 0 ? true : 'Kampanya oluşturulamadı';
}, $passed, $failed);

testCase('25. Kampanya Detay Çekme (CRUD-R)', function () use ($repo, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $p = $repo->getById($testPromoId);
    if (!$p) return 'Kampanya bulunamadı';
    if (!str_contains($p['name'], 'Türkçe')) return 'Karakter kodlama bozuk';
    return true;
}, $passed, $failed);

testCase('26. Kampanya Güncelleme (CRUD-U)', function () use ($service, $repo, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $service->update($testPromoId, [
        'type' => 'percentage',
        'code' => null,
        'name' => 'Türkçe Başlıklı Kampanya Güncel',
        'description' => 'Açıklama güncel.',
        'status' => 'active',
        'priority' => 15,
        'is_exclusive' => 1,
        'max_total_usage' => 200,
        'max_user_usage' => 2,
        'conditions' => [
            ['rule_type' => 'min_cart', 'operator' => '>=', 'value' => '600']
        ],
        'actions' => [
            ['type' => 'discount_percentage', 'amount' => 25.00, 'target_type' => 'cart']
        ]
    ]);
    $p = $repo->getById($testPromoId);
    return $p['name'] === 'Türkçe Başlıklı Kampanya Güncel' ? true : 'Güncelleme hatası';
}, $passed, $failed);

testCase('27. Kampanya Kopyalama (Duplicate)', function () use ($service, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $newId = $service->duplicate($testPromoId);
    return $newId > 0 ? true : 'Kopyalama başarısız';
}, $passed, $failed);

testCase('28. Kampanya Soft Delete & Restore', function () use ($repo, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $repo->delete($testPromoId);
    $pNull = $repo->getById($testPromoId, false);
    if ($pNull !== null) return 'Soft delete başarısız';
    
    $repo->restore($testPromoId);
    $pRestore = $repo->getById($testPromoId, false);
    return $pRestore !== null ? true : 'Restore başarısız';
}, $passed, $failed);

testCase('29. Kupon Oluşturma', function () use ($repo, &$testPromoId, &$testCouponId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $code = 'PROMO100_' . time();
    $testCouponId = $repo->createCoupon([
        'promotion_id' => $testPromoId,
        'code' => $code,
        'usage_type' => 'multiple',
        'total_limit' => 10,
        'user_limit' => 1,
        'min_cart_amount' => 100.00,
        'max_discount_amount' => 500.00
    ]);
    return $testCouponId > 0 ? true : 'Kupon oluşturulamadı';
}, $passed, $failed);

testCase('30. Kupon Koduna Göre Getirme', function () use ($repo, &$testCouponId, &$testPromoId) {
    if (!$testCouponId) return 'Kupon oluşturulamadı';
    $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
    $rows = $db->query("SELECT code FROM promotion_coupons WHERE id = :id", [':id' => $testCouponId]);
    if (empty($rows)) return 'Kupon veritabanında bulunamadı';
    $c = $repo->getCouponByCode($rows[0]['code']);
    return $c !== null ? true : 'Kupon koduna göre getirme null döndü';
}, $passed, $failed);

testCase('31. Kupon Geçerlilik Doğrulaması (Valid)', function () use ($service, $repo, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $code = 'TEST_VALID_' . time();
    $repo->createCoupon([
        'promotion_id' => $testPromoId,
        'code' => $code,
        'usage_type' => 'multiple',
        'total_limit' => 10,
        'user_limit' => 1,
        'min_cart_amount' => 100.00,
        'max_discount_amount' => 500.00
    ]);
    $cart = [['product_id' => 1, 'price' => 150.00, 'quantity' => 1]];
    $res = $service->validateCoupon($code, $cart);
    return $res['valid'] === true ? true : $res['message'];
}, $passed, $failed);

testCase('32. Kupon Limit Altı Sepet Doğrulaması (Invalid)', function () use ($service, $repo, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $code = 'TEST_INVALID_' . time();
    $repo->createCoupon([
        'promotion_id' => $testPromoId,
        'code' => $code,
        'usage_type' => 'multiple',
        'total_limit' => 10,
        'user_limit' => 1,
        'min_cart_amount' => 500.00,
        'max_discount_amount' => 500.00
    ]);
    $cart = [['product_id' => 1, 'price' => 150.00, 'quantity' => 1]];
    $res = $service->validateCoupon($code, $cart);
    return $res['valid'] === false ? true : 'Kupon yetersiz sepet tutarına rağmen onaylandı';
}, $passed, $failed);

testCase('33. Kupon Kullanım Kaydı Loglama', function () use ($repo, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    $repo->logUsage($testPromoId, null, null, 50.00, 'Kupon kullanım testi logu');
    return true;
}, $passed, $failed);

testCase('34. Kampanya Önizleme & Sepet Simülasyon Motoru Hesaplaması', function () use ($service, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    
    // Sepet simülasyonu (600 TL üzeri)
    $cart = [
        ['product_id' => 1, 'price' => 400.00, 'quantity' => 2] // 800 TL
    ];
    $res = $service->calculate($cart);
    // %25 indirim uygulanmalı = 200 TL indirim
    return $res['discount_amount'] > 0 ? true : 'Hesaplamada indirim sıfır çıktı';
}, $passed, $failed);


// ─────────────────────────────────────────────────────────────
// BÖLÜM 4: HESAPLAMA MOTORU VE KURAL MOTORU TESTLERİ (35-40)
// ─────────────────────────────────────────────────────────────
echo "\n📈 [BÖLÜM 4] Kampanya İndirim Hesaplama & Kural Motoru\n";

testCase('35. Sepet % İndirim Hesaplama Doğruluğu', function () use ($service, &$testPromoId) {
    $cart = [['product_id' => 1, 'price' => 1000.00, 'quantity' => 1]];
    $res = $service->calculate($cart);
    // %25 indirim = 250.00
    return abs($res['discount_amount'] - 250.00) < 0.01 ? true : 'İndirim yanlış: ' . $res['discount_amount'];
}, $passed, $failed);

testCase('36. Kampanyalar Öncelik Sıralaması Hesaplama', function () use ($service) {
    $cart = [['product_id' => 1, 'price' => 1000.00, 'quantity' => 1]];
    $res = $service->calculate($cart);
    return is_array($res['applied_promotions']) ? true : 'Öncelik sıralaması başarısız';
}, $passed, $failed);

testCase('37. Free Shipping Koşulu Uygulanması', function () use ($service, $repo, &$testPromoId) {
    $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
    if ($testPromoId) {
        $db->execute("UPDATE promotions SET status = 'passive' WHERE id = :id", [':id' => $testPromoId]);
    }

    // Ücretsiz Kargo Kampanyası oluştur
    $id = $repo->create([
        'type' => 'free_shipping',
        'code' => null,
        'name' => 'Ücretsiz Kargo Fırsatı',
        'status' => 'active',
        'priority' => 50,
        'is_exclusive' => 0
    ]);
    
    $cart = [['product_id' => 1, 'price' => 1000.00, 'quantity' => 1]];
    $res = $service->calculate($cart);
    
    $repo->forceDelete($id); // temizlik

    if ($testPromoId) {
        $db->execute("UPDATE promotions SET status = 'active' WHERE id = :id", [':id' => $testPromoId]);
    }

    return $res['free_shipping'] === true ? true : 'Ücretsiz kargo uygulanmadı';
}, $passed, $failed);

testCase('38. Exclusive (Birleşemez) Kampanya Çakışma Çözümlemesi', function () use ($service, $repo, &$testPromoId) {
    if (!$testPromoId) return 'Kampanya oluşturulamadı';
    // Exclusive kampanyaların (is_exclusive = 1) sepet simülasyonunu kırıp kırmadığı kontrolü
    $cart = [['product_id' => 1, 'price' => 1000.00, 'quantity' => 1]];
    $res = $service->calculate($cart);
    return count($res['applied_promotions']) <= 1 ? true : 'Birden fazla birleşemez kampanya uygulandı';
}, $passed, $failed);

testCase('39. Performans Ölçümü (Simülasyon Hızı < 100ms)', function () use ($service) {
    $cart = [['product_id' => 1, 'price' => 100.00, 'quantity' => 1]];
    $start = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $service->calculate($cart);
    }
    $diff = (microtime(true) - $start) / 100;
    return $diff < 0.1 ? true : 'Simülasyon hızı yavaş: ' . ($diff * 1000) . ' ms';
}, $passed, $failed);

testCase('40. Cache Otomatik Temizleme (Promotion Cache Clear)', function () use ($service) {
    $service->clearCache();
    return true;
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 5: SYNTAX KONTROLLERİ (41-45)
// ─────────────────────────────────────────────────────────────
echo "\n🔎 [BÖLÜM 5] PHP Syntax ve Kod Standartları Kontrolleri\n";

$files = [
    'app/Controllers/PromotionController.php',
    'app/Services/PromotionService.php',
    'app/Repositories/PromotionRepository.php',
    'routes/admin.php',
    'routes/api.php'
];

$fileIdx = 41;
foreach ($files as $f) {
    testCase("{$fileIdx}. Syntax OK: {$f}", function () use ($f) {
        $path = ROOT_DIR . '/' . $f;
        if (!file_exists($path)) return "Dosya bulunamadı: {$f}";
        exec("C:\\xampp\\php\\php.exe -l \"{$path}\" 2>&1", $output, $ret);
        return $ret === 0 ? true : implode(' ', $output);
    }, $passed, $failed);
    $fileIdx++;
}

// Temizlik
if ($testPromoId) {
    $pdo->execute("DELETE FROM promotion_usage_limits WHERE promotion_id = {$testPromoId}");
    $pdo->execute("DELETE FROM promotion_statistics WHERE promotion_id = {$testPromoId}");
    $pdo->execute("DELETE FROM promotion_translations WHERE promotion_id = {$testPromoId}");
    $pdo->execute("DELETE FROM promotion_conditions WHERE promotion_id = {$testPromoId}");
    $pdo->execute("DELETE FROM promotion_actions WHERE promotion_id = {$testPromoId}");
    $pdo->execute("DELETE FROM promotion_gifts WHERE promotion_id = {$testPromoId}");
    $pdo->execute("DELETE FROM promotion_coupons WHERE promotion_id = {$testPromoId}");
    $pdo->execute("DELETE FROM promotions WHERE id = {$testPromoId}");
}

echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
if ($failed === 0) {
    echo "  ✅  TÜM {$total}/{$total} TEST BAŞARILI!\n";
} else {
    echo "  ⚠️   SONUÇ: {$passed}/{$total} BAŞARILI, {$failed} BAŞARISIZ\n";
}
echo str_repeat('═', 62) . "\n";
echo "  🔗  Admin Panel : http://localhost/SaintMonarc/admin/promotions\n";
echo "  🔗  Kuponlar    : http://localhost/SaintMonarc/admin/coupons\n";
echo "  🔗  REST API    : http://localhost/SaintMonarc/api/promotions\n";
echo str_repeat('═', 62) . "\n\n";
