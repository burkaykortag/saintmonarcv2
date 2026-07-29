SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create media_folders table
CREATE TABLE IF NOT EXISTS `media_folders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_by_admin` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Drop or Modify media_library table to support full metadata
DROP TABLE IF EXISTS `media_library`;

CREATE TABLE `media_library` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `folder_id` BIGINT UNSIGNED NULL,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(255) NOT NULL,
    `file_size` INT NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `extension` VARCHAR(10) NOT NULL,
    `width` INT NULL,
    `height` INT NULL,
    `alt_text` VARCHAR(255) NULL,
    `title` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `file_hash` VARCHAR(64) NOT NULL, -- SHA256 file hash for duplicate check
    `uploaded_by_admin` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
    INDEX `idx_media_file_hash` (`file_hash`),
    INDEX `idx_media_folder` (`folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create media_tags and relations
CREATE TABLE IF NOT EXISTS `media_tags` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_tag_relations` (
    `media_id` BIGINT UNSIGNED NOT NULL,
    `tag_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`media_id`, `tag_id`),
    FOREIGN KEY (`media_id`) REFERENCES `media_library` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `media_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Seed some tags
INSERT INTO `media_tags` (name) VALUES 
('Banner'), ('Ürün'), ('Logo'), ('Slider'), ('Galeri'), ('Kategori')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 5. Seed permissions for media management
INSERT INTO `permissions` (id, name, description) VALUES
(35, 'view_media', 'Medya kütüphanesini görüntüleme yetkisi'),
(36, 'upload_media', 'Medya yükleme yetkisi'),
(37, 'edit_media', 'Medya başlık, alt text ve klasör düzenleme yetkisi'),
(38, 'delete_media', 'Medya silme yetkisi'),
(39, 'bulk_media', 'Medyalarda toplu işlem yetkisi')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Map all permissions to role ID 1 (super_admin) and ID 2 (administrator)
INSERT IGNORE INTO `role_permissions` (role_id, permission_id) VALUES
(1, 35), (1, 36), (1, 37), (1, 38), (1, 39),
(2, 35), (2, 36), (2, 37), (2, 38), (2, 39);

SET FOREIGN_KEY_CHECKS = 1;
