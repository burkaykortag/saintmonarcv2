<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Sprint 18 - Enterprise Search Engine CLI Test Betiği
 * Çalıştırma: php test_search.php
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
echo "  SPRINT 18 — ENTERPRISE SEARCH ENGINE CLI TESTLERİ\n";
echo str_repeat('═', 62) . "\n\n";

// --- BÖLÜM 1: 20 ADET VERİTABANI TABLO KONTROLLERİ (1-20) ---
echo "📦 [BÖLÜM 1] Veritabanı Tablo Varlık Kontrolleri\n";

$tables = [
    'search_index', 'search_keywords', 'search_synonyms', 'search_redirects',
    'search_popular', 'search_logs', 'search_statistics', 'search_filters',
    'search_cache', 'search_boost_rules', 'search_stop_words', 'search_suggestions',
    'search_clicks', 'search_history', 'search_ai_queries', 'search_blacklist',
    'search_whitelist', 'search_collections', 'search_index_queue', 'search_rebuild_logs'
];

$idx = 1;
foreach ($tables as $t) {
    testCase("{$idx}. Tablo varlığı: {$t}", function() use ($pdo, $t) {
        return count($pdo->query("SHOW TABLES LIKE '{$t}'")) > 0 ? true : "{$t} tablosu bulunamadı";
    }, $passed, $failed);
    $idx++;
}

// --- BÖLÜM 2: RBAC YETKİLERİ KONTROLLERİ (21-29) ---
echo "\n🔐 [BÖLÜM 2] RBAC Yetkilendirme İzin Kontrolleri\n";

$permissions = [
    'view_search', 'manage_search', 'manage_search_index', 'manage_synonyms',
    'manage_stopwords', 'manage_boost', 'search_reports', 'search_rebuild', 'search_ai'
];

$permIdx = 21;
foreach ($permissions as $p) {
    testCase("{$permIdx}. RBAC Yetki varlığı: {$p}", function() use ($pdo, $p) {
        $rows = $pdo->query("SELECT id FROM permissions WHERE name = :name", [':name' => $p]);
        return count($rows) > 0 ? true : "{$p} yetkisi bulunamadı";
    }, $passed, $failed);
    $permIdx++;
}

// --- BÖLÜM 3: REPOSITORY CRUD VE LOG İŞLEMLERİ (30-46) ---
echo "\n🎯 [BÖLÜM 3] Arama Deposu (SearchRepository) İşlemleri\n";

$repo = $container->get(\App\Repositories\SearchRepository::class);

testCase('30. Arama İndeksi Ekleme (upsertIndex)', function() use ($repo) {
    return $repo->upsertIndex([
        'item_type' => 'product',
        'item_id' => 99999,
        'title' => 'Test Ürünü Kırmızı Ceket',
        'content' => 'Bu harika kırmızı ceket spor tarzdadır.',
        'sku' => 'TEST-JKT-RED',
        'barcode' => '9999988888',
        'tags' => 'ceket, kırmızı',
        'price' => 599.90,
        'stock_status' => 'in_stock',
        'is_active' => 1
    ]);
}, $passed, $failed);

testCase('31. Arama İndeksi Sorgulama', function() use ($pdo) {
    $rows = $pdo->query("SELECT * FROM search_index WHERE item_id = 99999");
    return count($rows) > 0 ? true : 'İndekslenen veri çekilemedi';
}, $passed, $failed);

testCase('32. Arama İndeksinden Silme (deleteFromIndex)', function() use ($repo, $pdo) {
    $repo->deleteFromIndex('product', 99999);
    $rows = $pdo->query("SELECT * FROM search_index WHERE item_id = 99999");
    return count($rows) === 0 ? true : 'İndeksten silinemedi';
}, $passed, $failed);

testCase('33. Arama Loglama (logSearch)', function() use ($repo, $pdo) {
    $repo->logSearch('test arama sorgusu', 5);
    $rows = $pdo->query("SELECT id FROM search_logs WHERE query = 'test arama sorgusu'");
    return count($rows) > 0 ? true : 'Arama sorgusu loglanmadı';
}, $passed, $failed);

testCase('34. Arama Tıklama Kaydı (logClick)', function() use ($repo, $pdo) {
    $repo->logClick('test arama sorgusu', 'product', 123);
    $rows = $pdo->query("SELECT id FROM search_clicks WHERE query = 'test arama sorgusu'");
    return count($rows) > 0 ? true : 'Tıklama loglanmadı';
}, $passed, $failed);

testCase('35. Eş Anlamlı Ekleme (saveSynonym)', function() use ($repo) {
    return $repo->saveSynonym('notebook', 'laptop,bilgisayar');
}, $passed, $failed);

testCase('36. Eş Anlamlı Çekme (getSynonyms)', function() use ($repo) {
    $res = $repo->getSynonyms();
    return count($res) > 0 ? true : 'Eş anlamlılar çekilemedi';
}, $passed, $failed);

testCase('37. Eş Anlamlı Silme (deleteSynonym)', function() use ($repo, $pdo) {
    $rows = $pdo->query("SELECT id FROM search_synonyms WHERE source_word = 'notebook' LIMIT 1");
    if (empty($rows)) return 'Eş anlamlı bulunamadı';
    $id = (int)$rows[0]['id'];
    $repo->deleteSynonym($id);
    $rows2 = $pdo->query("SELECT deleted_at FROM search_synonyms WHERE id = {$id}");
    return !empty($rows2[0]['deleted_at']) ? true : 'deleted_at boş kaldı';
}, $passed, $failed);

testCase('38. Stop Word Ekleme (saveStopWord)', function() use ($repo) {
    return $repo->saveStopWord('veya');
}, $passed, $failed);

testCase('39. Stop Word Çekme (getStopWords)', function() use ($repo) {
    $res = $repo->getStopWords();
    return count($res) > 0 ? true : 'Stop words listesi boş döndü';
}, $passed, $failed);

testCase('40. Stop Word Silme (deleteStopWord)', function() use ($repo, $pdo) {
    $rows = $pdo->query("SELECT id FROM search_stop_words WHERE word = 'veya' LIMIT 1");
    if (empty($rows)) return 'Stop word bulunamadı';
    $id = (int)$rows[0]['id'];
    $repo->deleteStopWord($id);
    $rows2 = $pdo->query("SELECT deleted_at FROM search_stop_words WHERE id = {$id}");
    return !empty($rows2[0]['deleted_at']) ? true : 'deleted_at boş kaldı';
}, $passed, $failed);

testCase('41. Yönlendirme Ekleme (saveRedirect)', function() use ($repo) {
    return $repo->saveRedirect('eski-urun', '/products/yeni-urun', 301);
}, $passed, $failed);

testCase('42. Yönlendirme Çekme (getRedirects)', function() use ($repo) {
    $res = $repo->getRedirects();
    return count($res) > 0 ? true : 'Yönlendirme listesi boş';
}, $passed, $failed);

testCase('43. Yönlendirme Silme (deleteRedirect)', function() use ($repo, $pdo) {
    $rows = $pdo->query("SELECT id FROM search_redirects WHERE keyword = 'eski-urun' LIMIT 1");
    if (empty($rows)) return 'Yönlendirme bulunamadı';
    $id = (int)$rows[0]['id'];
    $repo->deleteRedirect($id);
    $rows2 = $pdo->query("SELECT deleted_at FROM search_redirects WHERE id = {$id}");
    return !empty($rows2[0]['deleted_at']) ? true : 'Yönlendirme soft delete edilmedi';
}, $passed, $failed);

testCase('44. Boost Kuralı Ekleme (saveBoostRule)', function() use ($repo) {
    return $repo->saveBoostRule('keyword', null, 'vip', 2.0);
}, $passed, $failed);

testCase('45. Boost Kuralı Çekme (getBoostRules)', function() use ($repo) {
    $res = $repo->getBoostRules();
    return count($res) > 0 ? true : 'Boost kuralları listesi boş';
}, $passed, $failed);

testCase('46. Boost Kuralı Silme (deleteBoostRule)', function() use ($repo, $pdo) {
    $rows = $pdo->query("SELECT id FROM search_boost_rules WHERE keyword = 'vip' LIMIT 1");
    if (empty($rows)) return 'Boost kuralı bulunamadı';
    $id = (int)$rows[0]['id'];
    $repo->deleteBoostRule($id);
    $rows2 = $pdo->query("SELECT deleted_at FROM search_boost_rules WHERE id = {$id}");
    return !empty($rows2[0]['deleted_at']) ? true : 'Boost kuralı silinmedi';
}, $passed, $failed);


// --- BÖLÜM 4: ARAMA MOTORU İŞ MANTIĞI VE ALGORTİMALAR (47-52) ---
echo "\n📈 [BÖLÜM 4] Arama Servisi ve Algoritmik Özellikler\n";

$service = $container->get(\App\Services\SearchService::class);

testCase('47. Autocomplete Sonuçları (autocomplete)', function() use ($service, $repo) {
    $repo->upsertIndex([
        'item_type' => 'product',
        'item_id' => 99990,
        'title' => 'Premium Elbise Şık',
        'is_active' => 1
    ]);
    $res = $service->autocomplete('Pre');
    $repo->deleteFromIndex('product', 99990);
    return count($res) > 0 ? true : 'Otomatik tamamlama başarısız';
}, $passed, $failed);

testCase('48. Fuzzy Search & Yazım Hatası Düzeltme (Suggestions)', function() use ($pdo, $service) {
    $pdo->execute("INSERT IGNORE INTO search_keywords (keyword) VALUES ('televizyon')");
    $res = $service->getSuggestions('telvizyon'); // Yazım hatası
    return in_array('televizyon', $res) ? true : 'Öneri bulunamadı: ' . json_encode($res);
}, $passed, $failed);

testCase('49. Türkçe Karakter Haritalama (ş->s, ı->i)', function() use ($service) {
    $norm = $service->normalizeText('Şekerli Çiçek İpek Ğüneş');
    return $norm === 'sekerli cicek ipek gunes' ? true : 'Haritalama hatası: ' . $norm;
}, $passed, $failed);

testCase('50. Arama Önbelleği (Cache set / get)', function() use ($repo) {
    $key = 'test_cache_' . time();
    $repo->setCache($key, ['status' => 'cached'], 60);
    $data = $repo->getCache($key);
    return isset($data['status']) && $data['status'] === 'cached' ? true : 'Cache okunamadı';
}, $passed, $failed);

testCase('51. Yeniden Yeniden Oluşturma Günlükleri Başlatma/Kapatma', function() use ($repo) {
    $id = $repo->startRebuildLog();
    if ($id <= 0) return 'Başlatma başarısız';
    return $repo->finishRebuildLog($id, 10, 'success');
}, $passed, $failed);

testCase('52. AI Arama Çözümleme (aiSearch)', function() use ($service) {
    $res = $service->aiSearch('ucuz laptop');
    return is_array($res['results']) ? true : 'AI arama başarısız';
}, $passed, $failed);


// --- BÖLÜM 5: SYNTAX KOD STANDARTLARI (53-57) ---
echo "\n🔎 [BÖLÜM 5] PHP Syntax ve Kod Standartları Kontrolleri\n";

$files = [
    'app/Repositories/SearchRepository.php',
    'app/Services/SearchService.php',
    'app/Controllers/SearchController.php',
    'routes/admin.php',
    'routes/api.php'
];

$fileIdx = 53;
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
$pdo->execute("DELETE FROM search_logs WHERE query LIKE 'test%'");
$pdo->execute("DELETE FROM search_clicks WHERE query LIKE 'test%'");
$pdo->execute("DELETE FROM search_synonyms WHERE source_word = 'notebook'");
$pdo->execute("DELETE FROM search_stop_words WHERE word = 'veya'");
$pdo->execute("DELETE FROM search_redirects WHERE keyword = 'eski-urun'");
$pdo->execute("DELETE FROM search_boost_rules WHERE keyword = 'vip'");
$pdo->execute("DELETE FROM search_keywords WHERE keyword = 'televizyon'");

echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
if ($failed === 0) {
    echo "  ✅  TÜM {$total}/{$total} TEST BAŞARILI!\n";
} else {
    echo "  ⚠️   SONUÇ: {$passed}/{$total} BAŞARILI, {$failed} BAŞARISIZ\n";
}
echo str_repeat('═', 62) . "\n";
echo "  🔗  Arama Paneli : http://localhost/SaintMonarc/admin/search\n";
echo "  🔗  İstatistikler: http://localhost/SaintMonarc/admin/search/statistics\n";
echo "  🔗  Synonyms     : http://localhost/SaintMonarc/admin/search/synonyms\n";
echo "  🔗  Boost        : http://localhost/SaintMonarc/admin/search/boost\n";
echo "  🔗  REST API     : http://localhost/SaintMonarc/api/search\n";
echo str_repeat('═', 62) . "\n\n";
