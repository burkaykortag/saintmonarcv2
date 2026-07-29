<?php

declare(strict_types=1);

namespace Core\Config;

use Core\Contracts\ConfigInterface;

class ConfigLoader implements ConfigInterface {
    private array $config = [];

    public function __construct(string $configPath) {
        $this->load($configPath);
    }

    private function load(string $path): void {
        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        return $value;
    }

    public function set(string $key, mixed $value): void {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $i => $k) {
            if (count($keys) === 1 || $i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }
    }

    public function has(string $key): bool {
        return $this->get($key) !== null;
    }
}
