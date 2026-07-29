<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\PasswordService;
use Core\View\View;
use Exception;

class PasswordController extends Controller {
    private PasswordService $passwordService;

    public function __construct(View $view, PasswordService $passwordService) {
        parent::__construct($view);
        $this->passwordService = $passwordService;
    }

    public function showForgot(Request $request, Response $response): string {
        return $this->render('auth/forgot_password');
    }

    public function forgot(Request $request, Response $response): void {
        $email = $request->post('email') ?? '';

        try {
            $token = $this->passwordService->generateResetToken($email);
            // In development, display link. In production, MailInterface is triggered.
            $resetLink = "/password/reset?token=" . $token;
            $response->redirect('/password/forgot?success=' . urlencode("Şifre sıfırlama bağlantısı oluşturuldu. Bağlantı: " . $resetLink));
        } catch (Exception $e) {
            $response->redirect('/password/forgot?error=' . urlencode($e->getMessage()));
        }
    }

    public function showReset(Request $request, Response $response): string {
        $token = $request->get('token') ?? '';
        return $this->render('auth/reset_password', ['token' => $token]);
    }

    public function reset(Request $request, Response $response): void {
        $token = $request->post('token') ?? '';
        $password = $request->post('password') ?? '';

        try {
            $this->passwordService->resetPassword($token, $password);
            $response->redirect('/login?success=' . urlencode('Şifreniz başarıyla sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.'));
        } catch (Exception $e) {
            $response->redirect("/password/reset?token={$token}&error=" . urlencode($e->getMessage()));
        }
    }
}
