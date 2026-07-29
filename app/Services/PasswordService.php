<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\CacheInterface;
use Core\Contracts\DatabaseInterface;
use Exception;

class PasswordService {
    private CacheInterface $cache;
    private DatabaseInterface $db;

    public function __construct(CacheInterface $cache, DatabaseInterface $db) {
        $this->cache = $cache;
        $this->db = $db;
    }

    public function generateResetToken(string $email): string {
        $user = $this->db->query("SELECT id FROM users WHERE email = :email LIMIT 1", [':email' => $email]);
        if (empty($user)) {
            throw new Exception("Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı.");
        }

        $userId = (int)$user[0]['id'];
        $token = bin2hex(random_bytes(32));
        
        // Cache token for 1 hour (3600 seconds)
        $this->cache->set("pwd_reset_{$token}", $userId, 3600);

        return $token;
    }

    public function validateResetToken(string $token): int {
        $userId = $this->cache->get("pwd_reset_{$token}");
        if (!$userId) {
            throw new Exception("Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.");
        }
        return (int)$userId;
    }

    public function resetPassword(string $token, string $newPassword): void {
        $userId = $this->validateResetToken($token);
        
        $this->validatePasswordStrength($newPassword);

        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        // Update user password and audit it
        $this->db->execute(
            "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
            [':password' => $hashedPassword, ':id' => $userId]
        );

        $this->db->execute(
            "INSERT INTO audit_logs (user_type, user_id, event, auditable_type, auditable_id, old_values, ip_address, user_agent) 
             VALUES ('user', :user_id, 'password_reset', 'User', :user_id, :old_values, :ip, :ua)",
            [
                ':user_id' => $userId,
                ':old_values' => json_encode(['action' => 'password_reset_via_token']),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]
        );

        // Delete token
        $this->cache->delete("pwd_reset_{$token}");
    }

    public function validatePasswordStrength(string $password): void {
        if (strlen($password) < 8) {
            throw new Exception("Şifre en az 8 karakter uzunluğunda olmalıdır.");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("Şifre en az bir büyük harf içermelidir.");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception("Şifre en az bir küçük harf içermelidir.");
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception("Şifre en az bir rakam içermelidir.");
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            throw new Exception("Şifre en az bir özel karakter içermelidir.");
        }
    }
}
