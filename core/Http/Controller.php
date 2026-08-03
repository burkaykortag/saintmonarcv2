<?php

declare(strict_types=1);

namespace Core\Http;

use Core\View\View;

abstract class Controller {
    protected View $view;

    public function __construct(View $view) {
        $this->view = $view;
    }
    
    protected function render(string $template, array $params = []): string {
        return $this->view->render($template, $params);
    }

    protected function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
