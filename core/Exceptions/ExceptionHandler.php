<?php

declare(strict_types=1);

namespace Core\Exceptions;

use Core\Contracts\LoggerInterface;
use Core\Contracts\ConfigInterface;
use Core\Http\Response;
use Core\View\View;
use ErrorException;
use Throwable;

class ExceptionHandler {
    private LoggerInterface $logger;
    private ConfigInterface $config;
    private Response $response;
    private View $view;

    public function __construct(LoggerInterface $logger, ConfigInterface $config, Response $response, View $view) {
        $this->logger = $logger;
        $this->config = $config;
        $this->response = $response;
        $this->view = $view;
    }

    public function register(): void {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    public function handleException(Throwable $e): void {
        $code = $e->getCode() ?: 500;
        if ($code < 400 || $code > 599) {
            $code = 500;
        }

        $this->logger->error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($this->config->get('app.debug', false)) {
            $content = "<h1>Fatal Error</h1>";
            $content .= "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            $content .= "<p><strong>File:</strong> {$e->getFile()} on line {$e->getLine()}</p>";
            $content .= "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            try {
                $content = $this->view->render("errors/{$code}");
            } catch (\Exception $ex) {
                $content = "<h1>An error occurred. Please try again later.</h1>";
            }
        }
        
        $this->response->setStatusCode((int)$code)->setContent($content)->send();
    }
}
