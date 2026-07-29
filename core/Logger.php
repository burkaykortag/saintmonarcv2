<?php

namespace Core;

class Logger {
    public static function log(string $level, string $message, array $context = []): void {
        $logDir = Application::$ROOT_DIR . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $date = date('Y-m-d H:i:s');
        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        
        $contextStr = !empty($context) ? json_encode($context) : '';
        $formattedMessage = "[{$date}] {$level}: {$message} {$contextStr}" . PHP_EOL;
        
        error_log($formattedMessage, 3, $logFile);
    }

    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }
    
    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }
}
