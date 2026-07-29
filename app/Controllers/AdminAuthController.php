<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\AuthService;
use Core\View\View;
use Exception;

class AdminAuthController extends Controller {
    private AuthService $authService;

    public function __construct(View $view, AuthService $authService) {
        parent::__construct($view);
        $this->authService = $authService;
    }

    public function showLogin(Request $request, Response $response): string {
        return $this->render('auth/admin_login');
    }

    public function login(Request $request, Response $response): void {
        $username = $request->post('username') ?? '';
        $password = $request->post('password') ?? '';

        try {
            $admin = $this->authService->attemptAdminLogin($username, $password, $request);
            $response->redirect('/admin');
        } catch (Exception $e) {
            $response->redirect('/admin/login?error=' . urlencode($e->getMessage()));
        }
    }

    public function logout(Request $request, Response $response): void {
        $this->authService->logout();
        $response->redirect('/admin/login');
    }
}
