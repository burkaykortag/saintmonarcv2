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
use App\Services\AttributeService;
use App\Repositories\AttributeRepository;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;

echo "==================================================\n";
echo "SAINTMONARC SPRINT 12 ATTRIBUTE ENTERPRISE TESTS\n";
echo "==================================================\n";

$app = new Application(ROOT_DIR);
$container = $app->getContainer();

$db = $container->get(DatabaseInterface::class);
$cache = $container->get(CacheInterface::class);
$service = $container->get(AttributeService::class);
$repository = $container->get(AttributeRepository::class);

function assertTest(string $title, bool $expr, ?string $error = null) {
    if ($expr) {
        echo "[SUCCESS] - {$title}\n";
    } else {
        echo "[FAILED] - {$title}" . ($error ? " ({$error})" : "") . "\n";
        exit(1);
    }
}

// Ensure clean state
$db->execute("DELETE FROM attribute_value_translations");
$db->execute("DELETE FROM attribute_values");
$db->execute("DELETE FROM attribute_translations");
$db->execute("DELETE FROM attributes");
$db->execute("DELETE FROM product_attribute_set_translations");
$db->execute("DELETE FROM product_attribute_set_items");
$db->execute("DELETE FROM product_attribute_sets");

// Test 1: Create Attribute with Turkish characters & Options
try {
    $data = [
        'code' => 'renk-secimi',
        'type' => 'color_picker',
        'name' => 'Gelişmiş Renk Seçimi',
        'translations' => [
            2 => ['name' => 'Advanced Color Selection']
        ],
        'values' => [
            ['name' => 'Fıstık Yeşili', 'code' => 'fistik-yesili', 'translations' => [2 => ['name' => 'Pistachio Green']]],
            ['name' => 'Nar Kırmızısı', 'code' => 'nar-kirmizisi', 'translations' => [2 => ['name' => 'Pomegranate Red']]]
        ]
    ];

    $attrId = $service->create($data);
    assertTest("Attribute Created successfully", $attrId > 0);

    // Verify Read
    $attr = $repository->getById($attrId);
    assertTest("Attribute code correct", $attr['code'] === 'renk-secimi');
    assertTest("Attribute translation correct", $attr['name'] === 'Gelişmiş Renk Seçimi');
    assertTest("Attribute value count is 2", count($attr['values']) === 2);
    
    // Check Turkish characters
    assertTest("Turkish character value correct", $attr['values'][0]['name'] === 'Fıstık Yeşili');
    assertTest("Value translations mapped", count($attr['values'][0]['translations']) === 2);
    
} catch (\Exception $e) {
    assertTest("Create Attribute failed", false, $e->getMessage());
}

// Test 2: Update Attribute
try {
    $attr = $repository->getByCode('renk-secimi');
    $id = (int)$attr['id'];
    $attr = $repository->getById($id);
    
    $updateData = [
        'code' => 'renk-secimi-guncel',
        'type' => 'select',
        'name' => 'Renk Seçeneği',
        'values' => [
            // Keep Pistachio Green but rename code and translation
            ['id' => $attr['values'][0]['id'], 'name' => 'Limon Sarısı', 'code' => 'limon-sarisi'],
            // Add Mavi
            ['name' => 'Deniz Mavisi', 'code' => 'deniz-mavisi']
        ]
    ];

    $service->update($id, $updateData);
    
    $updated = $repository->getById($id);
    assertTest("Attribute Updated code correct", $updated['code'] === 'renk-secimi-guncel');
    assertTest("Attribute Updated name correct", $updated['name'] === 'Renk Seçeneği');
    assertTest("Attribute Updated type correct", $updated['type'] === 'select');
    assertTest("Values synced correctly (count 2)", count($updated['values']) === 2);
    assertTest("Value updated name correct", $updated['values'][0]['name'] === 'Limon Sarısı');
    assertTest("Value added name correct", $updated['values'][1]['name'] === 'Deniz Mavisi');

} catch (\Exception $e) {
    assertTest("Update Attribute failed", false, $e->getMessage());
}

// Test 3: Attribute Sets Management
try {
    $attr = $repository->getByCode('renk-secimi-guncel');
    $setData = [
        'code' => 'ayakkabi-ozellikleri',
        'name' => 'Ayakkabı Özellik Grubu',
        'translations' => [
            2 => ['name' => 'Shoe Attribute Group']
        ],
        'attribute_ids' => [$attr['id']]
    ];

    $setId = $service->createSet($setData);
    assertTest("Attribute Set Created successfully", $setId > 0);

    $set = $repository->getSetById($setId);
    assertTest("Set code correct", $set['code'] === 'ayakkabi-ozellikleri');
    assertTest("Set translation correct", $set['name'] === 'Ayakkabı Özellik Grubu');
    assertTest("Set attributes populated", count($set['attributes']) === 1);
    assertTest("Set attribute maps correct", (int)$set['attributes'][0]['id'] === (int)$attr['id']);

} catch (\Exception $e) {
    assertTest("Attribute Set failed", false, $e->getMessage());
}

// Test 4: Delete Attribute
try {
    $attr = $repository->getByCode('renk-secimi-guncel');
    $service->delete((int)$attr['id']);
    
    $deleted = $repository->getById((int)$attr['id']);
    assertTest("Attribute soft deleted successfully", $deleted === null);

} catch (\Exception $e) {
    assertTest("Delete Attribute failed", false, $e->getMessage());
}

echo "==================================================\n";
echo "ALL SPRINT 12 ATTRIBUTE TESTS COMPLETED SUCCESSFULLY!\n";
echo "==================================================\n";
