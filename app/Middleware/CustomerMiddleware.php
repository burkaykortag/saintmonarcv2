<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;

class CustomerMiddleware {
    public function handle(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            $response->redirect('/login');
        }
    }
}
