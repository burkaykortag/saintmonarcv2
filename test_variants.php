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
use App\Services\VariantService;
use App\Repositories\VariantRepository;
use App\Services\AttributeService;
use App\Repositories\AttributeRepository;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;

echo "==================================================\n";
echo "SAINTMONARC SPRINT 12 VARIANT ENTERPRISE TESTS\n";
echo "==================================================\n";

$app = new Application(ROOT_DIR);
$container = $app->getContainer();

$db = $container->get(DatabaseInterface::class);
$cache = $container->get(CacheInterface::class);
$service = $container->get(VariantService::class);
$repository = $container->get(VariantRepository::class);
$attrService = $container->get(AttributeService::class);
$attrRepository = $container->get(AttributeRepository::class);

function assertTest(string $title, bool $expr, ?string $error = null) {
    if ($expr) {
        echo "[SUCCESS] - {$title}\n";
    } else {
        echo "[FAILED] - {$title}" . ($error ? " ({$error})" : "") . "\n";
        exit(1);
    }
}

// Clean variant data
$db->execute("DELETE FROM product_variant_prices");
$db->execute("DELETE FROM product_variant_stocks");
$db->execute("DELETE FROM product_variant_images");
$db->execute("DELETE FROM product_variant_options");
$db->execute("DELETE FROM product_variants");

// Seed temporary products if none exists
$prod = $db->query("SELECT id FROM products LIMIT 1");
$productId = !empty($prod) ? (int)$prod[0]['id'] : null;
if (!$productId) {
    $db->execute("INSERT INTO products (sku, slug) VALUES ('SM-PROD', 'test-urun')");
    $productId = (int)$db->lastInsertId();
    $db->execute("INSERT INTO product_translations (product_id, language_id, name) VALUES ({$productId}, 1, 'Deneme Ürünü')");
}

// Seed Renk and Beden Attributes
$db->execute("DELETE FROM attribute_value_translations");
$db->execute("DELETE FROM attribute_values");
$db->execute("DELETE FROM attribute_translations");
$db->execute("DELETE FROM attributes");

$renkId = $attrService->create([
    'code' => 'renk',
    'type' => 'select',
    'name' => 'Renk',
    'values' => [
        ['name' => 'Siyah', 'code' => 'blk'],
        ['name' => 'Beyaz', 'code' => 'wht']
    ]
]);

$bedenId = $attrService->create([
    'code' => 'beden',
    'type' => 'select',
    'name' => 'Beden',
    'values' => [
        ['name' => 'XS', 'code' => 'xs'],
        ['name' => 'S', 'code' => 's']
    ]
]);

// Test 1: Cartesian Product Combination Generation
try {
    $attributesMap = [
        $renkId => [
            $db->query("SELECT id FROM attribute_values WHERE attribute_id = :id AND code = 'blk'", [':id' => $renkId])[0]['id'],
            $db->query("SELECT id FROM attribute_values WHERE attribute_id = :id AND code = 'wht'", [':id' => $renkId])[0]['id']
        ],
        $bedenId => [
            $db->query("SELECT id FROM attribute_values WHERE attribute_id = :id AND code = 'xs'", [':id' => $bedenId])[0]['id'],
            $db->query("SELECT id FROM attribute_values WHERE attribute_id = :id AND code = 's'", [':id' => $bedenId])[0]['id']
        ]
    ];

    $combinations = $service->generateCombinations($productId, $attributesMap);
    assertTest("Combinations count is 4", count($combinations) === 4);
    assertTest("Combination 0 SKU formatted correct", str_contains($combinations[0]['sku'], 'BLK-XS'));
    assertTest("Combination 1 SKU formatted correct", str_contains($combinations[1]['sku'], 'BLK-S'));
    assertTest("Combination 2 SKU formatted correct", str_contains($combinations[2]['sku'], 'WHT-XS'));
    assertTest("Combination 3 SKU formatted correct", str_contains($combinations[3]['sku'], 'WHT-S'));

} catch (\Exception $e) {
    assertTest("Combinations generation failed", false, $e->getMessage());
}

// Test 2: Barcode Calculations
try {
    $ean13 = $service->generateEan13();
    assertTest("EAN13 Length correct", strlen($ean13) === 13);
    assertTest("EAN13 begins with Turkish prefix", str_starts_with($ean13, '869'));
    
    $ean8 = $service->generateEan8();
    assertTest("EAN8 Length correct", strlen($ean8) === 8);
    assertTest("EAN8 begins with Turkish prefix", str_starts_with($ean8, '869'));

    $c128 = $service->generateCode128();
    assertTest("Code128 generated", !empty($c128));

    $qr = $service->generateQrCode('https://saintmonarc.com');
    assertTest("QR Code content set", $qr === 'https://saintmonarc.com');

} catch (\Exception $e) {
    assertTest("Barcode calculations failed", false, $e->getMessage());
}

// Test 3: Create Variant, Price Matrix and Stock
try {
    $vData = [
        'product_id' => $productId,
        'sku' => 'SM-TEST-V-01',
        'barcode' => $ean13,
        'price' => 2500.00,
        'compare_at_price' => 3000.00,
        'cost_price' => 1500.00,
        'special_price' => 2200.00,
        'weight' => 1.2,
        'desi' => 2.5,
        'width' => 10.0,
        'height' => 15.0,
        'length' => 20.0,
        'stock' => 80,
        'is_active' => 1,
        'options' => [
            $renkId => $attributesMap[$renkId][0] // Siyah
        ]
    ];

    $variantId = $service->create($vData);
    assertTest("Variant Created successfully", $variantId > 0);

    // Read variant
    $var = $repository->getById($variantId);
    assertTest("Variant SKU correct", $var['sku'] === 'SM-TEST-V-01');
    assertTest("Variant price correct", (float)$var['price'] === 2500.00);
    assertTest("Variant special price correct", (float)$var['special_price'] === 2200.00);
    assertTest("Variant stock correct", (int)$var['total_stock'] === 80);
    assertTest("Variant options mapped correct", count($var['options']) === 1);
    assertTest("Variant value code is blk", $var['options'][0]['value_code'] === 'blk');

} catch (\Exception $e) {
    assertTest("Variant CRUD failed", false, $e->getMessage());
}

// Test 4: Stock Adjustment movement log
try {
    $var = $repository->getBySku('SM-TEST-V-01');
    $id = (int)$var['id'];

    // Add stock
    $service->adjustStock($id, 20, 'inlet', 'Yeni sevkiyat girişi');
    $updated = $repository->getById($id);
    assertTest("Variant stock adjusted correctly (100)", (int)$updated['total_stock'] === 100);

    $movements = $repository->getStockMovements($id);
    assertTest("Stock movement logged", count($movements) > 0);
    assertTest("Movement description matches", $movements[0]['description'] === 'Yeni sevkiyat girişi');

} catch (\Exception $e) {
    assertTest("Stock adjustment failed", false, $e->getMessage());
}

// Test 5: Excel & CSV Export Content Validation
try {
    $csv = $service->exportCsv();
    assertTest("CSV export contains SKU", str_contains($csv, 'SM-TEST-V-01'));
    
    $excel = $service->exportExcel();
    assertTest("Excel XML export contains Sheet header", str_contains($excel, 'ss:Name="Varyant Listesi"'));
    assertTest("Excel XML contains SKU", str_contains($excel, 'SM-TEST-V-01'));

} catch (\Exception $e) {
    assertTest("Export failed", false, $e->getMessage());
}

// Test 6: Bulk Operations
try {
    $var = $repository->getBySku('SM-TEST-V-01');
    $id = (int)$var['id'];

    // Bulk update price
    $service->bulkUpdatePrices([$id], 4000.00, 4500.00, 3800.00);
    $varPrice = $repository->getById($id);
    assertTest("Bulk price updated correct", (float)$varPrice['price'] === 4000.00);

    // Bulk update stock
    $service->bulkUpdateStocks([$id], 150);
    $varStock = $repository->getById($id);
    assertTest("Bulk stock updated correct", (int)$varStock['total_stock'] === 150);

} catch (\Exception $e) {
    assertTest("Bulk operations failed", false, $e->getMessage());
}

echo "==================================================\n";
echo "ALL SPRINT 12 VARIANT TESTS COMPLETED SUCCESSFULLY!\n";
echo "==================================================\n";
