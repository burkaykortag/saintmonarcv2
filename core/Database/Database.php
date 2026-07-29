<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\ConfigInterface;
use PDO;
use PDOException;
use RuntimeException;

class Database implements DatabaseInterface {
    private PDO $pdo;

    public function __construct(ConfigInterface $config) {
        $host = $config->get('database.host', '127.0.0.1');
        $port = $config->get('database.port', '3306');
        $dbname = $config->get('database.dbname', 'saintmonarc');
        $user = $config->get('database.user', 'root');
        $pass = $config->get('database.password', '');
        $charset = $config->get('database.charset', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollBack(): bool {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }
}
