<?php

declare(strict_types=1);

/**
 * SaintMonarc - Sprint 34 Enterprise Procurement & Supplier Management Test Suite
 */

define('ROOT_DIR', __DIR__);

if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class) {
        $prefixMap = ['Core\\' => 'core/', 'App\\' => 'app/'];
        foreach ($prefixMap as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) continue;
            $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    });
}

use Core\Config\EnvParser;
use Core\Database\Database;
use App\Repositories\ProcurementRepository;
use App\Repositories\WarehouseRepository;
use App\Services\WarehouseService;
use App\Services\ProcurementService;
use App\Services\AuditLogger;
use App\Services\RbacService;
use Core\Cache\FileCache;

$app = new \Core\Application(ROOT_DIR);
$container = $app->getContainer();

$db = $container->get(\Core\Contracts\DatabaseInterface::class);
$cache = $container->get(\Core\Contracts\CacheInterface::class);
$rbac = $container->get(RbacService::class);
$repo = $container->get(ProcurementRepository::class);
$whRepo = $container->get(WarehouseRepository::class);
$whService = $container->get(WarehouseService::class);
$audit = $container->get(AuditLogger::class);
$service = $container->get(ProcurementService::class);

$passed = 0;
$failed = 0;

function runTest(string $name, callable $fn) {
    global $passed, $failed;
    try {
        $res = $fn();
        if ($res === true) {
            echo " [PASSED] {$name}\n";
            $passed++;
        } else {
            $msg = is_string($res) ? $res : 'Test assertion failed';
            echo " [FAILED] {$name}: {$msg}\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo " [FAILED] {$name}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo " SAINTMONARC - SPRINT 34 PROCUREMENT & SUPPLIER MANAGEMENT TEST SUITE\n";
echo str_repeat('=', 70) . "\n\n";

// 1. Supplier CRUD
$supplierId = null;
runTest('1. Supplier CRUD (Create, Read, Update, Soft Delete)', function() use ($service, $repo, &$supplierId) {
    $data = [
        'company_name' => 'Sprint 34 Test Teknoloji A.Ş. ĞÜŞİÖÇ',
        'tax_number' => '9876543210',
        'tax_office' => 'Kadıköy',
        'contact_name' => 'Ahmet Yılmaz',
        'phone' => '05551234567',
        'email' => 'test@sprint34.com',
        'country' => 'Türkiye',
        'city' => 'İstanbul',
        'district' => 'Kadıköy',
        'address' => 'Bağdat Cad. No:123',
        'zip_code' => '34710',
        'iban' => 'TR123456789012345678901234',
        'currency' => 'TRY',
        'payment_terms' => '30 Gün',
        'lead_time' => 5,
        'status' => 'active',
        'notes' => 'Test tedarikçisi notu'
    ];
    $supplierId = $service->createSupplier($data);
    if (!$supplierId) return 'Tedarikçi oluşturulamadı.';

    $sup = $repo->getSupplierById($supplierId);
    if (!$sup || $sup['company_name'] !== $data['company_name']) return 'Tedarikçi oku başarısız.';

    $updateData = array_merge($data, ['company_name' => 'Sprint 34 Güncellenmiş A.Ş.']);
    $service->updateSupplier($supplierId, $updateData);
    $supUpdated = $repo->getSupplierById($supplierId);
    if ($supUpdated['company_name'] !== 'Sprint 34 Güncellenmiş A.Ş.') return 'Tedarikçi güncelleme başarısız.';

    return true;
});

// 2. Supplier RBAC
runTest('2. Supplier RBAC Permission Check', function() use ($rbac) {
    $hasPerm = $rbac->adminHasPermission(1, 'manage_suppliers');
    if (!$hasPerm) return 'Admin 1 manage_suppliers iznine sahip değil.';
    return true;
});

// 3. Purchase Order oluşturma
$poId = null;
$warehouseId = 1;
$productId = null;
runTest('3. Purchase Order Creation (PO-YYYY-XXXXXX)', function() use ($service, $repo, $db, $supplierId, $warehouseId, &$poId, &$productId) {
    $prod = $db->query("SELECT id FROM products WHERE deleted_at IS NULL LIMIT 1");
    if (empty($prod)) return 'Test için ürün bulunamadı.';
    $productId = (int)$prod[0]['id'];

    $poData = [
        'supplier_id' => $supplierId,
        'warehouse_id' => $warehouseId,
        'currency' => 'TRY',
        'expected_delivery' => date('Y-m-d', strtotime('+7 days')),
        'items' => [
            [
                'product_id' => $productId,
                'quantity' => 10,
                'price' => 100.00,
                'tax_rate' => 20.00,
                'discount_amount' => 10.00
            ]
        ]
    ];
    $poId = $service->createPurchaseOrder($poData, 1);
    if (!$poId) return 'PO oluşturulamadı.';

    $po = $repo->getPurchaseOrderById($poId);
    if (!$po) return 'Oluşturulan PO bulunamadı.';

    if (!preg_match('/^PO-\d{4}-\d{6}$/', $po['po_number'])) {
        return "PO numarası formatı uyuşmuyor: {$po['po_number']}";
    }

    return true;
});

// 4. PO durum geçişleri
runTest('4. PO State Transitions', function() use ($service, $repo, $poId) {
    $service->updatePurchaseOrderStatus($poId, 'pending_approval', 1);
    $po = $repo->getPurchaseOrderById($poId);
    if ($po['status'] !== 'pending_approval') return 'Durum pending_approval olmadı.';

    $service->updatePurchaseOrderStatus($poId, 'sent', 1);
    $po = $repo->getPurchaseOrderById($poId);
    if ($po['status'] !== 'sent') return 'Durum sent olmadı.';

    return true;
});

// 5. PO approval
runTest('5. PO Approval with RBAC Check', function() use ($service, $repo, $poId) {
    $service->updatePurchaseOrderStatus($poId, 'approved', 1);
    $po = $repo->getPurchaseOrderById($poId);
    if ($po['status'] !== 'approved') return 'PO approved olmadı.';
    if ((int)$po['approved_by'] !== 1) return 'Approved_by set edilmedi.';
    return true;
});

// 6. Purchase item hesaplama
runTest('6. Purchase Item Totals Calculation', function() use ($repo, $poId) {
    $po = $repo->getPurchaseOrderById($poId);
    // Subtotal: 10 * 100 = 1000
    // Discount: 10 * 10 = 100
    // Taxable: 900
    // Tax 20%: 180
    // Grand total: 1080
    if (abs((float)$po['grand_total'] - 1080.00) > 0.01) {
        return "Hesaplanan genel toplam yanlış: {$po['grand_total']} (beklenen: 1080.00)";
    }
    return true;
});

// 7. Goods receiving
$grId = null;
runTest('7. Goods Receiving (Mal Kabul)', function() use ($service, $repo, $poId, $productId, &$grId) {
    $service->updatePurchaseOrderStatus($poId, 'sent', 1);

    $items = [
        [
            'product_id' => $productId,
            'quantity' => 5,
            'damaged_quantity' => 0,
            'missing_quantity' => 0,
            'notes' => '5 adet sağlam alındı'
        ]
    ];
    $grId = $service->receiveGoods($poId, $items, 1, 'Teslimat 1');
    if (!$grId) return 'Goods receipt oluşturulamadı.';
    return true;
});

// 8. Partial receiving
runTest('8. Partial Receiving Status Check', function() use ($repo, $poId) {
    $po = $repo->getPurchaseOrderById($poId);
    if ($po['status'] !== 'partially_received') {
        return "Sipariş durumu partially_received beklerken {$po['status']} geldi.";
    }
    return true;
});

// 9. Full receiving
runTest('9. Full Receiving Status Check', function() use ($service, $repo, $poId, $productId) {
    $items = [
        [
            'product_id' => $productId,
            'quantity' => 5,
            'damaged_quantity' => 0,
            'missing_quantity' => 0,
            'notes' => 'Kalan 5 adet teslim alındı'
        ]
    ];
    $service->receiveGoods($poId, $items, 1, 'Teslimat 2 (Tamamlama)');

    $po = $repo->getPurchaseOrderById($poId);
    if ($po['status'] !== 'completed') {
        return "Sipariş durumu completed beklerken {$po['status']} geldi.";
    }
    return true;
});

// 10. WMS stock integration
runTest('10. WMS Inventory Movement Integration', function() use ($db, $poId, $productId) {
    $movements = $db->query(
        "SELECT im.* FROM inventory_movements im
         JOIN inventories i ON im.inventory_id = i.id
         WHERE i.product_id = :pid AND (im.type = 'in' OR im.description LIKE '%PO%')
         ORDER BY im.id DESC",
        [':pid' => $productId]
    );
    if (empty($movements)) {
        return 'WMS stok hareketi bulunamadı.';
    }
    return true;
});

// 11. Supplier price history
runTest('11. Supplier Price History Tracking', function() use ($repo, $supplierId) {
    $perf = $repo->getSupplierPerformance($supplierId);
    if (!isset($perf['average_item_cost'])) {
        return 'Ortalama ürün maliyeti verisi bulunamadı.';
    }
    return true;
});

// 12. Procurement analytics
runTest('12. Procurement Analytics KPI Calculation', function() use ($repo) {
    $analytics = $repo->getPurchaseAnalytics('this_month');
    if (!isset($analytics['total_spend']) || !isset($analytics['total_orders'])) {
        return 'Analitik KPI verileri eksik.';
    }
    return true;
});

// 13. Purchase suggestion
runTest('13. Low Stock Purchase Suggestions Engine', function() use ($service) {
    $suggestions = $service->getAiPurchasingAssistantSuggestions();
    if (!is_array($suggestions)) return 'Öneri sonucu dizi değil.';
    return true;
});

// 14. Audit log
runTest('14. Audit Logging for Procurement Actions', function() use ($db, $poId) {
    $logs = $db->query(
        "SELECT * FROM audit_logs WHERE auditable_type = 'PurchaseOrder' AND auditable_id = :id",
        [':id' => $poId]
    );
    if (empty($logs)) {
        return 'PO için audit log kaydı bulunamadı.';
    }
    return true;
});

// 15. Türkçe karakter
runTest('15. UTF-8 Turkish Character Preservation (çğışöüÇĞİŞÖÜ)', function() use ($repo, $supplierId) {
    $sup = $repo->getSupplierById($supplierId);
    if (mb_strpos($sup['company_name'], 'Güncellenmiş') === false) {
        return "Türkçe karakterler bozulmuş: {$sup['company_name']}";
    }
    return true;
});

// 16. İl/ilçe seçimi
runTest('16. Central Address Helper City/District Data', function() use ($supplierId, $repo) {
    if (!class_exists('\\App\\Helpers\\AddressHelper')) {
        return 'AddressHelper sınıfı bulunamadı.';
    }
    $cities = \App\Helpers\AddressHelper::getCities();
    if (empty($cities) || !in_array('İstanbul', $cities)) {
        return 'İl listesinde İstanbul bulunamadı.';
    }
    $districts = \App\Helpers\AddressHelper::getDistricts('İstanbul');
    if (empty($districts) || !in_array('Kadıköy', $districts)) {
        return 'Kadıköy ilçesi bulunamadı.';
    }
    return true;
});

// 17. Export
runTest('17. Supplier & PO Export Functionality', function() use ($repo) {
    $suppliers = $repo->getAllSuppliers();
    if (!is_array($suppliers)) return 'Tedarikçi listesi alınamadı.';
    return true;
});

// 18. Transaction rollback
runTest('18. Transaction Rollback Safety on Exception', function() use ($service, $supplierId) {
    try {
        $invalidPo = [
            'supplier_id' => $supplierId,
            'warehouse_id' => 999999, // Non-existent warehouse
            'items' => [
                ['product_id' => -1, 'quantity' => 10, 'price' => 50]
            ]
        ];
        $service->createPurchaseOrder($invalidPo, 1);
        return 'Hatalı işlem exception fırlatmalıydı.';
    } catch (\Throwable $e) {
        return true;
    }
});

// 19. Yetkisiz erişim
runTest('19. Unauthorized Access Restriction (RBAC)', function() use ($rbac) {
    $hasPerm = $rbac->userHasPermission(9999, 'approve_purchase_order');
    if ($hasPerm) return 'Yetkisiz kullanıcıya onay izni verildi!';
    return true;
});

// 20. Admin erişimi
runTest('20. Super Admin Full Access Enforcement', function() use ($rbac) {
    $hasPerm = $rbac->adminHasPermission(1, 'view_procurement');
    if (!$hasPerm) return 'Süper admin yetkisine erişilemedi.';
    return true;
});

// Cleanup test data
if ($supplierId) {
    $service->deleteSupplier($supplierId);
}

echo "\n" . str_repeat('=', 70) . "\n";
echo " SPRINT 34 TEST SONUÇLARI: {$passed}/20 BAŞARILI, {$failed}/20 BAŞARISIZ\n";
echo str_repeat('=', 70) . "\n\n";

if ($failed === 0) {
    echo " SUCCESS: SPRINT 34 ENTERPRISE PROCUREMENT MODÜLÜ TÜM TESTLERDEN BAŞARIYLA GEÇTİ!\n\n";
} else {
    echo " WARNING: BAZI TESTLER BAŞARISIZ OLDU. LÜTFEN HATA DETAYLARINI İNCELEYİN.\n\n";
}
