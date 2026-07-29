<?php

namespace Core;

class Config {
    private static array $config = [];

    public static function load(string $path): void {
        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            self::$config[$key] = require $file;
        }
    }

    public static function get(string $key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        return $value;
    }
}
