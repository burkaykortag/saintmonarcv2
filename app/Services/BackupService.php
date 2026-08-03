<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use Exception;

class BackupService
{
    private DatabaseInterface $db;
    private string $backupDir;

    public function __construct(DatabaseInterface $db, ?string $backupDir = null)
    {
        $this->db = $db;
        $this->backupDir = $backupDir ?? (ROOT_DIR . '/storage/backups');
        if (!file_exists($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }

    public function createDatabaseBackup(): array
    {
        $filename = 'backup_db_' . date('Ymd_His') . '.json';
        $filepath = $this->backupDir . '/' . $filename;

        $tables = ['orders', 'products', 'customers', 'inventories', 'payment_transactions', 'finance_entries'];
        $backupData = [
            'created_at' => date('Y-m-d H:i:s'),
            'tables' => []
        ];

        foreach ($tables as $table) {
            try {
                $rows = $this->db->query("SELECT * FROM {$table} LIMIT 1000");
                $backupData['tables'][$table] = $rows;
            } catch (Exception $e) {
                $backupData['tables'][$table] = [];
            }
        }

        file_put_contents($filepath, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size_bytes' => file_exists($filepath) ? filesize($filepath) : 0
        ];
    }

    public function verifyBackupIntegrity(string $filepath): array
    {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file does not exist'];
        }

        $content = file_get_contents($filepath);
        $data = json_decode($content, true);

        if (!$data || !isset($data['tables'])) {
            return ['success' => false, 'error' => 'Invalid backup format'];
        }

        return [
            'success' => true,
            'tables_count' => count($data['tables']),
            'created_at' => $data['created_at'] ?? null
        ];
    }
}
