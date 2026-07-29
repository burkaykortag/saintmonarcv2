<?php

namespace Core;

abstract class Controller {
    protected View $view;

    public function __construct() {
        $this->view = new View();
    }
    
    protected function render(string $view, array $params = []): string {
        return $this->view->render($view, $params);
    }
    
    protected function json(array $data, int $status = 200): void {
        Application::$app->response->json($data, $status);
    }
}
