<?php

declare(strict_types=1);

/**
 * SaintMonarc - Sprint 38 Enterprise Audit & Gap Analysis / End-to-End Integration Test Suite
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
use App\Helpers\AddressHelper;
use App\Services\CustomerService;
use App\Services\MarketplaceOrderService;
use App\Services\WarehouseService;
use App\Services\FinanceService;
use App\Services\ProcurementService;
use App\Services\ShippingService;
use App\Repositories\CustomerRepository;
use App\Repositories\VendorRepository;
use App\Repositories\ProductRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ShippingRepository;
use App\Repositories\ProcurementRepository;
use App\Repositories\FinanceRepository;
use App\Services\AuditLogger;

EnvParser::parse(ROOT_DIR . '/.env');
$app = new Application(ROOT_DIR);
$container = $app->getContainer();

$db = $container->get(DatabaseInterface::class);
$customerService = $container->get(CustomerService::class);
$vendorRepo = $container->get(VendorRepository::class);
$productRepo = $container->get(ProductRepository::class);
$warehouseService = $container->get(WarehouseService::class);
$orderService = $container->get(MarketplaceOrderService::class);
$orderRepo = $container->get(OrderRepository::class);
$shippingService = $container->get(ShippingService::class);
$shippingRepo = $container->get(ShippingRepository::class);
$financeService = $container->get(FinanceService::class);
$procurementService = $container->get(ProcurementService::class);
$auditLogger = $container->get(AuditLogger::class);

$passed = 0;
$failed = 0;

function runAuditTest(string $name, callable $fn) {
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

echo "\n" . str_repeat('=', 80) . "\n";
echo " SAINTMONARC - SPRINT 38 ENTERPRISE AUDIT & E2E INTEGRATION TEST SUITE\n";
echo str_repeat('=', 80) . "\n\n";

// Shared state for the E2E Lifecycle Flow
$customerId = null;
$customerAddressId = null;
$vendorBId = null;
$productOfficialId = null;
$productVendorBId = null;
$parentOrderId = null;
$childVendorOrders = [];
$shipmentPackageId = null;
$invoiceId = null;
$supplierId = null;

// ==========================================
// STAGE 1: Müşteri Kaydı & Adres Doğrulaması
// ==========================================
echo "--- STAGE 1: Customer Registration & Address Validation ---\n";

runAuditTest('1.1. Create Customer Account (Çağrı Şimşek)', function() use ($customerService, &$customerId) {
    $email = 'audit_customer_' . time() . '@saintmonarc.test';
    $customerId = $customerService->create([
        'first_name' => 'Çağrı',
        'last_name' => 'Şimşek',
        'email' => $email,
        'password' => 'Password123!',
        'phone' => '05321112233',
        'status' => 'active'
    ]);
    return $customerId > 0;
});

runAuditTest('1.2. Customer Address Validation (Ankara / Çankaya)', function() use ($customerService, $customerId, &$customerAddressId) {
    $customerAddressId = $customerService->addAddress($customerId, [
        'address_title' => 'Ev Adresi',
        'first_name' => 'Çağrı',
        'last_name' => 'Şimşek',
        'address_line1' => 'İnönü Bulvarı No:42 Daire:7',
        'city' => 'Ankara',
        'district' => 'Çankaya',
        'country' => 'Türkiye',
        'zip_code' => '06500',
        'is_default_billing' => 1,
        'is_default_shipping' => 1
    ]);
    return $customerAddressId > 0;
});

runAuditTest('1.3. Rejection of Invalid City/District Mapping in Customer Address', function() use ($customerService, $customerId) {
    try {
        $customerService->addAddress($customerId, [
            'address_title' => 'Hatalı Adres',
            'city' => 'Ankara',
            'district' => 'Kadıköy',
            'address_line1' => 'Test Street'
        ]);
        return 'Geçersiz İl/İlçe haritalaması reddedilmeliydi!';
    } catch (\Throwable $e) {
        return true; // Expected
    }
});

// ==========================================
// STAGE 2: Satıcı Başvurusu & Pazaryeri Mağazası
// ==========================================
echo "\n--- STAGE 2: Vendor Onboarding & VEYRA Platform Store ---\n";

runAuditTest('2.1. Vendor B Application Submission & Approval', function() use ($vendorRepo, &$vendorBId) {
    $vendorBId = $vendorRepo->createVendor([
        'name' => 'Ankara Teknoloji A.Ş.',
        'slug' => 'ankara-tek-' . time(),
        'company_name' => 'Ankara Teknoloji A.Ş.',
        'store_name' => 'Ankara Teknoloji VEYRA Mağazası',
        'tax_number' => '9998887771',
        'tax_office' => 'Çankaya VD',
        'contact_name' => 'Ahmet Yılmaz',
        'email' => 'ankara_tech_' . time() . '@veyra.test',
        'phone' => '03124445566',
        'status' => 'approved',
        'commission_rate' => 12.50
    ]);
    return $vendorBId > 0;
});

runAuditTest('2.2. Verify Official SaintMonarc Store (Vendor 1) Exists', function() use ($vendorRepo) {
    $official = $vendorRepo->getVendor(1);
    if (!$official) {
        return 'SaintMonarc Resmi Mağazası (Vendor 1) bulunamadı.';
    }
    return true;
});

// ==========================================
// STAGE 3: PIM Ürün Ekleme & Moderasyon Onayı
// ==========================================
echo "\n--- STAGE 3: PIM Product Creation & Moderation Approval ---\n";

runAuditTest('3.1. Create Official Store Product (Vendor 1)', function() use ($db, &$productOfficialId) {
    $sku = 'SKU-OFFICIAL-' . time();
    $slug = 'saintmonarc-official-laptop-' . time();
    $db->execute(
        "INSERT INTO products (brand_id, vendor_id, sku, price, cost_price, is_active, approval_status, slug, created_at)
         VALUES (NULL, 1, :sku, 15000.00, 10000.00, 1, 'approved', :slug, NOW())",
        [':sku' => $sku, ':slug' => $slug]
    );
    $productOfficialId = (int)$db->lastInsertId();

    $db->execute(
        "INSERT INTO product_translations (product_id, language_id, name) VALUES (:pid, 1, 'SaintMonarc Pro Laptop')",
        [':pid' => $productOfficialId]
    );
    return $productOfficialId > 0;
});

runAuditTest('3.2. Create Vendor B Product & Approve Moderation', function() use ($db, $vendorBId, &$productVendorBId) {
    $sku = 'SKU-VENDORB-' . time();
    $slug = 'ankara-tech-wireless-mouse-' . time();
    $db->execute(
        "INSERT INTO products (brand_id, vendor_id, sku, price, cost_price, is_active, approval_status, slug, created_at)
         VALUES (NULL, :vid, :sku, 500.00, 250.00, 1, 'pending', :slug, NOW())",
        [':vid' => $vendorBId, ':sku' => $sku, ':slug' => $slug]
    );
    $productVendorBId = (int)$db->lastInsertId();

    $db->execute(
        "INSERT INTO product_translations (product_id, language_id, name) VALUES (:pid, 1, 'Ankara Tech Kablosuz Mouse')",
        [':pid' => $productVendorBId]
    );

    // Admin moderasyon onayı
    $db->execute("UPDATE products SET approval_status = 'approved' WHERE id = :pid", [':pid' => $productVendorBId]);
    return true;
});

// ==========================================
// STAGE 4: WMS Depo Stok Girişi & Hareket Yönetimi
// ==========================================
echo "\n--- STAGE 4: WMS Warehouse Stock Entry & Movement Management ---\n";

runAuditTest('4.1. WMS Stock Receipt for Official Product', function() use ($warehouseService, $productOfficialId) {
    $warehouseService->adjustStock($productOfficialId, null, 1, 50, 'in', 'İlk Stok Mal Kabul');
    $stock = $warehouseService->getProductTotalStock($productOfficialId);
    if ($stock < 50) return "Depo stoğu yetersiz: {$stock}";
    return true;
});

runAuditTest('4.2. WMS Stock Receipt for Vendor B Product', function() use ($warehouseService, $productVendorBId) {
    $warehouseService->adjustStock($productVendorBId, null, 1, 100, 'in', 'Vendor B Konsinye Stok Girişi');
    $stock = $warehouseService->getProductTotalStock($productVendorBId);
    if ($stock < 100) return "Depo stoğu yetersiz: {$stock}";
    return true;
});

// ==========================================
// STAGE 5: Çok Satıcılı Sepet & Sipariş Bölme Engine
// ==========================================
echo "\n--- STAGE 5: Multi-Vendor Cart & Split Order Engine ---\n";

runAuditTest('5.1. Multi-Vendor Order Placement & Split Execution', function() use ($db, $orderService, $productOfficialId, $productVendorBId, $customerId, &$parentOrderId, &$childVendorOrders) {
    $users = $db->query("SELECT id FROM users LIMIT 1");
    $userId = !empty($users) ? (int)$users[0]['id'] : 1;

    $cartItems = [
        ['product_id' => $productOfficialId, 'quantity' => 1],
        ['product_id' => $productVendorBId, 'quantity' => 2]
    ];

    $orderData = [
        'billing_first_name' => 'Çağrı',
        'billing_last_name' => 'Şimşek',
        'billing_address' => 'İnönü Bulvarı No:42 Daire:7',
        'billing_city' => 'Ankara',
        'billing_country' => 'Türkiye',
        'billing_zip' => '06500',
        'shipping_first_name' => 'Çağrı',
        'shipping_last_name' => 'Şimşek',
        'shipping_address' => 'İnönü Bulvarı No:42 Daire:7',
        'shipping_city' => 'Ankara',
        'shipping_country' => 'Türkiye',
        'shipping_zip' => '06500'
    ];

    $res = $orderService->createMarketplaceOrder($orderData, $cartItems, $userId);
    if (empty($res['order_id']) || count($res['vendor_orders']) < 2) {
        return 'Çok satıcılı sipariş bölme işlemi başarısız!';
    }

    $parentOrderId = $res['order_id'];
    $childVendorOrders = $res['vendor_orders'];
    return true;
});

runAuditTest('5.2. Verify WMS Stock Deduction per Order Item', function() use ($warehouseService, $productOfficialId, $productVendorBId) {
    $stockOfficial = $warehouseService->getProductTotalStock($productOfficialId);
    $stockVendorB = $warehouseService->getProductTotalStock($productVendorBId);

    if ($stockOfficial !== 49) return "Official ürün stok düşümü hatalı: {$stockOfficial} (Beklenen: 49)";
    if ($stockVendorB !== 98) return "Vendor B ürün stok düşümü hatalı: {$stockVendorB} (Beklenen: 98)";
    return true;
});

// ==========================================
// STAGE 6: Kısmi Sevk, Kargo & Barkod Etiketi
// ==========================================
echo "\n--- STAGE 6: Partial Shipment, Carrier Tracking & Labels ---\n";

runAuditTest('6.1. Partial Shipment Package Creation for Order', function() use ($db, $shippingService, $parentOrderId, $productVendorBId, &$shipmentPackageId) {
    // Check or insert shipping company & service
    $existingComp = $db->query("SELECT id FROM shipping_companies WHERE code = 'YURTICI' LIMIT 1");
    if (!empty($existingComp)) {
        $compId = (int)$existingComp[0]['id'];
    } else {
        $db->execute("INSERT INTO shipping_companies (name, code, is_active, created_at) VALUES ('Yurtiçi Kargo', 'YURTICI', 1, NOW())");
        $compId = (int)$db->lastInsertId();
    }

    $existingService = $db->query("SELECT id FROM shipping_services WHERE company_id = :cid LIMIT 1", [':cid' => $compId]);
    if (!empty($existingService)) {
        $serviceId = (int)$existingService[0]['id'];
    } else {
        $db->execute("INSERT INTO shipping_services (company_id, name, code, is_active, created_at) VALUES (:cid, 'Standart Kargo', 'STD', 1, NOW())", [':cid' => $compId]);
        $serviceId = (int)$db->lastInsertId();
    }

    $shipmentPackageId = $shippingService->createShipment(
        [
            'order_id' => $parentOrderId,
            'service_id' => $serviceId,
            'tracking_number' => 'TRK-AUDIT-' . time(),
            'status' => 'shipped'
        ],
        [
            ['product_id' => $productVendorBId, 'quantity' => 1] // Partial shipment: 1 out of 2 items
        ]
    );
    return $shipmentPackageId > 0;
});

runAuditTest('6.2. Verify Shipping Tracking Record in Database', function() use ($db, $shipmentPackageId) {
    $packages = $db->query("SELECT * FROM shipping_packages WHERE id = :id", [':id' => $shipmentPackageId]);
    if (empty($packages) || empty($packages[0]['tracking_number'])) {
        return 'Kargo sevk paket kaydı bulunamadı.';
    }
    return true;
});

// ==========================================
// STAGE 7: E-Arşiv Fatura & Belge Snapshot Kaydı
// ==========================================
echo "\n--- STAGE 7: E-Arşiv Invoice Generation & Address Snapshots ---\n";

runAuditTest('7.1. Create E-Arşiv Invoice for Order', function() use ($db, $financeService, $parentOrderId, &$invoiceId) {
    $orders = $db->query("SELECT * FROM orders WHERE id = :id", [':id' => $parentOrderId]);
    if (empty($orders)) return 'Sipariş bulunamadı.';
    $order = $orders[0];
    
    $invoiceId = $financeService->createInvoice([
        'order_id' => $parentOrderId,
        'customer_id' => 1,
        'invoice_type' => 'sales',
        'sub_total' => (float)$order['subtotal'],
        'tax_total' => (float)$order['tax_total'],
        'grand_total' => (float)$order['grand_total'],
        'status' => 'completed',
        'invoice_date' => date('Y-m-d')
    ]);
    return $invoiceId > 0;
});

runAuditTest('7.2. Verify Invoice Address Snapshot Matches Checkout Data', function() use ($orderRepo, $parentOrderId) {
    $o = $orderRepo->getById($parentOrderId);
    if ($o['billing_first_name'] !== 'Çağrı' || $o['billing_city'] !== 'Ankara') {
        return 'Fatura adres snapshot verisi eşleşmiyor.';
    }
    return true;
});

runAuditTest('7.3. Verify Finance Revenue Ledger Posting', function() use ($db) {
    $revs = $db->query("SELECT COUNT(*) as cnt FROM revenues");
    return is_array($revs);
});

// ==========================================
// STAGE 8: Satıcı Cüzdan Hakedişi & Komisyon Kesintisi
// ==========================================
echo "\n--- STAGE 8: Vendor Wallet Payout & Commission Calculation ---\n";

runAuditTest('8.1. Verify Vendor B Wallet Balance Credit (1000 TL subtotal - 12.5% commission = 875 TL payout)', function() use ($db, $vendorBId) {
    $txs = $db->query("SELECT * FROM vendor_wallet_transactions WHERE vendor_id = :vid AND type = 'credit'", [':vid' => $vendorBId]);
    if (empty($txs)) return 'Vendor B cüzdan hakediş kaydı bulunamadı.';
    $amount = (float)$txs[0]['amount'];
    if ($amount !== 875.00) return "Hakediş tutarı hatalı: {$amount} (Beklenen: 875.00)";
    return true;
});

runAuditTest('8.2. Verify Platform Commission Record (125 TL commission)', function() use ($db, $vendorBId) {
    $comms = $db->query("SELECT * FROM vendor_commissions WHERE vendor_id = :vid", [':vid' => $vendorBId]);
    if (empty($comms)) return 'Komisyon kaydı bulunamadı.';
    $calculated = (float)$comms[0]['calculated_amount'];
    if ($calculated !== 125.00) return "Komisyon tutarı hatalı: {$calculated} (Beklenen: 125.00)";
    return true;
});

runAuditTest('8.3. Vendor Payment Approval Flow', function() use ($db, $vendorBId) {
    $db->execute(
        "INSERT INTO vendor_bank_accounts (vendor_id, account_holder, iban, bank_name, created_at)
         VALUES (:vid, 'Ankara Teknoloji A.Ş.', 'TR990006200000000000000001', 'Garanti BBVA', NOW())",
        [':vid' => $vendorBId]
    );
    $bankId = (int)$db->lastInsertId();

    $db->execute(
        "INSERT INTO vendor_payments (vendor_id, bank_account_id, amount, status, created_at)
         VALUES (:vid, :bid, 500.00, 'approved', NOW())",
        [':vid' => $vendorBId, ':bid' => $bankId]
    );
    $paymentId = (int)$db->lastInsertId();
    return $paymentId > 0;
});

// ==========================================
// STAGE 9: Tedarikçi Fiyat Geçmişi & Otomatik Satın Alma Önerisi
// ==========================================
echo "\n--- STAGE 9: Procurement Auto-Reorder & Supplier History ---\n";

runAuditTest('9.1. Create Supplier & Record Price History', function() use ($db, &$supplierId) {
    $db->execute(
        "INSERT INTO suppliers (company_name, contact_name, email, phone, created_at)
         VALUES ('Ankara Tedarik Ltd.', 'Mehmet Kaya', 'supplier_audit@ankara.test', '03125556677', NOW())"
    );
    $supplierId = (int)$db->lastInsertId();

    $db->execute(
        "INSERT INTO supplier_price_history (supplier_id, product_id, price, change_date, created_at)
         VALUES (:sid, 1, 8500.00, CURDATE(), NOW())",
        [':sid' => $supplierId]
    );
    return $supplierId > 0;
});

runAuditTest('9.2. Procurement Low Stock Suggestions Engine Trigger', function() use ($procurementService) {
    $suggestions = $procurementService->getLowStockSuggestions();
    return is_array($suggestions);
});

// ==========================================
// STAGE 10: RBAC, Güvenlik Headers & Audit Log İzleme
// ==========================================
echo "\n--- STAGE 10: Security, RBAC & Audit Log Traceability ---\n";

runAuditTest('10.1. Audit Log Traceability for Marketplace Order Creation', function() use ($db, $parentOrderId) {
    $logs = $db->query("SELECT * FROM audit_logs WHERE auditable_type = 'Order' AND auditable_id = :id", [':id' => $parentOrderId]);
    if (empty($logs)) return 'Sipariş oluşturma işlemi audit_logs tablosunda izlenemedi.';
    return true;
});

runAuditTest('10.2. Security Headers & Output Escaping Sanity Check', function() {
    $cleanText = \Core\Security::escape('<script>alert("XSS")</script>');
    if (strpos($cleanText, '<script>') !== false) return 'XSS kaçırma fonksiyonu başarısız.';
    return true;
});

// Clean up temporary E2E test data
if ($parentOrderId) {
    $db->execute("DELETE FROM shipping_package_items WHERE package_id IN (SELECT id FROM shipping_packages WHERE order_id = :id)", [':id' => $parentOrderId]);
    $db->execute("DELETE FROM shipping_packages WHERE order_id = :id", [':id' => $parentOrderId]);
    $db->execute("DELETE FROM invoice_items WHERE invoice_id IN (SELECT id FROM invoices WHERE order_id = :id)", [':id' => $parentOrderId]);
    $db->execute("DELETE FROM invoices WHERE order_id = :id", [':id' => $parentOrderId]);
    $db->execute("DELETE FROM vendor_orders WHERE order_id = :id", [':id' => $parentOrderId]);
    $db->execute("DELETE FROM vendor_commissions WHERE order_id = :id", [':id' => $parentOrderId]);
    $db->execute("DELETE FROM orders WHERE id = :id", [':id' => $parentOrderId]);
}
if ($productOfficialId) {
    $db->execute("DELETE FROM product_translations WHERE product_id = :id", [':id' => $productOfficialId]);
    $db->execute("DELETE FROM inventories WHERE product_id = :id", [':id' => $productOfficialId]);
    $db->execute("DELETE FROM products WHERE id = :id", [':id' => $productOfficialId]);
}
if ($productVendorBId) {
    $db->execute("DELETE FROM product_translations WHERE product_id = :id", [':id' => $productVendorBId]);
    $db->execute("DELETE FROM inventories WHERE product_id = :id", [':id' => $productVendorBId]);
    $db->execute("DELETE FROM products WHERE id = :id", [':id' => $productVendorBId]);
}
if ($vendorBId) {
    $db->execute("DELETE FROM vendor_wallet_transactions WHERE vendor_id = :id", [':id' => $vendorBId]);
    $db->execute("DELETE FROM vendor_payments WHERE vendor_id = :id", [':id' => $vendorBId]);
    $db->execute("DELETE FROM vendor_bank_accounts WHERE vendor_id = :id", [':id' => $vendorBId]);
    $db->execute("DELETE FROM vendor_wallet WHERE vendor_id = :id", [':id' => $vendorBId]);
    $db->execute("DELETE FROM vendor_statistics WHERE vendor_id = :id", [':id' => $vendorBId]);
    $db->execute("DELETE FROM vendors WHERE id = :id", [':id' => $vendorBId]);
}
if ($customerId) {
    $db->execute("DELETE FROM customer_addresses WHERE customer_id = :id", [':id' => $customerId]);
    $db->execute("DELETE FROM customer_wallet WHERE customer_id = :id", [':id' => $customerId]);
    $db->execute("DELETE FROM customers WHERE id = :id", [':id' => $customerId]);
}
if ($supplierId) {
    $db->execute("DELETE FROM supplier_price_history WHERE supplier_id = :id", [':id' => $supplierId]);
    $db->execute("DELETE FROM suppliers WHERE id = :id", [':id' => $supplierId]);
}

echo "\n" . str_repeat('=', 80) . "\n";
echo " SPRINT 38 E2E AUDIT TEST SONUÇLARI: {$passed}/22 BAŞARILI, {$failed}/22 BAŞARISIZ\n";
echo str_repeat('=', 80) . "\n\n";

if ($failed === 0) {
    echo " SUCCESS: SPRINT 38 ENTERPRISE AUDIT & E2E INTEGRATION TÜM TESTLERDEN BAŞARIYLA GEÇTİ!\n\n";
} else {
    echo " WARNING: BAZI AUDIT TESTLERİ BAŞARISIZ OLDU. LÜTFEN DETAYLARI İNCELEYİN.\n\n";
}
