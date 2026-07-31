<?php

declare(strict_types=1);

/**
 * Sprint 33 - Enterprise Procurement & Supplier Management - CLI Verification Test Suite
 */

define('ROOT_DIR', __DIR__);

// Autoload loader
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class) {
        $prefixMap = ['Core\\' => 'core/', 'App\\' => 'app/'];
        foreach ($prefixMap as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) continue;
            $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) { require $file; return; }
        }
    });
}

use Core\Application;
use Core\Contracts\DatabaseInterface;
use App\Repositories\ProcurementRepository;
use App\Services\ProcurementService;
use App\Services\WarehouseService;
use App\Services\AuditLogger;
use App\Repositories\WarehouseRepository;
use App\Repositories\ProductRepository;
use App\Controllers\ProcurementController;

$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$db = $container->get(DatabaseInterface::class);

$passed = 0;
$failed = 0;

function test(string $name, callable $fn) {
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "  [PASS] {$name}\n";
            $passed++;
        } else {
            echo "  [FAIL] {$name}: " . (is_string($result) ? $result : json_encode($result)) . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "  [FAIL] {$name}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo " SPRINT 33 - ENTERPRISE PROCUREMENT SYSTEM CLI VERIFICATION\n";
echo str_repeat('=', 60) . "\n\n";

// --- TEST 1: Database Tables Verification ---
echo "[1/6] Database Schema Check...\n";

$tablesToCheck = [
    'suppliers',
    'purchase_orders',
    'purchase_order_items',
    'rfqs',
    'rfq_responses',
    'goods_receipts',
    'goods_receipt_items',
    'supplier_contracts',
    'supplier_documents',
    'supplier_payments'
];

foreach ($tablesToCheck as $table) {
    test("Table '{$table}' exists in database", function() use ($db, $table) {
        $stmt = $db->query("SHOW TABLES LIKE '" . $table . "'");
        return count($stmt) > 0 ? true : "Table '{$table}' not found in database schema";
    });
}

// --- TEST 2: RBAC Permissions Check ---
echo "\n[2/6] RBAC & Permissions Check...\n";

$permissions = [
    'view_procurement',
    'manage_procurement',
    'approve_purchase_orders',
    'manage_suppliers',
    'manage_rfq',
    'receive_goods',
    'view_purchase_analytics',
    'manage_supplier_contracts'
];

foreach ($permissions as $perm) {
    test("Permission '{$perm}' is registered in permissions table", function() use ($db, $perm) {
        $stmt = $db->query("SELECT id FROM permissions WHERE name = :name LIMIT 1", [':name' => $perm]);
        return count($stmt) > 0 ? true : "Permission '{$perm}' not found";
    });
}

// --- TEST 3: Supplier Management CRUD Test ---
echo "\n[3/6] Supplier Management CRUD Test...\n";

$testSupplierId = null;
$supplierData = [
    'company_name' => 'Çağrı Dış Ticaret A.Ş.',
    'tax_number' => '3300112233',
    'tax_office' => 'İkitelli VD',
    'contact_name' => 'Çağrı Bey',
    'phone' => '+90 555 123 45 67',
    'email' => 'cagri@cagritrade.com',
    'country' => 'Türkiye',
    'city' => 'İstanbul',
    'currency' => 'TRY',
    'payment_terms' => '30 Days',
    'lead_time' => 7,
    'score' => 4.8
];

test("Create supplier with Turkish character name", function() use ($db, $supplierData, &$testSupplierId) {
    $auditLogger = new AuditLogger($db);
    $warehouseService = new WarehouseService(new WarehouseRepository($db), $db, $auditLogger);
    $procRepository = new ProcurementRepository($db);
    $service = new ProcurementService($procRepository, $db, $warehouseService, $auditLogger);
    
    $testSupplierId = $service->createSupplier($supplierData);
    return $testSupplierId > 0 ? true : "Supplier ID not returned";
});

test("Retrieve created supplier and verify UTF-8 content", function() use ($db, &$testSupplierId, $supplierData) {
    $procRepository = new ProcurementRepository($db);
    $sup = $procRepository->getSupplierById($testSupplierId);
    if (!$sup) return "Supplier not retrieved";
    if ($sup['company_name'] !== $supplierData['company_name']) return "Company name mismatch";
    if ($sup['contact_name'] !== $supplierData['contact_name']) return "Contact name mismatch";
    return true;
});

test("Update supplier information", function() use ($db, &$testSupplierId) {
    $auditLogger = new AuditLogger($db);
    $warehouseService = new WarehouseService(new WarehouseRepository($db), $db, $auditLogger);
    $procRepository = new ProcurementRepository($db);
    $service = new ProcurementService($procRepository, $db, $warehouseService, $auditLogger);
    
    $updated = $service->updateSupplier($testSupplierId, [
        'company_name' => 'Çağrı Dış Ticaret A.Ş. Yeni',
        'tax_number' => '3300112233',
        'tax_office' => 'İkitelli VD',
        'contact_name' => 'Çağrı Bey',
        'phone' => '+90 555 123 45 67',
        'email' => 'cagri@cagritrade.com',
        'country' => 'Türkiye',
        'city' => 'İstanbul',
        'currency' => 'TRY',
        'payment_terms' => '45 Days',
        'lead_time' => 5,
        'is_active' => 1,
        'score' => 4.9
    ]);
    
    if (!$updated) return "Update method failed";
    
    $sup = $procRepository->getSupplierById($testSupplierId);
    return $sup['payment_terms'] === '45 Days' ? true : "Update verification failed";
});

// --- TEST 4: Purchase Order and WMS Integration ---
echo "\n[4/6] Purchase Order & WMS Stock Integration Check...\n";

$testPoId = null;
$testProductId = null;
$testWarehouseId = null;

test("Create Purchase Order with items", function() use ($db, $testSupplierId, &$testPoId, &$testProductId, &$testWarehouseId) {
    $auditLogger = new AuditLogger($db);
    $warehouseService = new WarehouseService(new WarehouseRepository($db), $db, $auditLogger);
    $procRepository = new ProcurementRepository($db);
    $service = new ProcurementService($procRepository, $db, $warehouseService, $auditLogger);
    
    // Get valid product and warehouse
    $prod = $db->query("SELECT id FROM products WHERE deleted_at IS NULL LIMIT 1")[0] ?? null;
    $wh = $db->query("SELECT id FROM warehouses WHERE is_active = 1 LIMIT 1")[0] ?? null;
    
    if (!$prod || !$wh) return "No products or warehouses available in DB to link";
    
    $testProductId = (int)$prod['id'];
    $testWarehouseId = (int)$wh['id'];
    
    $poData = [
        'supplier_id' => $testSupplierId,
        'warehouse_id' => $testWarehouseId,
        'currency' => 'TRY',
        'expected_delivery' => date('Y-m-d', strtotime('+5 days')),
        'items' => [
            [
                'product_id' => $testProductId,
                'quantity' => 10,
                'price' => 120.00,
                'tax_rate' => 20.00,
                'discount_amount' => 10.00
            ]
        ]
    ];
    
    $testPoId = $service->createPurchaseOrder($poData, 1);
    return $testPoId > 0 ? true : "PO ID not returned";
});

test("Retrieve PO and verify draft status", function() use ($db, &$testPoId) {
    $procRepository = new ProcurementRepository($db);
    $po = $procRepository->getPurchaseOrderById($testPoId);
    if (!$po) return "PO not found";
    return $po['status'] === 'draft' ? true : "Status is not draft: {$po['status']}";
});

test("Approve PO and set status to sent", function() use ($db, &$testPoId) {
    $auditLogger = new AuditLogger($db);
    $warehouseService = new WarehouseService(new WarehouseRepository($db), $db, $auditLogger);
    $procRepository = new ProcurementRepository($db);
    $service = new ProcurementService($procRepository, $db, $warehouseService, $auditLogger);
    
    $service->updatePurchaseOrderStatus($testPoId, 'approved', 1);
    $po1 = $procRepository->getPurchaseOrderById($testPoId);
    if ($po1['status'] !== 'approved') return "PO status is not approved";
    
    $service->updatePurchaseOrderStatus($testPoId, 'sent', 1);
    $po2 = $procRepository->getPurchaseOrderById($testPoId);
    return $po2['status'] === 'sent' ? true : "PO status is not sent";
});

test("WMS Stock Integration: Perform goods receipt and check stocks adjust", function() use ($db, &$testPoId, $testProductId, $testWarehouseId) {
    $auditLogger = new AuditLogger($db);
    $warehouseService = new WarehouseService(new WarehouseRepository($db), $db, $auditLogger);
    $procRepository = new ProcurementRepository($db);
    $service = new ProcurementService($procRepository, $db, $warehouseService, $auditLogger);
    
    // Get initial stock inside warehouse
    $initialStockRow = $db->query(
        "SELECT stock FROM inventories WHERE product_id = :pid AND warehouse_id = :wid LIMIT 1",
        [':pid' => $testProductId, ':wid' => $testWarehouseId]
    )[0] ?? null;
    $initialStock = $initialStockRow ? (int)$initialStockRow['stock'] : 0;
    
    // Perform Goods Receipt (Mal Kabul)
    $receiptItems = [
        [
            'product_id' => $testProductId,
            'quantity' => 10,
            'damaged_quantity' => 2, // 2 items damaged, 8 items accepted into stock
            'missing_quantity' => 0,
            'lot_number' => 'LOT-33-01',
            'serial_number' => 'SN-33-001',
            'expire_date' => date('Y-m-d', strtotime('+365 days'))
        ]
    ];
    
    $grId = $service->receiveGoods($testPoId, $receiptItems, 1, 'Kısmi mal kabul, 2 adet hasarlı kolide geldi.');
    if ($grId <= 0) return "Goods receipt headers not created";
    
    // Check stock was incremented by net accepted good count (8)
    $newStockRow = $db->query(
        "SELECT stock FROM inventories WHERE product_id = :pid AND warehouse_id = :wid LIMIT 1",
        [':pid' => $testProductId, ':wid' => $testWarehouseId]
    )[0] ?? null;
    $newStock = $newStockRow ? (int)$newStockRow['stock'] : 0;
    
    if ($newStock !== ($initialStock + 8)) {
        return "Stock not correctly incremented in WMS. Initial: {$initialStock}, New: {$newStock}, Expected: " . ($initialStock + 8);
    }
    
    // Check PO status was updated to completed
    $po = $procRepository->getPurchaseOrderById($testPoId);
    return $po['status'] === 'completed' ? true : "PO status not set to completed. Current: {$po['status']}";
});

// --- TEST 5: RFQ AI Bids Comparison ---
echo "\n[5/6] RFQ AI Comparison Test...\n";

$testRfqId = null;

test("Create RFQ Request", function() use ($db, $testProductId, &$testRfqId) {
    $auditLogger = new AuditLogger($db);
    $warehouseService = new WarehouseService(new WarehouseRepository($db), $db, $auditLogger);
    $procRepository = new ProcurementRepository($db);
    $service = new ProcurementService($procRepository, $db, $warehouseService, $auditLogger);
    
    $testRfqId = $service->createRFQ([
        'product_id' => $testProductId,
        'quantity' => 50,
        'title' => 'Deri Ayakkabı Alımı',
        'description' => 'Yüksek kalite deri kışlık ayakkabı sipariş teklif toplama.'
    ]);
    
    return $testRfqId > 0 ? true : "RFQ ID not returned";
});

test("Submit multiple supplier bids and check AI recommendation", function() use ($db, $testRfqId, $testSupplierId) {
    $auditLogger = new AuditLogger($db);
    $warehouseService = new WarehouseService(new WarehouseRepository($db), $db, $auditLogger);
    $procRepository = new ProcurementRepository($db);
    $service = new ProcurementService($procRepository, $db, $warehouseService, $auditLogger);
    
    // First bid: ₺100.00 price, 10 days delivery
    $service->submitRFQResponse([
        'rfq_id' => $testRfqId,
        'supplier_id' => $testSupplierId,
        'price' => 100.00,
        'delivery_lead_time' => 10
    ]);
    
    // Create secondary supplier for comparison
    $secSupplierId = $service->createSupplier([
        'company_name' => 'Şule Tekstil San. Tic.',
        'tax_number' => '4400112233',
        'tax_office' => 'Göztepe VD',
        'contact_name' => 'Şule Hanım',
        'phone' => '+90 555 987 65 43',
        'email' => 'sule@suletextile.com',
        'country' => 'Türkiye',
        'city' => 'İstanbul',
        'currency' => 'TRY',
        'payment_terms' => 'Cash',
        'lead_time' => 3,
        'score' => 4.5
    ]);
    
    // Second bid: ₺120.00 price, 4 days delivery
    $service->submitRFQResponse([
        'rfq_id' => $testRfqId,
        'supplier_id' => $secSupplierId,
        'price' => 120.00,
        'delivery_lead_time' => 4
    ]);
    
    // Compare RFQ
    $compareResult = $service->compareRFQ($testRfqId);
    
    if (empty($compareResult['responses'])) return "No bids found in comparison results";
    if ($compareResult['cheapest']['supplier_id'] !== $testSupplierId) return "Cheapest calculation wrong";
    if ($compareResult['fastest']['supplier_id'] !== $secSupplierId) return "Fastest calculation wrong";
    if (!$compareResult['ai_recommended']) return "AI recommended bid is empty";
    
    // Clean up second supplier
    $service->deleteSupplier($secSupplierId);
    return true;
});

// --- TEST 6: Cleanup Test Data ---
echo "\n[6/6] Cleanup Test Data...\n";

test("Delete temporary PO, RFQ and Supplier", function() use ($db, $testPoId, $testRfqId, $testSupplierId) {
    // Delete payments
    $db->execute("DELETE FROM supplier_payments WHERE purchase_order_id = :id", [':id' => $testPoId]);
    // Delete goods receipt items & goods receipts
    $gr = $db->query("SELECT id FROM goods_receipts WHERE purchase_order_id = :id", [':id' => $testPoId]);
    foreach ($gr as $g) {
        $db->execute("DELETE FROM goods_receipt_items WHERE goods_receipt_id = :id", [':id' => (int)$g['id']]);
    }
    $db->execute("DELETE FROM goods_receipts WHERE purchase_order_id = :id", [':id' => $testPoId]);
    // Delete PO items & PO
    $db->execute("DELETE FROM purchase_order_items WHERE purchase_order_id = :id", [':id' => $testPoId]);
    $db->execute("DELETE FROM purchase_orders WHERE id = :id", [':id' => $testPoId]);
    // Delete RFQ responses & RFQ
    $db->execute("DELETE FROM rfq_responses WHERE rfq_id = :id", [':id' => $testRfqId]);
    $db->execute("DELETE FROM rfqs WHERE id = :id", [':id' => $testRfqId]);
    // Delete supplier
    $db->execute("DELETE FROM suppliers WHERE id = :id", [':id' => $testSupplierId]);
    
    return true;
});

// --- FINAL VERIFICATION ---
echo "\n" . str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo " RESULT: {$passed}/{$total} tests PASSED" . ($failed > 0 ? ", {$failed} FAILED" : "") . "\n";
echo str_repeat('=', 60) . "\n\n";

if ($failed === 0) {
    echo "✅ SPRINT 33 PROCUREMENT VERIFICATION TESTS COMPLETED SUCCESSFULLY!\n\n";
    exit(0);
} else {
    echo "⚠️ Some tests failed. Please review execution log.\n\n";
    exit(1);
}
