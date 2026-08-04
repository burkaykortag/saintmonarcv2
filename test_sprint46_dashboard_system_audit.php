<?php
/**
 * Sprint 46 - Dashboard System Audit Test Script
 * SaintMonarc E-Commerce Platform
 *
 * Gerçek kullanıcı akışını simüle eden kapsamlı sistem testi.
 * PASS / FAIL sonuçları üretir.
 *
 * Çalıştır: http://localhost/SaintMonarc/test_sprint46_dashboard_system_audit.php
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Bootstrap the app
define('APP_ROOT', dirname(__FILE__));
$configPath = APP_ROOT . '/config/database.php';

$results = [];
$totalPass = 0;
$totalFail = 0;
$phpErrors = [];
$sqlErrors = [];
$jsIssues = [];
$routeIssues = [];
$securityIssues = [];

// ─────────────────────────────────────────────────
// Helper functions
// ─────────────────────────────────────────────────
function pass(string $id, string $label, string $detail = ''): void {
    global $results, $totalPass;
    $results[] = ['id' => $id, 'label' => $label, 'status' => 'PASS', 'detail' => $detail, 'color' => 'green'];
    $totalPass++;
}

function fail(string $id, string $label, string $screen, string $route, string $controller, string $method,
              string $error, string $cause, string $file, string $fix): void {
    global $results, $totalFail;
    $results[] = [
        'id' => $id, 'label' => $label, 'status' => 'FAIL',
        'screen' => $screen, 'route' => $route, 'controller' => $controller,
        'method' => $method, 'error' => $error, 'cause' => $cause,
        'file' => $file, 'fix' => $fix, 'color' => 'red'
    ];
    $totalFail++;
}

function warn(string $id, string $label, string $detail): void {
    global $results;
    $results[] = ['id' => $id, 'label' => $label, 'status' => 'WARN', 'detail' => $detail, 'color' => 'orange'];
}

// ─────────────────────────────────────────────────
// DATABASE CONNECTION
// ─────────────────────────────────────────────────
$pdo = null;
try {
    if (!file_exists($configPath)) {
        throw new Exception("config/database.php bulunamadı");
    }
    $dbConf = require $configPath;
    $host = $dbConf['host'] ?? '127.0.0.1';
    $dbname = $dbConf['database'] ?? $dbConf['dbname'] ?? 'saintmonarc';
    $user = $dbConf['username'] ?? $dbConf['user'] ?? 'root';
    $pass = $dbConf['password'] ?? $dbConf['pass'] ?? '';
    $charset = $dbConf['charset'] ?? 'utf8mb4';

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset={$charset}",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    pass('DB-000', 'Database Connection', "Host: {$host}, DB: {$dbname}");
} catch (Throwable $e) {
    fail('DB-000', 'Database Connection',
        '/admin/dashboard', 'N/A', 'DashboardService', 'getAnalytics',
        $e->getMessage(), 'PDO bağlantısı kurulamadı',
        'config/database.php', 'config/database.php dosyasını ve MySQL servisini kontrol et');
    $sqlErrors[] = "DB-000: " . $e->getMessage();
}

// ─────────────────────────────────────────────────
// SYSTEM-001: Database Health Check (Real Connection)
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $row = $pdo->query("SELECT 1 AS ping")->fetch();
        if (($row['ping'] ?? 0) === 1) {
            pass('SYSTEM-001', 'Database Health Check', 'PDO SELECT 1 başarıyla döndü');
        } else {
            fail('SYSTEM-001', 'Database Health Check',
                '/admin/dashboard', 'N/A', 'DashboardService', 'getAnalytics',
                'SELECT 1 beklenmedik sonuç döndü', 'MySQL yanıt vermiyor',
                'app/Services/DashboardService.php', 'MySQL servisini yeniden başlat');
        }
    } catch (Throwable $e) {
        fail('SYSTEM-001', 'Database Health Check',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getAnalytics',
            $e->getMessage(), 'PDO exception',
            'app/Services/DashboardService.php', 'DB bağlantısını kontrol et');
        $sqlErrors[] = $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-002: Table Existence Checks
// ─────────────────────────────────────────────────
$requiredTables = [
    'orders', 'order_items', 'products', 'product_translations',
    'inventories', 'users', 'user_profiles', 'categories',
    'category_translations', 'purchase_orders', 'suppliers',
    'admins', 'product_category_relations'
];

if ($pdo) {
    foreach ($requiredTables as $tbl) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tbl}'");
            if ($stmt->rowCount() > 0) {
                pass("SYSTEM-002-{$tbl}", "Table Exists: {$tbl}");
            } else {
                fail("SYSTEM-002-{$tbl}", "Table Exists: {$tbl}",
                    '/admin/dashboard', 'N/A', 'DashboardService', 'getAnalytics',
                    "Tablo '{$tbl}' veritabanında bulunamadı",
                    'Tablo oluşturulmamış veya farklı isimde',
                    'app/Services/DashboardService.php',
                    "Migration çalıştır veya tablo adını düzelt");
                $sqlErrors[] = "Tablo yok: {$tbl}";
            }
        } catch (Throwable $e) {
            fail("SYSTEM-002-{$tbl}", "Table Exists: {$tbl}",
                '/admin/dashboard', 'N/A', 'DashboardService', 'getAnalytics',
                $e->getMessage(), 'SHOW TABLES sorgusu başarısız',
                'app/Services/DashboardService.php', 'DB bağlantısını kontrol et');
            $sqlErrors[] = $e->getMessage();
        }
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-003: orders table schema validation
// ─────────────────────────────────────────────────
if ($pdo) {
    $expectedOrderCols = ['id', 'user_id', 'grand_total', 'status', 'created_at'];
    try {
        $stmt = $pdo->query("DESCRIBE orders");
        $cols = array_column($stmt->fetchAll(), 'Field');
        foreach ($expectedOrderCols as $col) {
            if (in_array($col, $cols)) {
                pass("SYSTEM-003-{$col}", "orders.{$col} column exists");
            } else {
                fail("SYSTEM-003-{$col}", "orders.{$col} column exists",
                    '/admin/dashboard', 'N/A', 'DashboardService', 'getSalesStats',
                    "orders.{$col} kolonu bulunamadı",
                    "SQL sorgusunda kullanılan kolon DB'de yok",
                    'app/Services/DashboardService.php',
                    "Migration ile kolonu ekle veya sorguyu düzelt");
                $sqlErrors[] = "Kolon yok: orders.{$col}";
            }
        }
    } catch (Throwable $e) {
        fail('SYSTEM-003', 'Orders table schema',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getSalesStats',
            $e->getMessage(), 'DESCRIBE orders hatası',
            'app/Services/DashboardService.php', 'DB bağlantısını kontrol et');
        $sqlErrors[] = $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-004: products table schema validation
// ─────────────────────────────────────────────────
if ($pdo) {
    $expectedProductCols = ['id', 'is_active', 'status', 'deleted_at', 'total_stock', 'critical_stock', 'cost_price'];
    try {
        $stmt = $pdo->query("DESCRIBE products");
        $cols = array_column($stmt->fetchAll(), 'Field');
        foreach ($expectedProductCols as $col) {
            if (in_array($col, $cols)) {
                pass("SYSTEM-004-{$col}", "products.{$col} column exists");
            } else {
                if (in_array($col, ['total_stock', 'critical_stock', 'cost_price'])) {
                    fail("SYSTEM-004-{$col}", "products.{$col} column exists",
                        '/admin/dashboard', 'N/A', 'DashboardService', 'getStockStatus',
                        "products.{$col} kolonu bulunamadı",
                        "DashboardService::getStockStatus kritik stok hesabında bu kolonu kullanıyor",
                        'app/Services/DashboardService.php',
                        "Migration ile kolonu ekle veya sorguyu gerçek şemayla eşleştir");
                    $sqlErrors[] = "Kritik kolon yok: products.{$col}";
                } else {
                    warn("SYSTEM-004-{$col}", "products.{$col} column exists",
                        "Kolon yok ama kritik değil: {$col}");
                }
            }
        }
    } catch (Throwable $e) {
        fail('SYSTEM-004', 'Products table schema',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getStockStatus',
            $e->getMessage(), 'DESCRIBE products hatası',
            'app/Services/DashboardService.php', 'DB bağlantısını kontrol et');
        $sqlErrors[] = $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-005: purchase_orders table schema validation
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_orders'");
        if ($stmt->rowCount() > 0) {
            $poStmt = $pdo->query("DESCRIBE purchase_orders");
            $poCols = array_column($poStmt->fetchAll(), 'Field');
            $expectedPOCols = ['id', 'grand_total', 'status', 'deleted_at', 'expected_delivery'];
            foreach ($expectedPOCols as $col) {
                if (in_array($col, $poCols)) {
                    pass("SYSTEM-005-{$col}", "purchase_orders.{$col} column exists");
                } else {
                    fail("SYSTEM-005-{$col}", "purchase_orders.{$col} column exists",
                        '/admin/dashboard', 'N/A', 'DashboardService', 'getProcurementStats',
                        "purchase_orders.{$col} kolonu bulunamadı",
                        "getProcurementStats bu kolonu kullanıyor, SQLSTATE hatası üretebilir",
                        'app/Services/DashboardService.php',
                        "Migration ile kolonu ekle");
                    $sqlErrors[] = "PO kolon yok: purchase_orders.{$col}";
                }
            }
        } else {
            fail('SYSTEM-005', 'purchase_orders table',
                '/admin/dashboard', 'N/A', 'DashboardService', 'getProcurementStats',
                "purchase_orders tablosu yok",
                "Tedarik PO widget'ı sahte veri gösteriyor",
                'app/Services/DashboardService.php',
                "Migration çalıştır veya tablo oluştur");
            $sqlErrors[] = "Tablo yok: purchase_orders";
        }
    } catch (Throwable $e) {
        fail('SYSTEM-005', 'purchase_orders schema', '/admin/dashboard', 'N/A',
            'DashboardService', 'getProcurementStats', $e->getMessage(), 'DESCRIBE hatası',
            'app/Services/DashboardService.php', 'DB bağlantısını kontrol et');
        $sqlErrors[] = $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-006: suppliers table schema validation
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'suppliers'");
        if ($stmt->rowCount() > 0) {
            $sStmt = $pdo->query("DESCRIBE suppliers");
            $sCols = array_column($sStmt->fetchAll(), 'Field');
            $expectedSupplierCols = ['company_name', 'score', 'deleted_at'];
            foreach ($expectedSupplierCols as $col) {
                if (in_array($col, $sCols)) {
                    pass("SYSTEM-006-{$col}", "suppliers.{$col} column exists");
                } else {
                    fail("SYSTEM-006-{$col}", "suppliers.{$col} column exists",
                        '/admin/dashboard', 'N/A', 'DashboardService', 'getProcurementStats',
                        "suppliers.{$col} kolonu bulunamadı",
                        "En iyi/riskli tedarikçi tespiti için bu kolon gerekli",
                        'app/Services/DashboardService.php',
                        "Migration ile kolonu ekle veya sorguyu düzelt");
                    $sqlErrors[] = "Supplier kolon yok: suppliers.{$col}";
                }
            }
        } else {
            fail('SYSTEM-006', 'suppliers table',
                '/admin/dashboard', 'N/A', 'DashboardService', 'getProcurementStats',
                "suppliers tablosu yok",
                "Tedarikçi widget'ı sahte veri gösteriyor (Yok/Yok)",
                'app/Services/DashboardService.php',
                "Migration çalıştır");
            $sqlErrors[] = "Tablo yok: suppliers";
        }
    } catch (Throwable $e) {
        fail('SYSTEM-006', 'suppliers schema', '/admin/dashboard', 'N/A',
            'DashboardService', 'getProcurementStats', $e->getMessage(), 'DESCRIBE hatası',
            'app/Services/DashboardService.php', 'DB bağlantısını kontrol et');
        $sqlErrors[] = $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-007: inventories table (getStockStatus)
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'inventories'");
        if ($stmt->rowCount() > 0) {
            $invStmt = $pdo->query("DESCRIBE inventories");
            $invCols = array_column($invStmt->fetchAll(), 'Field');
            if (in_array('stock', $invCols)) {
                pass('SYSTEM-007', 'inventories.stock column exists');
            } else {
                fail('SYSTEM-007', 'inventories.stock column exists',
                    '/admin/dashboard', 'N/A', 'DashboardService', 'getStockStatus',
                    "inventories.stock kolonu bulunamadı",
                    "getStockStatus: SELECT COUNT(*) FROM inventories WHERE stock = 0 sorgusunda kullanılıyor",
                    'app/Services/DashboardService.php',
                    "Stok kolonunu inventory tablosuna ekle");
                $sqlErrors[] = "Kolon yok: inventories.stock";
            }
        } else {
            fail('SYSTEM-007', 'inventories table',
                '/admin/dashboard', 'N/A', 'DashboardService', 'getStockStatus',
                "inventories tablosu yok",
                "Stok durumu widget'ı sıfır veya hatalı veri gösteriyor",
                'app/Services/DashboardService.php',
                "Migration çalıştır");
            $sqlErrors[] = "Tablo yok: inventories";
        }
    } catch (Throwable $e) {
        fail('SYSTEM-007', 'inventories schema', '/admin/dashboard', 'N/A',
            'DashboardService', 'getStockStatus', $e->getMessage(), 'DESCRIBE hatası',
            'app/Services/DashboardService.php', 'DB bağlantısını kontrol et');
        $sqlErrors[] = $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-008: DashboardService::getSalesStats real SQL
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $start = date('Y-m-d H:i:s', strtotime('-30 days'));
        $end = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT COUNT(*) as order_count, COALESCE(SUM(grand_total), 0) as total_sales
                               FROM orders
                               WHERE created_at BETWEEN :start AND :end AND status != 'cancelled'");
        $stmt->execute([':start' => $start, ':end' => $end]);
        $row = $stmt->fetch();
        pass('SYSTEM-008', 'getSalesStats SQL Query', "Sipariş Sayısı: {$row['order_count']}, Ciro: ₺{$row['total_sales']}");
    } catch (Throwable $e) {
        fail('SYSTEM-008', 'getSalesStats SQL Query',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getSalesStats',
            $e->getMessage(), 'orders sorgusunda kolon veya tablo hatası',
            'app/Services/DashboardService.php',
            "orders tablosu şemasını kontrol et: grand_total, status, created_at kolonları");
        $sqlErrors[] = "SYSTEM-008: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-009: COGS SQL (order_items + products JOIN)
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $start = date('Y-m-d H:i:s', strtotime('-30 days'));
        $end = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity * COALESCE(p.cost_price, 0)), 0) as cost_total
                               FROM order_items oi
                               JOIN orders o ON oi.order_id = o.id
                               JOIN products p ON oi.product_id = p.id
                               WHERE o.created_at BETWEEN :start AND :end AND o.status != 'cancelled'");
        $stmt->execute([':start' => $start, ':end' => $end]);
        $row = $stmt->fetch();
        pass('SYSTEM-009', 'COGS SQL Query (order_items + products)', "COGS: ₺{$row['cost_total']}");
    } catch (Throwable $e) {
        fail('SYSTEM-009', 'COGS SQL Query',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getSalesStats',
            $e->getMessage(), 'cost_price kolonu veya JOIN hatası',
            'app/Services/DashboardService.php',
            "products.cost_price kolonunu kontrol et");
        $sqlErrors[] = "SYSTEM-009: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-010: getStockStatus SQL
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE total_stock BETWEEN 1 AND critical_stock AND deleted_at IS NULL");
        $row = $stmt->fetch();
        pass('SYSTEM-010', 'Critical Stock SQL Query', "Kritik Stok: {$row['count']} ürün");
    } catch (Throwable $e) {
        fail('SYSTEM-010', 'Critical Stock SQL Query',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getStockStatus',
            $e->getMessage(), 'total_stock veya critical_stock kolonu yok',
            'app/Services/DashboardService.php',
            "products tablosuna total_stock ve critical_stock kolonlarını ekle");
        $sqlErrors[] = "SYSTEM-010: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-011: getProcurementStats SQL
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_orders'");
        if ($stmt->rowCount() > 0) {
            $r1 = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM purchase_orders WHERE status = 'completed' AND deleted_at IS NULL")->fetch();
            $r2 = $pdo->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status IN ('pending_approval', 'approved', 'sent') AND deleted_at IS NULL")->fetch();
            $r3 = $pdo->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status = 'sent' AND expected_delivery < CURDATE() AND deleted_at IS NULL")->fetch();
            pass('SYSTEM-011', 'getProcurementStats SQL Queries',
                "Tamamlanan PO: ₺{$r1['total']}, Bekleyen: {$r2['cnt']}, Geciken: {$r3['cnt']}");
        } else {
            warn('SYSTEM-011', 'getProcurementStats SQL Queries',
                'purchase_orders tablosu yok - try/catch ile varsayılan değerler kullanılıyor');
        }
    } catch (Throwable $e) {
        fail('SYSTEM-011', 'getProcurementStats SQL Queries',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getProcurementStats',
            $e->getMessage(), 'purchase_orders sorgu hatası',
            'app/Services/DashboardService.php',
            "purchase_orders şemasını kontrol et");
        $sqlErrors[] = "SYSTEM-011: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-012: getRecentOrders SQL (JOIN user_profiles)
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT o.*, CONCAT(up.first_name, ' ', up.last_name) as customer_name
                             FROM orders o
                             JOIN user_profiles up ON o.user_id = up.user_id
                             ORDER BY o.id DESC LIMIT 5");
        $rows = $stmt->fetchAll();
        pass('SYSTEM-012', 'getRecentOrders SQL Query', "Son " . count($rows) . " sipariş döndürüldü");
    } catch (Throwable $e) {
        fail('SYSTEM-012', 'getRecentOrders SQL Query',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getRecentOrders',
            $e->getMessage(), 'user_profiles JOIN hatası veya first_name/last_name kolonu yok',
            'app/Services/DashboardService.php',
            "user_profiles tablosunda first_name, last_name, user_id kolonlarını kontrol et");
        $sqlErrors[] = "SYSTEM-012: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-013: getRecentMembers SQL
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT u.id, u.email, u.created_at, CONCAT(up.first_name, ' ', up.last_name) as name, up.phone
                             FROM users u
                             JOIN user_profiles up ON u.id = up.user_id
                             ORDER BY u.id DESC LIMIT 5");
        $rows = $stmt->fetchAll();
        pass('SYSTEM-013', 'getRecentMembers SQL Query', "Son " . count($rows) . " üye döndürüldü");
    } catch (Throwable $e) {
        fail('SYSTEM-013', 'getRecentMembers SQL Query',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getRecentMembers',
            $e->getMessage(), 'users veya user_profiles JOIN hatası',
            'app/Services/DashboardService.php',
            "users ve user_profiles şemasını kontrol et");
        $sqlErrors[] = "SYSTEM-013: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-014: getCategorySales SQL (complex JOIN)
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $start = date('Y-m-d H:i:s', strtotime('-30 days'));
        $end = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT ct.name as category_name, COALESCE(SUM(oi.total), 0) as total_sales
                               FROM order_items oi
                               JOIN orders o ON oi.order_id = o.id
                               JOIN product_category_relations pcr ON oi.product_id = pcr.product_id
                               JOIN categories c ON pcr.category_id = c.id
                               JOIN category_translations ct ON c.id = ct.category_id
                               WHERE o.created_at BETWEEN :start AND :end AND o.status != 'cancelled'
                               GROUP BY ct.name
                               ORDER BY total_sales DESC");
        $stmt->execute([':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll();
        pass('SYSTEM-014', 'getCategorySales SQL Query', count($rows) . " kategori sonucu döndürüldü");
    } catch (Throwable $e) {
        fail('SYSTEM-014', 'getCategorySales SQL Query',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getCategorySales',
            $e->getMessage(), 'category_translations JOIN veya order_items.total kolonu hatası',
            'app/Services/DashboardService.php',
            "order_items.total kolonu ve JOIN zinciri şemasını kontrol et");
        $sqlErrors[] = "SYSTEM-014: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-015: getChartData SQL
// ─────────────────────────────────────────────────
if ($pdo) {
    try {
        $start = date('Y-m-d H:i:s', strtotime('-30 days'));
        $end = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT DATE(created_at) as date, COALESCE(SUM(grand_total), 0) as sales, COUNT(*) as orders
                               FROM orders
                               WHERE created_at BETWEEN :start AND :end AND status != 'cancelled'
                               GROUP BY DATE(created_at)
                               ORDER BY DATE(created_at) ASC");
        $stmt->execute([':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll();
        pass('SYSTEM-015', 'getChartData SQL Query', count($rows) . " günlük veri noktası döndürüldü");
    } catch (Throwable $e) {
        fail('SYSTEM-015', 'getChartData SQL Query',
            '/admin/dashboard', 'N/A', 'DashboardService', 'getChartData',
            $e->getMessage(), 'orders tablosunda created_at veya grand_total kolonu hatası',
            'app/Services/DashboardService.php',
            "orders şemasını doğrula");
        $sqlErrors[] = "SYSTEM-015: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-016: Route File Existence Checks
// ─────────────────────────────────────────────────
$routeFile = APP_ROOT . '/routes/admin.php';
if (file_exists($routeFile)) {
    $routeContent = file_get_contents($routeFile);

    $routeChecks = [
        'SYSTEM-016-R1' => ['/admin/dashboard', "get('/admin/dashboard'"],
        'SYSTEM-016-R2' => ['/admin/workflows', "get('/admin/workflows'"],
        'SYSTEM-016-R3' => ['/admin/shipping', "get('/admin/shipping'"],
        'SYSTEM-016-R4' => ['/admin/products/create', "get('/admin/products/create'"],
        'SYSTEM-016-R5' => ['/admin/orders/create', "get('/admin/orders/create'"],
        'SYSTEM-016-R6' => ['/admin/customers/create', "get('/admin/customers/create'"],
        'SYSTEM-016-R7' => ['/admin/promotions', "get('/admin/promotions'"],
    ];

    foreach ($routeChecks as $id => [$routeName, $needle]) {
        if (strpos($routeContent, $needle) !== false) {
            pass($id, "Route exists: {$routeName}");
        } else {
            fail($id, "Route exists: {$routeName}",
                '/admin/dashboard', $routeName, 'Router', 'N/A',
                "Route '{$routeName}' routes/admin.php içinde tanımlı değil",
                "Hızlı işlem butonu geçersiz URL'e yönlendiriyor",
                'routes/admin.php',
                "routes/admin.php dosyasına route ekle");
            $routeIssues[] = "Route yok: {$routeName}";
        }
    }
} else {
    fail('SYSTEM-016', 'Route File Exists',
        '/admin/dashboard', 'N/A', 'Router', 'N/A',
        'routes/admin.php bulunamadı', 'Route dosyası mevcut değil',
        'routes/admin.php', 'routes/admin.php dosyasını oluştur');
    $routeIssues[] = "routes/admin.php bulunamadı";
}

// ─────────────────────────────────────────────────
// SYSTEM-017: Controller File Existence
// ─────────────────────────────────────────────────
$controllerChecks = [
    'SYSTEM-017-C1' => ['AdminDashboardController', 'app/Controllers/AdminDashboardController.php'],
    'SYSTEM-017-C2' => ['WorkflowController', 'app/Controllers/WorkflowController.php'],
    'SYSTEM-017-C3' => ['DashboardService', 'app/Services/DashboardService.php'],
];

foreach ($controllerChecks as $id => [$name, $path]) {
    $fullPath = APP_ROOT . '/' . $path;
    if (file_exists($fullPath)) {
        pass($id, "File exists: {$name}", $path);
    } else {
        fail($id, "File exists: {$name}",
            '/admin/dashboard', 'N/A', $name, 'N/A',
            "{$path} dosyası bulunamadı",
            "Kritik controller/service dosyası eksik",
            $path, "Dosyayı oluştur");
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-018: Widget PHP File Existence
// ─────────────────────────────────────────────────
$widgetDir = APP_ROOT . '/resources/views/admin/dashboard/widgets/';
$requiredWidgets = [
    'SalesWidget', 'RevenueWidget', 'OrdersWidget', 'CustomersWidget',
    'ProductsWidget', 'AIWidget', 'ActivityWidget', 'WorkflowWidget',
    'ShippingWidget', 'AnalyticsWidget', 'CategoryBrandWidget', 'AIExecutiveWidget',
    'RealTimeSalesWidget', 'ActivityLogWidget', 'PaymentShippingWidget',
    'IadeTrendWidget', 'WidgetMarketWidget'
];

foreach ($requiredWidgets as $widget) {
    $widgetFile = $widgetDir . $widget . '.php';
    if (file_exists($widgetFile)) {
        // Check for syntax errors
        $output = [];
        exec("php -l " . escapeshellarg($widgetFile) . " 2>&1", $output, $exitCode);
        $outputStr = implode(' ', $output);
        if ($exitCode === 0 || strpos($outputStr, 'No syntax errors') !== false) {
            pass("SYSTEM-018-{$widget}", "Widget: {$widget} - File exists & syntax OK");
        } else {
            fail("SYSTEM-018-{$widget}", "Widget: {$widget} - Syntax Error",
                '/admin/dashboard', 'N/A', $widget, 'render',
                "PHP Syntax Error: {$outputStr}",
                "Widget dosyasında PHP syntax hatası var",
                "resources/views/admin/dashboard/widgets/{$widget}.php",
                "Syntax hatalarını düzelt");
            $phpErrors[] = "Widget syntax: {$widget}: {$outputStr}";
        }
    } else {
        fail("SYSTEM-018-{$widget}", "Widget: {$widget} - File missing",
            '/admin/dashboard', 'N/A', $widget, 'render',
            "Widget dosyası bulunamadı: {$widget}.php",
            "Dashboard'da widget include edilmiş ama PHP dosyası yok",
            "resources/views/admin/dashboard/widgets/{$widget}.php",
            "Widget PHP dosyasını oluştur");
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-019: Static/Fake Data Detection
// ─────────────────────────────────────────────────
// KPI counter values in dashboard are data-target="14890" etc. (hardcoded)
// But procurement data comes from real DB via DashboardService
// Activity feeds are simulated (setInterval simulation) - FAKE
$fakeDataItems = [
    'KPI Sayaçları (Günlük/Haftalık/Aylık Ciro)' => 'Hardcoded data-target değerleri. Gerçek DB verisi değil (sales[\'total_sales\'] kullanılmalı)',
    'Aktif Sepet (42 Adet)' => 'Statik hardcoded değer. Gerçek session/cart tablosu yok',
    'Terk Edilen Sepet (18 Adet)' => 'Statik hardcoded değer. Gerçek cart_items tablosu sorgulanmalı',
    'Aktif Üye Canlı (120 Çevrimiçi)' => 'Statik. Gerçek session tablosu sorgusuna bağlanmalı',
    'Bekleyen Kargo (8 Paket)' => 'Statik. shipments/orders tablosundan çekilmeli',
    'İade Bekleyen (2 Talep)' => 'Statik. returns/refunds tablosundan çekilmeli',
    'Canlı Sipariş Akışı Widget' => 'Tamamen simülasyon (setInterval JS). Gerçek DB verisi yok',
    'Canlı Aktivite Log Widget' => 'Tamamen simülasyon. Gerçek sistem logu yok',
    'AI Öneri Widget' => 'Statik hardcoded metin. Gerçek AI entegrasyonu yok',
    'Haftalık Satış Grafiği' => 'Hardcoded JS dizisi. getChartData() verisi kullanılmalı',
    'Fatura Kes Butonu' => 'alert() çağrısı. Gerçek fatura entegrasyonu yok',
    'AI Analiz Butonu' => 'alert() çağrısı. Gerçek AI servisi yok',
];

foreach ($fakeDataItems as $item => $reason) {
    warn("SYSTEM-019", "Fake/Static Data: {$item}", $reason);
}

// ─────────────────────────────────────────────────
// SYSTEM-020: Security Checks
// ─────────────────────────────────────────────────
// Check that dashboard route has 'admin' middleware
if (isset($routeContent)) {
    if (strpos($routeContent, "get('/admin/dashboard', [AdminDashboardController::class, 'index'], ['admin']") !== false) {
        pass('SYSTEM-020-AUTH', 'Dashboard route admin middleware', "Route ['admin'] middleware ile korunuyor");
    } else {
        fail('SYSTEM-020-AUTH', 'Dashboard route admin middleware',
            '/admin/dashboard', '/admin/dashboard', 'Router', 'admin middleware',
            "Dashboard route 'admin' middleware'i eksik",
            "Yetkisiz erişime açık olabilir",
            'routes/admin.php',
            "['admin'] middleware ekle");
        $securityIssues[] = "Dashboard admin middleware eksik";
    }
}

// Check admins table exists
if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
        if ($stmt->rowCount() > 0) {
            pass('SYSTEM-020-ADMINS', 'Admins table exists for auth check');
        } else {
            fail('SYSTEM-020-ADMINS', 'Admins table exists',
                '/admin/dashboard', '/admin/dashboard', 'AdminDashboardController', 'index',
                "admins tablosu bulunamadı",
                "Controller admin kullanıcısını admins tablosundan çekiyor",
                'app/Controllers/AdminDashboardController.php',
                "admins tablosunu oluştur");
            $securityIssues[] = "admins tablosu yok";
        }
    } catch (Throwable $e) {
        $sqlErrors[] = "SYSTEM-020: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-021: Quick Action Buttons Validity
// ─────────────────────────────────────────────────
$quickActionRoutes = [
    '/admin/products/create' => "create('/admin/products/create'",
    '/admin/orders/create'   => "create'",
    '/admin/promotions'      => "promotions",
    '/admin/customers/create'=> "customers/create'",
    '/admin/shipping'        => "get('/admin/shipping'",
    '/admin/workflows'       => "get('/admin/workflows'",
];

if (isset($routeContent)) {
    foreach ($quickActionRoutes as $routePath => $needle) {
        if (strpos($routeContent, ltrim($routePath, '/')) !== false || strpos($routeContent, $needle) !== false) {
            pass("SYSTEM-021-" . md5($routePath), "Quick Action Route: {$routePath}");
        } else {
            warn("SYSTEM-021-" . md5($routePath), "Quick Action Route: {$routePath}",
                "Route tanımı routes/admin.php içinde doğrulanamadı - 404 riski mevcut");
            $routeIssues[] = "Hızlı işlem route doğrulanamadı: {$routePath}";
        }
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-022: Dashboard View File Check
// ─────────────────────────────────────────────────
$dashViewFile = APP_ROOT . '/resources/views/admin/dashboard.php';
if (file_exists($dashViewFile)) {
    // Check for fake alert() calls
    $dashContent = file_get_contents($dashViewFile);
    $alertCount = substr_count($dashContent, "onclick=\"alert(");
    if ($alertCount > 0) {
        warn('SYSTEM-022-ALERT', 'Dashboard fake alert() buttons',
            "{$alertCount} adet alert() çağrısı var (Fatura Kes, AI Analiz). Gerçek işlev yok.");
    } else {
        pass('SYSTEM-022-ALERT', 'No fake alert() buttons');
    }

    // Check for fetch/AJAX calls
    if (strpos($dashContent, 'fetch(') !== false || strpos($dashContent, 'XMLHttpRequest') !== false) {
        pass('SYSTEM-022-AJAX', 'Dashboard has real AJAX/fetch calls');
    } else {
        warn('SYSTEM-022-AJAX', 'No real AJAX/fetch calls in dashboard',
            "Dashboard hiç fetch() veya XMLHttpRequest çağrısı yapmıyor. Tüm filtre değişiklikleri sadece JS animasyon (triggerFilterSimulation). Gerçek backend sorgusu tetiklenmiyor.");
        $jsIssues[] = "Dashboard filtre değişimlerinde gerçek AJAX çağrısı yok";
    }

    // Check for simulation code
    if (strpos($dashContent, 'startRealtimeSimulation') !== false) {
        warn('SYSTEM-022-SIM', 'Realtime simulation is fake setInterval',
            "startRealtimeSimulation() fonksiyonu gerçek WebSocket veya SSE değil, sahte setInterval simülasyonu kullanıyor");
        $jsIssues[] = "Realtime simulation sahte setInterval - gerçek veri yok";
    }
} else {
    fail('SYSTEM-022', 'Dashboard view file exists',
        '/admin/dashboard', 'N/A', 'AdminDashboardController', 'index',
        'resources/views/admin/dashboard.php bulunamadı',
        'View dosyası eksik', 'resources/views/admin/dashboard.php',
        'View dosyasını oluştur');
}

// ─────────────────────────────────────────────────
// SYSTEM-023: AdminDashboardController method check
// ─────────────────────────────────────────────────
$controllerFile = APP_ROOT . '/app/Controllers/AdminDashboardController.php';
if (file_exists($controllerFile)) {
    $ctrlContent = file_get_contents($controllerFile);
    if (strpos($ctrlContent, 'public function index') !== false) {
        pass('SYSTEM-023-INDEX', 'AdminDashboardController::index() method exists');
    } else {
        fail('SYSTEM-023-INDEX', 'AdminDashboardController::index() method',
            '/admin/dashboard', '/admin/dashboard', 'AdminDashboardController', 'index',
            'index() methodu bulunamadı', 'Controller method eksik',
            'app/Controllers/AdminDashboardController.php',
            'index() metodunu ekle');
    }
    if (strpos($ctrlContent, 'DashboardService') !== false) {
        pass('SYSTEM-023-SERVICE', 'DashboardService injected in Controller');
    } else {
        fail('SYSTEM-023-SERVICE', 'DashboardService injection',
            '/admin/dashboard', '/admin/dashboard', 'AdminDashboardController', '__construct',
            'DashboardService inject edilmemiş', 'Dependency injection eksik',
            'app/Controllers/AdminDashboardController.php',
            'DashboardService inject et');
    }
}

// ─────────────────────────────────────────────────
// SYSTEM-024: Apache Error Log Check
// ─────────────────────────────────────────────────
$errorLog = 'C:\\xampp\\apache\\logs\\error.log';
if (file_exists($errorLog)) {
    $logContent = file_get_contents($errorLog);
    $logLines = explode("\n", $logContent);
    $recentErrors = array_filter(array_slice($logLines, -100), fn($l) => strpos($l, 'dashboard') !== false || strpos($l, 'DashboardService') !== false);
    if (empty($recentErrors)) {
        pass('SYSTEM-024', 'Apache Error Log - No recent dashboard errors');
    } else {
        $errorSample = array_slice(array_values($recentErrors), 0, 3);
        warn('SYSTEM-024', 'Apache Error Log - Dashboard related errors found',
            implode("\n", $errorSample));
    }
} else {
    warn('SYSTEM-024', 'Apache Error Log not found', 'Log dosyası bulunamadı: ' . $errorLog);
}

// ─────────────────────────────────────────────────
// OUTPUT HTML REPORT
// ─────────────────────────────────────────────────
$passCount = $totalPass;
$failCount = $totalFail;
$warnCount = count(array_filter($results, fn($r) => $r['status'] === 'WARN'));

?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sprint 46 - Dashboard System Audit Report</title>
<style>
body { font-family: 'Courier New', monospace; background: #0d0d0d; color: #e0e0e0; padding: 20px; }
h1 { color: #c5a880; }
h2 { color: #c5a880; border-bottom: 1px solid #333; padding-bottom: 6px; }
.pass { color: #10b981; }
.fail { color: #ef4444; }
.warn { color: #f59e0b; }
table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 13px; }
th { background: #1a1a1a; color: #c5a880; padding: 8px; text-align: left; }
td { padding: 6px 8px; border-bottom: 1px solid #222; vertical-align: top; }
tr.pass-row { background: rgba(16,185,129,0.04); }
tr.fail-row { background: rgba(239,68,68,0.07); }
tr.warn-row { background: rgba(245,158,11,0.05); }
.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.badge-pass { background: #10b981; color: #fff; }
.badge-fail { background: #ef4444; color: #fff; }
.badge-warn { background: #f59e0b; color: #000; }
.summary { background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
.summary-item { display: inline-block; margin-right: 30px; }
.summary-item .num { font-size: 32px; font-weight: bold; }
pre { background: #111; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; color: #aaa; }
</style>
</head>
<body>
<h1>⚙ SAINTMONARC SPRINT 46 — Dashboard System Audit Report</h1>
<p style="color:#888">Çalıştırıldı: <?= date('Y-m-d H:i:s') ?></p>

<div class="summary">
    <div class="summary-item"><div class="num" style="color:#c5a880"><?= $passCount + $failCount + $warnCount ?></div><div>TOPLAM TEST</div></div>
    <div class="summary-item"><div class="num pass"><?= $passCount ?></div><div>PASS ✓</div></div>
    <div class="summary-item"><div class="num fail"><?= $failCount ?></div><div>FAIL ✗</div></div>
    <div class="summary-item"><div class="num warn"><?= $warnCount ?></div><div>WARN ⚠</div></div>
    <div class="summary-item"><div class="num" style="color:#ef4444"><?= count($sqlErrors) ?></div><div>SQL ERROR</div></div>
    <div class="summary-item"><div class="num" style="color:#f59e0b"><?= count($routeIssues) ?></div><div>ROUTE ISSUE</div></div>
    <div class="summary-item"><div class="num" style="color:#60a5fa"><?= count($jsIssues) ?></div><div>JS ISSUE</div></div>
    <div class="summary-item"><div class="num" style="color:#a78bfa"><?= count($securityIssues) ?></div><div>SECURITY</div></div>
</div>

<h2>📋 Test Sonuçları</h2>
<table>
<tr>
    <th>ID</th>
    <th>Test Adı</th>
    <th>Sonuç</th>
    <th>Detay</th>
</tr>
<?php foreach ($results as $r): ?>
<tr class="<?= strtolower($r['status']) ?>-row">
    <td><?= htmlspecialchars($r['id']) ?></td>
    <td><?= htmlspecialchars($r['label']) ?></td>
    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
    <td>
        <?php if ($r['status'] === 'FAIL'): ?>
            <strong>Hata:</strong> <?= htmlspecialchars($r['error'] ?? '') ?><br>
            <strong>Neden:</strong> <?= htmlspecialchars($r['cause'] ?? '') ?><br>
            <strong>Dosya:</strong> <code><?= htmlspecialchars($r['file'] ?? '') ?></code><br>
            <strong>Çözüm:</strong> <?= htmlspecialchars($r['fix'] ?? '') ?>
        <?php else: ?>
            <?= htmlspecialchars($r['detail'] ?? '') ?>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php if (!empty($sqlErrors)): ?>
<h2>🔴 SQL Hataları</h2>
<pre><?= htmlspecialchars(implode("\n", $sqlErrors)) ?></pre>
<?php endif; ?>

<?php if (!empty($routeIssues)): ?>
<h2>🟡 Route Sorunları</h2>
<pre><?= htmlspecialchars(implode("\n", $routeIssues)) ?></pre>
<?php endif; ?>

<?php if (!empty($jsIssues)): ?>
<h2>🔵 JavaScript Sorunları</h2>
<pre><?= htmlspecialchars(implode("\n", $jsIssues)) ?></pre>
<?php endif; ?>

<?php if (!empty($securityIssues)): ?>
<h2>🟣 Güvenlik Sorunları</h2>
<pre><?= htmlspecialchars(implode("\n", $securityIssues)) ?></pre>
<?php endif; ?>

<h2>📝 Sahte/Statik Veri Tespiti (WARN listesi)</h2>
<table>
<tr><th>Öğe</th><th>Sorun</th></tr>
<?php foreach (array_filter($results, fn($r) => $r['status'] === 'WARN') as $r): ?>
<tr class="warn-row">
    <td><?= htmlspecialchars($r['label']) ?></td>
    <td><?= htmlspecialchars($r['detail']) ?></td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
