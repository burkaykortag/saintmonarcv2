<?php

namespace Core;

class ExceptionHandler {
    public static function register(): void {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    public static function handleException(\Throwable $e): void {
        $code = $e->getCode() ?: 500;
        if ($code < 400 || $code > 599) {
            $code = 500;
        }
        
        Application::$app->response->setStatusCode($code);
        
        Logger::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if (Config::get('app.debug')) {
            echo "<h1>Fatal Error</h1>";
            echo "<p><strong>Message:</strong> " . Security::escape($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            // Render a friendly error page based on $code
            $view = new View();
            try {
                echo $view->render("errors/{$code}");
            } catch (\Exception $e2) {
                echo "<h1>An error occurred. Please try again later.</h1>";
            }
        }
        exit;
    }
}
