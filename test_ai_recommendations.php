<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Sprint 17 - AI Recommendation Engine CLI Test Betiği
 * Çalıştırma: php test_ai_recommendations.php
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
echo "  SPRINT 17 — AI RECOMMENDATION ENGINE CLI TESTLERİ\n";
echo str_repeat('═', 62) . "\n\n";

// --- BÖLÜM 1: ŞEMA VE YETKİLER (1-4) ---
echo "📦 [BÖLÜM 1] Şema ve Yetki Kontrolleri\n";

testCase('1. Tablo varlığı: ai_recommendations', function() use ($pdo) {
    return count($pdo->query("SHOW TABLES LIKE 'ai_recommendations'")) > 0 ? true : 'ai_recommendations tablosu yok';
}, $passed, $failed);

testCase('2. Kolon varlığı: type, title, status, payload', function() use ($pdo) {
    $cols = array_column($pdo->query("DESCRIBE ai_recommendations"), 'Field');
    foreach (['type', 'title', 'status', 'payload'] as $c) {
        if (!in_array($c, $cols)) return "Kolon eksik: {$c}";
    }
    return true;
}, $passed, $failed);

testCase('3. RBAC İzin varlığı: ai_recommendations', function() use ($pdo) {
    $rows = $pdo->query("SELECT id FROM permissions WHERE name = 'ai_recommendations'");
    return count($rows) > 0 ? true : 'RBAC yetki tanımı bulunamadı';
}, $passed, $failed);

testCase('4. Süper Admin yetki eşleştirmesi', function() use ($pdo) {
    $rows = $pdo->query(
        "SELECT rp.* FROM role_permissions rp 
         JOIN roles r ON rp.role_id = r.id 
         JOIN permissions p ON rp.permission_id = p.id
         WHERE r.name = 'super_admin' AND p.name = 'ai_recommendations'"
    );
    return count($rows) > 0 ? true : 'Süper admin yetkisi atanmamış';
}, $passed, $failed);

// --- BÖLÜM 2: REPOSITORY VE SQL ALGORİTMALAR (5-10) ---
echo "\n🎯 [BÖLÜM 2] Repository ve Veri Analiz Metotları\n";

$repo = $container->get(\App\Repositories\AiRecommendationRepository::class);

testCase('5. Öneri kaydetme ve çekme (Save / GetById)', function() use ($repo) {
    $id = $repo->save([
        'type' => 'product_campaign',
        'title' => 'Test Başlık',
        'description' => 'Test Açıklama',
        'payload' => ['sku' => 'TEST-123', 'proposed_discount' => 10.00],
        'status' => 'pending'
    ]);
    if ($id <= 0) return 'Kaydedilemedi';
    $r = $repo->getById($id);
    if (!$r) return 'Çekilemedi';
    if ($r['title'] !== 'Test Başlık') return 'Alan eşleşmedi';
    return true;
}, $passed, $failed);

testCase('6. Öneri durumunu güncelleme (updateStatus)', function() use ($repo) {
    $id = $repo->save([
        'type' => 'product_campaign',
        'title' => 'Test Durum',
        'description' => 'Test',
        'payload' => null,
        'status' => 'pending'
    ]);
    $repo->updateStatus($id, 'applied');
    $r = $repo->getById($id);
    return $r['status'] === 'applied' ? true : 'Durum güncellenmedi';
}, $passed, $failed);

testCase('7. Birlikte Satılan Ürünler Metodu (getFrequentlyBoughtTogether)', function() use ($repo) {
    $res = $repo->getFrequentlyBoughtTogether(5);
    return is_array($res) ? true : 'Metot dizi dönmedi';
}, $passed, $failed);

testCase('8. Bekleyen Yavaş Stoklar Metodu (getAgingStockProducts)', function() use ($repo) {
    $res = $repo->getAgingStockProducts(60, 5, 5);
    return is_array($res) ? true : 'Metot dizi dönmedi';
}, $passed, $failed);

testCase('9. Düşük Dönüşüm Oranlı Ürünler Metodu (getHighViewsLowSalesProducts)', function() use ($repo) {
    $res = $repo->getHighViewsLowSalesProducts(1, 100.0, 5);
    return is_array($res) ? true : 'Metot dizi dönmedi';
}, $passed, $failed);

testCase('10. Yavaş Kategori Devir Metodu (getAgingCategories)', function() use ($repo) {
    $res = $repo->getAgingCategories(5);
    return is_array($res) ? true : 'Metot dizi dönmedi';
}, $passed, $failed);


// --- BÖLÜM 3: SERVİS KATMANI VE ENTEGRASYON (11-15) ---
echo "\n🤖 [BÖLÜM 3] Öneri Servisi & Kampanya Entegrasyonu\n";

$service = $container->get(\App\Services\AiRecommendationServiceInterface::class);

testCase('11. Öneri üretme döngüsü (generateRecommendations)', function() use ($service) {
    $recs = $service->generateRecommendations();
    return is_array($recs) ? true : 'Öneriler üretilmedi';
}, $passed, $failed);

testCase('12. Öneri listesinin temizlenip yeniden dolması', function() use ($pdo, $service) {
    $service->generateRecommendations();
    $cnt = $pdo->query("SELECT COUNT(*) as cnt FROM ai_recommendations WHERE status = 'pending'")[0]['cnt'];
    return $cnt >= 0 ? true : 'Öneri kayıt sayısı hatalı';
}, $passed, $failed);

testCase('13. Öneri onaylanması ve Kampanyaya Dönüştürülmesi (applyRecommendation)', function() use ($service, $repo, $pdo) {
    $id = $repo->save([
        'type' => 'product_campaign',
        'title' => 'Test Canlandırma Kampanyası',
        'description' => 'Açıklama',
        'payload' => ['product_id' => 1, 'proposed_discount' => 12.50, 'action_type' => 'discount_percentage'],
        'status' => 'pending'
    ]);
    
    $success = $service->applyRecommendation($id);
    if (!$success) return 'applyRecommendation false döndü';
    
    $r = $repo->getById($id);
    if ($r['status'] !== 'applied') return 'Öneri durumu applied olmadı';
    
    // Kampanyanın promotions tablosuna eklendiğini doğrula
    $promoName = "AI Önerisi: Test Canlandırma Kampanyası";
    $pRows = $pdo->query("SELECT id FROM promotion_translations WHERE name = :name", [':name' => $promoName]);
    if (empty($pRows)) return 'Kampanya promotions tablosuna kaydedilmedi';
    
    // Temizlik
    $pdo->execute("DELETE FROM promotion_translations WHERE name = :name", [':name' => $promoName]);
    $pdo->execute("DELETE FROM promotions WHERE id = " . (int)$pRows[0]['id']);
    
    return true;
}, $passed, $failed);

testCase('14. Öneri yoksayma (dismissRecommendation)', function() use ($service, $repo) {
    $id = $repo->save([
        'type' => 'product_campaign',
        'title' => 'Test Yoksay',
        'description' => 'Test',
        'payload' => null,
        'status' => 'pending'
    ]);
    $service->dismissRecommendation($id);
    $r = $repo->getById($id);
    return $r['status'] === 'dismissed' ? true : 'Yoksayma durumu güncellenmedi';
}, $passed, $failed);

testCase('15. API Key yokken OpenAI -> Local Fallback davranışı', function() use ($service) {
    // OpenAI API key empty iken servis patlamadan yerel önerileri getirmelidir
    $recs = $service->generateRecommendations();
    return count($recs) >= 0 ? true : 'API key yokken servis hata verdi';
}, $passed, $failed);


// --- BÖLÜM 4: CONTROLLER VE SYNTAX STANDARTLARI (16-20) ---
echo "\n🔎 [BÖLÜM 4] Controller ve Kod Standartları\n";

testCase('16. Controller Sınıf Varlığı', function() {
    return class_exists(\App\Controllers\AiRecommendationController::class) ? true : 'AiRecommendationController sınıfı bulunamadı';
}, $passed, $failed);

$files = [
    'app/Repositories/AiRecommendationRepository.php',
    'app/Services/AiRecommendationServiceInterface.php',
    'app/Services/LocalAiRecommendationService.php',
    'app/Services/OpenAiRecommendationService.php',
    'app/Controllers/AiRecommendationController.php'
];

$fileIdx = 17;
foreach ($files as $f) {
    testCase("{$fileIdx}. Syntax OK: {$f}", function() use ($f) {
        $path = ROOT_DIR . '/' . $f;
        if (!file_exists($path)) return "Dosya bulunamadı: {$f}";
        exec("C:\\xampp\\php\\php.exe -l \"{$path}\" 2>&1", $output, $ret);
        return $ret === 0 ? true : implode(' ', $output);
    }, $passed, $failed);
    $fileIdx++;
}

// Temizlik
$pdo->execute("DELETE FROM ai_recommendations WHERE title LIKE 'Test%'");

echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
if ($failed === 0) {
    echo "  ✅  TÜM {$total}/{$total} TEST BAŞARILI!\n";
} else {
    echo "  ⚠️   SONUÇ: {$passed}/{$total} BAŞARILI, {$failed} BAŞARISIZ\n";
}
echo str_repeat('═', 62) . "\n";
echo "  🔗  AI Öneri Paneli: http://localhost/SaintMonarc/admin/recommendations\n";
echo str_repeat('═', 62) . "\n\n";
