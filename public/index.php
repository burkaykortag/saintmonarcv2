<?php

/**
 * SaintMonarc E-Commerce Platform
 * Enterprise Front Controller
 */

declare(strict_types=1);

function url(string $path): string {
    $basePath = getenv('APP_BASE_PATH') ?: '';
    return rtrim($basePath, '/') . '/' . ltrim($path, '/');
}

define('ROOT_DIR', dirname(__DIR__));

if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
} else {
    // Development fallback autoloader
    spl_autoload_register(function (string $class) {
        $prefixMap = [
            'Core\\' => 'core/',
            'App\\' => 'app/',
            'Modules\\' => 'modules/',
            'Admin\\' => 'admin/'
        ];

        foreach ($prefixMap as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            $relativeClass = substr($class, $len);
            $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    });
}

use Core\Application;

$app = new Application(ROOT_DIR);

// Load Routes
$router = $app->getContainer()->get(\Core\Http\Router::class);
require_once ROOT_DIR . '/routes/web.php';
require_once ROOT_DIR . '/routes/api.php';
require_once ROOT_DIR . '/routes/admin.php';

$app->run();
