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
}
