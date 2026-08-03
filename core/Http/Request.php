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

    public function get(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->postParams;
        }
        return $this->postParams[$key] ?? $default;
    }

    /** Return all query + post params merged */
    public function all(): array {
        return array_merge($this->queryParams, $this->postParams);
    }

    /** Route parameter (fallback: look in GET params) */
    public function getRouteParam(string $key, mixed $default = null): mixed {
        return $this->queryParams[$key] ?? $default;
    }

    /** Raw request body (for JSON APIs) */
    public function getRawBody(): string {
        return file_get_contents('php://input') ?: '';
    }

    /** Decoded JSON body or POST params */
    public function getBody(): array {
        $raw = $this->getRawBody();
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $this->postParams;
    }
}
