<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Contracts\QueueInterface;

class SyncQueue implements QueueInterface {
    public function push(string $job, array $data = []): bool {
        if (class_exists($job) && method_exists($job, 'handle')) {
            $instance = new $job();
            $instance->handle($data);
            return true;
        }
        return false;
    }

    public function pop(string $queue = 'default'): ?array {
        // Sync queue processes immediately, nothing to pop.
        return null;
    }
}
