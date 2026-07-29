<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT_DIR', 'c:/xampp/htdocs/SaintMonarc');

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

use Core\Application;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;

echo "==================================================\n";
echo "SAINTMONARC SPRINT 13 - ÜRÜN YÖNETİMİ 2.0 CLI TESTLERİ\n";
echo "==================================================\n";

$app = new Application(ROOT_DIR);
$container = $app->getContainer();

$db = $container->get(DatabaseInterface::class);
$cache = $container->get(CacheInterface::class);
$productService = $container->get(ProductService::class);
$productRepo = $container->get(ProductRepository::class);

// Set fake user session for logging
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'test_admin';

// Clean old test products if exist
$db->execute("DELETE FROM product_translations WHERE name LIKE 'Test Ürün%'");
$db->execute("DELETE FROM products WHERE sku LIKE 'TEST-%'");

// Test Case 1: Ürün Oluşturma ve Yeni Alanlar (is_deal, available_from, available_to, box_content, return_policy)
echo "[TEST 1] Sprint 13 alanlarıyla yeni ürün ekleniyor...\n";
try {
    $data = [
        'name' => 'Test Ürün Enterprise 2.0',
        'subtitle' => 'Sprint 13 Alt Başlık',
        'sku' => 'TEST-PROD-13',
        'barcode' => '8680000000013',
        'price' => 1250.00,
        'cost_price' => 750.00,
        'total_stock' => 100,
        'status' => 'published',
        'is_deal' => 1,
        'available_from' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'available_to' => date('Y-m-d H:i:s', strtotime('+30 days')),
        'box_content' => 'Test Cihazı, Şarj Aleti, Garanti Belgesi',
        'return_policy' => '14 gün koşulsuz iade garantisi.',
        'seo' => [
            'title' => 'SEO Test Ürün Başlığı',
            'description' => 'SEO Test Ürün Açıklaması',
            'keywords' => 'test, enterprise, sprint13',
            'canonical_url' => 'https://saintmonarc.com/test-urun-13',
            'og_title' => 'OG Title Test',
            'og_description' => 'OG Desc Test',
            'robots' => 'index, follow'
        ],
        'product_files' => [
            ['name' => 'Kullanım Kılavuzu', 'path' => '/uploads/documents/manual.pdf', 'file_type' => 'pdf']
        ]
    ];

    $productId = $productService->create($data);
    echo "SUCCESS: Ürün başarıyla oluşturuldu. ID: {$productId}\n";

    // DB'den oku ve alanları doğrula
    $prod = $productRepo->getById($productId, true);
    if ($prod) {
        assert((int)$prod['is_deal'] === 1, "is_deal eşleşmedi");
        assert($prod['box_content'] === 'Test Cihazı, Şarj Aleti, Garanti Belgesi', "box_content eşleşmedi");
        assert($prod['return_policy'] === '14 gün koşulsuz iade garantisi.', "return_policy eşleşmedi");
        echo "SUCCESS: Sprint 13 alanları DB'ye başarıyla yazıldı.\n";
    } else {
        throw new Exception("Ürün DB'den çekilemedi.");
    }
} catch (Exception $e) {
    echo "ERROR: Test 1 Başarısız: " . $e->getMessage() . "\n";
    exit(1);
}

// Test Case 2: Ürün Güncelleme ve Kâr Matrisi
echo "\n[TEST 2] Kâr marjı hesaplamaları ve ürün güncellemesi test ediliyor...\n";
try {
    $prod = $productRepo->getById($productId, true);
    $updateData = array_merge($prod, [
        'price' => 1500.00, // Maliyet: 750ydi. Kâr: 750, Marj: 50%
        'cost_price' => 750.00,
        'box_content' => 'Güncellenmiş Kutu İçeriği',
        'return_policy' => 'Güncellenmiş İade Koşulu'
    ]);
    
    $productService->update($productId, $updateData);
    
    $updatedProd = $productRepo->getById($productId, true);
    assert((float)$updatedProd['profit'] === 750.00, "Profit hesaplaması yanlış");
    assert((float)$updatedProd['profit_margin'] === 50.00, "Profit margin hesaplaması yanlış");
    assert($updatedProd['box_content'] === 'Güncellenmiş Kutu İçeriği', "box_content güncellenemedi");
    echo "SUCCESS: Ürün güncelleme ve kâr hesaplama matrisi başarıyla doğrulandı.\n";
} catch (Exception $e) {
    echo "ERROR: Test 2 Başarısız: " . $e->getMessage() . "\n";
    exit(1);
}

// Test Case 3: Cache Temizleme Doğrulaması
echo "\n[TEST 3] Cache temizleme mekanizmaları doğrulanıyor...\n";
try {
    // Set dummy cache for categories and brands
    $cache->set('category_tree', ['dummy_category'], 3600);
    $cache->set('active_brands', ['dummy_brand'], 3600);

    // Call clearCache via ProductService
    $productService->clearCache();

    assert(!$cache->has('category_tree'), "Kategori cache temizlenemedi");
    assert(!$cache->has('active_brands'), "Marka cache temizlenemedi");
    assert(!$cache->has('active_products_list'), "Ürün listesi cache temizlenemedi");
    echo "SUCCESS: Ürün, Kategori ve Marka cacheleri otomatik temizlendi.\n";
} catch (Exception $e) {
    echo "ERROR: Test 3 Başarısız: " . $e->getMessage() . "\n";
    exit(1);
}

// Test Case 4: XML / JSON / Excel İçe Aktarım Parsingi ve Eşleştirme Testleri
echo "\n[TEST 4] CSV / JSON / XML / Excel İçe Aktarım parsingleri test ediliyor...\n";
try {
    $tempDir = ROOT_DIR . '/public/uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // 4.1 JSON Parse
    $jsonFile = $tempDir . '/test_import.json';
    $jsonData = [
        [
            'UrunAdi' => 'Test Ürün JSON 1',
            'StokKodu' => 'TEST-JSON-1',
            'SatisFiyati' => 99.99,
            'StokAdedi' => 50,
            'BarkodNo' => '8680000000099'
        ]
    ];
    file_put_contents($jsonFile, json_encode($jsonData));

    $headers = $productService->parseHeaders($jsonFile, 'json');
    assert(in_array('UrunAdi', $headers), "JSON Header ayrıştırılamadı");

    $mapping = [
        'name' => 'UrunAdi',
        'sku' => 'StokKodu',
        'price' => 'SatisFiyati',
        'total_stock' => 'StokAdedi',
        'barcode' => 'BarkodNo'
    ];
    $res = $productService->importMappedData($jsonFile, 'json', $mapping);
    echo "SUCCESS: JSON map import edildi: Eklenen {$res['imported']}, Güncellenen {$res['updated']}\n";
    
    // Cleanup JSON file
    @unlink($jsonFile);

    // 4.2 XML Parse
    $xmlFile = $tempDir . '/test_import.xml';
    $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
    <products>
        <product>
            <title>Test Ürün XML 1</title>
            <sku_code>TEST-XML-1</sku_code>
            <price_val>299.90</price_val>
            <qty>10</qty>
            <barcode>8680000000100</barcode>
        </product>
    </products>';
    file_put_contents($xmlFile, $xmlContent);

    $xmlHeaders = $productService->parseHeaders($xmlFile, 'xml');
    assert(in_array('title', $xmlHeaders), "XML Header ayrıştırılamadı");

    $xmlMapping = [
        'name' => 'title',
        'sku' => 'sku_code',
        'price' => 'price_val',
        'total_stock' => 'qty',
        'barcode' => 'barcode'
    ];
    $resXml = $productService->importMappedData($xmlFile, 'xml', $xmlMapping);
    echo "SUCCESS: XML map import edildi: Eklenen {$resXml['imported']}, Güncellenen {$resXml['updated']}\n";
    
    // Cleanup XML file
    @unlink($xmlFile);
} catch (Exception $e) {
    echo "ERROR: Test 4 Başarısız: " . $e->getMessage() . "\n";
    exit(1);
}

// Clean up
$db->execute("DELETE FROM product_translations WHERE name LIKE 'Test Ürün%'");
$db->execute("DELETE FROM products WHERE sku LIKE 'TEST-%'");

echo "\n==================================================\n";
echo "TÜM ÜRÜN YÖNETİMİ 2.0 CLI TESTLERİ BAŞARIYLA TAMAMLANDI!\n";
echo "==================================================\n";
