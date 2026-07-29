<?php

declare(strict_types=1);

namespace Core\View;

use Core\Contracts\ConfigInterface;
use Exception;

class View {
    private ConfigInterface $config;
    private string $rootDir;

    public function __construct(ConfigInterface $config) {
        $this->config = $config;
        $this->rootDir = dirname(__DIR__, 2);
    }

    public function render(string $template, array $params = []): string {
        $theme = $this->config->get('app.theme', 'default');
        
        $themePath = $this->rootDir . "/themes/{$theme}/views/{$template}.php";
        $defaultPath = $this->rootDir . "/resources/views/{$template}.php";
        
        $path = file_exists($themePath) ? $themePath : $defaultPath;
        
        if (!file_exists($path)) {
            throw new Exception("View template not found: {$template}");
        }
        
        extract($params);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
