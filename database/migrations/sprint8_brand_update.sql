SET FOREIGN_KEY_CHECKS = 0;

-- 1. Add missing fields to brands table
ALTER TABLE `brands` 
ADD COLUMN `logo_image_id` BIGINT UNSIGNED NULL AFTER `logo`,
ADD COLUMN `cover_image_id` BIGINT UNSIGNED NULL AFTER `logo_image_id`,
ADD COLUMN `banner_image_id` BIGINT UNSIGNED NULL AFTER `cover_image_id`,
ADD COLUMN `website` VARCHAR(255) NULL AFTER `banner_image_id`,
ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
ADD COLUMN `show_in_home` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `show_in_home`,
ADD CONSTRAINT `fk_brands_logo` FOREIGN KEY (`logo_image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_brands_cover` FOREIGN KEY (`cover_image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_brands_banner` FOREIGN KEY (`banner_image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL;

-- 2. Add short_description to brand_translations
ALTER TABLE `brand_translations`
ADD COLUMN `short_description` VARCHAR(255) NULL AFTER `name`;

-- 3. Seed Brand Management Permissions
INSERT INTO `permissions` (id, name, description) VALUES
(45, 'view_brands', 'Markaları görüntüleme yetkisi'),
(46, 'create_brands', 'Marka ekleme yetkisi'),
(47, 'edit_brands', 'Marka düzenleme yetkisi'),
(48, 'delete_brands', 'Marka silme yetkisi'),
(49, 'export_brands', 'Markaları Excel/CSV dışa aktarma yetkisi')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Map all permissions to role ID 1 (super_admin) and ID 2 (administrator)
INSERT IGNORE INTO `role_permissions` (role_id, permission_id) VALUES
(1, 45), (1, 46), (1, 47), (1, 48), (1, 49),
(2, 45), (2, 46), (2, 47), (2, 48), (2, 49);

SET FOREIGN_KEY_CHECKS = 1;
