<?php

namespace Core;

abstract class Model {
    protected string $table;
    protected string $primaryKey = 'id';
    
    protected function getDb(): \PDO {
        return Application::$app->getDatabase()->pdo;
    }
    
    public function find(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }
    
    public function all(): array {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
