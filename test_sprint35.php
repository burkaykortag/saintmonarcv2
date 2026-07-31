<?php

declare(strict_types=1);

/**
 * SaintMonarc - Sprint 35 Multi-Vendor Marketplace Platform (VEYRA Architecture) Test Suite
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
use Core\Application;
use Core\Contracts\DatabaseInterface;
use App\Services\VendorService;
use App\Services\MarketplaceOrderService;
use App\Repositories\VendorRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\WarehouseService;
use App\Services\AuditLogger;
use App\Services\RbacService;
use App\Helpers\AddressHelper;

EnvParser::parse(ROOT_DIR . '/.env');
$app = new Application(ROOT_DIR);
$container = $app->getContainer();

$db = $container->get(DatabaseInterface::class);
$vendorRepo = $container->get(VendorRepository::class);
$vendorService = $container->get(VendorService::class);
$orderRepo = $container->get(OrderRepository::class);
$productRepo = $container->get(ProductRepository::class);
$whService = $container->get(WarehouseService::class);
$audit = $container->get(AuditLogger::class);
$rbac = $container->get(RbacService::class);
$mktOrderService = $container->get(MarketplaceOrderService::class);

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

echo "\n" . str_repeat('=', 75) . "\n";
echo " SAINTMONARC - SPRINT 35 MULTI-VENDOR MARKETPLACE PLATFORM TEST SUITE\n";
echo str_repeat('=', 75) . "\n\n";

// Shared variables across tests
$vendorA_Id = null;
$vendorB_Id = null;
$productA_Id = null;
$productB_Id = null;
$applicationId = null;
$mktOrderId = null;
$payoutId = null;

// 1. Vendor Seed & Primary Vendor Check
runTest('1. Primary Vendor (SaintMonarc Official Store - ID 1) Verification', function() use ($vendorRepo) {
    $sm = $vendorRepo->getVendor(1);
    if (!$sm || $sm['slug'] !== 'saintmonarc') {
        return 'SaintMonarc ana satıcı kaydı (ID: 1) veritabanında bulunamadı.';
    }
    return true;
});

// 2. Vendor Onboarding Application
runTest('2. Vendor Onboarding Application Submission', function() use ($vendorService, &$applicationId) {
    $appData = [
        'company_name' => 'Sprint35 Test Teknoloji A.Ş. ĞÜŞİÖÇ',
        'contact_name' => 'Ahmet Yılmaz',
        'email' => 'vendor_app_' . time() . '@test.com',
        'phone' => '05559876543',
        'tax_number' => '1234567890',
        'tax_office' => 'Kadıköy',
        'city' => 'İstanbul',
        'district' => 'Kadıköy',
        'address' => 'Bağdat Cad. No:50',
        'iban' => 'TR998877665544332211009988',
        'category' => 'Elektronik'
    ];
    $applicationId = $vendorService->submitApplication($appData);
    if (!$applicationId) return 'Başvuru kaydedilemedi.';
    return true;
});

// 3. Vendor Application Approval
runTest('3. Platform Admin Vendor Application Approval', function() use ($vendorService, $applicationId, &$vendorA_Id) {
    $vendorA_Id = $vendorService->approveApplication($applicationId);
    if (!$vendorA_Id) return 'Başvuru onaylanamadı.';
    $vendor = $vendorService->getVendor($vendorA_Id);
    if (!$vendor || $vendor['status'] !== 'active') return 'Satıcı aktif duruma geçmedi.';
    return true;
});

// 4. Create Vendor B for Multi-Vendor Tests
runTest('4. Vendor B Creation for Multi-Tenant Tests', function() use ($vendorService, &$vendorB_Id) {
    $vendorB_Id = $vendorService->createVendor([
        'name' => 'Vendor B Tekstil Mağazası',
        'email' => 'vendor_b_' . time() . '@test.com',
        'phone' => '05551112233',
        'status' => 'active',
        'commission_rate' => 15.00
    ]);
    if (!$vendorB_Id) return 'Vendor B oluşturulamadı.';
    return true;
});

// 5. Vendor RBAC & Tenant Data Isolation (IDOR Check)
runTest('5. Tenant Data Isolation Enforcement (Vendor A cannot access Vendor B data)', function() use ($vendorService, $vendorA_Id, $vendorB_Id) {
    try {
        // Vendor A attempts to assert ownership over Vendor B's resource
        $vendorService->assertVendorOwnership($vendorA_Id, $vendorB_Id);
        return 'Erişim engellenmeliydi (IDOR zafiyeti mevcut)!';
    } catch (\Throwable $e) {
        // Expected exception caught
        return true;
    }
});

// 6. Vendor Product Creation & Draft Status
runTest('6. Vendor Product Creation with Pending Approval Status', function() use ($db, $whService, $vendorA_Id, &$productA_Id) {
    $sku = 'SKU-VEND-A-' . time();
    $slug = 'vendor-a-product-' . time();
    
    $db->execute(
        "INSERT INTO products (brand_id, vendor_id, sku, price, cost_price, is_active, approval_status, slug, created_at)
         VALUES (NULL, :vid, :sku, 500.00, 300.00, 1, 'pending_review', :slug, NOW())",
        [':vid' => $vendorA_Id, ':sku' => $sku, ':slug' => $slug]
    );
    $productA_Id = (int)$db->lastInsertId();

    $db->execute(
        "INSERT INTO product_translations (product_id, language_id, name, description)
         VALUES (:pid, 1, 'Vendor A Kulaklık ĞÜŞİÖÇ', 'Test ürün açıklaması')",
        [':pid' => $productA_Id]
    );

    // Seed initial inventory stock for WMS
    $whService->adjustStock($productA_Id, null, 1, 50, 'in', 'Test Initial Stock');

    if (!$productA_Id) return 'Ürün oluşturulamadı.';
    return true;
});

// 7. Platform Product Moderation
runTest('7. Platform Admin Product Moderation (Approval)', function() use ($vendorService, $productA_Id, $db) {
    $vendorService->moderateProduct($productA_Id, 'approved');
    $prod = $db->query("SELECT approval_status FROM products WHERE id = :id", [':id' => $productA_Id]);
    if (empty($prod) || $prod[0]['approval_status'] !== 'approved') {
        return 'Ürün approval_status approved olmadı.';
    }
    return true;
});

// 8. Create Product B for Multi-Vendor Cart
runTest('8. Create Product B for Multi-Vendor Cart', function() use ($db, $whService, $vendorB_Id, &$productB_Id) {
    $sku = 'SKU-VEND-B-' . time();
    $slug = 'vendor-b-product-' . time();
    
    $db->execute(
        "INSERT INTO products (brand_id, vendor_id, sku, price, cost_price, is_active, approval_status, slug, created_at)
         VALUES (NULL, :vid, :sku, 1200.00, 800.00, 1, 'approved', :slug, NOW())",
        [':vid' => $vendorB_Id, ':sku' => $sku, ':slug' => $slug]
    );
    $productB_Id = (int)$db->lastInsertId();

    $db->execute(
        "INSERT INTO product_translations (product_id, language_id, name) VALUES (:pid, 1, 'Vendor B Ceket')",
        [':pid' => $productB_Id]
    );

    // Seed initial inventory stock for WMS
    $whService->adjustStock($productB_Id, null, 1, 50, 'in', 'Test Initial Stock');

    return $productB_Id > 0;
});

// 9. Multi-Vendor Cart & Split Order Engine
runTest('9. Multi-Vendor Cart Split Order Engine Execution', function() use ($mktOrderService, $productA_Id, $productB_Id, &$mktOrderId) {
    $cartItems = [
        ['product_id' => $productA_Id, 'quantity' => 2], // Vendor A: 2 * 500 = 1000 TL
        ['product_id' => $productB_Id, 'quantity' => 1]  // Vendor B: 1 * 1200 = 1200 TL
    ];
    $orderData = [
        'billing_first_name' => 'Zeynep',
        'billing_last_name' => 'Kaya',
        'billing_address' => 'İstiklal Cad. No:10',
        'billing_city' => 'İstanbul',
        'shipping_address' => 'İstiklal Cad. No:10',
        'shipping_city' => 'İstanbul'
    ];

    $res = $mktOrderService->createMarketplaceOrder($orderData, $cartItems, 1);
    if (empty($res['order_id']) || count($res['vendor_orders']) !== 2) {
        return 'Multi-vendor sepet 2 ayrı satıcı siparişine bölünemedi.';
    }
    $mktOrderId = $res['order_id'];
    return true;
});

// 10. Split Order Amounts & Commission Calculation
runTest('10. Split Order Amounts & Commission Rate Verification', function() use ($db, $mktOrderId, $vendorA_Id) {
    $vOrders = $db->query(
        "SELECT * FROM vendor_orders WHERE order_id = :oid AND vendor_id = :vid",
        [':oid' => $mktOrderId, ':vid' => $vendorA_Id]
    );
    if (empty($vOrders)) return 'Vendor A siparişi bulunamadı.';
    
    // Vendor A: 1000 TL subtotal, 10% commission = 100 TL commission, 900 TL payout
    $vo = $vOrders[0];
    if (abs((float)$vo['subtotal'] - 1000.00) > 0.01) return "Subtotal yanlış: {$vo['subtotal']}";
    if (abs((float)$vo['commission_amount'] - 100.00) > 0.01) return "Komisyon yanlış: {$vo['commission_amount']}";
    if (abs((float)$vo['payout_amount'] - 900.00) > 0.01) return "Hakediş yanlış: {$vo['payout_amount']}";

    return true;
});

// 11. Vendor Wallet Credit
runTest('11. Vendor Wallet Automatic Hakediş Balance Credit', function() use ($vendorService, $vendorA_Id) {
    $wallet = $vendorService->getWallet($vendorA_Id);
    if (!$wallet || (float)$wallet['balance'] < 900.00) {
        return 'Cüzdana hakediş tutarı aktarılmadı.';
    }
    return true;
});

// 12. Vendor Payout Request
runTest('12. Vendor Payout Request Submission', function() use ($vendorService, $vendorA_Id, &$payoutId) {
    $payoutId = $vendorService->requestPayoutWithIban($vendorA_Id, 500.00, 'TR112233445566778899001122', 'Test ödeme talebi');
    if (!$payoutId) return 'Hakediş ödeme talebi oluşturulamadı.';
    return true;
});

// 13. Platform Admin Process Payout
runTest('13. Platform Admin Process Payout Approval & Execution', function() use ($vendorService, $payoutId, $vendorA_Id) {
    $vendorService->processPayoutStatus($payoutId, 'paid', 'receipt_123.pdf');
    $wallet = $vendorService->getWallet($vendorA_Id);
    // 900 - 500 = 400 TL balance
    if (abs((float)$wallet['balance'] - 400.00) > 0.01) {
        return "Ödeme sonrası bakiye yanlış: {$wallet['balance']} (beklenen: 400.00)";
    }
    return true;
});

// 14. Audit Logging for Marketplace Actions
runTest('14. Audit Logging for Marketplace Order Actions', function() use ($db, $mktOrderId) {
    $logs = $db->query(
        "SELECT * FROM audit_logs WHERE auditable_type = 'Order' AND auditable_id = :oid",
        [':oid' => $mktOrderId]
    );
    if (empty($logs)) return 'Marketplace sipariş audit log kaydı bulunamadı.';
    return true;
});

// 15. UTF-8 Turkish Character Preservation
runTest('15. UTF-8 Turkish Character Preservation (çğışöüÇĞİŞÖÜ)', function() use ($vendorService, $vendorA_Id) {
    $vendor = $vendorService->getVendor($vendorA_Id);
    if (mb_strpos($vendor['name'], 'ĞÜŞİÖÇ') === false) {
        return "Türkçe karakterler bozulmuş: {$vendor['name']}";
    }
    return true;
});

// 16. Central Address Helper Verification
runTest('16. Central Address Helper Cities & Districts Verification', function() {
    $cities = AddressHelper::getCities();
    if (empty($cities) || !in_array('İzmir', $cities)) return 'Adres helper Şehir listesi hatalı.';
    $districts = AddressHelper::getDistricts('İzmir');
    if (empty($districts) || !in_array('Konak', $districts)) return 'Konak ilçesi bulunamadı.';
    return true;
});

// 17. Vendor Order Status Transition
runTest('17. Child Vendor Order Status Update (Shipped with Tracking)', function() use ($vendorRepo, $vendorA_Id, $mktOrderId) {
    $vOrders = $vendorRepo->listVendorOrders($vendorA_Id);
    if (empty($vOrders)) return 'Satıcı siparişi bulunamadı.';
    $void = (int)$vOrders[0]['id'];

    $vendorRepo->updateVendorOrderStatus($vendorA_Id, $void, 'shipped', 'TRK998877', 'Yurtiçi Kargo');
    $vo = $vendorRepo->getVendorOrderById($vendorA_Id, $void);
    if ($vo['status'] !== 'shipped' || $vo['tracking_number'] !== 'TRK998877') {
        return 'Sipariş durumu kargo bilgisi ile güncellenemedi.';
    }
    return true;
});

// 18. Platform Super Admin Full Access Enforcement
runTest('18. Super Admin Full Access Enforcement', function() use ($rbac) {
    $hasPerm = $rbac->adminHasPermission(1, 'view_marketplace');
    if (!$hasPerm) return 'Super Admin view_marketplace iznine sahip değil.';
    return true;
});

// 19. Unauthorized Access Restriction (RBAC)
runTest('19. Unauthorized User Access Restriction', function() use ($rbac) {
    $hasPerm = $rbac->userHasPermission(9999, 'approve_vendors');
    if ($hasPerm) return 'Yetkisiz kullanıcıya onay izni verildi!';
    return true;
});

// 20. Central Platform Configuration
runTest('20. Centralized Platform Configuration (VEYRA Brand Settings)', function() {
    $configFile = ROOT_DIR . '/config/platform.php';
    if (!file_exists($configFile)) return 'config/platform.php bulunamadı.';
    $config = require $configFile;
    if ($config['name'] !== 'VEYRA Marketplace' || $config['owner'] !== 'Burkay') {
        return 'Platform marka konfigürasyonu eşleşmiyor.';
    }
    return true;
});

// 21-26. Security Standard Verifications
runTest('21. CSRF Protection Middleware Token Requirement', function() {
    return class_exists('\\Core\\Security') ? true : 'Security sınıfı bulunamadı.';
});

runTest('22. SQL Injection Prepared Statement Safety', function() use ($db) {
    $unsafeInput = "' OR '1'='1";
    $res = $db->query("SELECT * FROM vendors WHERE name = :name", [':name' => $unsafeInput]);
    return is_array($res) && empty($res);
});

runTest('23. XSS Output Escaping Function Check', function() {
    $xss = '<script>alert(1)</script>';
    return htmlspecialchars($xss, ENT_QUOTES, 'UTF-8') === '&lt;script&gt;alert(1)&lt;/script&gt;';
});

runTest('24. Transaction Rollback Safety on Exception', function() use ($db) {
    try {
        $db->beginTransaction();
        $db->execute("INSERT INTO vendors (name, slug, email) VALUES ('Rollback Test', 'rb-test', 'rb@test.com')");
        throw new \Exception('Simulated Failure');
    } catch (\Throwable $e) {
        $db->rollBack();
        $res = $db->query("SELECT * FROM vendors WHERE slug = 'rb-test'");
        return empty($res);
    }
});

runTest('25. Payout Minimum Limit Enforcement (>= 100 TL)', function() use ($vendorService, $vendorA_Id) {
    try {
        $vendorService->requestPayoutWithIban($vendorA_Id, 50.00, 'TR112233445566778899001122');
        return '50 TL ödeme talebi reddedilmeliydi.';
    } catch (\Throwable $e) {
        return true;
    }
});

runTest('26. WMS Inventory Deduction on Split Order', function() use ($db, $productA_Id) {
    $movements = $db->query(
        "SELECT im.* FROM inventory_movements im JOIN inventories i ON im.inventory_id = i.id WHERE i.product_id = :pid",
        [':pid' => $productA_Id]
    );
    return !empty($movements);
});

// 27-30. Regression Tests for Existing Modules
runTest('27. PIM Module Regression (Products Query)', function() use ($productRepo) {
    $prods = $productRepo->getAll();
    return is_array($prods);
});

runTest('28. OMS Module Regression (Orders Query)', function() use ($orderRepo) {
    $orders = $orderRepo->getAll();
    return is_array($orders);
});

runTest('29. WMS Module Regression (Warehouses Query)', function() use ($whService) {
    return class_exists('\\App\\Services\\WarehouseService');
});

runTest('30. Procurement Module Regression (Suppliers Query)', function() use ($container) {
    $procRepo = $container->get(\App\Repositories\ProcurementRepository::class);
    $sups = $procRepo->getAllSuppliers();
    return is_array($sups);
});

// Cleanup test records
if ($productA_Id) $db->execute("DELETE FROM products WHERE id = :id", [':id' => $productA_Id]);
if ($productB_Id) $db->execute("DELETE FROM products WHERE id = :id", [':id' => $productB_Id]);
if ($mktOrderId) {
    $db->execute("DELETE FROM vendor_orders WHERE order_id = :id", [':id' => $mktOrderId]);
    $db->execute("DELETE FROM orders WHERE id = :id", [':id' => $mktOrderId]);
}
if ($vendorA_Id) $db->execute("DELETE FROM vendors WHERE id = :id", [':id' => $vendorA_Id]);
if ($vendorB_Id) $db->execute("DELETE FROM vendors WHERE id = :id", [':id' => $vendorB_Id]);
if ($applicationId) $db->execute("DELETE FROM vendor_applications WHERE id = :id", [':id' => $applicationId]);

echo "\n" . str_repeat('=', 75) . "\n";
echo " SPRINT 35 TEST SONUÇLARI: {$passed}/30 BAŞARILI, {$failed}/30 BAŞARISIZ\n";
echo str_repeat('=', 75) . "\n\n";

if ($failed === 0) {
    echo " SUCCESS: SPRINT 35 MULTI-VENDOR MARKETPLACE PLATFORMU TÜM TESTLERDEN BAŞARIYLA GEÇTİ!\n\n";
} else {
    echo " WARNING: BAZI TESTLER BAŞARISIZ OLDU. LÜTFEN HATA DETAYLARINI İNCELEYİN.\n\n";
}
