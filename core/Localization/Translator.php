<?php

declare(strict_types=1);

namespace Core\Localization;

use Core\Contracts\ConfigInterface;

class Translator {
    private array $translations = [];
    private string $locale;
    private string $fallbackLocale;
    private string $langPath;

    public function __construct(ConfigInterface $config) {
        $this->locale = $config->get('app.locale', 'en');
        $this->fallbackLocale = $config->get('app.fallback_locale', 'en');
        $this->langPath = dirname(__DIR__, 2) . '/resources/lang';
        $this->load($this->locale);
    }

    public function setLocale(string $locale): void {
        $this->locale = $locale;
        $this->load($locale);
    }

    private function load(string $locale): void {
        $file = "{$this->langPath}/{$locale}.php";
        if (file_exists($file)) {
            $this->translations = require $file;
        } else {
            $fallbackFile = "{$this->langPath}/{$this->fallbackLocale}.php";
            if (file_exists($fallbackFile)) {
                $this->translations = require $fallbackFile;
            }
        }
    }

    public function get(string $key, array $replace = []): string {
        $keys = explode('.', $key);
        $value = $this->translations;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $key; // Return key if translation not found
            }
        }

        if (is_string($value)) {
            foreach ($replace as $placeholder => $replacement) {
                $value = str_replace(':' . $placeholder, (string)$replacement, $value);
            }
            return $value;
        }

        return $key;
    }
}
