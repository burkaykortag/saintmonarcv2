<?php

declare(strict_types=1);

namespace Core\Cache;

use Core\Contracts\CacheInterface;

class FileCache implements CacheInterface {
    private string $cacheDir;

    public function __construct(string $cacheDir = null) {
        $this->cacheDir = $cacheDir ?? dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = include $file;
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl = null): bool {
        $file = $this->getFilePath($key);
        $expiresAt = $ttl !== null ? time() + $ttl : PHP_INT_MAX;

        $content = "<?php return " . var_export(['value' => $value, 'expires_at' => $expiresAt], true) . ";";
        return file_put_contents($file, $content) !== false;
    }

    public function delete(string $key): bool {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function clear(): bool {
        $files = glob($this->cacheDir . '/*.php');
        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = $success && unlink($file);
            }
        }
        return $success;
    }

    public function has(string $key): bool {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return false;
        }

        $data = include $file;
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->delete($key);
            return false;
        }
        return true;
    }

    private function getFilePath(string $key): string {
        return $this->cacheDir . '/' . md5($key) . '.php';
    }
}
