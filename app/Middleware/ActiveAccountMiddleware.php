<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Contracts\DatabaseInterface;
use Exception;

class ActiveAccountMiddleware {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function handle(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            $user = $this->db->query("SELECT status FROM users WHERE id = :id LIMIT 1", [':id' => $userId]);
            if (!empty($user) && $user[0]['status'] === 'active') {
                return;
            }
            
            session_destroy();
            throw new Exception("Bu hesap askıya alınmış veya aktif değil.", 403);
        }
    }
}
