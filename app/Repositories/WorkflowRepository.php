<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database\Database;
use PDO;

class WorkflowRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // --- WORKFLOW CRUD ---

    public function create(array $data): int
    {
        $this->db->query(
            "INSERT INTO workflows (name, description, status, trigger_type, created_at)
             VALUES (:name, :description, :status, :trigger_type, NOW())",
            [
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':trigger_type' => $data['trigger_type']
            ]
        );
        $workflowId = (int)$this->db->lastInsertId();

        // Initialize stats
        $this->db->query(
            "INSERT INTO workflow_statistics (workflow_id, total_runs, total_success, total_failed, avg_duration_ms, created_at)
             VALUES (:wid, 0, 0, 0, 0, NOW())",
            [':wid' => $workflowId]
        );

        return $workflowId;
    }

    public function get(int $id): ?array
    {
        $stmt = $this->db->query("SELECT * FROM workflows WHERE id = :id AND deleted_at IS NULL LIMIT 1", [':id' => $id]);
        return $stmt[0] ?? null;
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $val) {
            $fields[] = "`$key` = :$key";
            $params[":$key"] = $val;
        }
        if (empty($fields)) {
            return false;
        }
        $this->db->query("UPDATE workflows SET " . implode(', ', $fields) . " WHERE id = :id", $params);
        return true;
    }

    public function delete(int $id): bool
    {
        $this->db->query("UPDATE workflows SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
        return true;
    }

    public function list(array $filters = []): array
    {
        $sql = "SELECT w.*, ws.total_runs, ws.total_success, ws.total_failed 
                FROM workflows w
                LEFT JOIN workflow_statistics ws ON w.id = ws.workflow_id
                WHERE w.deleted_at IS NULL";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND w.status = :status";
            $params[':status'] = $filters['status'];
        }
        $sql .= " ORDER BY w.id DESC";
        return $this->db->query($sql, $params);
    }

    // --- TRIGGERS, ACTIONS & CONDITIONS ---

    public function saveTrigger(int $workflowId, array $data): int
    {
        $this->db->query("DELETE FROM workflow_triggers WHERE workflow_id = :wid", [':wid' => $workflowId]);
        $this->db->query(
            "INSERT INTO workflow_triggers (workflow_id, event_name, conditions_json, created_at)
             VALUES (:wid, :event_name, :conditions, NOW())",
            [
                ':wid' => $workflowId,
                ':event_name' => $data['event_name'],
                ':conditions' => json_encode($data['conditions'] ?? null)
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getTrigger(int $workflowId): ?array
    {
        $stmt = $this->db->query("SELECT * FROM workflow_triggers WHERE workflow_id = :wid LIMIT 1", [':wid' => $workflowId]);
        return $stmt[0] ?? null;
    }

    public function addAction(int $workflowId, array $data): int
    {
        $this->db->query(
            "INSERT INTO workflow_actions (workflow_id, type, config_json, sort_order, created_at)
             VALUES (:wid, :type, :config, :sort, NOW())",
            [
                ':wid' => $workflowId,
                ':type' => $data['type'],
                ':config' => json_encode($data['config'] ?? null),
                ':sort' => $data['sort_order'] ?? 0
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getActions(int $workflowId): array
    {
        return $this->db->query(
            "SELECT * FROM workflow_actions WHERE workflow_id = :wid ORDER BY sort_order ASC, id ASC",
            [':wid' => $workflowId]
        );
    }

    // --- QUEUE ---

    public function addToQueue(array $data): int
    {
        $this->db->query(
            "INSERT INTO workflow_queue (workflow_id, action_id, payload_json, status, run_at, created_at)
             VALUES (:wid, :action_id, :payload, :status, :run_at, NOW())",
            [
                ':wid' => $data['workflow_id'],
                ':action_id' => $data['action_id'],
                ':payload' => json_encode($data['payload'] ?? null),
                ':status' => $data['status'] ?? 'pending',
                ':run_at' => $data['run_at'] ?? null
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getQueueJobs(string $status = 'pending'): array
    {
        return $this->db->query(
            "SELECT q.*, a.type as action_type, a.config_json 
             FROM workflow_queue q
             JOIN workflow_actions a ON q.action_id = a.id
             WHERE q.status = :status AND (q.run_at IS NULL OR q.run_at <= NOW()) 
             ORDER BY q.id ASC LIMIT 50",
            [':status' => $status]
        );
    }

    public function updateQueueJob(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $val) {
            $fields[] = "`$key` = :$key";
            $params[":$key"] = $val;
        }
        $this->db->query("UPDATE workflow_queue SET " . implode(', ', $fields) . " WHERE id = :id", $params);
        return true;
    }

    // --- TEMPLATES ---

    public function listTemplates(): array
    {
        return $this->db->query("SELECT * FROM workflow_templates ORDER BY id DESC");
    }

    // --- HISTORY & LOGS ---

    public function addExecution(array $data): int
    {
        $this->db->query(
            "INSERT INTO workflow_executions (workflow_id, trigger_payload, status, created_at)
             VALUES (:wid, :payload, :status, NOW())",
            [
                ':wid' => $data['workflow_id'],
                ':payload' => json_encode($data['payload'] ?? null),
                ':status' => $data['status'] ?? 'running'
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function addHistory(array $data): int
    {
        $this->db->query(
            "INSERT INTO workflow_history (workflow_id, execution_id, status, started_at, completed_at, error_message)
             VALUES (:wid, :exec_id, :status, NOW(), :completed, :error)",
            [
                ':wid' => $data['workflow_id'],
                ':exec_id' => $data['execution_id'],
                ':status' => $data['status'],
                ':completed' => $data['completed_at'] ?? null,
                ':error' => $data['error_message'] ?? null
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function addLog(int $workflowId, int $executionId, string $level, string $message): int
    {
        $this->db->query(
            "INSERT INTO workflow_logs (workflow_id, execution_id, level, message, created_at)
             VALUES (:wid, :exec_id, :level, :message, NOW())",
            [
                ':wid' => $workflowId,
                ':exec_id' => $executionId,
                ':level' => $level,
                ':message' => $message
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getLogs(int $workflowId = null): array
    {
        $sql = "SELECT l.*, w.name as workflow_name 
                FROM workflow_logs l
                JOIN workflows w ON l.workflow_id = w.id";
        $params = [];
        if ($workflowId) {
            $sql .= " WHERE l.workflow_id = :wid";
            $params[':wid'] = $workflowId;
        }
        $sql .= " ORDER BY l.id DESC LIMIT 100";
        return $this->db->query($sql, $params);
    }

    public function getHistory(int $workflowId = null): array
    {
        $sql = "SELECT h.*, w.name as workflow_name 
                FROM workflow_history h
                JOIN workflows w ON h.workflow_id = w.id";
        $params = [];
        if ($workflowId) {
            $sql .= " WHERE h.workflow_id = :wid";
            $params[':wid'] = $workflowId;
        }
        $sql .= " ORDER BY h.id DESC LIMIT 100";
        return $this->db->query($sql, $params);
    }
}
