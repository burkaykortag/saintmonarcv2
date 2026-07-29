<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\AuthService;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Core\View\View;
use Exception;

class AuthController extends Controller {
    private AuthService $authService;
    private DatabaseInterface $db;
    private CacheInterface $cache;

    public function __construct(View $view, AuthService $authService, DatabaseInterface $db, CacheInterface $cache) {
        parent::__construct($view);
        $this->authService = $authService;
        $this->db = $db;
        $this->cache = $cache;
    }

    public function showLogin(Request $request, Response $response): string {
        return $this->render('auth/login');
    }

    public function login(Request $request, Response $response): void {
        $email = $request->post('email') ?? '';
        $password = $request->post('password') ?? '';

        try {
            $user = $this->authService->attemptUserLogin($email, $password, $request);
            $response->redirect('/');
        } catch (Exception $e) {
            $response->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    public function showRegister(Request $request, Response $response): string {
        return $this->render('auth/register');
    }

    public function register(Request $request, Response $response): void {
        $email = $request->post('email') ?? '';
        $password = $request->post('password') ?? '';
        $firstName = $request->post('first_name') ?? '';
        $lastName = $request->post('last_name') ?? '';
        
        // Consent checkboxes
        $kvkkConsent = $request->post('kvkk_consent') === '1';
        $termsConsent = $request->post('terms_consent') === '1';

        if (!$kvkkConsent || !$termsConsent) {
            $response->redirect('/register?error=' . urlencode('KVKK ve Kullanım Sözleşmesi onayları zorunludur.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
            
            // Create user
            $this->db->execute(
                "INSERT INTO users (email, password, status) VALUES (:email, :password, 'active')",
                [':email' => $email, ':password' => $hashedPassword]
            );
            $userId = (int)$this->db->lastInsertId();

            // Create user profile
            $this->db->execute(
                "INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (:user_id, :first_name, :last_name)",
                [':user_id' => $userId, ':first_name' => $firstName, ':last_name' => $lastName]
            );

            // Save consents (KVKK Version 1.0, Terms Version 1.0)
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $documents = $this->db->query("SELECT id, type, version FROM legal_documents");
            foreach ($documents as $doc) {
                if (($doc['type'] === 'kvkk' && $kvkkConsent) || ($doc['type'] === 'terms_of_service' && $termsConsent)) {
                    $this->db->execute(
                        "INSERT INTO user_consents (user_id, legal_document_id, accepted_version, ip_address, user_agent) 
                         VALUES (:user_id, :doc_id, :version, :ip, :ua)",
                        [
                            ':user_id' => $userId,
                            ':doc_id' => $doc['id'],
                            ':version' => $doc['version'],
                            ':ip' => $ip,
                            ':ua' => $ua
                        ]
                    );
                }
            }

            $this->db->commit();

            // Trigger Verification
            $token = bin2hex(random_bytes(32));
            $this->cache->set("email_verification_{$token}", $userId, 86400); // 24 hours expiry
            
            // In a full application, we would send this link via mail service.
            // $verificationLink = "/verify-email?token=" . $token;

            $response->redirect('/login?success=' . urlencode('Kayıt başarılı. E-posta doğrulama linki oluşturuldu. Lütfen giriş yapın.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/register?error=' . urlencode($e->getMessage()));
        }
    }

    public function verifyEmail(Request $request, Response $response): void {
        $token = $request->get('token') ?? '';
        $userId = $this->cache->get("email_verification_{$token}");

        if (!$userId) {
            $response->redirect('/login?error=' . urlencode('Geçersiz veya süresi dolmuş doğrulama bağlantısı.'));
            return;
        }

        $this->db->execute(
            "UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE id = :id",
            [':id' => $userId]
        );

        $this->cache->delete("email_verification_{$token}");
        $response->redirect('/login?success=' . urlencode('E-posta adresiniz başarıyla doğrulandı.'));
    }

    public function showSessions(Request $request, Response $response): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $response->redirect('/login');
        }

        $sessions = $this->authService->getUserSessions((int)$userId);
        return $this->render('auth/sessions', ['sessions' => $sessions]);
    }

    public function revokeSession(Request $request, Response $response): void {
        $sessionId = $request->post('session_id') ?? '';
        $this->authService->revokeSession($sessionId);
        $response->redirect('/sessions?success=' . urlencode('Oturum başarıyla sonlandırıldı.'));
    }

    public function logout(Request $request, Response $response): void {
        $this->authService->logout();
        $response->redirect('/login');
    }
}
