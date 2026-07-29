<?php

declare(strict_types=1);

namespace App\Events;

use Core\Events\Event;

class UserLoggedInEvent extends Event {
    public int $userId;
    public string $userType;

    public function __construct(int $userId, string $userType) {
        $this->userId = $userId;
        $this->userType = $userType;
    }
}
