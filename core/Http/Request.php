<?php

declare(strict_types=1);

namespace Core\Http;

class Request {
    private array $queryParams;
    private array $postParams;
    private array $server;

    public function __construct() {
        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->server = $_SERVER;
    }

    public function getMethod(): string {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getUri(): string {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        
        $basePath = getenv('APP_BASE_PATH') ?: '';
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
            if ($uri === '') {
                $uri = '/';
            }
        }

        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed {
        return $this->postParams[$key] ?? $default;
    }
}
