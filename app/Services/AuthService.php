<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\EventDispatcherInterface;
use Core\Http\Request;
use Exception;

class AuthService {
    private DatabaseInterface $db;
    private EventDispatcherInterface $events;

    public function __construct(DatabaseInterface $db, EventDispatcherInterface $events) {
        $this->db = $db;
        $this->events = $events;
    }

    public function attemptUserLogin(string $email, string $password, Request $request): array {
        $user = $this->db->query("SELECT * FROM users WHERE email = :email LIMIT 1", [':email' => $email]);
        if (empty($user)) {
            $this->logLoginHistory(null, null, $request, 'failed_password');
            throw new Exception("Geçersiz e-posta adresi veya şifre.", 401);
        }

        $user = $user[0];

        if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            $this->logLoginHistory((int)$user['id'], null, $request, 'locked');
            throw new Exception("Çok fazla başarısız deneme yaptınız. Hesabınız geçici olarak kilitlendi.", 423);
        }

        if (!password_verify($password, $user['password'])) {
            $this->incrementFailedAttempts((int)$user['id'], 'users');
            $this->logLoginHistory((int)$user['id'], null, $request, 'failed_password');
            throw new Exception("Geçersiz e-posta adresi veya şifre.", 401);
        }

        if ($user['status'] !== 'active') {
            throw new Exception("Hesabınız aktif değil. Lütfen destek ekibiyle iletişime geçin.", 403);
        }

        $this->resetFailedAttempts((int)$user['id'], 'users');

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];

        $this->logLoginHistory((int)$user['id'], null, $request, 'success');

        return $user;
    }

    public function attemptAdminLogin(string $username, string $password, Request $request): array {
        $admin = $this->db->query("SELECT * FROM admins WHERE username = :username LIMIT 1", [':username' => $username]);
        if (empty($admin)) {
            $this->logLoginHistory(null, null, $request, 'failed_password');
            throw new Exception("Geçersiz kullanıcı adı veya şifre.", 401);
        }

        $admin = $admin[0];

        if ($admin['lockout_until'] && strtotime($admin['lockout_until']) > time()) {
            $this->logLoginHistory(null, (int)$admin['id'], $request, 'locked');
            throw new Exception("Hesabınız geçici olarak kilitlendi.", 423);
        }

        if (!password_verify($password, $admin['password'])) {
            $this->incrementFailedAttempts((int)$admin['id'], 'admins');
            $this->logLoginHistory(null, (int)$admin['id'], $request, 'failed_password');
            throw new Exception("Geçersiz kullanıcı adı veya şifre.", 401);
        }

        if (!$admin['is_active']) {
            throw new Exception("Yönetici hesabınız pasif durumda.", 403);
        }

        $this->resetFailedAttempts((int)$admin['id'], 'admins');

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['is_super_admin'] = (bool)$admin['is_super'];

        $this->logLoginHistory(null, (int)$admin['id'], $request, 'success');

        return $admin;
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Audit Log on logout
        if ($userId || $adminId) {
            $this->db->execute(
                "INSERT INTO audit_logs (user_type, user_id, event, auditable_type, auditable_id, old_values, ip_address, user_agent) 
                 VALUES (:user_type, :user_id, 'logout', :auditable, :auditable_id, :old_values, :ip, :ua)",
                [
                    ':user_type' => $adminId ? 'admin' : 'user',
                    ':user_id' => $adminId ?: $userId,
                    ':auditable' => $adminId ? 'Admin' : 'User',
                    ':auditable_id' => $adminId ?: $userId,
                    ':old_values' => json_encode(['logout_at' => date('Y-m-d H:i:s')]),
                    ':ip' => $ip,
                    ':ua' => $userAgent
                ]
            );
        }

        session_destroy();
    }

    public function getUserSessions(int $userId): array {
        return $this->db->query("SELECT * FROM sessions WHERE user_id = :user_id", [':user_id' => $userId]);
    }

    public function revokeSession(string $sessionId): void {
        $this->db->execute("DELETE FROM sessions WHERE id = :id", [':id' => $sessionId]);
    }

    public function revokeAllOtherSessions(int $userId = null, int $adminId = null, string $currentSessionId = ''): void {
        if ($userId) {
            $this->db->execute(
                "DELETE FROM sessions WHERE user_id = :user_id AND id != :current_id",
                [':user_id' => $userId, ':current_id' => $currentSessionId]
            );
        }
        if ($adminId) {
            $this->db->execute(
                "DELETE FROM sessions WHERE admin_id = :admin_id AND id != :current_id",
                [':admin_id' => $adminId, ':current_id' => $currentSessionId]
            );
        }
    }

    private function incrementFailedAttempts(int $id, string $table): void {
        $user = $this->db->query("SELECT failed_login_attempts FROM {$table} WHERE id = :id LIMIT 1", [':id' => $id])[0];
        $attempts = (int)$user['failed_login_attempts'] + 1;
        
        $lockoutUntil = null;
        if ($attempts >= 5) {
            $lockoutUntil = date('Y-m-d H:i:s', time() + 900);
        }

        $this->db->execute(
            "UPDATE {$table} SET failed_login_attempts = :attempts, lockout_until = :lockout WHERE id = :id",
            [':attempts' => $attempts, ':lockout' => $lockoutUntil, ':id' => $id]
        );
    }

    private function resetFailedAttempts(int $id, string $table): void {
        $this->db->execute(
            "UPDATE {$table} SET failed_login_attempts = 0, lockout_until = NULL WHERE id = :id",
            [':id' => $id]
        );
    }

    private function logLoginHistory(?int $userId, ?int $adminId, Request $request, string $status): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $deviceType = 'desktop';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobi))/i', $userAgent)) {
            $deviceType = 'tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            $deviceType = 'mobile';
        }

        $this->db->execute(
            "INSERT INTO user_login_histories (user_id, admin_id, ip_address, user_agent, device_type, status) 
             VALUES (:user_id, :admin_id, :ip_address, :user_agent, :device_type, :status)",
            [
                ':user_id' => $userId,
                ':admin_id' => $adminId,
                ':ip_address' => $ip,
                ':user_agent' => $userAgent,
                ':device_type' => $deviceType,
                ':status' => $status
            ]
        );

        if ($status === 'success') {
            $this->db->execute(
                "INSERT INTO audit_logs (user_type, user_id, event, auditable_type, auditable_id, new_values, ip_address, user_agent) 
                 VALUES (:user_type, :user_id, 'login', :auditable, :auditable_id, :new_values, :ip, :ua)",
                [
                    ':user_type' => $adminId ? 'admin' : 'user',
                    ':user_id' => $adminId ?: $userId,
                    ':auditable' => $adminId ? 'Admin' : 'User',
                    ':auditable_id' => $adminId ?: $userId,
                    ':new_values' => json_encode(['login_at' => date('Y-m-d H:i:s')]),
                    ':ip' => $ip,
                    ':ua' => $userAgent
                ]
            );
        }
    }
}
