<?php

namespace Core;

class Request {
    public function getPath(): string {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        // Base path removal if needed
        $basePath = Config::get('app.base_url_path', '');
        if ($basePath && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
            if ($path === '') $path = '/';
        }
        return $path;
    }

    public function getMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getBody(): array {
        $body = [];
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        return $body;
    }
}
