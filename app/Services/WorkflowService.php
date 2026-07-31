<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\WorkflowRepository;
use Core\Contracts\CacheInterface;
use Exception;

class WorkflowService
{
    private WorkflowRepository $repository;
    private CacheInterface $cache;

    public function __construct(WorkflowRepository $repository, CacheInterface $cache)
    {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    // --- WORKFLOW CRUD ---

    public function createWorkflow(array $data): int
    {
        if (empty($data['name'])) {
            throw new Exception("İş akışı adı boş bırakılamaz.");
        }
        if (empty($data['trigger_type'])) {
            throw new Exception("Tetikleyici tipi seçilmelidir.");
        }
        $id = $this->repository->create($data);
        $this->cache->delete("active_workflows");
        return $id;
    }

    public function getWorkflow(int $id): ?array
    {
        $cacheKey = "workflow_{$id}";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $workflow = $this->repository->get($id);
        if ($workflow) {
            $this->cache->set($cacheKey, $workflow, 3600);
        }
        return $workflow;
    }

    public function updateWorkflow(int $id, array $data): bool
    {
        $success = $this->repository->update($id, $data);
        if ($success) {
            $this->cache->delete("workflow_{$id}");
            $this->cache->delete("active_workflows");
        }
        return $success;
    }

    public function deleteWorkflow(int $id): bool
    {
        $success = $this->repository->delete($id);
        if ($success) {
            $this->cache->delete("workflow_{$id}");
            $this->cache->delete("active_workflows");
        }
        return $success;
    }

    public function listWorkflows(array $filters = []): array
    {
        return $this->repository->list($filters);
    }

    // --- WORKFLOW BUILDER ---

    public function saveTrigger(int $workflowId, string $eventName, array $conditions = []): bool
    {
        $this->repository->saveTrigger($workflowId, [
            'event_name' => $eventName,
            'conditions' => $conditions
        ]);
        $this->cache->delete("workflow_trigger_{$workflowId}");
        return true;
    }

    public function addAction(int $workflowId, string $type, array $config = [], int $sortOrder = 0): int
    {
        return $this->repository->addAction($workflowId, [
            'type' => $type,
            'config' => $config,
            'sort_order' => $sortOrder
        ]);
    }

    // --- TRIGGER & DISPATCH MOTORU ---

    public function dispatchTrigger(string $eventName, array $payload): array
    {
        // Get all active workflows
        $workflows = $this->listWorkflows(['status' => 'active']);
        $executions = [];

        foreach ($workflows as $w) {
            $trigger = $this->repository->getTrigger((int)$w['id']);
            if (!$trigger || $trigger['event_name'] !== $eventName) {
                continue;
            }

            // Evaluate conditions
            $conditionsMet = true;
            if (!empty($trigger['conditions_json'])) {
                $conditions = json_decode($trigger['conditions_json'], true);
                if (is_array($conditions)) {
                    $conditionsMet = $this->evaluateConditions($conditions, $payload);
                }
            }

            if ($conditionsMet) {
                $execId = $this->runWorkflow((int)$w['id'], $payload);
                $executions[] = [
                    'workflow_id' => $w['id'],
                    'execution_id' => $execId
                ];
            }
        }

        return $executions;
    }

    public function runWorkflow(int $workflowId, array $payload): int
    {
        // 1. Create execution record
        $executionId = $this->repository->addExecution([
            'workflow_id' => $workflowId,
            'payload' => $payload,
            'status' => 'running'
        ]);

        $this->repository->addLog($workflowId, $executionId, 'info', "İş akışı tetiklendi. Tetikleyici veri: " . json_encode($payload));

        // 2. Fetch actions
        $actions = $this->repository->getActions($workflowId);
        $hasErrors = false;
        $errorMessage = '';

        foreach ($actions as $act) {
            $config = json_decode($act['config_json'], true) ?? [];
            
            // If it is a queued action (like mail, sms, webhook, AI, slack, etc.), queue it
            $queuedTypes = ['mail', 'sms', 'webhook', 'ai', 'slack', 'pdf', 'excel'];
            if (in_array($act['type'], $queuedTypes)) {
                $this->repository->addToQueue([
                    'workflow_id' => $workflowId,
                    'action_id' => $act['id'],
                    'payload' => array_merge($payload, ['config' => $config])
                ]);
                $this->repository->addLog($workflowId, $executionId, 'info', "Aksiyon kuyruğa eklendi. Tür: " . $act['type']);
            } else {
                // Synchronous action execution
                try {
                    $this->executeSyncAction($act['type'], $config, $payload);
                    $this->repository->addLog($workflowId, $executionId, 'info', "Senkron aksiyon tamamlandı. Tür: " . $act['type']);
                } catch (Exception $e) {
                    $hasErrors = true;
                    $errorMessage = $e->getMessage();
                    $this->repository->addLog($workflowId, $executionId, 'error', "Aksiyon başarısız: " . $errorMessage);
                    break;
                }
            }
        }

        // 3. Update history
        $this->repository->addHistory([
            'workflow_id' => $workflowId,
            'execution_id' => $executionId,
            'status' => $hasErrors ? 'failed' : 'success',
            'completed_at' => date('Y-m-d H:i:s'),
            'error_message' => $hasErrors ? $errorMessage : null
        ]);

        // 4. Update statistics
        $this->updateStatistics($workflowId, !$hasErrors);

        return $executionId;
    }

    // --- QUEUE PROCESSOR (WORKER) ---

    public function processQueue(): int
    {
        $jobs = $this->repository->getQueueJobs('pending');
        $processed = 0;

        foreach ($jobs as $job) {
            $this->repository->updateQueueJob((int)$job['id'], ['status' => 'processing']);
            $payload = json_decode($job['payload_json'], true) ?? [];
            $config = $payload['config'] ?? [];

            try {
                // Execute action
                $this->executeSyncAction($job['action_type'], $config, $payload);
                $this->repository->updateQueueJob((int)$job['id'], ['status' => 'completed']);
                $processed++;
            } catch (Exception $e) {
                $retryCount = (int)$job['retry_count'] + 1;
                $status = ($retryCount >= 3) ? 'failed' : 'pending';
                
                $this->repository->updateQueueJob((int)$job['id'], [
                    'status' => $status,
                    'retry_count' => $retryCount,
                    'error_message' => $e->getMessage()
                ]);
            }
        }

        return $processed;
    }

    // --- HELPERS ---

    public function evaluateConditions(array $conditions, array $payload): bool
    {
        foreach ($conditions as $cond) {
            $field = $cond['field'];
            $operator = $cond['operator'];
            $expectedValue = $cond['value'];

            $actualValue = $payload[$field] ?? null;

            switch ($operator) {
                case 'equals':
                    if ($actualValue != $expectedValue) return false;
                    break;
                case 'not_equals':
                    if ($actualValue == $expectedValue) return false;
                    break;
                case 'greater_than':
                    if ($actualValue <= $expectedValue) return false;
                    break;
                case 'less_than':
                    if ($actualValue >= $expectedValue) return false;
                    break;
                case 'contains':
                    if (strpos((string)$actualValue, (string)$expectedValue) === false) return false;
                    break;
                case 'is_empty':
                    if (!empty($actualValue)) return false;
                    break;
                case 'is_not_empty':
                    if (empty($actualValue)) return false;
                    break;
            }
        }
        return true;
    }

    private function executeSyncAction(string $type, array $config, array $payload): void
    {
        // Mock actual execution logic for third party actions
        switch ($type) {
            case 'mail':
            case 'sms':
            case 'slack':
            case 'webhook':
                // Success mock
                break;
            case 'crm_note':
                // Sync action mock
                break;
            case 'update_order':
                // Sync status modification
                break;
            default:
                break;
        }
    }

    private function updateStatistics(int $workflowId, bool $success): void
    {
        $this->cache->delete("workflow_stats_{$workflowId}");
        // Statistics table update increment
        $field = $success ? 'total_success' : 'total_failed';
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->query(
            "UPDATE workflow_statistics 
             SET total_runs = total_runs + 1, {$field} = {$field} + 1 
             WHERE workflow_id = :wid",
            [':wid' => $workflowId]
        );
    }
}
