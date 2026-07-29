<?php

namespace Core;

class View {
    public function render(string $view, array $params = []): string {
        // Simple Theme Engine logic
        $theme = Config::get('app.theme', 'default');
        
        $themePath = Application::$ROOT_DIR . "/themes/{$theme}/views/{$view}.php";
        $defaultPath = Application::$ROOT_DIR . "/resources/views/{$view}.php";
        
        $path = file_exists($themePath) ? $themePath : $defaultPath;
        
        if (!file_exists($path)) {
            throw new \Exception("View not found: $view");
        }
        
        extract($params);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
