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
        
        // Step 1: Render the template body to capture internal variables (like $title)
        ob_start();
        include $path;
        $body = ob_get_clean();
        
        // Step 2: Render layout wrapper around the body
        ob_start();
        $hasHeader = str_contains($body, 'id="wrapper"') || str_contains($body, '<body');
        
        if (str_starts_with($template, 'admin/') && !$hasHeader) {
            $headerPath = $this->rootDir . "/resources/views/admin/layouts/header.php";
            $footerPath = $this->rootDir . "/resources/views/admin/layouts/footer.php";
            if (file_exists($headerPath)) {
                include $headerPath;
            }
            echo $body;
            if (file_exists($footerPath)) {
                include $footerPath;
            }
        } elseif (str_starts_with($template, 'store/') && !$hasHeader) {
            $headerPath = $this->rootDir . "/resources/views/store/layouts/header.php";
            $footerPath = $this->rootDir . "/resources/views/store/layouts/footer.php";
            if (file_exists($headerPath)) {
                include $headerPath;
            }
            echo $body;
            if (file_exists($footerPath)) {
                include $footerPath;
            }
        } else {
            echo $body;
        }
        
        return ob_get_clean();
    }
}
