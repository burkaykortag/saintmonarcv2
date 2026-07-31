<?php

declare(strict_types=1);

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Whitelist allowed SQL ORDER BY columns and direction to prevent SQL Injection
     */
    public static function sanitizeOrderBy(string $column, array $allowedColumns, string $defaultColumn = 'id'): string
    {
        $column = strtolower(trim($column));
        if (in_array($column, $allowedColumns, true)) {
            return $column;
        }
        return $defaultColumn;
    }

    /**
     * Whitelist allowed SQL ORDER BY direction (ASC or DESC)
     */
    public static function sanitizeDirection(string $direction, string $default = 'DESC'): string
    {
        $direction = strtoupper(trim($direction));
        return in_array($direction, ['ASC', 'DESC'], true) ? $direction : $default;
    }

    /**
     * Prevent Path Traversal attacks by stripping directory movement characters
     */
    public static function sanitizePath(string $filename): string
    {
        // Strip null bytes and directory traversal symbols
        $clean = str_replace(["\0", '../', '..\\', './', '.\\'], '', $filename);
        return basename($clean);
    }

    /**
     * Validate File Upload for security (extension whitelist, MIME type, double extensions)
     */
    public static function validateFileUpload(array $file, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'docx'], int $maxSizeBytes = 10485760): array
    {
        if (empty($file['name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Geçersiz veya eksik dosya yüklemesi.'];
        }

        if ($file['size'] > $maxSizeBytes) {
            return ['valid' => false, 'error' => 'Dosya boyutu çok büyük (Max 10MB).'];
        }

        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Check extension whitelist
        if (!in_array($ext, $allowedExtensions, true)) {
            return ['valid' => false, 'error' => 'İzin verilmeyen dosya uzantısı.'];
        }

        // Prevent executable extensions anywhere in filename (e.g. shell.php.jpg)
        if (preg_match('/\.(php|phtml|php3|php4|php5|phar|exe|bat|cmd|sh|pl|py|cgi)$/i', $originalName) ||
            preg_match('/\.(php|phtml|php3|php4|php5|phar|exe|bat|cmd|sh|pl|py|cgi)\./i', $originalName)) {
            return ['valid' => false, 'error' => 'Güvenlik riski taşıyan dosya formatı reddedildi.'];
        }

        // Validate actual MIME type using finfo if available
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (isset($allowedMimes[$ext]) && strpos($mimeType, explode('/', $allowedMimes[$ext])[0]) === false && $mimeType !== $allowedMimes[$ext]) {
                return ['valid' => false, 'error' => 'Dosya içeriği MIME tipi ile uyuşmuyor.'];
            }
        }

        // Generate safe random filename
        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;

        return [
            'valid' => true,
            'original_name' => $originalName,
            'safe_filename' => $randomName,
            'extension' => $ext
        ];
    }
}
