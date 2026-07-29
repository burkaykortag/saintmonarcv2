<?php

declare(strict_types=1);

namespace Core\Contracts;

interface StorageInterface {
    public function put(string $path, string $contents): bool;
    public function get(string $path): ?string;
    public function exists(string $path): bool;
    public function delete(string $path): bool;
}
