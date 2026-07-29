<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Contracts\ContainerInterface;
use Exception;

class Router {
    private array $routes = [];
    private ContainerInterface $container;
    private array $routeMiddlewares = [];

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function get(string $path, array|callable $callback, array $middlewares = []): void {
        $this->addRoute('GET', $path, $callback, $middlewares);
    }

    public function post(string $path, array|callable $callback, array $middlewares = []): void {
        $this->addRoute('POST', $path, $callback, $middlewares);
    }

    public function put(string $path, array|callable $callback, array $middlewares = []): void {
        $this->addRoute('PUT', $path, $callback, $middlewares);
    }

    public function delete(string $path, array|callable $callback, array $middlewares = []): void {
        $this->addRoute('DELETE', $path, $callback, $middlewares);
    }

    public function patch(string $path, array|callable $callback, array $middlewares = []): void {
        $this->addRoute('PATCH', $path, $callback, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $callback, array $middlewares = []): void {
        $this->routes[$method][$path] = $callback;
        $this->routeMiddlewares[$method][$path] = $middlewares;
    }

    public function resolve(Request $request, Response $response) {
        $path = $request->getUri();
        $method = $request->getMethod();
        
        // 301 Redirect lookup before matching route
        try {
            $db = $this->container->get(\Core\Contracts\DatabaseInterface::class);
            $redirect = $db->query("SELECT * FROM redirects WHERE source_url = :url LIMIT 1", [':url' => $path]);
            if (!empty($redirect)) {
                $response->redirect($redirect[0]['target_url'], (int)$redirect[0]['status_code']);
                exit;
            }
        } catch (\Throwable $t) {
            // Silently skip if DB is not ready or table doesn't exist
        }
        
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            throw new Exception("Route not found: {$path}", 404);
        }

        // Execute route-specific middlewares
        $middlewares = $this->routeMiddlewares[$method][$path] ?? [];
        foreach ($middlewares as $middleware) {
            $middlewareParams = [];
            
            if (is_string($middleware) && str_contains($middleware, ':')) {
                list($middlewareName, $paramString) = explode(':', $middleware, 2);
                $middlewareParams = explode(',', $paramString);
                $middlewareClass = $this->getMiddlewareClass($middlewareName);
            } else {
                $middlewareClass = is_string($middleware) ? $this->getMiddlewareClass($middleware) : $middleware;
            }

            if ($middlewareClass) {
                $instance = $this->container->get($middlewareClass);
                if (method_exists($instance, 'handle')) {
                    $instance->handle($request, $response, ...$middlewareParams);
                }
            }
        }

        if (is_array($callback)) {
            $controller = $this->container->get($callback[0]);
            return call_user_func([$controller, $callback[1]], $request, $response);
        }

        return call_user_func($callback, $request, $response);
    }

    private function getMiddlewareClass(string $name): ?string {
        $aliases = [
            'auth' => \App\Middleware\AuthMiddleware::class,
            'guest' => \App\Middleware\GuestMiddleware::class,
            'admin' => \App\Middleware\AdminMiddleware::class,
            'customer' => \App\Middleware\CustomerMiddleware::class,
            'permission' => \App\Middleware\PermissionMiddleware::class,
            'verified' => \App\Middleware\VerifiedEmailMiddleware::class,
            'active' => \App\Middleware\ActiveAccountMiddleware::class,
            'csrf' => \App\Middleware\CsrfMiddleware::class,
        ];

        return $aliases[strtolower($name)] ?? $name;
    }
}
