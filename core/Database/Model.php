<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Contracts\DatabaseInterface;

abstract class Model {
    protected string $table;
    protected string $primaryKey = 'id';
    protected DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function find(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $result = $this->db->query($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    public function all(): array {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->query($sql);
    }

    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->execute($sql, [':id' => $id]);
    }
}
