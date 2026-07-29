<?php

namespace Core;

class Database {
    public \PDO $pdo;

    public function __construct() {
        $config = Config::get('database');
        
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        
        try {
            $this->pdo = new \PDO($dsn, $config['user'], $config['password']);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception("Database Connection Failed: " . $e->getMessage());
        }
    }
    
    public function prepare(string $sql): \PDOStatement {
        return $this->pdo->prepare($sql);
    }
}
