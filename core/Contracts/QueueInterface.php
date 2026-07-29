<?php

declare(strict_types=1);

namespace Core\Contracts;

interface QueueInterface {
    public function push(string $job, array $data = []): bool;
    public function pop(string $queue = 'default'): ?array;
}
