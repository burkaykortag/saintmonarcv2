<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Contracts\DatabaseInterface;

class VerifiedEmailMiddleware {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function handle(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;

        if ($adminId) {
            return;
        }

        if ($userId) {
            $user = $this->db->query("SELECT email_verified_at FROM users WHERE id = :id LIMIT 1", [':id' => $userId]);
            if (!empty($user) && !empty($user[0]['email_verified_at'])) {
                return;
            }
            
            $response->redirect('/login?error=' . urlencode('E-posta adresinizi doğrulamanız gerekmektedir.'));
        }

        $response->redirect('/login');
    }
}
