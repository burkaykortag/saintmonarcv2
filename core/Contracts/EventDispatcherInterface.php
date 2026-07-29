<?php

declare(strict_types=1);

namespace Core\Contracts;

interface EventDispatcherInterface {
    public function dispatch(object $event): object;
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;
    public function removeListener(string $eventName, callable $listener): void;
}
