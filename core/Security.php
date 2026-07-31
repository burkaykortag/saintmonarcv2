<?php

namespace Core;

class Security {
    public function generateCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        Application::$app->session->set('csrf_token', $token);
        return $token;
    }

    public function validateCsrfToken(?string $token): bool {
        if (empty($token)) {
            return false;
        }
        $storedToken = Application::$app->session->get('csrf_token');
        return is_string($storedToken) && hash_equals($storedToken, $token);
    }
    
    public static function escape(?string $string): string {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
