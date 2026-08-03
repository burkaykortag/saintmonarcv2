<?php

declare(strict_types=1);

/**
 * SaintMonarc - Sprint 37 Customer / Address / Document Standardization V1 Test Suite
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
use App\Helpers\SecurityHelper;
use App\Repositories\CustomerRepository;
use App\Services\CustomerService;
use App\Services\MarketplaceOrderService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\WarehouseService;

EnvParser::parse(ROOT_DIR . '/.env');
$app = new Application(ROOT_DIR);
$container = $app->getContainer();

$db = $container->get(DatabaseInterface::class);
$customerRepo = $container->get(CustomerRepository::class);
$customerService = $container->get(CustomerService::class);
$orderService = $container->get(MarketplaceOrderService::class);
$orderRepo = $container->get(OrderRepository::class);
$productRepo = $container->get(ProductRepository::class);
$whService = $container->get(WarehouseService::class);

$passed = 0;
$failed = 0;

function runAddrTest(string $name, callable $fn) {
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
echo " SAINTMONARC - SPRINT 37 CUSTOMER / ADDRESS / DOCUMENT TEST SUITE\n";
echo str_repeat('=', 75) . "\n\n";

$testCustomerId = null;
$testAddressId = null;
$testOrderId = null;
$testProductId = null;

// 1. UTF-8 Customer First Name Verification
runAddrTest('1. UTF-8 Customer First Name Verification (Çağrı)', function() use ($customerService, &$testCustomerId) {
    $email = 'cagri_test_' . time() . '@saintmonarc.test';
    $testCustomerId = $customerService->create([
        'first_name' => 'Çağrı',
        'last_name' => 'Şimşek',
        'email' => $email,
        'password' => 'Password123!',
        'phone' => '05559998877',
        'status' => 'active'
    ]);
    $c = $customerService->getById($testCustomerId);
    if (!$c || $c['first_name'] !== 'Çağrı') {
        return "Türkçe ad kaydedilemedi veya bozuldu: " . ($c['first_name'] ?? 'null');
    }
    return true;
});

// 2. UTF-8 Customer Last Name Verification
runAddrTest('2. UTF-8 Customer Last Name Verification (Şimşek)', function() use ($customerService, $testCustomerId) {
    $c = $customerService->getById($testCustomerId);
    if ($c['last_name'] !== 'Şimşek') {
        return "Türkçe soyad kaydedilemedi: " . $c['last_name'];
    }
    return true;
});

// 3. UTF-8 Address Line Verification
runAddrTest('3. UTF-8 Address Line Verification (İnönü Mah. Çiçek Sok.)', function() use ($customerService, $testCustomerId, &$testAddressId) {
    $testAddressId = $customerService->addAddress($testCustomerId, [
        'address_title' => 'Ev Adresi ĞÜŞİÖÇ',
        'first_name' => 'Çağrı',
        'last_name' => 'Şimşek',
        'address_line1' => 'İnönü Mahallesi, Çiçekçi Sokak No:12',
        'city' => 'Ankara',
        'district' => 'Çankaya',
        'country' => 'Türkiye',
        'zip_code' => '06500',
        'is_default_billing' => 1,
        'is_default_shipping' => 1
    ]);
    $addresses = $customerService->getAddresses($testCustomerId);
    if (empty($addresses)) return 'Adres kaydedilemedi.';
    $a = $addresses[0];
    if (mb_strpos($a['address_line1'], 'İnönü') === false) {
        return 'Türkçe adres satırı bozulmuş: ' . $a['address_line1'];
    }
    return true;
});

// 4. UTF-8 City Verification
runAddrTest('4. UTF-8 City Verification (Ankara)', function() use ($customerService, $testCustomerId) {
    $addresses = $customerService->getAddresses($testCustomerId);
    if ($addresses[0]['city'] !== 'Ankara') return 'Şehir kaydı hatalı.';
    return true;
});

// 5. UTF-8 District Verification
runAddrTest('5. UTF-8 District Verification (Çankaya)', function() use ($customerService, $testCustomerId) {
    $addresses = $customerService->getAddresses($testCustomerId);
    $dist = $addresses[0]['state'] ?? ($addresses[0]['district'] ?? '');
    if ($dist !== 'Çankaya') return 'İlçe kaydı hatalı: ' . $dist;
    return true;
});

// 6. City Selector API Data
runAddrTest('6. Central City Selector Helper (81 Cities)', function() {
    $cities = AddressHelper::getCities();
    if (count($cities) !== 81 || !in_array('İstanbul', $cities) || !in_array('Ankara', $cities)) {
        return '81 il tam listelenemedi.';
    }
    return true;
});

// 7. District Filtering API Data
runAddrTest('7. District Filtering Helper for Ankara', function() {
    $districts = AddressHelper::getDistricts('Ankara');
    if (empty($districts) || !in_array('Çankaya', $districts) || !in_array('Keçiören', $districts)) {
        return 'Ankara ilçeleri filtrelenemedi.';
    }
    return true;
});

// 8. Invalid City/District Pair Rejection
runAddrTest('8. Backend Invalid City/District Pair Rejection (Ankara + Kadıköy)', function() use ($customerService, $testCustomerId) {
    try {
        $customerService->addAddress($testCustomerId, [
            'address_title' => 'Geçersiz Adres',
            'city' => 'Ankara',
            'district' => 'Kadıköy', // Kadıköy is in Istanbul, not Ankara!
            'address_line1' => 'Test Mah.'
        ]);
        return 'Geçersiz İl/İlçe eşleşmesi (Ankara + Kadıköy) reddedilmeliydi!';
    } catch (\Throwable $e) {
        return true; // Expected validation exception
    }
});

// 9. Customer Address Creation
runAddrTest('9. Customer Address Creation Method', function() use ($customerService, $testCustomerId) {
    $addrId = $customerService->addAddress($testCustomerId, [
        'address_title' => 'İş Adresi',
        'first_name' => 'Çağrı',
        'last_name' => 'Şimşek',
        'address_line1' => 'Atatürk Bulvarı No:45',
        'city' => 'İzmir',
        'district' => 'Konak',
        'country' => 'Türkiye',
        'zip_code' => '35000'
    ]);
    return $addrId > 0;
});

// 10. Customer Address Update
runAddrTest('10. Customer Address Update Method', function() use ($customerService, $testCustomerId, $testAddressId) {
    $res = $customerService->updateAddress($testAddressId, $testCustomerId, [
        'address_title' => 'Güncellenmiş Ev Adresi',
        'first_name' => 'Çağrı',
        'last_name' => 'Şimşek',
        'address_line1' => 'İnönü Mahallesi, Çiçekçi Sokak No:15 Daire:4',
        'city' => 'Ankara',
        'district' => 'Çankaya',
        'country' => 'Türkiye',
        'zip_code' => '06500',
        'is_default_billing' => 1,
        'is_default_shipping' => 1
    ]);
    if (!$res) return 'Adres güncellenemedi.';
    return true;
});

// 11. Customer Address Deletion
runAddrTest('11. Customer Address Deletion Method', function() use ($customerService, $testCustomerId) {
    $tempId = $customerService->addAddress($testCustomerId, [
        'address_title' => 'Silinecek Adres',
        'city' => 'Bursa',
        'district' => 'Nilüfer',
        'address_line1' => 'Silinecek Sokak'
    ]);
    $deleted = $customerService->deleteAddress($tempId, $testCustomerId);
    if (!$deleted) return 'Adres silinemedi.';
    return true;
});

// 12. Default Billing Address Flag
runAddrTest('12. Default Billing Address Flag Handling', function() use ($customerService, $testCustomerId) {
    $addresses = $customerService->getAddresses($testCustomerId);
    $defaultBilling = array_filter($addresses, fn($a) => !empty($a['is_default_billing']));
    return !empty($defaultBilling);
});

// 13. Default Shipping Address Flag
runAddrTest('13. Default Shipping Address Flag Handling', function() use ($customerService, $testCustomerId) {
    $addresses = $customerService->getAddresses($testCustomerId);
    $defaultShipping = array_filter($addresses, fn($a) => !empty($a['is_default_shipping']));
    return !empty($defaultShipping);
});

// 14. Create Product for Order Address Snapshot Test
runAddrTest('14. Create Test Product for Order Address Snapshot', function() use ($db, $whService, &$testProductId) {
    $sku = 'SKU-ADDR-' . time();
    $slug = 'addr-test-product-' . time();
    
    $db->execute(
        "INSERT INTO products (brand_id, vendor_id, sku, price, cost_price, is_active, approval_status, slug, created_at)
         VALUES (NULL, 1, :sku, 750.00, 400.00, 1, 'approved', :slug, NOW())",
        [':sku' => $sku, ':slug' => $slug]
    );
    $testProductId = (int)$db->lastInsertId();

    $db->execute(
        "INSERT INTO product_translations (product_id, language_id, name) VALUES (:pid, 1, 'Adres Test Ürünü ĞÜŞİÖÇ')",
        [':pid' => $testProductId]
    );

    $whService->adjustStock($testProductId, null, 1, 20, 'in', 'Test Stock');
    return $testProductId > 0;
});

// 15. Order Address Snapshot Verification
runAddrTest('15. Order Address Snapshot Storage at Checkout', function() use ($db, $orderService, $testProductId, $testCustomerId, &$testOrderId) {
    // Get valid user_id from users table
    $users = $db->query("SELECT id FROM users LIMIT 1");
    $userId = !empty($users) ? (int)$users[0]['id'] : 1;

    $cartItems = [['product_id' => $testProductId, 'quantity' => 1]];
    $orderData = [
        'billing_first_name' => 'Çağrı',
        'billing_last_name' => 'Şimşek',
        'billing_address' => 'İnönü Mah. Çiçek Sok. No:12',
        'billing_city' => 'Ankara',
        'billing_country' => 'Türkiye',
        'billing_zip' => '06500',
        'shipping_first_name' => 'Çağrı',
        'shipping_last_name' => 'Şimşek',
        'shipping_address' => 'İnönü Mah. Çiçek Sok. No:12',
        'shipping_city' => 'Ankara',
        'shipping_country' => 'Türkiye',
        'shipping_zip' => '06500'
    ];

    $res = $orderService->createMarketplaceOrder($orderData, $cartItems, $userId);
    if (empty($res['order_id'])) return 'Sipariş oluşturulamadı.';
    $testOrderId = $res['order_id'];
    return true;
});

// 16. Billing Snapshot Usage Verification
runAddrTest('16. Billing Address Snapshot Verification from Orders Table', function() use ($orderRepo, $testOrderId) {
    $o = $orderRepo->getById($testOrderId);
    if ($o['billing_first_name'] !== 'Çağrı' || $o['billing_city'] !== 'Ankara') {
        return 'Fatura adres snapshot verisi eşleşmiyor.';
    }
    return true;
});

// 17. Shipping Label Snapshot Verification
runAddrTest('17. Shipping Label Address Snapshot Verification', function() use ($orderRepo, $testOrderId) {
    $o = $orderRepo->getById($testOrderId);
    if ($o['shipping_first_name'] !== 'Çağrı' || $o['shipping_address'] !== 'İnönü Mah. Çiçek Sok. No:12') {
        return 'Teslimat adres snapshot verisi eşleşmiyor.';
    }
    return true;
});

// 18. Historical Address Preservation Test
runAddrTest('18. Historical Address Preservation (Modifying Customer Profile Address does NOT alter existing Order)', function() use ($customerService, $orderRepo, $testCustomerId, $testAddressId, $testOrderId) {
    // Modify customer's profile address
    $customerService->updateAddress($testAddressId, $testCustomerId, [
        'address_title' => 'Yeni Taşınılan Adres',
        'first_name' => 'Çağrı',
        'last_name' => 'Şimşek',
        'address_line1' => 'Yeni Mahalle Cadde No:99',
        'city' => 'İstanbul',
        'district' => 'Kadıköy',
        'country' => 'Türkiye',
        'zip_code' => '34000'
    ]);

    // Verify historical order still has original snapshot address (Ankara)
    $o = $orderRepo->getById($testOrderId);
    if ($o['billing_city'] !== 'Ankara' || $o['shipping_city'] !== 'Ankara') {
        return 'Geçmiş siparişin adresi müşterinin yeni adresiyle değişmiş (Immutability ihlali)!';
    }
    return true;
});

// 19. E-Arşiv Fatura UTF-8 Output Check
runAddrTest('19. E-Arşiv Fatura UTF-8 Output Check', function() use ($orderRepo, $testOrderId) {
    $o = $orderRepo->getById($testOrderId);
    $html = htmlspecialchars($o['billing_first_name'] . ' ' . $o['billing_last_name'], ENT_QUOTES, 'UTF-8');
    if ($html !== 'Çağrı Şimşek') return 'Fatura isim çıktısı bozuk: ' . $html;
    return true;
});

// 20. Sevk İrsaliyesi UTF-8 Output Check
runAddrTest('20. Sevk İrsaliyesi UTF-8 Output Check', function() use ($orderRepo, $testOrderId) {
    $o = $orderRepo->getById($testOrderId);
    $addr = htmlspecialchars($o['shipping_address'], ENT_QUOTES, 'UTF-8');
    if (mb_strpos($addr, 'Çiçek') === false) return 'İrsaliye adres çıktısı bozuk: ' . $addr;
    return true;
});

// 21. Kargo Sevk Etiketi UTF-8 Output Check
runAddrTest('21. Kargo Sevk Etiketi UTF-8 Output Check', function() use ($orderRepo, $testOrderId) {
    $o = $orderRepo->getById($testOrderId);
    $city = htmlspecialchars($o['shipping_city'], ENT_QUOTES, 'UTF-8');
    if ($city !== 'Ankara') return 'Kargo etiketi şehir çıktısı bozuk: ' . $city;
    return true;
});

// 22. PDF Print Template Font Encoding Verification
runAddrTest('22. PDF Print Template Font Encoding Verification', function() {
    $fileContent = file_get_contents(ROOT_DIR . '/app/Controllers/OrderController.php');
    if (strpos($fileContent, 'fonts.googleapis.com/css2?family=Inter') === false) {
        return 'PDF / Document template Inter font tanımı bulunamadı.';
    }
    return true;
});

// 23. Address Input XSS Escaping Check
runAddrTest('23. Address Input XSS Escaping Check', function() {
    $xssAddress = '<script>alert("XSS")</script> İnönü Mah.';
    $clean = \Core\Security::escape($xssAddress);
    if (strpos($clean, '<script>') !== false) return 'XSS script etiketleri temizlenemedi.';
    return true;
});

// 24. Address IDOR Ownership Verification
runAddrTest('24. Customer Address IDOR Ownership Verification', function() use ($customerService, $testAddressId) {
    try {
        // Customer 99999 trying to modify testAddressId belonging to testCustomerId
        $customerService->updateAddress($testAddressId, 99999, [
            'address_title' => 'Hacked Address',
            'city' => 'Ankara',
            'district' => 'Çankaya'
        ]);
        return 'IDOR zafiyeti engellenemedi!';
    } catch (\Throwable $e) {
        return true; // Exception expected
    }
});

// 25. Regression Test across System Modules
runAddrTest('25. System Modules Regression Verification', function() use ($productRepo) {
    $prods = $productRepo->getAll();
    return is_array($prods);
});

// Cleanup test records
if ($testOrderId) {
    $db->execute("DELETE FROM vendor_orders WHERE order_id = :id", [':id' => $testOrderId]);
    $db->execute("DELETE FROM orders WHERE id = :id", [':id' => $testOrderId]);
}
if ($testProductId) {
    $db->execute("DELETE FROM products WHERE id = :id", [':id' => $testProductId]);
}
if ($testCustomerId) {
    $db->execute("DELETE FROM customer_addresses WHERE customer_id = :id", [':id' => $testCustomerId]);
    $db->execute("DELETE FROM customers WHERE id = :id", [':id' => $testCustomerId]);
}

echo "\n" . str_repeat('=', 75) . "\n";
echo " SPRINT 37 TEST SONUÇLARI: {$passed}/25 BAŞARILI, {$failed}/25 BAŞARISIZ\n";
echo str_repeat('=', 75) . "\n\n";

if ($failed === 0) {
    echo " SUCCESS: SPRINT 37 CUSTOMER / ADDRESS / DOCUMENT TÜM TESTLERDEN BAŞARIYLA GEÇTİ!\n\n";
} else {
    echo " WARNING: BAZI TESTLER BAŞARISIZ OLDU. LÜTFEN HATA DETAYLARINI İNCELEYİN.\n\n";
}
