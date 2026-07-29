<?php

declare(strict_types=1);

namespace Core\Events;

use Core\Contracts\EventDispatcherInterface;

class EventDispatcher implements EventDispatcherInterface {
    private array $listeners = [];

    public function addListener(string $eventName, callable $listener, int $priority = 0): void {
        $this->listeners[$eventName][$priority][] = $listener;
    }

    public function removeListener(string $eventName, callable $listener): void {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $key => $registeredListener) {
                if ($registeredListener === $listener) {
                    unset($this->listeners[$eventName][$priority][$key]);
                }
            }
        }
    }

    public function dispatch(object $event): object {
        $eventName = get_class($event);

        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        krsort($this->listeners[$eventName]);

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                if ($event instanceof Event && $event->isPropagationStopped()) {
                    break 2;
                }
                
                call_user_func($listener, $event);
            }
        }

        return $event;
    }
}
