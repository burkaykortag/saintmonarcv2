<?php

declare(strict_types=1);

namespace Core\Events;

abstract class Event {
    protected bool $isPropagationStopped = false;

    public function isPropagationStopped(): bool {
        return $this->isPropagationStopped;
    }

    public function stopPropagation(): void {
        $this->isPropagationStopped = true;
    }
}
