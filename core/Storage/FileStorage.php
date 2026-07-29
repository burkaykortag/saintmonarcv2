<?php

declare(strict_types=1);

namespace Core\Storage;

use Core\Contracts\StorageInterface;

class FileStorage implements StorageInterface {
    private string $storagePath;

    public function __construct(string $storagePath = null) {
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/uploads';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }

    public function put(string $path, string $contents): bool {
        $fullPath = $this->getFullPath($path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return file_put_contents($fullPath, $contents) !== false;
    }

    public function get(string $path): ?string {
        $fullPath = $this->getFullPath($path);
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            return $content !== false ? $content : null;
        }
        return null;
    }

    public function exists(string $path): bool {
        return file_exists($this->getFullPath($path));
    }

    public function delete(string $path): bool {
        $fullPath = $this->getFullPath($path);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true;
    }

    private function getFullPath(string $path): string {
        return rtrim($this->storagePath, '/') . '/' . ltrim($path, '/');
    }
}
