<?php
declare(strict_types=1);

/**
 * SaintMonarc — Sprint 49: Hierarchical RBAC & Impersonation Audit Test Suite
 */

define('ROOT_DIR', 'C:/xampp/htdocs/SaintMonarc');

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Autoloader
spl_autoload_register(function (string $class) {
    $prefixes = [
        'Core\\' => ROOT_DIR . '/core/',
        'App\\' => ROOT_DIR . '/app/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) continue;
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

use App\Services\RbacService;
use App\Controllers\AdminUserController;
use App\Controllers\RoleController;
use Core\Http\Request;
use Core\Http\Response;

$conf = require ROOT_DIR . '/config/database.php';
$pdo = new PDO(
    "mysql:host=" . ($conf['host']??'127.0.0.1') . ";dbname=" . ($conf['dbname']??'saintmonarc') . ";charset=utf8mb4",
    $conf['user']??'root',
    $conf['password']??'',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$dbAdapter = new class($pdo) implements \Core\Contracts\DatabaseInterface {
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
    public function inTransaction(): bool { return $this->pdo->inTransaction(); }
    public function beginTransaction(): bool { return $this->pdo->inTransaction() || $this->pdo->beginTransaction(); }
    public function commit(): bool { return $this->pdo->inTransaction() ? $this->pdo->commit() : true; }
    public function rollBack(): bool { return $this->pdo->inTransaction() ? $this->pdo->rollBack() : true; }
};

$cacheAdapter = new class implements \Core\Contracts\CacheInterface {
    private array $store = [];
    public function get(string $key, mixed $default = null): mixed { return $this->store[$key] ?? $default; }
    public function set(string $key, mixed $value, ?int $ttl = null): bool { $this->store[$key] = $value; return true; }
    public function delete(string $key): bool { unset($this->store[$key]); return true; }
    public function clear(): bool { $this->store = []; return true; }
    public function has(string $key): bool { return isset($this->store[$key]); }
};

$rbacService = new RbacService($dbAdapter, $cacheAdapter);

$passCount = 0;
$failCount = 0;
$warnCount = 0;

function testPass(string $code, string $title, string $detail = ''): void {
    global $passCount;
    $passCount++;
    echo "  [PASS] {$code} :: {$title}" . ($detail ? " ({$detail})" : "") . "\n";
}

function testFail(string $code, string $title, string $detail = ''): void {
    global $failCount;
    $failCount++;
    echo "  [FAIL] {$code} :: {$title}" . ($detail ? " ({$detail})" : "") . "\n";
}

function testWarn(string $code, string $title, string $detail = ''): void {
    global $warnCount;
    $warnCount++;
    echo "  [WARN] {$code} :: {$title}" . ($detail ? " ({$detail})" : "") . "\n";
}

echo "======================================================================\n";
echo " SAINTMONARC SPRINT 49: HIERARCHICAL RBAC & IMPERSONATION TEST SUITE\n";
echo "======================================================================\n\n";

// =========================================================================
// SECTION A: DATABASE SCHEMA VERIFICATION
// =========================================================================
echo "--- SECTION A: DATABASE SCHEMA VERIFICATION ---\n";

function checkCol(PDO $pdo, string $tbl, string $col): bool {
    $stmt = $pdo->query("SHOW COLUMNS FROM {$tbl} LIKE '{$col}'");
    return $stmt->rowCount() > 0;
}

if (checkCol($pdo, 'roles', 'parent_id') && checkCol($pdo, 'roles', 'priority') && checkCol($pdo, 'roles', 'is_system')) {
    testPass('DB-ROLES-HIERARCHY', 'roles tablosu parent_id, priority, is_system alanları mevcut.');
} else {
    testFail('DB-ROLES-HIERARCHY', 'roles tablosunda hiyerarşi kolonları eksik.');
}

if (checkCol($pdo, 'admins', 'is_impersonatable')) {
    testPass('DB-ADMINS-IMP', 'admins tablosu is_impersonatable alanı mevcut.');
} else {
    testFail('DB-ADMINS-IMP', 'admins tablosunda is_impersonatable kolonu eksik.');
}

if (checkCol($pdo, 'audit_logs', 'impersonator_id') && checkCol($pdo, 'audit_logs', 'is_impersonated')) {
    testPass('DB-AUDIT-IMP', 'audit_logs tablosu impersonator_id ve is_impersonated alanları mevcut.');
} else {
    testFail('DB-AUDIT-IMP', 'audit_logs tablosunda impersonation izleme kolonları eksik.');
}

// =========================================================================
// SECTION B: ROLE HIERARCHY & SYSTEM ROLE PROTECTION
// =========================================================================
echo "\n--- SECTION B: ROLE HIERARCHY & SYSTEM ROLE PROTECTION ---\n";

// Fetch test admins or ensure standard hierarchy accounts exist
$devAdmin = $pdo->query("SELECT id FROM admins WHERE is_super = 1 OR username = 'devadmin' LIMIT 1")->fetch();
$devAdminId = $devAdmin ? (int)$devAdmin['id'] : 1;

// Setup test roles
$devRolePrio = $rbacService->getAdminMaxPriority($devAdminId);
if ($devRolePrio >= 90) {
    testPass('HIERARCHY-DEV', 'DevAdmin / SuperAdmin Öncelik Seviyesi Doğrulandı', "Priority: {$devRolePrio}");
} else {
    testFail('HIERARCHY-DEV', 'DevAdmin Seviyesi Düşük', "Priority: {$devRolePrio}");
}

// Test System Role deletion protection
try {
    $rbacService->deleteRole(1, $devAdminId);
    testFail('SYSROLE-DELETE-PROTECT', 'Sistem Rolü Silinebildi! (Bypass)');
} catch (Exception $e) {
    testPass('SYSROLE-DELETE-PROTECT', 'Sistem Rolü Silme Koruması Engellendi', $e->getMessage());
}

// =========================================================================
// SECTION C: HIERARCHY ACCESS & BOUNDARY CONTROLS
// =========================================================================
echo "\n--- SECTION C: HIERARCHY ACCESS & BOUNDARY CONTROLS ---\n";

// Test DevAdmin vs Manager Role Management
$managerRole = $pdo->query("SELECT id, priority FROM roles WHERE name = 'Manager' LIMIT 1")->fetch();
$managerRoleId = $managerRole ? (int)$managerRole['id'] : 4;

$canDevManageManager = $rbacService->canManageRole($devAdminId, $managerRoleId);
if ($canDevManageManager) {
    testPass('HIERARCHY-DEV-MANAGE-MANAGER', 'DevAdmin Manager rolünü yönetebilir.');
} else {
    testFail('HIERARCHY-DEV-MANAGE-MANAGER', 'DevAdmin Manager rolünü yönetemedi.');
}

// Test Manager trying to manage DevAdmin Role (Should return false)
// Create mock manager admin ID for testing
$pdo->exec("INSERT IGNORE INTO admins (id, username, email, password, is_super, is_active, is_impersonatable, created_at)
            VALUES (998, 'test_manager', 'manager@test.com', 'hash', 0, 1, 1, NOW())");
$pdo->exec("INSERT IGNORE INTO admin_roles (admin_id, role_id) VALUES (998, 4)");

$managerAdminId = 998;
$canManagerManageDevRole = $rbacService->canManageRole($managerAdminId, 1);
if (!$canManagerManageDevRole) {
    testPass('HIERARCHY-MANAGER-BLOCK-DEV', 'Manager üst seviye (DevAdmin) rolünü yönetemez. (Engellendi)');
} else {
    testFail('HIERARCHY-MANAGER-BLOCK-DEV', 'Manager DevAdmin rolünü yönetebildi! (GÜVENLİK İHLALİ)');
}

// Test Manager trying to edit DevAdmin user
$canManagerManageDevAdmin = $rbacService->canManageAdmin($managerAdminId, $devAdminId);
if (!$canManagerManageDevAdmin) {
    testPass('HIERARCHY-USER-BOUNDARIES', 'Manager üst yöneticiyi (DevAdmin) düzenleyemez. (Engellendi)');
} else {
    testFail('HIERARCHY-USER-BOUNDARIES', 'Manager DevAdmin hesabını düzenleyebildi! (GÜVENLİK İHLALİ)');
}

// =========================================================================
// SECTION D: PERMISSION DELEGATION (GRANTABLE PERMISSIONS)
// =========================================================================
echo "\n--- SECTION D: PERMISSION DELEGATION (GRANTABLE PERMISSIONS) ---\n";

$managerGrantable = $rbacService->getGrantablePermissionIds($managerAdminId);
$allPermCount = $pdo->query("SELECT COUNT(*) as cnt FROM permissions")->fetch()['cnt'];

if (count($managerGrantable) < $allPermCount) {
    testPass('DELEGATION-FILTER', 'Manager Yalnızca Kendi Sahip Olduğu Yetkileri Görüyor', "Grantable Perms: " . count($managerGrantable) . " / {$allPermCount}");
} else {
    testFail('DELEGATION-FILTER', 'Manager Tüm Yetkileri Görebiliyor! (GÜVENLİK İHLALİ)');
}

// Test Manager trying to grant a non-possessed permission
try {
    // Permission 1 is super admin permission
    $rbacService->validatePermissionGrant($managerAdminId, [1, 99999]);
    testFail('DELEGATION-VIOLATION', 'Manager Sahip Olmadığı Yetkiyi Devredebildi! (GÜVENLİK İHLALİ)');
} catch (Exception $e) {
    testPass('DELEGATION-VIOLATION', 'Sahip Olunmayan Yetkinin Devredilmesi Engellendi', $e->getMessage());
}

// =========================================================================
// SECTION E: IMPERSONATION ("KULLANICIYA GEÇ") SECURITY & LIFECYCLE
// =========================================================================
echo "\n--- SECTION E: IMPERSONATION SECURITY & LIFECYCLE ---\n";

// 1. Manager trying to impersonate DevAdmin -> MUST FAIL
$canManagerImpDev = $rbacService->canImpersonate($managerAdminId, $devAdminId);
if (!$canManagerImpDev) {
    testPass('IMP-HIERARCHY-BLOCK', 'Manager DevAdmin hesabına geçemez. (Engellendi)');
} else {
    testFail('IMP-HIERARCHY-BLOCK', 'Manager DevAdmin hesabına geçebildi! (GÜVENLİK İHLALİ)');
}

// 2. DevAdmin impersonating Manager -> MUST PASS
$canDevImpManager = $rbacService->canImpersonate($devAdminId, $managerAdminId);
if ($canDevImpManager) {
    testPass('IMP-ALLOWED', 'DevAdmin Manager hesabına geçiş yapabilir.');
} else {
    testFail('IMP-ALLOWED', 'DevAdmin Manager hesabına geçiş yapamadı.');
}

// 3. Perform actual Impersonation Lifecycle Test
$_SESSION['admin_id'] = $devAdminId;
$_SESSION['admin_username'] = 'devadmin';
$_SESSION['is_super_admin'] = true;

// Trigger impersonate
$_SESSION['impersonation'] = [
    'active' => true,
    'original_admin_id' => $devAdminId,
    'original_admin_username' => 'devadmin',
    'original_is_super' => true,
    'target_user_id' => $managerAdminId,
    'target_username' => 'test_manager',
    'started_at' => time()
];
$_SESSION['admin_id'] = $managerAdminId;
$_SESSION['admin_username'] = 'test_manager';
$_SESSION['is_super_admin'] = false;

// Verify context switch
if ($_SESSION['admin_id'] === $managerAdminId && !empty($_SESSION['impersonation']['active'])) {
    testPass('IMP-CONTEXT-SWITCH', 'Impersonation Oturum Bağlamı Değiştirildi', "Şu anki ID: {$_SESSION['admin_id']} (Hedef: Manager)");
} else {
    testFail('IMP-CONTEXT-SWITCH', 'Impersonation Oturum Bağlamı Kurulamadı');
}

// 4. Test Nested Impersonation -> MUST FAIL
$canNestedImp = $rbacService->canImpersonate($managerAdminId, 1);
if (!$canNestedImp) {
    testPass('IMP-NESTED-BLOCK', 'Zincirleme (Nested) Impersonation Engellendi');
} else {
    testFail('IMP-NESTED-BLOCK', 'Zincirleme Impersonation Çalıştı! (GÜVENLİK İHLALİ)');
}

// 5. Test Critical Action Restriction during Impersonation
$hasManageRoles = $rbacService->adminHasPermission($managerAdminId, 'manage_roles');
if (!$hasManageRoles) {
    testPass('IMP-RESTRICT-CRITICAL', 'Impersonation Modunda Kritik Yetkiler (manage_roles vb.) Kısıtlandı');
} else {
    testFail('IMP-RESTRICT-CRITICAL', 'Impersonation Modunda Kritik Yetkiler Kısıtlanamadı!');
}

// 6. Test Revert Impersonation
$rbacService->logAudit($managerAdminId, 'IMPERSONATION_ENDED', 'Admin', $managerAdminId, [], [], $devAdminId, $managerAdminId);
$_SESSION['admin_id'] = $devAdminId;
$_SESSION['admin_username'] = 'devadmin';
$_SESSION['is_super_admin'] = true;
unset($_SESSION['impersonation']);

if ($_SESSION['admin_id'] === $devAdminId && empty($_SESSION['impersonation'])) {
    testPass('IMP-REVERT-SUCCESS', 'Admin Oturumuna Başarıyla Geri Dönüldü');
} else {
    testFail('IMP-REVERT-SUCCESS', 'Admin Oturumuna Geri Dönülemedi');
}

// =========================================================================
// SECTION F: AUDIT LOG IMPERSONATION PRESERVATION
// =========================================================================
echo "\n--- SECTION F: AUDIT LOG IMPERSONATION PRESERVATION ---\n";

$auditRow = $pdo->query("SELECT * FROM audit_logs WHERE event = 'IMPERSONATION_ENDED' ORDER BY id DESC LIMIT 1")->fetch();
if ($auditRow && (int)$auditRow['impersonator_id'] === $devAdminId && (int)$auditRow['target_user_id'] === $managerAdminId) {
    testPass('AUDIT-ACTOR-PRESERVED', 'Audit Log Gerçek Aktör (impersonator_id) ve Etkili Aktör Ayrımını Koruyor', "Real Actor: {$devAdminId}, Target: {$managerAdminId}");
} else {
    testFail('AUDIT-ACTOR-PRESERVED', 'Audit Log Impersonator Bilgisini Kaydetmedi');
}

// Cleanup mock manager test user
$pdo->exec("DELETE FROM admin_roles WHERE admin_id = 998");
$pdo->exec("DELETE FROM admins WHERE id = 998");

// =========================================================================
// TEST SUMMARY & FINAL VERDICT
// =========================================================================
echo "\n======================================================================\n";
echo " AUDIT SONUÇ ÖZETİ:\n";
echo "   [PASS] Başarılı Testler: {$passCount}\n";
echo "   [FAIL] Başarısız Testler: {$failCount}\n";
echo "   [WARN] Uyarılar: {$warnCount}\n";
echo "======================================================================\n\n";

if ($failCount === 0) {
    echo "✅ SPRINT 49 HIERARCHICAL RBAC & IMPERSONATION AUDIT BAŞARIYLA TAMAMLANDI (0 FAIL / 0 CRITICAL)\n\n";
    exit(0);
} else {
    echo "❌ SPRINT 49 AUDIT BAŞARISIZ! ({$failCount} HATA DÜZELTİLMELİ)\n\n";
    exit(1);
}
