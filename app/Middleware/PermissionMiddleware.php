<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use App\Services\RbacService;
use Exception;

class PermissionMiddleware {
    private RbacService $rbacService;

    public function __construct(RbacService $rbacService) {
        $this->rbacService = $rbacService;
    }

    public function handle(Request $request, Response $response, string $permission): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;

        if ($adminId) {
            // Check if super admin (bypass permissions)
            if (!empty($_SESSION['is_super_admin'])) {
                return;
            }
            if ($this->rbacService->adminHasPermission((int)$adminId, $permission)) {
                return;
            }
        }

        if ($userId && $this->rbacService->userHasPermission((int)$userId, $permission)) {
            return;
        }

        throw new Exception("Unauthorized access. Missing permission: {$permission}", 403);
    }
}
