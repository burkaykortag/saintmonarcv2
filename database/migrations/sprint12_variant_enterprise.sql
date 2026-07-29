-- Sprint 12 Variant and Attribute Management Enterprise Database Migration

-- 1. Product Attribute Sets
CREATE TABLE IF NOT EXISTS `product_attribute_sets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attribute Set Translations
CREATE TABLE IF NOT EXISTS `product_attribute_set_translations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `set_id` BIGINT UNSIGNED NOT NULL,
    `language_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    UNIQUE KEY `set_lang_unique` (`set_id`, `language_id`),
    CONSTRAINT `fk_set_trans_set` FOREIGN KEY (`set_id`) REFERENCES `product_attribute_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attribute Set Items
CREATE TABLE IF NOT EXISTS `product_attribute_set_items` (
    `set_id` BIGINT UNSIGNED NOT NULL,
    `attribute_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`set_id`, `attribute_id`),
    CONSTRAINT `fk_set_items_set` FOREIGN KEY (`set_id`) REFERENCES `product_attribute_sets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_set_items_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Modify attributes table to support type configuration
ALTER TABLE `attributes` MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'text';
-- Ensure deleted_at exists on attributes
ALTER TABLE `attributes` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL;

-- 3. Modify product_variants to support additional dimensions, special pricing and active status
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `special_price` DECIMAL(15,4) NULL DEFAULT NULL AFTER `cost_price`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `special_price_start` DATETIME NULL DEFAULT NULL AFTER `special_price`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `special_price_end` DATETIME NULL DEFAULT NULL AFTER `special_price_start`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `desi` DECIMAL(10,2) NULL DEFAULT NULL AFTER `weight`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `width` DECIMAL(10,2) NULL DEFAULT NULL AFTER `desi`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `height` DECIMAL(10,2) NULL DEFAULT NULL AFTER `width`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `length` DECIMAL(10,2) NULL DEFAULT NULL AFTER `height`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `length`;

-- 4. Product Variant Option Mapping (Product Variant Options)
CREATE TABLE IF NOT EXISTS `product_variant_options` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `variant_id` BIGINT UNSIGNED NOT NULL,
    `attribute_id` BIGINT UNSIGNED NOT NULL,
    `attribute_value_id` BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY `var_attr_unique` (`variant_id`, `attribute_id`),
    CONSTRAINT `fk_var_options_var` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_var_options_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_var_options_val` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Product Variant Images Gallery
CREATE TABLE IF NOT EXISTS `product_variant_images` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `variant_id` BIGINT UNSIGNED NOT NULL,
    `image_id` BIGINT UNSIGNED NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    CONSTRAINT `fk_var_imgs_var` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_var_imgs_media` FOREIGN KEY (`image_id`) REFERENCES `media_library` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Product Variant Stock Movements Tracker
CREATE TABLE IF NOT EXISTS `product_variant_stocks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `variant_id` BIGINT UNSIGNED NOT NULL,
    `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `reserved` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_var_stocks_var` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Product Variant Price Currency Matrix
CREATE TABLE IF NOT EXISTS `product_variant_prices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `variant_id` BIGINT UNSIGNED NOT NULL,
    `currency_code` VARCHAR(3) NOT NULL,
    `price` DECIMAL(15,4) NOT NULL,
    `compare_at_price` DECIMAL(15,4) NULL DEFAULT NULL,
    `special_price` DECIMAL(15,4) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `var_curr_unique` (`variant_id`, `currency_code`),
    CONSTRAINT `fk_var_prices_var` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. RBAC Permissions Registration
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_attributes', 'Özellikleri görüntüleme yetkisi'),
('create_attributes', 'Özellik oluşturma yetkisi'),
('edit_attributes', 'Özellik düzenleme yetkisi'),
('delete_attributes', 'Özellik silme yetkisi'),
('manage_variants', 'Varyant yönetimi yetkisi'),
('manage_variant_stock', 'Varyant stok yönetimi yetkisi'),
('manage_variant_price', 'Varyant fiyat yönetimi yetkisi');

-- Bind new permissions to Admin role (role_id = 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions` WHERE `name` IN (
    'view_attributes', 'create_attributes', 'edit_attributes', 'delete_attributes',
    'manage_variants', 'manage_variant_stock', 'manage_variant_price'
);
