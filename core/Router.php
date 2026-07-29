<?php

namespace Core;

class Router {
    private array $routes = [];

    public function get(string $path, array|callable $callback): void {
        $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, array|callable $callback): void {
        $this->addRoute('POST', $path, $callback);
    }

    private function addRoute(string $method, string $path, array|callable $callback): void {
        $this->routes[$method][$path] = $callback;
    }

    public function resolve(Request $request, Response $response) {
        $path = $request->getPath();
        $method = $request->getMethod();
        
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            $response->setStatusCode(404);
            throw new \Exception("Route not found: $path", 404);
        }

        if (is_array($callback)) {
            $controller = new $callback[0]();
            return call_user_func([$controller, $callback[1]], $request, $response);
        }

        return call_user_func($callback, $request, $response);
    }
}
