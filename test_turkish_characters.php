<?php
declare(strict_types=1);

/**
 * Sprint 32 - Turkish Character UTF-8 Verification Test Suite
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
            if (file_exists($file)) { require $file; return; }
        }
    });
}

use Core\Config\EnvParser;

EnvParser::parse(ROOT_DIR . '/.env');

$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'saintmonarc';
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$dbPass = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

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
echo " TURKISH CHARACTER UTF-8 SUPPORT VERIFICATION\n";
echo str_repeat('=', 60) . "\n\n";

$testOrderIds = [];
$testNames = ["Çağrı", "İsmail", "Şule", "Özgür", "Ümit", "Çetin", "Gökhan", "Işıl"];

test('Database insertion and retrieval of Turkish characters', function() use ($pdo, $testNames, &$testOrderIds) {
    $userRow = $pdo->query('SELECT id FROM users WHERE deleted_at IS NULL LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $userId = $userRow ? (int)$userRow['id'] : null;

    $pdo->beginTransaction();
    try {
        foreach ($testNames as $idx => $name) {
            $orderNum = 'TEST-TR-' . $idx . '-' . time();
            
            // Insert order with special characters in billing and shipping fields
            $stmt = $pdo->prepare(
                "INSERT INTO orders (
                    user_id, order_number, status, billing_first_name, billing_last_name, billing_address, billing_city, billing_country, billing_zip,
                    shipping_first_name, shipping_last_name, shipping_address, shipping_city, shipping_country, shipping_zip,
                    subtotal, tax_total, grand_total, created_at
                 ) VALUES (
                    :uid, :num, 'pending', :name, 'Testoğlu', 'Çınar Sokak No: 5, Da: 3', 'İstanbul', 'Türkiye', '34000',
                    :name, 'Testoğlu', 'Çınar Sokak No: 5, Da: 3', 'İstanbul', 'Türkiye', '34000',
                    100.00, 18.00, 118.00, NOW()
                 )"
            );
            $stmt->execute([
                ':uid' => $userId,
                ':num' => $orderNum,
                ':name' => $name
            ]);
            $insertedId = (int)$pdo->lastInsertId();
            $testOrderIds[] = $insertedId;

            // Retrieve from database and assert exact match
            $checkStmt = $pdo->prepare("SELECT billing_first_name FROM orders WHERE id = :id");
            $checkStmt->execute([':id' => $insertedId]);
            $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($row['billing_first_name'] !== $name) {
                throw new Exception("Character mismatch. Expected: {$name}, Got: " . $row['billing_first_name']);
            }
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
});

test('generatePdf HTML template outputs UTF-8 meta charset tag', function() {
    // We can simulate call to generatePdf by capturing buffer output
    // Initialize required environment mock if necessary
    $_GET['id'] = 1; // dummy
    $_GET['type'] = 'invoice';

    // We can fetch the raw file content and inspect it or execute a mock
    $filePath = ROOT_DIR . '/app/Controllers/OrderController.php';
    $code = file_get_contents($filePath);
    
    // Check if '<meta charset="utf-8">' is present in code
    if (!str_contains(strtolower($code), '<meta charset="utf-8">')) {
        return 'OrderController generatePdf is missing meta charset tag';
    }
    return true;
});

test('Excel exports have utf-8 Content-Type meta header', function() {
    $filePath1 = ROOT_DIR . '/app/Controllers/CustomerController.php';
    $code1 = file_get_contents($filePath1);
    if (!str_contains($code1, "meta http-equiv='Content-Type' content='text/html; charset=utf-8'")) {
        return 'CustomerController excel export is missing Content-Type meta tag';
    }

    $filePath2 = ROOT_DIR . '/app/Services/OrderService.php';
    $code2 = file_get_contents($filePath2);
    if (!str_contains($code2, "meta http-equiv='Content-Type' content='text/html; charset=utf-8'")) {
        return 'OrderService excel export is missing Content-Type meta tag';
    }
    return true;
});

// --- CLEANUP ---
test('Cleanup temporary Turkish character test orders', function() use ($pdo, &$testOrderIds) {
    if (!empty($testOrderIds)) {
        $in = implode(',', array_map('intval', $testOrderIds));
        $pdo->exec("DELETE FROM orders WHERE id IN ({$in})");
    }
    return true;
});

echo "\n" . str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo " RESULT: {$passed}/{$total} tests PASSED" . ($failed > 0 ? ", {$failed} FAILED" : '') . "\n";
echo str_repeat('=', 60) . "\n\n";

if ($failed === 0) {
    echo "✅ ALL TURKISH CHARACTER TESTS PASSED SUCCESSFULLY!\n\n";
} else {
    echo "⚠️ Some tests failed. Please review execution log.\n\n";
    exit(1);
}
