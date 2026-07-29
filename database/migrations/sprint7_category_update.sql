SET FOREIGN_KEY_CHECKS = 0;

-- 1. Add missing cover, banner, icon and display parameters to categories table
ALTER TABLE `categories` 
ADD COLUMN `cover_image_id` BIGINT UNSIGNED NULL AFTER `parent_id`,
ADD COLUMN `banner_image_id` BIGINT UNSIGNED NULL AFTER `cover_image_id`,
ADD COLUMN `icon_image_id` BIGINT UNSIGNED NULL AFTER `banner_image_id`,
ADD COLUMN `show_in_menu` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
ADD COLUMN `show_in_home` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_in_menu`,
ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_in_home`,
ADD CONSTRAINT `fk_categories_cover` FOREIGN KEY (`cover_image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_categories_banner` FOREIGN KEY (`banner_image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_categories_icon` FOREIGN KEY (`icon_image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL;

-- 2. Seed permissions for Category Management
INSERT INTO `permissions` (id, name, description) VALUES
(40, 'view_categories', 'Kategorileri görüntüleme yetkisi'),
(41, 'create_categories', 'Kategori ekleme yetkisi'),
(42, 'edit_categories', 'Kategori düzenleme yetkisi'),
(43, 'delete_categories', 'Kategori silme yetkisi'),
(44, 'sort_categories', 'Kategorileri sıralama yetkisi')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Map all permissions to role ID 1 (super_admin) and ID 2 (administrator)
INSERT IGNORE INTO `role_permissions` (role_id, permission_id) VALUES
(1, 40), (1, 41), (1, 42), (1, 43), (1, 44),
(2, 40), (2, 41), (2, 42), (2, 43), (2, 44);

SET FOREIGN_KEY_CHECKS = 1;
