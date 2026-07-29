<?php

namespace Core;

class Security {
    public function generateCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        Application::$app->session->set('csrf_token', $token);
        return $token;
    }

    public function validateCsrfToken(string $token): bool {
        $storedToken = Application::$app->session->get('csrf_token');
        return $storedToken && hash_equals($storedToken, $token);
    }
    
    public static function escape(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
