<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;

class AuthMiddleware {
    public function handle(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id']) && empty($_SESSION['admin_id'])) {
            if (str_starts_with($request->getUri(), '/admin')) {
                $response->redirect('/admin/login');
            }
            $response->redirect('/login');
        }
    }
}
