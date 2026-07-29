<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Security;
use Exception;

class CsrfMiddleware {
    private Security $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function handle(Request $request, Response $response): void {
        $method = strtoupper($request->getMethod());
        
        // Only validate CSRF for state-changing methods
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $token = $request->post('csrf_token') ?: '';
            
            // Check headers as fallback (useful for AJAX)
            if (empty($token)) {
                $headers = $this->getRequestHeaders();
                $token = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? '';
            }

            if (empty($token) || !$this->security->validateCsrfToken($token)) {
                $isAjax = $request->post('ajax') !== null 
                    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                    || str_contains($request->getUri(), '-ajax') 
                    || str_starts_with($request->getUri(), '/api');

                if ($isAjax) {
                    $response->json(['success' => false, 'error' => 'Geçersiz CSRF token. Lütfen sayfayı yenileyip tekrar deneyin.'], 403);
                    exit;
                } else {
                    throw new Exception("CSRF doğrulama hatası. Yetkisiz istek.", 403);
                }
            }
        }
    }

    private function getRequestHeaders(): array {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}
