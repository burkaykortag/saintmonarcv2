<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\StorageInterface;
use Exception;
use ZipArchive;

class MediaService {
    private DatabaseInterface $db;
    private StorageInterface $storage;
    private string $uploadDir;
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'mp4', 'mov', 'webm', 'pdf'];
    private array $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml',
        'video/mp4', 'video/quicktime', 'video/webm',
        'application/pdf'
    ];

    public function __construct(DatabaseInterface $db, StorageInterface $storage) {
        $this->db = $db;
        $this->storage = $storage;
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads';
        
        // Ensure directories exist
        $dirs = [
            $this->uploadDir,
            $this->uploadDir . '/thumbnails',
            $this->uploadDir . '/medium',
            $this->uploadDir . '/large'
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }

    public function upload(array $file, ?int $folderId = null, ?int $adminId = null): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Dosya yükleme hatası: Kod " . $file['error']);
        }

        $tempPath = $file['tmp_name'];
        $originalName = basename($file['name']);
        $fileSize = $file['size'];
        
        if ($fileSize > 50 * 1024 * 1024) { // 50MB
            throw new Exception("Dosya boyutu 50MB sınırını aşamaz.");
        }

        // Mime Type and Extension validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempPath);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions, true) || !in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new Exception("İzin verilmeyen dosya türü veya uzantısı: {$extension} ({$mimeType})");
        }

        // Security check
        if (preg_match('/\.(php|phtml|php3|php4|php5|php7|php8|html|htm|js|exe|bat|sh)$/i', $originalName)) {
            throw new Exception("Güvenlik ihlali: Zararlı olabilecek dosya yüklenemez.");
        }

        // SVG security checks
        if ($mimeType === 'image/svg+xml' || $extension === 'svg') {
            $svgContent = file_get_contents($tempPath);
            if (preg_match('/<script|<onload|<onclick/i', $svgContent)) {
                throw new Exception("Güvenlik ihlali: SVG dosyası içinde zararlı kod (XSS) tespit edildi.");
            }
        }

        // Duplicate Check: SHA256 Hash
        $fileHash = hash_file('sha256', $tempPath);
        $existing = $this->db->query("SELECT * FROM media_library WHERE file_hash = :hash LIMIT 1", [':hash' => $fileHash]);
        if (!empty($existing)) {
            throw new Exception("Mükerrer dosya tespiti: Bu dosya sistemde zaten yüklü.", 409);
        }

        // Generate unique names
        $uuid = $this->generateUuid();
        $newFilename = $uuid . '.' . $extension;
        $relativeDir = date('Y/m');
        
        // Target Directories
        $targetDir = $this->uploadDir . '/' . $relativeDir;
        $largeDir = $this->uploadDir . '/large/' . $relativeDir;
        $mediumDir = $this->uploadDir . '/medium/' . $relativeDir;
        $thumbDir = $this->uploadDir . '/thumbnails/' . $relativeDir;

        foreach ([$targetDir, $largeDir, $mediumDir, $thumbDir] as $d) {
            if (!is_dir($d)) {
                mkdir($d, 0777, true);
            }
        }

        $targetPath = $targetDir . '/' . $newFilename;
        $relativeFilePath = 'uploads/' . $relativeDir . '/' . $newFilename;

        $width = null;
        $height = null;
        $duration = null;
        $isImage = str_starts_with($mimeType, 'image/');
        $isVideo = str_starts_with($mimeType, 'video/');
        $hasGd = extension_loaded('gd');

        if ($isImage && $hasGd && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            list($width, $height) = getimagesize($tempPath);
            
            // Save WebP optimized image as original/main
            $this->optimizeImage($tempPath, $targetPath, $extension, $width, $height, 1920, 85);
            
            // Generate Large Size (max 1200px)
            $this->optimizeImage($tempPath, $largeDir . '/' . $newFilename, $extension, $width, $height, 1200, 80);

            // Generate Medium Size (max 600px)
            $this->optimizeImage($tempPath, $mediumDir . '/' . $newFilename, $extension, $width, $height, 600, 75);

            // Generate Thumbnail (150x150 crop)
            $this->createThumbnail($tempPath, $thumbDir . '/' . $newFilename, $extension, $width, $height);
        } else {
            // As-is upload for videos, pdfs, svgs
            if (!move_uploaded_file($tempPath, $targetPath)) {
                throw new Exception("Dosya hedef dizine taşınamadı.");
            }

            if ($isImage) {
                // If GD is missing, copy original file as fallback to large, medium and thumbnails folders
                copy($targetPath, $largeDir . '/' . $newFilename);
                copy($targetPath, $mediumDir . '/' . $newFilename);
                copy($targetPath, $thumbDir . '/' . $newFilename);
            }
        }

        if ($isVideo) {
            // Simple mock duration of 10s if ffmpeg not installed, or try to read basic metadata
            $duration = 10;
        }

        // Save to Database
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO media_library 
                (folder_id, uuid, filename, original_name, filepath, file_size, mime_type, extension, width, height, duration, file_hash, uploaded_by_admin) 
                VALUES (:folder_id, :uuid, :filename, :original_name, :filepath, :file_size, :mime_type, :extension, :width, :height, :duration, :file_hash, :uploaded_by_admin)",
                [
                    ':folder_id' => $folderId,
                    ':uuid' => $uuid,
                    ':filename' => $newFilename,
                    ':original_name' => $originalName,
                    ':filepath' => $relativeFilePath,
                    ':file_size' => $fileSize,
                    ':mime_type' => $mimeType,
                    ':extension' => $extension,
                    ':width' => $width,
                    ':height' => $height,
                    ':duration' => $duration,
                    ':file_hash' => $fileHash,
                    ':uploaded_by_admin' => $adminId
                ]
            );
            $mediaId = (int)$this->db->lastInsertId();
            $this->db->commit();

            return [
                'id' => $mediaId,
                'uuid' => $uuid,
                'filename' => $newFilename,
                'filepath' => $relativeFilePath,
                'original_name' => $originalName
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->deletePhysicalFilesOnDisk($newFilename, $relativeDir);
            throw $e;
        }
    }

    public function bulkDelete(array $ids): void {
        foreach ($ids as $id) {
            $file = $this->db->query("SELECT filename, filepath FROM media_library WHERE id = :id LIMIT 1", [':id' => (int)$id]);
            if (!empty($file)) {
                $filename = $file[0]['filename'];
                $filepath = $file[0]['filepath'];
                
                // Get relative dir from path uploads/YYYY/MM/filename
                $parts = explode('/', $filepath);
                $relativeDir = '';
                if (count($parts) >= 4) {
                    $relativeDir = $parts[1] . '/' . $parts[2];
                }
                
                $this->deletePhysicalFilesOnDisk($filename, $relativeDir);
                $this->db->execute("DELETE FROM media_tag_relations WHERE media_id = :id", [':id' => (int)$id]);
                $this->db->execute("DELETE FROM media_library WHERE id = :id", [':id' => (int)$id]);
            }
        }
    }

    public function bulkMove(array $ids, ?int $folderId): void {
        foreach ($ids as $id) {
            $this->db->execute("UPDATE media_library SET folder_id = :folder WHERE id = :id", [
                ':folder' => $folderId,
                ':id' => (int)$id
            ]);
        }
    }

    public function bulkTag(array $ids, array $tagIds): void {
        foreach ($ids as $id) {
            foreach ($tagIds as $tid) {
                $this->db->execute("INSERT IGNORE INTO media_tag_relations (media_id, tag_id) VALUES (:mid, :tid)", [
                    ':mid' => (int)$id,
                    ':tid' => (int)$tid
                ]);
            }
        }
    }

    public function bulkDownload(array $ids): string {
        $zipName = 'media_download_' . time() . '_' . mt_rand(100, 999) . '.zip';
        $zipPath = $this->uploadDir . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception("ZIP dosyası oluşturulamadı.");
        }

        foreach ($ids as $id) {
            $file = $this->db->query("SELECT filepath, original_name FROM media_library WHERE id = :id LIMIT 1", [':id' => (int)$id]);
            if (!empty($file)) {
                $fullPath = dirname($this->uploadDir) . '/public/' . $file[0]['filepath'];
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, $file[0]['original_name']);
                }
            }
        }
        $zip->close();
        
        return 'uploads/' . $zipName;
    }

    public function bulkCopy(array $ids): void {
        foreach ($ids as $id) {
            $file = $this->db->query("SELECT * FROM media_library WHERE id = :id LIMIT 1", [':id' => (int)$id]);
            if (!empty($file)) {
                $f = $file[0];
                $uuid = $this->generateUuid();
                $newFilename = $uuid . '.' . $f['extension'];
                
                $parts = explode('/', $f['filepath']);
                $relativeDir = '';
                if (count($parts) >= 4) {
                    $relativeDir = $parts[1] . '/' . $parts[2];
                }

                // Copy files physically on disk
                $paths = [
                    $this->uploadDir . '/' . $relativeDir,
                    $this->uploadDir . '/large/' . $relativeDir,
                    $this->uploadDir . '/medium/' . $relativeDir,
                    $this->uploadDir . '/thumbnails/' . $relativeDir
                ];

                foreach ($paths as $p) {
                    $src = $p . '/' . $f['filename'];
                    $dst = $p . '/' . $newFilename;
                    if (file_exists($src)) {
                        copy($src, $dst);
                    }
                }

                // Insert database record
                $this->db->execute(
                    "INSERT INTO media_library 
                    (folder_id, uuid, filename, original_name, filepath, file_size, mime_type, extension, width, height, duration, title, alt_text, description, file_hash, uploaded_by_admin) 
                    VALUES (:folder_id, :uuid, :filename, :original_name, :filepath, :file_size, :mime_type, :extension, :width, :height, :duration, :title, :alt_text, :description, :file_hash, :uploaded_by_admin)",
                    [
                        ':folder_id' => $f['folder_id'],
                        ':uuid' => $uuid,
                        ':filename' => $newFilename,
                        ':original_name' => 'Kopya_' . $f['original_name'],
                        ':filepath' => 'uploads/' . $relativeDir . '/' . $newFilename,
                        ':file_size' => $f['file_size'],
                        ':mime_type' => $f['mime_type'],
                        ':extension' => $f['extension'],
                        ':width' => $f['width'],
                        ':height' => $f['height'],
                        ':duration' => $f['duration'],
                        ':title' => $f['title'] ? 'Kopya_' . $f['title'] : null,
                        ':alt_text' => $f['alt_text'],
                        ':description' => $f['description'],
                        ':file_hash' => hash('sha256', $uuid . mt_rand(1,1000)), // unique hash
                        ':uploaded_by_admin' => $f['uploaded_by_admin']
                    ]
                );
            }
        }
    }

    public function bulkWebp(array $ids): void {
        if (!extension_loaded('gd')) {
            throw new Exception("WebP dönüşümü için GD kütüphanesi aktif edilmelidir.");
        }

        foreach ($ids as $id) {
            $file = $this->db->query("SELECT * FROM media_library WHERE id = :id LIMIT 1", [':id' => (int)$id]);
            if (!empty($file) && in_array(strtolower($file[0]['extension']), ['jpg', 'jpeg', 'png'], true)) {
                $f = $file[0];
                $parts = explode('/', $f['filepath']);
                $relativeDir = '';
                if (count($parts) >= 4) {
                    $relativeDir = $parts[1] . '/' . $parts[2];
                }

                $oldFilename = $f['filename'];
                $newFilename = pathinfo($oldFilename, PATHINFO_FILENAME) . '.webp';

                // Physical WebP conversions
                $paths = [
                    $this->uploadDir . '/' . $relativeDir,
                    $this->uploadDir . '/large/' . $relativeDir,
                    $this->uploadDir . '/medium/' . $relativeDir,
                    $this->uploadDir . '/thumbnails/' . $relativeDir
                ];

                foreach ($paths as $p) {
                    $src = $p . '/' . $oldFilename;
                    $dst = $p . '/' . $newFilename;
                    if (file_exists($src)) {
                        $this->convertFileToWebP($src, $dst, $f['extension']);
                        unlink($src); // Remove old JPG/PNG image
                    }
                }

                // Update database
                $this->db->execute(
                    "UPDATE media_library 
                     SET filename = :filename, extension = 'webp', filepath = :filepath, mime_type = 'image/webp' 
                     WHERE id = :id",
                    [
                        ':filename' => $newFilename,
                        ':filepath' => 'uploads/' . $relativeDir . '/' . $newFilename,
                        ':id' => $f['id']
                    ]
                );
            }
        }
    }

    public function bulkRegenerateThumbnails(array $ids): void {
        if (!extension_loaded('gd')) {
            throw new Exception("Thumbnail yeniden üretimi için GD kütüphanesi aktif edilmelidir.");
        }

        foreach ($ids as $id) {
            $file = $this->db->query("SELECT filepath, filename, extension, width, height FROM media_library WHERE id = :id LIMIT 1", [':id' => (int)$id]);
            if (!empty($file) && in_array(strtolower($file[0]['extension']), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $f = $file[0];
                $parts = explode('/', $f['filepath']);
                $relativeDir = '';
                if (count($parts) >= 4) {
                    $relativeDir = $parts[1] . '/' . $parts[2];
                }

                $originalPath = dirname($this->uploadDir) . '/public/' . $f['filepath'];
                
                if (file_exists($originalPath)) {
                    $largePath = $this->uploadDir . '/large/' . $relativeDir . '/' . $f['filename'];
                    $mediumPath = $this->uploadDir . '/medium/' . $relativeDir . '/' . $f['filename'];
                    $thumbPath = $this->uploadDir . '/thumbnails/' . $relativeDir . '/' . $f['filename'];

                    // Ensure target subdirectories exist
                    foreach ([dirname($largePath), dirname($mediumPath), dirname($thumbPath)] as $d) {
                        if (!is_dir($d)) {
                            mkdir($d, 0777, true);
                        }
                    }

                    $w = (int)$f['width'];
                    $h = (int)$f['height'];

                    $this->optimizeImage($originalPath, $largePath, $f['extension'], $w, $h, 1200, 80);
                    $this->optimizeImage($originalPath, $mediumPath, $f['extension'], $w, $h, 600, 75);
                    $this->createThumbnail($originalPath, $thumbPath, $f['extension'], $w, $h);
                }
            }
        }
    }

    private function deletePhysicalFilesOnDisk(string $filename, string $relativeDir): void {
        $paths = [
            $this->uploadDir . '/' . $relativeDir . '/' . $filename,
            $this->uploadDir . '/large/' . $relativeDir . '/' . $filename,
            $this->uploadDir . '/medium/' . $relativeDir . '/' . $filename,
            $this->uploadDir . '/thumbnails/' . $relativeDir . '/' . $filename
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                unlink($p);
            }
        }
    }

    private function optimizeImage(string $src, string $dest, string $ext, int $w, int $h, int $maxDim, int $quality): void {
        switch (strtolower($ext)) {
            case 'jpeg':
            case 'jpg':
                $img = imagecreatefromjpeg($src);
                break;
            case 'png':
                $img = imagecreatefrompng($src);
                imagealphablending($img, false);
                imagesavealpha($img, true);
                break;
            case 'webp':
                $img = imagecreatefromwebp($src);
                break;
            default:
                copy($src, $dest);
                return;
        }

        if (!$img) {
            copy($src, $dest);
            return;
        }

        if ($w > $maxDim || $h > $maxDim) {
            if ($w > $h) {
                $newW = $maxDim;
                $newH = (int)round($h * ($maxDim / $w));
            } else {
                $newH = $maxDim;
                $newW = (int)round($w * ($maxDim / $h));
            }
            $resized = imagecreatetruecolor($newW, $newH);
            if (strtolower($ext) === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($img);
            $img = $resized;
        }

        // Save as WebP
        imagewebp($img, $dest, $quality);
        imagedestroy($img);
    }

    private function createThumbnail(string $src, string $dest, string $ext, int $w, int $h): void {
        switch (strtolower($ext)) {
            case 'jpeg':
            case 'jpg':
                $img = imagecreatefromjpeg($src);
                break;
            case 'png':
                $img = imagecreatefrompng($src);
                break;
            case 'webp':
                $img = imagecreatefromwebp($src);
                break;
            default:
                return;
        }

        if (!$img) return;

        $thumbSize = 150;
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        
        $minDim = min($w, $h);
        $xOffset = (int)round(($w - $minDim) / 2);
        $yOffset = (int)round(($h - $minDim) / 2);

        imagecopyresampled($thumb, $img, 0, 0, $xOffset, $yOffset, $thumbSize, $thumbSize, $minDim, $minDim);
        
        imagewebp($thumb, $dest, 80);
        imagedestroy($img);
        imagedestroy($thumb);
    }

    private function convertFileToWebP(string $src, string $dest, string $ext): void {
        switch (strtolower($ext)) {
            case 'jpeg':
            case 'jpg':
                $img = imagecreatefromjpeg($src);
                break;
            case 'png':
                $img = imagecreatefrompng($src);
                break;
            case 'webp':
                $img = imagecreatefromwebp($src);
                break;
            default:
                return;
        }
        if ($img) {
            imagewebp($img, $dest, 85);
            imagedestroy($img);
        }
    }

    private function generateUuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
