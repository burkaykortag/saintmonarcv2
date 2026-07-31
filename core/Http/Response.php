<?php

declare(strict_types=1);

namespace Core\Http;

class Response {
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function setStatusCode(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent(string $content): self {
        $this->content = $content;
        return $this;
    }

    public function json(array $data, int $status = 200): void {
        $this->setStatusCode($status);
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        $this->send();
    }
    
    public function redirect(string $url): void {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            $basePath = getenv('APP_BASE_PATH') ?: '';
            if ($basePath !== '' && !str_starts_with($url, $basePath)) {
                $url = rtrim($basePath, '/') . '/' . ltrim($url, '/');
            }
        }
        $this->setHeader('Location', $url);
        $this->setStatusCode(302);
        $this->send();
    }

    public function send(): void {
        http_response_code($this->statusCode);

        // Standard Security Headers
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: blob:; img-src 'self' https: data: blob:; font-src 'self' https: data:;");
        }

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->content;
        exit;
    }
}
