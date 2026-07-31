<?php
declare(strict_types=1);

/**
 * Sprint 32 - Enterprise WMS - CLI Test Suite
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
use App\Repositories\WarehouseRepository;
use App\Services\WarehouseService;
use App\Services\AuditLogger;

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

$auditLogger = new AuditLogger($dbWrapper);
$warehouseRepo = new WarehouseRepository($dbWrapper);
$warehouseService = new WarehouseService($warehouseRepo, $dbWrapper, $auditLogger);

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
echo " SPRINT 32 - ENTERPRISE WMS CLI TEST SUITE\n";
echo str_repeat('=', 60) . "\n\n";

// --- SECTION 1: Schema Checks ---
echo "[SECTION 1] Database WMS Schema Verification\n";

test('warehouse_locations table is present', function() use ($pdo) {
    $stmt = $pdo->query("SHOW TABLES LIKE 'warehouse_locations'");
    return $stmt->rowCount() > 0 ? true : 'warehouse_locations is missing';
});

test('warehouse transfers & counts tables present', function() use ($pdo) {
    $stmt1 = $pdo->query("SHOW TABLES LIKE 'warehouse_transfers'");
    $stmt2 = $pdo->query("SHOW TABLES LIKE 'inventory_counts'");
    return ($stmt1->rowCount() > 0 && $stmt2->rowCount() > 0) ? true : 'Transfer or Count tables missing';
});

test('composite unique key constraint updated', function() use ($pdo) {
    $stmt = $pdo->query("SHOW INDEX FROM inventories WHERE KEY_NAME = 'unique_product_variant_warehouse'");
    return $stmt->rowCount() > 0 ? true : 'unique_product_variant_warehouse composite constraint not found';
});

// --- SECTION 2: Repository Queries ---
echo "\n[SECTION 2] Warehouse Repository Query Functions\n";

test('getAll returns warehouses', function() use ($warehouseRepo) {
    $list = $warehouseRepo->getAll();
    return count($list) > 0 ? true : 'No warehouses fetched';
});

test('getLocations returns locations', function() use ($warehouseRepo) {
    $list = $warehouseRepo->getLocations(1);
    return count($list) > 0 ? true : 'No locations fetched for warehouse 1';
});

test('getDashboardStats returns KPI stats', function() use ($warehouseRepo) {
    $stats = $warehouseRepo->getDashboardStats(1);
    return isset($stats['total_products']) && isset($stats['total_stock']) ? true : 'KPI stats missing';
});

// --- SECTION 3: Service Operations ---
echo "\n[SECTION 3] Warehouse Service Operations\n";

$testInventoryId1 = null;
$testInventoryId2 = null;
$origStock1 = 0;
$origStock2 = 0;

test('Adjust stock increases and creates inventories correctly', function() use ($dbWrapper, $warehouseService, &$testInventoryId1, &$testInventoryId2, &$origStock1, &$origStock2) {
    $row1 = $dbWrapper->query("SELECT stock FROM inventories WHERE product_id = 1 AND warehouse_id = 1 LIMIT 1");
    $origStock1 = !empty($row1) ? (int)$row1[0]['stock'] : 0;

    $row2 = $dbWrapper->query("SELECT stock FROM inventories WHERE product_id = 1 AND warehouse_id = 2 LIMIT 1");
    $origStock2 = !empty($row2) ? (int)$row2[0]['stock'] : 0;

    $testInventoryId1 = $warehouseService->adjustStock(1, null, 1, 50, 'giriş', 'Test mal kabul');
    $testInventoryId2 = $warehouseService->adjustStock(1, null, 2, 20, 'giriş', 'Test mal kabul Ege');

    return ($testInventoryId1 > 0 && $testInventoryId2 > 0) ? true : 'Failed to adjust stock in multiple warehouses';
});

test('Initiate warehouse transfer request', function() use ($warehouseService, &$transferId) {
    $items = [
        ['product_id' => 1, 'variant_id' => null, 'quantity' => 10]
    ];
    $transferId = $warehouseService->initiateTransfer(1, 2, $items, 1);
    return $transferId > 0 ? true : 'Failed to initiate transfer';
});

test('Advance transfer status approved -> shipped -> completed and verify stock balances', function() use ($dbWrapper, $warehouseService, &$transferId, &$testInventoryId1, &$testInventoryId2, &$origStock1, &$origStock2) {
    // 1. Approve
    $warehouseService->updateTransferStatus($transferId, 'approved', 1);
    
    // 2. Ship (Deducts 10 units from Warehouse 1)
    $warehouseService->updateTransferStatus($transferId, 'shipped', 1);
    
    $inv1 = $dbWrapper->query("SELECT stock FROM inventories WHERE id = :id", [':id' => $testInventoryId1]);
    $expected1 = $origStock1 + 40;
    if ((int)$inv1[0]['stock'] !== $expected1) {
        return "Origin stock did not deduct. Expected: {$expected1}, Got: " . $inv1[0]['stock'];
    }

    // 3. Complete (Adds 10 units to Warehouse 2)
    $warehouseService->updateTransferStatus($transferId, 'completed', 1);
    
    $inv2 = $dbWrapper->query("SELECT stock FROM inventories WHERE id = :id", [':id' => $testInventoryId2]);
    $expected2 = $origStock2 + 30;
    if ((int)$inv2[0]['stock'] !== $expected2) {
        return "Target stock did not add. Expected: {$expected2}, Got: " . $inv2[0]['stock'];
    }

    return true;
});

test('Reconcile cycle counts and apply differences', function() use ($dbWrapper, $warehouseService, &$testInventoryId1, &$origStock1) {
    // Expected stock before count: origStock1 + 40
    // Reconciled stock count: origStock1 + 42
    $targetCount = $origStock1 + 42;
    $items = [
        $testInventoryId1 => $targetCount
    ];
    $countId = $warehouseService->executeCount(1, 'cycle', $items, 1);
    
    $cnt = $dbWrapper->query("SELECT status FROM inventory_counts WHERE id = :id", [':id' => $countId]);
    if (empty($cnt) || $cnt[0]['status'] !== 'completed') {
        return 'Count status is not completed';
    }

    $inv = $dbWrapper->query("SELECT stock FROM inventories WHERE id = :id", [':id' => $testInventoryId1]);
    if ((int)$inv[0]['stock'] !== $targetCount) {
        return "Stock not reconciled. Expected: {$targetCount}, Got: " . $inv[0]['stock'];
    }

    return true;
});

// --- CLEANUP ---
test('Cleanup test WMS transactions', function() use ($dbWrapper, &$testInventoryId1, &$testInventoryId2, &$origStock1, &$origStock2) {
    $dbWrapper->execute("DELETE FROM warehouse_transfer_items");
    $dbWrapper->execute("DELETE FROM warehouse_transfers");
    $dbWrapper->execute("DELETE FROM inventory_count_items");
    $dbWrapper->execute("DELETE FROM inventory_counts");
    
    // Reset stock changes
    if ($testInventoryId1 && $testInventoryId2) {
        $dbWrapper->execute("DELETE FROM inventory_movements WHERE inventory_id IN (:id1, :id2)", [':id1' => $testInventoryId1, ':id2' => $testInventoryId2]);
        $dbWrapper->execute("UPDATE inventories SET stock = :stock WHERE id = :id", [':stock' => $origStock1, ':id' => $testInventoryId1]);
        $dbWrapper->execute("UPDATE inventories SET stock = :stock WHERE id = :id", [':stock' => $origStock2, ':id' => $testInventoryId2]);
    }
    
    return true;
});

echo "\n" . str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo " RESULT: {$passed}/{$total} tests PASSED" . ($failed > 0 ? ", {$failed} FAILED" : '') . "\n";
echo str_repeat('=', 60) . "\n\n";

if ($failed === 0) {
    echo "✅ ALL SPRINT 32 WMS TESTS PASSED SUCCESSFULLY!\n\n";
} else {
    echo "⚠️ Some tests failed. Please review execution log.\n\n";
    exit(1);
}
