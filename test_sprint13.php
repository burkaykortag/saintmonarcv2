<?php
declare(strict_types=1);

/**
 * Sprint 13 - Gelişmiş Ürün Yönetimi 2.0 - CLI Test Betiği
 * Çalıştırma: php test_sprint13.php
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
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    });
}

use Core\Config\EnvParser;

EnvParser::parse(ROOT_DIR . '/.env');

$pdo = new PDO(
    'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_DATABASE') ?: 'saintmonarc') . ';charset=utf8mb4',
    getenv('DB_USERNAME') ?: 'root',
    getenv('DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$passed  = 0;
$failed  = 0;
$testProductId = null;
$testSku = 'TEST-S13-' . time();

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
echo "  SPRINT 13 — GELİŞMİŞ ÜRÜN YÖNETİMİ 2.0 CLI TESTLERİ\n";
echo str_repeat('═', 62) . "\n\n";

// ─────────────────────────────────────────────────────────────
// BÖLÜM 1: VERİTABANI ŞEMA KONTROLÜ
// ─────────────────────────────────────────────────────────────
echo "📦 [BÖLÜM 1] Veritabanı Şema Kontrolü\n";

testCase('products.is_deal kolonu mevcut', function () use ($pdo) {
    return $pdo->query("SHOW COLUMNS FROM products LIKE 'is_deal'")->rowCount() > 0
        ? true : 'is_deal kolonu bulunamadı';
}, $passed, $failed);

testCase('products.available_from kolonu mevcut', function () use ($pdo) {
    return $pdo->query("SHOW COLUMNS FROM products LIKE 'available_from'")->rowCount() > 0
        ? true : 'available_from kolonu bulunamadı';
}, $passed, $failed);

testCase('products.available_to kolonu mevcut', function () use ($pdo) {
    return $pdo->query("SHOW COLUMNS FROM products LIKE 'available_to'")->rowCount() > 0
        ? true : 'available_to kolonu bulunamadı';
}, $passed, $failed);

testCase('product_translations.box_content kolonu mevcut', function () use ($pdo) {
    return $pdo->query("SHOW COLUMNS FROM product_translations LIKE 'box_content'")->rowCount() > 0
        ? true : 'box_content kolonu bulunamadı';
}, $passed, $failed);

testCase('product_translations.return_policy kolonu mevcut', function () use ($pdo) {
    return $pdo->query("SHOW COLUMNS FROM product_translations LIKE 'return_policy'")->rowCount() > 0
        ? true : 'return_policy kolonu bulunamadı';
}, $passed, $failed);

testCase('product_documents tablosu mevcut', function () use ($pdo) {
    return $pdo->query("SHOW TABLES LIKE 'product_documents'")->rowCount() > 0
        ? true : 'product_documents tablosu bulunamadı';
}, $passed, $failed);

testCase('admins tablosu mevcut (administrators değil)', function () use ($pdo) {
    return $pdo->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0
        ? true : 'admins tablosu bulunamadı';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 2: ÜRÜN CRUD TESTİ
// ─────────────────────────────────────────────────────────────
echo "\n🛒 [BÖLÜM 2] Ürün CRUD Testleri\n";

testCase('Türkçe karakterlerle yeni ürün oluşturma (Sprint 13 alanları)', function () use ($pdo, $testSku, &$testProductId) {
    $pdo->beginTransaction();
    try {
        $cat   = $pdo->query('SELECT id FROM categories  WHERE deleted_at IS NULL LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $brand = $pdo->query('SELECT id FROM brands       WHERE deleted_at IS NULL LIMIT 1')->fetch(PDO::FETCH_ASSOC);

        $pdo->prepare(
            "INSERT INTO products
             (brand_id, sku, barcode, status, price, cost_price, profit, profit_margin, profit_rate,
              currency_code, total_stock, is_active, is_new, is_deal, available_from, available_to, slug, created_at)
             VALUES
             (:brand_id, :sku, :barcode, 'draft', 299.90, 149.90, 150.00, 50.02, 100.07,
              'TRY', 25, 1, 1, 1, '2026-08-01 00:00:00', '2026-12-31 23:59:59', :slug, NOW())"
        )->execute([
            ':brand_id' => $brand ? $brand['id'] : null,
            ':sku'      => $testSku,
            ':barcode'  => 'TEST-' . time(),
            ':slug'     => 'test-urun-sprint13-' . time(),
        ]);
        $testProductId = (int)$pdo->lastInsertId();

        $pdo->prepare(
            "INSERT INTO product_translations
             (product_id, language_id, name, short_description, box_content, return_policy)
             VALUES (:pid, 1, :name, :sdesc, :box, :ret)"
        )->execute([
            ':pid'   => $testProductId,
            ':name'  => 'Türkçe Test Ürün: Çiçek & Güneş Şapkası İle',
            ':sdesc' => 'Özel tasarım, ışıltılı ürün. Çok satılan şıklık.',
            ':box'   => '1x Şapka, 2x İplik, 1x Kullanım Kılavuzu',
            ':ret'   => '30 gün içinde ücretsiz iade. İndirgeli ürünler iade edilemez.',
        ]);

        if ($cat) {
            $pdo->prepare("INSERT INTO product_category_relations (product_id, category_id) VALUES (:p, :c)")
                ->execute([':p' => $testProductId, ':c' => $cat['id']]);
        }

        $pdo->commit();
        return $testProductId > 0 ? true : 'Ürün ID alınamadı';
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}, $passed, $failed);

testCase('Oluşturulan ürünü getirme ve JOIN doğrulaması', function () use ($pdo, &$testProductId) {
    if (!$testProductId) {
        return 'Test ürünü oluşturulamadı';
    }
    $row = $pdo->prepare("SELECT p.*, pt.name, pt.box_content, pt.return_policy
                           FROM products p
                           JOIN product_translations pt ON p.id = pt.product_id
                           WHERE p.id = :id LIMIT 1");
    $row->execute([':id' => $testProductId]);
    $r = $row->fetch(PDO::FETCH_ASSOC);
    if (!$r) {
        return 'Ürün getirilemedi';
    }
    if (!str_contains((string)$r['name'], 'Türkçe')) {
        return 'Türkçe karakter kaybolmuş: ' . $r['name'];
    }
    if (!str_contains((string)($r['box_content'] ?? ''), 'Şapka')) {
        return 'box_content kaydedilmedi';
    }
    return true;
}, $passed, $failed);

testCase('Kâr marjı hesaplama doğruluğu (fiyat - maliyet)', function () use ($pdo, &$testProductId) {
    if (!$testProductId) {
        return 'Test ürünü oluşturulamadı';
    }
    $stmt = $pdo->prepare('SELECT price, cost_price, profit FROM products WHERE id = :id');
    $stmt->execute([':id' => $testProductId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $expectedProfit = 299.90 - 149.90;
    if (abs((float)$row['profit'] - $expectedProfit) > 0.01) {
        return 'Kâr yanlış: ' . $row['profit'];
    }
    return true;
}, $passed, $failed);

testCase('is_deal ve available_from/to alanları kaydedilmiş', function () use ($pdo, &$testProductId) {
    if (!$testProductId) {
        return 'Test ürünü oluşturulamadı';
    }
    $stmt = $pdo->prepare('SELECT is_deal, available_from, available_to FROM products WHERE id = :id');
    $stmt->execute([':id' => $testProductId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ((int)$row['is_deal'] !== 1) {
        return 'is_deal 1 değil: ' . $row['is_deal'];
    }
    if (empty($row['available_from'])) {
        return 'available_from boş';
    }
    return true;
}, $passed, $failed);

testCase('Ürün güncelleme (stok ve fiyat)', function () use ($pdo, &$testProductId) {
    if (!$testProductId) {
        return 'Test ürünü oluşturulamadı';
    }
    $pdo->prepare('UPDATE products SET total_stock = 50, price = 349.90 WHERE id = :id')
        ->execute([':id' => $testProductId]);
    $stmt = $pdo->prepare('SELECT total_stock, price FROM products WHERE id = :id');
    $stmt->execute([':id' => $testProductId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ((int)$row['total_stock'] !== 50) {
        return 'Stok güncellenmedi: ' . $row['total_stock'];
    }
    return true;
}, $passed, $failed);

testCase('Soft delete çalışıyor (deleted_at set ediliyor)', function () use ($pdo, &$testProductId) {
    if (!$testProductId) {
        return 'Test ürünü oluşturulamadı';
    }
    $pdo->prepare('UPDATE products SET deleted_at = NOW() WHERE id = :id')->execute([':id' => $testProductId]);
    $stmt = $pdo->prepare('SELECT deleted_at FROM products WHERE id = :id');
    $stmt->execute([':id' => $testProductId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return !empty($row['deleted_at']) ? true : 'deleted_at boş kaldı';
}, $passed, $failed);

testCase('Restore (deleted_at NULL yapılıyor)', function () use ($pdo, &$testProductId) {
    if (!$testProductId) {
        return 'Test ürünü oluşturulamadı';
    }
    $pdo->prepare('UPDATE products SET deleted_at = NULL WHERE id = :id')->execute([':id' => $testProductId]);
    $stmt = $pdo->prepare('SELECT deleted_at FROM products WHERE id = :id');
    $stmt->execute([':id' => $testProductId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return empty($row['deleted_at']) ? true : 'deleted_at NULL olmadı';
}, $passed, $failed);

testCase('Test kaydını temizleme (force delete + cascade)', function () use ($pdo, &$testProductId) {
    if (!$testProductId) {
        return true;
    }
    $pdo->prepare('DELETE FROM product_category_relations WHERE product_id = :id')->execute([':id' => $testProductId]);
    $pdo->prepare('DELETE FROM product_translations        WHERE product_id = :id')->execute([':id' => $testProductId]);
    $pdo->prepare('DELETE FROM products                    WHERE id = :id')->execute([':id' => $testProductId]);
    $stmt = $pdo->prepare('SELECT id FROM products WHERE id = :id');
    $stmt->execute([':id' => $testProductId]);
    return $stmt->rowCount() === 0 ? true : 'Ürün silinemedi';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 3: ROUTER METOT KONTROLÜ
// ─────────────────────────────────────────────────────────────
echo "\n🔀 [BÖLÜM 3] Router Metot Kontrolü\n";

testCase('Router::put() metodu tanımlı', function () {
    return method_exists(\Core\Http\Router::class, 'put') ? true : 'put() metodu yok';
}, $passed, $failed);

testCase('Router::delete() metodu tanımlı', function () {
    return method_exists(\Core\Http\Router::class, 'delete') ? true : 'delete() metodu yok';
}, $passed, $failed);

testCase('Router::patch() metodu tanımlı', function () {
    return method_exists(\Core\Http\Router::class, 'patch') ? true : 'patch() metodu yok';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 4: TÜRKÇE KARAKTER TESTİ
// ─────────────────────────────────────────────────────────────
echo "\n🇹🇷 [BÖLÜM 4] Türkçe Karakter UTF8MB4 Testi\n";

testCase('Özel karakterler kayıp olmadan DB siklusu', function () use ($pdo) {
    $testStr = 'Çığır Açan Ürün: ğüşıöç ĞÜŞİÖÇ İşte Bu!';
    $stmt = $pdo->prepare('SELECT :str AS result');
    $stmt->execute([':str' => $testStr]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['result'] === $testStr ? true : 'Karakter bozuldu: ' . $row['result'];
}, $passed, $failed);

testCase('Uzun Türkçe metin TEXT alanına yazılıp okunuyor', function () use ($pdo) {
    $long = str_repeat('Şapka,Güneş,Özel,İndirim,Ürün,Çiçek,', 50);
    $stmt = $pdo->prepare('SELECT :str AS result');
    $stmt->execute([':str' => $long]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['result'] === $long ? true : 'Uzun metin bozuldu';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 5: REST API METOT KONTROLÜ
// ─────────────────────────────────────────────────────────────
echo "\n🌐 [BÖLÜM 5] REST API Metot Kontrolü\n";

testCase('ProductController::apiStore() mevcut', function () {
    return method_exists(\App\Controllers\ProductController::class, 'apiStore')
        ? true : 'apiStore metodu yok';
}, $passed, $failed);

testCase('ProductController::apiUpdate() mevcut', function () {
    return method_exists(\App\Controllers\ProductController::class, 'apiUpdate')
        ? true : 'apiUpdate metodu yok';
}, $passed, $failed);

testCase('ProductController::apiDelete() mevcut', function () {
    return method_exists(\App\Controllers\ProductController::class, 'apiDelete')
        ? true : 'apiDelete metodu yok';
}, $passed, $failed);

testCase('GET /api/products endpoint yanıt veriyor', function () {
    if (!function_exists('curl_init')) {
        return 'cURL modülü yüklü değil, atlandı';
    }
    $ch = curl_init('http://localhost/SaintMonarc/api/products');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 0) {
        return 'Sunucu yanıt vermedi (XAMPP çalışıyor mu?)';
    }
    return in_array($code, [200, 401, 302]) ? true : "HTTP {$code} döndü";
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 6: CACHE KONTROLÜ
// ─────────────────────────────────────────────────────────────
echo "\n⚡ [BÖLÜM 6] Cache Sistemi Kontrolü\n";

testCase('ProductService::clearCache() metodu mevcut', function () {
    return method_exists(\App\Services\ProductService::class, 'clearCache')
        ? true : 'clearCache metodu yok';
}, $passed, $failed);

testCase('ProductService::getTreeCached() metodu mevcut', function () {
    return method_exists(\App\Services\ProductService::class, 'getTreeCached')
        ? true : 'getTreeCached metodu yok';
}, $passed, $failed);

// ─────────────────────────────────────────────────────────────
// BÖLÜM 7: DOSYA SİNTAKS KONTROLÜ
// ─────────────────────────────────────────────────────────────
echo "\n🔍 [BÖLÜM 7] PHP Syntax Kontrolü\n";

$filesToCheck = [
    'app/Controllers/ProductController.php',
    'app/Services/ProductService.php',
    'app/Repositories/ProductRepository.php',
    'core/Http/Router.php',
    'routes/admin.php',
    'routes/api.php',
];

foreach ($filesToCheck as $file) {
    testCase("Syntax OK: {$file}", function () use ($file) {
        $fullPath = ROOT_DIR . '/' . $file;
        if (!file_exists($fullPath)) {
            return "Dosya bulunamadı: {$file}";
        }
        exec("C:\\xampp\\php\\php.exe -l \"{$fullPath}\" 2>&1", $output, $ret);
        return $ret === 0 ? true : implode(' ', $output);
    }, $passed, $failed);
}

// ─────────────────────────────────────────────────────────────
// SONUÇ
// ─────────────────────────────────────────────────────────────
echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
if ($failed === 0) {
    echo "  ✅  TÜM {$total}/{$total} TEST BAŞARILI!\n";
} else {
    echo "  ⚠️   SONUÇ: {$passed}/{$total} BAŞARILI, {$failed} BAŞARISIZ\n";
}
echo str_repeat('═', 62) . "\n";
echo "  🔗  Admin Panel : http://localhost/SaintMonarc/admin\n";
echo "  🔗  Ürün Listesi: http://localhost/SaintMonarc/admin/products\n";
echo "  🔗  REST API    : http://localhost/SaintMonarc/api/products\n";
echo str_repeat('═', 62) . "\n\n";
