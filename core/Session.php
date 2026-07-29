<?php

namespace Core;

class Session {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => Config::get('app.domain', ''),
                'secure' => Config::get('app.secure', true),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    public function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }
    
    public function destroy(): void {
        session_destroy();
    }
}
