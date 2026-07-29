<?php

declare(strict_types=1);

namespace Core\Logging;

use Core\Contracts\LoggerInterface;
use Core\Contracts\ConfigInterface;

class Logger implements LoggerInterface {
    private string $logPath;

    public function __construct(ConfigInterface $config) {
        $this->logPath = $config->get('app.log_path', dirname(__DIR__, 2) . '/storage/logs');
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
    }

    public function emergency(string $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }
    public function alert(string $message, array $context = []): void { $this->log('ALERT', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
    public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
    public function notice(string $message, array $context = []): void { $this->log('NOTICE', $message, $context); }
    public function info(string $message, array $context = []): void { $this->log('INFO', $message, $context); }
    public function debug(string $message, array $context = []): void { $this->log('DEBUG', $message, $context); }

    public function log(string $level, string $message, array $context = []): void {
        $date = date('Y-m-d H:i:s');
        $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';
        $contextStr = !empty($context) ? json_encode($context) : '';
        $formattedMessage = "[{$date}] {$level}: {$message} {$contextStr}" . PHP_EOL;
        error_log($formattedMessage, 3, $logFile);
    }
}
