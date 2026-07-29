SET FOREIGN_KEY_CHECKS = 0;

-- 1. Add fields to products table
ALTER TABLE `products` 
ADD COLUMN `unlimited_stock` TINYINT(1) NOT NULL DEFAULT 0 AFTER `track_stock`,
ADD COLUMN `min_stock` INT NOT NULL DEFAULT 0 AFTER `unlimited_stock`,
ADD COLUMN `max_stock` INT NULL DEFAULT NULL AFTER `min_stock`,
ADD COLUMN `allow_backorder` TINYINT(1) NOT NULL DEFAULT 0 AFTER `max_stock`,
ADD COLUMN `is_preorder` TINYINT(1) NOT NULL DEFAULT 0 AFTER `allow_backorder`,
ADD COLUMN `currency_code` VARCHAR(3) NOT NULL DEFAULT 'TRY' AFTER `compare_at_price`,
ADD COLUMN `images_360` TEXT NULL AFTER `cover_image_id`,
ADD COLUMN `youtube_url` VARCHAR(255) NULL AFTER `promo_video_id`,
ADD COLUMN `vimeo_url` VARCHAR(255) NULL AFTER `youtube_url`,
ADD COLUMN `mp4_url` VARCHAR(255) NULL AFTER `vimeo_url`,
ADD COLUMN `is_discount` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_taxable`,
ADD COLUMN `is_editors_choice` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_discount`,
ADD COLUMN `is_campaign` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_editors_choice`,
ADD COLUMN `is_new_arrival` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_campaign`,
ADD COLUMN `is_premium` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_new_arrival`,
ADD COLUMN `view_count` INT NOT NULL DEFAULT 0 AFTER `is_premium`;

-- 2. Add fields to product_translations table
ALTER TABLE `product_translations`
ADD COLUMN `summary` TEXT NULL AFTER `subtitle`,
ADD COLUMN `technical_specs` LONGTEXT NULL AFTER `summary`,
ADD COLUMN `instructions` LONGTEXT NULL AFTER `technical_specs`,
ADD COLUMN `warranty` TEXT NULL AFTER `instructions`,
ADD COLUMN `delivery_info` TEXT NULL AFTER `warranty`;

-- 3. Add fields to product_variants table
ALTER TABLE `product_variants`
ADD COLUMN `barcode` VARCHAR(100) NULL AFTER `sku`,
ADD COLUMN `image_id` BIGINT UNSIGNED NULL AFTER `barcode`,
ADD CONSTRAINT `fk_variants_image` FOREIGN KEY (`image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL;

-- 4. Create product_relations table
CREATE TABLE IF NOT EXISTS `product_relations` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `related_product_id` BIGINT UNSIGNED NOT NULL,
  `relation_type` VARCHAR(20) NOT NULL, -- 'similar', 'complementary', 'cross_sell', 'upsell'
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `idx_product_relation_unique` (`product_id`, `related_product_id`, `relation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Seed Permissions
INSERT INTO `permissions` (id, name, description) VALUES
(59, 'audit_products', 'Ürün denetim loglarını görüntüleme yetkisi')
ON DUPLICATE KEY UPDATE description=VALUES(description);

INSERT IGNORE INTO `role_permissions` (role_id, permission_id) VALUES
(1, 59), (2, 59);

SET FOREIGN_KEY_CHECKS = 1;
