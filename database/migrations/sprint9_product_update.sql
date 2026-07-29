SET FOREIGN_KEY_CHECKS = 0;

-- 1. Add fields to products table
ALTER TABLE `products` 
ADD COLUMN `cover_image_id` BIGINT UNSIGNED NULL AFTER `brand_id`,
ADD COLUMN `barcode` VARCHAR(100) NULL AFTER `sku`,
ADD COLUMN `gtin` VARCHAR(50) NULL AFTER `barcode`,
ADD COLUMN `ean` VARCHAR(50) NULL AFTER `gtin`,
ADD COLUMN `upc` VARCHAR(50) NULL AFTER `ean`,
ADD COLUMN `mpn` VARCHAR(50) NULL AFTER `upc`,
ADD COLUMN `model_no` VARCHAR(100) NULL AFTER `mpn`,
ADD COLUMN `product_type` VARCHAR(20) NOT NULL DEFAULT 'physical' AFTER `model_no`,
ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'draft' AFTER `product_type`,
ADD COLUMN `is_new` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
ADD COLUMN `is_bestseller` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_new`,
ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_bestseller`,
ADD COLUMN `show_in_home` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
ADD COLUMN `show_in_slider` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_in_home`,
ADD COLUMN `show_in_banner` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_in_slider`,
ADD COLUMN `free_shipping` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_in_banner`,
ADD COLUMN `tax_included` TINYINT(1) NOT NULL DEFAULT 0 AFTER `free_shipping`,
ADD COLUMN `is_taxable` TINYINT(1) NOT NULL DEFAULT 1 AFTER `tax_included`,
ADD COLUMN `profit` DECIMAL(15,4) NULL AFTER `compare_at_price`,
ADD COLUMN `profit_margin` DECIMAL(5,2) NULL AFTER `profit`,
ADD COLUMN `profit_rate` DECIMAL(5,2) NULL AFTER `profit_margin`,
ADD COLUMN `special_price` DECIMAL(15,4) NULL AFTER `price`,
ADD COLUMN `special_price_start` TIMESTAMP NULL AFTER `special_price`,
ADD COLUMN `special_price_end` TIMESTAMP NULL AFTER `special_price_start`,
ADD COLUMN `total_stock` INT NOT NULL DEFAULT 0 AFTER `special_price_end`,
ADD COLUMN `critical_stock` INT NOT NULL DEFAULT 5 AFTER `total_stock`,
ADD COLUMN `min_order` INT NOT NULL DEFAULT 1 AFTER `critical_stock`,
ADD COLUMN `max_order` INT NULL AFTER `min_order`,
ADD COLUMN `track_stock` TINYINT(1) NOT NULL DEFAULT 1 AFTER `max_order`,
ADD COLUMN `stock_status` VARCHAR(20) NOT NULL DEFAULT 'in_stock' AFTER `track_stock`,
ADD COLUMN `weight` DECIMAL(8,2) NULL AFTER `stock_status`,
ADD COLUMN `desi` DECIMAL(8,2) NULL AFTER `weight`,
ADD COLUMN `width` DECIMAL(8,2) NULL AFTER `desi`,
ADD COLUMN `height` DECIMAL(8,2) NULL AFTER `width`,
ADD COLUMN `length` DECIMAL(8,2) NULL AFTER `height`,
ADD COLUMN `delivery_time` VARCHAR(50) NULL AFTER `length`,
ADD COLUMN `preparation_time` INT NULL AFTER `delivery_time`,
ADD CONSTRAINT `fk_products_cover` FOREIGN KEY (`cover_image_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL;

-- 2. Add subtitle to product_translations
ALTER TABLE `product_translations`
ADD COLUMN `subtitle` VARCHAR(255) NULL AFTER `name`;

-- 3. Seed Product Management Permissions
INSERT INTO `permissions` (id, name, description) VALUES
(50, 'view_products', 'Ürünleri listeleme yetkisi'),
(51, 'create_products', 'Ürün ekleme yetkisi'),
(52, 'edit_products', 'Ürün düzenleme yetkisi'),
(53, 'delete_products', 'Ürün silme yetkisi'),
(54, 'restore_products', 'Silinen ürünleri geri yükleme yetkisi'),
(55, 'duplicate_products', 'Ürün kopyalama yetkisi'),
(56, 'export_products', 'Ürünleri dışa aktarma yetkisi'),
(57, 'import_products', 'Ürünleri içeri aktarma yetkisi'),
(58, 'bulk_products', 'Ürünlerde toplu işlem yetkisi')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Map all permissions to role ID 1 (super_admin) and ID 2 (administrator)
INSERT IGNORE INTO `role_permissions` (role_id, permission_id) VALUES
(1, 50), (1, 51), (1, 52), (1, 53), (1, 54), (1, 55), (1, 56), (1, 57), (1, 58),
(2, 50), (2, 51), (2, 52), (2, 53), (2, 54), (2, 55), (2, 56), (2, 57), (2, 58);

SET FOREIGN_KEY_CHECKS = 1;
