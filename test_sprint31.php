<?php
declare(strict_types=1);

/**
 * Sprint 31 - Enterprise OMS V2 - CLI Test Suite
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
use Core\Database\Database;
use App\Services\OrderService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\BrandRepository;
use App\Services\AuditLogger;

// Class mock for Cache
class MockCache implements Core\Contracts\CacheInterface {
    public function get(string $key, mixed $default = null): mixed { return null; }
    public function set(string $key, mixed $value, ?int $ttl = null): bool { return true; }
    public function delete(string $key): bool { return true; }
    public function clear(): bool { return true; }
    public function has(string $key): bool { return false; }
}

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

// Create Core Database instance wrapper
$dbWrapper = new class($pdo) implements Core\Contracts\DatabaseInterface {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function query(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    public function lastInsertId(): string { return $this->pdo->lastInsertId(); }
    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool { return $this->pdo->commit(); }
    public function rollBack(): bool { return $this->pdo->rollBack(); }
    public function inTransaction(): bool { return $this->pdo->inTransaction(); }
};

// Instantiate mock dependencies
$cache = new MockCache();
$auditLogger = new AuditLogger($dbWrapper);

$orderRepo = new OrderRepository($dbWrapper);
$prodRepo = new ProductRepository($dbWrapper);
$catRepo = new CategoryRepository($dbWrapper);
$brandRepo = new BrandRepository($dbWrapper);

$orderService = new OrderService($orderRepo, $dbWrapper, $cache, $auditLogger);

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
echo " SPRINT 31 - ENTERPRISE OMS V2 CLI TEST SUITE\n";
echo str_repeat('=', 60) . "\n\n";

// --- TEST 1: Schema Integrity ---
echo "[SECTION 1] Database Schema Verification\n";

test('warehouses table exists', function() use ($pdo) {
    $stmt = $pdo->query("SHOW TABLES LIKE 'warehouses'");
    return $stmt->rowCount() > 0 ? true : 'warehouses table is missing';
});

test('inventories has warehouse_id column', function() use ($pdo) {
    $stmt = $pdo->query("SHOW COLUMNS FROM inventories LIKE 'warehouse_id'");
    return $stmt->rowCount() > 0 ? true : 'warehouse_id is missing from inventories';
});

test('orders has sla_due_at and is_delayed', function() use ($pdo) {
    $stmt1 = $pdo->query("SHOW COLUMNS FROM orders LIKE 'sla_due_at'");
    $stmt2 = $pdo->query("SHOW COLUMNS FROM orders LIKE 'is_delayed'");
    return ($stmt1->rowCount() > 0 && $stmt2->rowCount() > 0) ? true : 'SLA columns are missing from orders';
});

test('order_shipment_items table exists', function() use ($pdo) {
    $stmt = $pdo->query("SHOW TABLES LIKE 'order_shipment_items'");
    return $stmt->rowCount() > 0 ? true : 'order_shipment_items is missing';
});

// --- TEST 2: Order Merging Logic ---
echo "\n[SECTION 2] Order Merging Business Logic Tests\n";

$mergedParentId = null;
$order1 = null;
$order2 = null;

test('Create two mergeable pending orders', function() use ($dbWrapper, $orderService, &$order1, &$order2) {
    // Order 1
    $data1 = [
        'user_id' => 1,
        'billing_first_name' => 'MergeTest',
        'billing_last_name' => 'Customer',
        'billing_address' => 'Test address 1',
        'billing_city' => 'Istanbul',
        'billing_zip' => '34000',
        'currency_code' => 'TRY',
        'subtotal' => 100.0,
        'tax_total' => 18.0,
        'grand_total' => 118.0,
        'items' => [
            ['product_id' => 1, 'product_sku' => 'TEST-001', 'product_name' => 'Product A', 'quantity' => 1, 'price' => 100.0, 'tax_amount' => 18.0]
        ]
    ];
    $order1 = $orderService->create($data1);

    // Order 2
    $data2 = [
        'user_id' => 1,
        'billing_first_name' => 'MergeTest',
        'billing_last_name' => 'Customer',
        'billing_address' => 'Test address 1',
        'billing_city' => 'Istanbul',
        'billing_zip' => '34000',
        'currency_code' => 'TRY',
        'subtotal' => 200.0,
        'tax_total' => 36.0,
        'grand_total' => 236.0,
        'items' => [
            ['product_id' => 2, 'product_sku' => 'TEST-002', 'product_name' => 'Product B', 'quantity' => 2, 'price' => 100.0, 'tax_amount' => 18.0]
        ]
    ];
    $order2 = $orderService->create($data2);

    return ($order1 > 0 && $order2 > 0) ? true : 'Failed to create test orders';
});

test('Merge orders', function() use ($orderService, &$order1, &$order2, &$mergedParentId) {
    $mergedParentId = $orderService->mergeOrders([$order1, $order2]);
    return ($mergedParentId === $order1) ? true : 'Merged parent is not the target order';
});

test('Verify merged parent properties', function() use ($orderRepo, &$mergedParentId) {
    $parent = $orderRepo->getById($mergedParentId);
    $items = $orderRepo->getItems($mergedParentId);

    if ((float)$parent['grand_total'] != (118.0 + 236.0 - (float)$parent['discount_total'])) {
        return 'Grand total did not sum correctly: ' . $parent['grand_total'];
    }
    if (count($items) < 2) {
        return 'Items did not combine correctly. Item count: ' . count($items);
    }
    return true;
});

test('Verify child order status changed to cancelled and merged_into_id set', function() use ($dbWrapper, &$order2, &$mergedParentId) {
    $rows = $dbWrapper->query("SELECT status, merged_into_id FROM orders WHERE id = :id", [':id' => $order2]);
    if ($rows[0]['status'] !== 'cancelled') {
        return 'Child order status is not cancelled: ' . $rows[0]['status'];
    }
    if ((int)$rows[0]['merged_into_id'] !== $mergedParentId) {
        return 'Child order merged_into_id is incorrect';
    }
    return true;
});

// --- TEST 3: Partial Shipments ---
echo "\n[SECTION 3] Partial Shipment Business Logic Tests\n";

$shipmentOrderId = null;

test('Create order for partial shipment', function() use ($orderService, &$shipmentOrderId) {
    $data = [
        'user_id' => 1,
        'billing_first_name' => 'ShipTest',
        'billing_last_name' => 'Customer',
        'billing_address' => 'Test shipping address',
        'billing_city' => 'Izmir',
        'billing_zip' => '35000',
        'currency_code' => 'TRY',
        'subtotal' => 300.0,
        'tax_total' => 54.0,
        'grand_total' => 354.0,
        'items' => [
            ['product_id' => 1, 'product_sku' => 'TEST-001', 'product_name' => 'Product A', 'quantity' => 2, 'price' => 100.0, 'tax_amount' => 18.0],
            ['product_id' => 2, 'product_sku' => 'TEST-002', 'product_name' => 'Product B', 'quantity' => 1, 'price' => 100.0, 'tax_amount' => 18.0]
        ]
    ];
    $shipmentOrderId = $orderService->create($data);
    return $shipmentOrderId > 0 ? true : 'Failed to create partial shipment order';
});

test('Create partial shipment of 1 unit of Product A', function() use ($dbWrapper, $orderService, &$shipmentOrderId) {
    $items = $dbWrapper->query("SELECT id FROM order_items WHERE order_id = :oid AND product_sku = 'TEST-001'", [':oid' => $shipmentOrderId]);
    $itemId = (int)$items[0]['id'];

    // Ship 1 out of 2 units of Product A
    $shipmentId = $orderService->createPartialShipment($shipmentOrderId, [$itemId => 1], 'Yurtici', 'TR123456789', 1);
    
    // Verify order status changed to partially_shipped or processing
    $ordRows = $dbWrapper->query("SELECT status FROM orders WHERE id = :id", [':id' => $shipmentOrderId]);
    $status = $ordRows[0]['status'];

    if ($status !== 'partially_shipped') {
        return 'Order status is not partially_shipped: ' . $status;
    }

    // Verify shipment item created
    $shipItems = $dbWrapper->query("SELECT quantity_shipped FROM order_shipment_items WHERE shipment_id = :sid", [':sid' => $shipmentId]);
    if (empty($shipItems) || (int)$shipItems[0]['quantity_shipped'] !== 1) {
        return 'Shipment item quantity mismatch';
    }

    return true;
});

// --- CLEANUP ---
test('Cleanup test orders', function() use ($dbWrapper, &$order1, &$order2, &$shipmentOrderId) {
    if ($order1) {
        $dbWrapper->execute("DELETE FROM order_status_history WHERE order_id = :id", [':id' => $order1]);
        $dbWrapper->execute("DELETE FROM order_items WHERE order_id = :id", [':id' => $order1]);
        $dbWrapper->execute("DELETE FROM orders WHERE id = :id", [':id' => $order1]);
    }
    if ($order2) {
        $dbWrapper->execute("DELETE FROM order_status_history WHERE order_id = :id", [':id' => $order2]);
        $dbWrapper->execute("DELETE FROM order_items WHERE order_id = :id", [':id' => $order2]);
        $dbWrapper->execute("DELETE FROM orders WHERE id = :id", [':id' => $order2]);
    }
    if ($shipmentOrderId) {
        $shipments = $dbWrapper->query("SELECT id FROM shipments WHERE order_id = :oid", [':oid' => $shipmentOrderId]);
        foreach ($shipments as $sh) {
            $dbWrapper->execute("DELETE FROM order_shipment_items WHERE shipment_id = :sid", [':sid' => $sh['id']]);
            $dbWrapper->execute("DELETE FROM shipments WHERE id = :id", [':id' => $sh['id']]);
        }
        $dbWrapper->execute("DELETE FROM order_status_history WHERE order_id = :id", [':id' => $shipmentOrderId]);
        $dbWrapper->execute("DELETE FROM order_items WHERE order_id = :id", [':id' => $shipmentOrderId]);
        $dbWrapper->execute("DELETE FROM orders WHERE id = :id", [':id' => $shipmentOrderId]);
    }
    return true;
});

echo "\n" . str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo " RESULT: {$passed}/{$total} tests PASSED" . ($failed > 0 ? ", {$failed} FAILED" : '') . "\n";
echo str_repeat('=', 60) . "\n\n";

if ($failed === 0) {
    echo "✅ ALL SPRINT 31 OMS V2 TESTS PASSED SUCCESSFULLY!\n\n";
} else {
    echo "⚠️ Some tests failed. Please review execution log.\n\n";
    exit(1);
}
