<?php
declare(strict_types=1);

/**
 * Sprint 23 - Enterprise Workflow Automation & Processes CLI Tests
 */

define('ROOT_DIR', __DIR__);

// Autoload
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
use Core\Application;
use App\Repositories\WorkflowRepository;
use App\Services\WorkflowService;

EnvParser::parse(ROOT_DIR . '/.env');

$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$db = $container->get(\Core\Contracts\DatabaseInterface::class);
$repository = new WorkflowRepository($db);

$cacheMock = new class implements \Core\Contracts\CacheInterface {
    private array $data = [];
    public function get(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
    public function set(string $key, mixed $value, int $ttl = null): bool { $this->data[$key] = $value; return true; }
    public function has(string $key): bool { return isset($this->data[$key]); }
    public function delete(string $key): bool { unset($this->data[$key]); return true; }
    public function clear(): bool { $this->data = []; return true; }
};

$service = new WorkflowService($repository, $cacheMock);

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
echo " SPRINT 23 - ENTERPRISE WORKFLOW AUTOMATION MOTORU UNIT TESTLERİ\n";
echo str_repeat('=', 60) . "\n\n";

// --- SECTION 1: SCHEMA CHECK ---
echo "📂 [BÖLÜM 1] Veritabanı Tablo Varlık Kontrolleri\n";

$tables = [
    'workflows', 'workflow_triggers', 'workflow_actions', 'workflow_conditions',
    'workflow_variables', 'workflow_executions', 'workflow_logs', 'workflow_history',
    'workflow_templates', 'workflow_schedules', 'workflow_queue', 'workflow_errors',
    'workflow_webhooks', 'workflow_notifications', 'workflow_versions', 'workflow_statistics',
    'workflow_permissions', 'workflow_ai_rules', 'workflow_delays', 'workflow_wait_states'
];

foreach ($tables as $t) {
    test("Tablo varlığı: {$t}", function() use ($db, $t) {
        $res = $db->query("SHOW TABLES LIKE '{$t}'");
        return count($res) > 0 ? true : "Tablo bulunamadı: {$t}";
    });
}

// --- SECTION 2: CRUD & CONDITIONS ---
echo "\n🎯 [BÖLÜM 2] CRUD, Koşullar ve Tetikleyici Mantık Testleri\n";

$testWorkflowId = null;
$testActionId = null;

test("Yeni Otomasyon Akışı Ekleme", function() use ($service, &$testWorkflowId) {
    $id = $service->createWorkflow([
        'name' => 'Kritik Sipariş Slack Bildirimi',
        'description' => '1000 TL üzeri siparişlerde Slack kanalına bildirim atar.',
        'status' => 'active',
        'trigger_type' => 'order_created'
    ]);
    $testWorkflowId = $id;
    return $id > 0 ? true : 'İş akışı oluşturulamadı';
});

test("Akışa Tetikleyici Olay ve Koşul Bağlama", function() use ($service, &$testWorkflowId) {
    $success = $service->saveTrigger($testWorkflowId, 'order_created', [
        ['field' => 'total_amount', 'operator' => 'greater_than', 'value' => 1000.00]
    ]);
    return $success;
});

test("Akışa Eylem/Aksiyon Adımları Ekleme", function() use ($service, &$testWorkflowId, &$testActionId) {
    $actId1 = $service->addAction($testWorkflowId, 'slack', ['channel' => '#sales-alerts']);
    $actId2 = $service->addAction($testWorkflowId, 'mail', ['to' => 'yonetici@saintmonarc.com']);
    $testActionId = $actId1;
    return ($actId1 > 0 && $actId2 > 0) ? true : 'Eylem adımları eklenemedi';
});

test("Koşul Değerlendirme Motoru (Şartlar Sağlanıyor)", function() use ($service) {
    $conditions = [
        ['field' => 'total_amount', 'operator' => 'greater_than', 'value' => 1000.00]
    ];
    $payload = ['total_amount' => 1250.00];
    return $service->evaluateConditions($conditions, $payload);
});

test("Koşul Değerlendirme Motoru (Şartlar Sağlanmıyor)", function() use ($service) {
    $conditions = [
        ['field' => 'total_amount', 'operator' => 'greater_than', 'value' => 1000.00]
    ];
    $payload = ['total_amount' => 850.00];
    return !$service->evaluateConditions($conditions, $payload) ? true : 'Koşul hatalı şekilde sağlandı';
});

test("Koşul Değerlendirme Motoru (equals eşittir kontrolü)", function() use ($service) {
    $conditions = [
        ['field' => 'city', 'operator' => 'equals', 'value' => 'Istanbul']
    ];
    $payload = ['city' => 'Istanbul'];
    return $service->evaluateConditions($conditions, $payload);
});

test("Koşul Değerlendirme Motoru (contains içerir kontrolü)", function() use ($service) {
    $conditions = [
        ['field' => 'name', 'operator' => 'contains', 'value' => 'Premium']
    ];
    $payload = ['name' => 'SaintMonarc Premium Suite'];
    return $service->evaluateConditions($conditions, $payload);
});

// --- SECTION 3: QUEUE SYSTEM & WORKERS ---
echo "\n📦 [BÖLÜM 3] Sıralı İş Kuyruğu & Asenkron Worker Testleri\n";

test("Kuyruğa yeni asenkron eylem ekleme", function() use ($repository, &$testWorkflowId, &$testActionId) {
    $jobId = $repository->addToQueue([
        'workflow_id' => $testWorkflowId,
        'action_id' => $testActionId,
        'payload' => ['to' => 'yonetici@saintmonarc.com'],
        'status' => 'pending'
    ]);
    return $jobId > 0 ? true : 'İş kuyruğa eklenemedi';
});

test("Bekleyen kuyruk işlerini getirme", function() use ($repository) {
    $jobs = $repository->getQueueJobs('pending');
    return count($jobs) > 0 ? true : 'Kuyruk işi listelenemedi';
});

test("Kuyruk Worker (processQueue) Çalıştırılması", function() use ($service) {
    $processed = $service->processQueue();
    return $processed >= 0 ? true : 'Worker hatası';
});

// --- SECTION 4: REST API ACCESS ---
echo "\n🌐 [BÖLÜM 4] REST API Rota Erişim Testleri\n";

test("REST API: /api/workflows endpoint metot varlığı", function() {
    return method_exists(\App\Controllers\WorkflowController::class, 'apiList') ? true : 'apiList metodu yok';
});

test("REST API: /api/workflows/run tetikleme metot varlığı", function() {
    return method_exists(\App\Controllers\WorkflowController::class, 'apiRun') ? true : 'apiRun metodu yok';
});

test("REST API: /api/workflows/history geçmiş metot varlığı", function() {
    return method_exists(\App\Controllers\WorkflowController::class, 'apiHistory') ? true : 'apiHistory metodu yok';
});

test("REST API: /api/workflows/logs günlük metot varlığı", function() {
    return method_exists(\App\Controllers\WorkflowController::class, 'apiLogs') ? true : 'apiLogs metodu yok';
});

test("REST API: /api/workflows/templates şablon metot varlığı", function() {
    return method_exists(\App\Controllers\WorkflowController::class, 'apiTemplates') ? true : 'apiTemplates metodu yok';
});

// --- CLEANUP ---
test("Test verilerini force delete ile temizleme", function() use ($db, &$testWorkflowId) {
    if (!$testWorkflowId) return true;
    $db->execute("DELETE FROM workflow_history WHERE workflow_id = :wid", [':wid' => $testWorkflowId]);
    $db->execute("DELETE FROM workflow_logs WHERE workflow_id = :wid", [':wid' => $testWorkflowId]);
    $db->execute("DELETE FROM workflow_queue WHERE workflow_id = :wid", [':wid' => $testWorkflowId]);
    $db->execute("DELETE FROM workflow_actions WHERE workflow_id = :wid", [':wid' => $testWorkflowId]);
    $db->execute("DELETE FROM workflow_triggers WHERE workflow_id = :wid", [':wid' => $testWorkflowId]);
    $db->execute("DELETE FROM workflow_statistics WHERE workflow_id = :wid", [':wid' => $testWorkflowId]);
    $db->execute("DELETE FROM workflows WHERE id = :wid", [':wid' => $testWorkflowId]);
    return true;
});

echo "\n" . str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo " SPRINT 23 WORKFLOW SONUÇ: {$passed}/{$total} test BAŞARILI\n";
echo str_repeat('=', 60) . "\n\n";

if ($failed === 0) {
    echo "✅ TÜM WORKFLOW OTOMASYON TESTLERİ BAŞARIYLA TAMAMLANDI!\n\n";
} else {
    echo "⚠️ Bazı testler başarısız. Lütfen hataları inceleyin.\n\n";
}
