<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;

class AuditLogger {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function logActivity(string $action, string $description): void {
        if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $adminId = $_SESSION['admin_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        
        $userType = $adminId ? 'admin' : ($userId ? 'user' : 'guest');
        $resolvedUserId = $adminId ?: $userId ?: null;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $this->db->execute(
            "INSERT INTO activity_logs (user_type, user_id, action, description, ip_address, user_agent) 
             VALUES (:user_type, :user_id, :action, :description, :ip, :ua)",
            [
                ':user_type' => $userType,
                ':user_id' => $resolvedUserId,
                ':action' => $action,
                ':description' => $description,
                ':ip' => $ip,
                ':ua' => $userAgent
            ]
        );
    }

    public function logAudit(string $event, string $auditableType, int $auditableId, ?array $oldValues, ?array $newValues): void {
        if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $adminId = $_SESSION['admin_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        
        $userType = $adminId ? 'admin' : ($userId ? 'user' : 'system');
        $resolvedUserId = $adminId ?: $userId ?: null;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $this->db->execute(
            "INSERT INTO audit_logs (user_type, user_id, event, auditable_type, auditable_id, old_values, new_values, ip_address, user_agent) 
             VALUES (:user_type, :user_id, :event, :auditable_type, :auditable_id, :old_values, :new_values, :ip, :ua)",
            [
                ':user_type' => $userType,
                ':user_id' => $resolvedUserId,
                ':event' => $event,
                ':auditable_type' => $auditableType,
                ':auditable_id' => $auditableId,
                ':old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                ':new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                ':ip' => $ip,
                ':ua' => $userAgent
            ]
        );
    }
}
