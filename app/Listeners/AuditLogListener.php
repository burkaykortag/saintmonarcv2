<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLoggedInEvent;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;

class AuditLogListener {
    private DatabaseInterface $db;
    private LoggerInterface $logger;

    public function __construct(DatabaseInterface $db, LoggerInterface $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function handleUserLoggedIn(UserLoggedInEvent $event): void {
        $this->logger->info("User logged in successfully via event loop.", [
            'user_id' => $event->userId,
            'user_type' => $event->userType
        ]);
    }
}
