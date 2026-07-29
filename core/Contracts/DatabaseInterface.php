<?php

declare(strict_types=1);

namespace Core\Contracts;

interface DatabaseInterface {
    public function query(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): bool;
    public function lastInsertId(): string;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
    public function inTransaction(): bool;
}
