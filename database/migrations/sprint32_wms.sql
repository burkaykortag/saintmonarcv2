-- Sprint 32: Enterprise WMS - Database Upgrades
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. Alter warehouses table
ALTER TABLE `warehouses`
    ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `is_active`,
    ADD COLUMN IF NOT EXISTS `region` VARCHAR(100) NULL AFTER `address`,
    ADD COLUMN IF NOT EXISTS `manager_name` VARCHAR(100) NULL AFTER `region`,
    ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL AFTER `manager_name`,
    ADD COLUMN IF NOT EXISTS `is_default` TINYINT(1) NOT NULL DEFAULT 0 AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `max_capacity` INT(11) NOT NULL DEFAULT 10000 AFTER `is_default`,
    ADD COLUMN IF NOT EXISTS `used_capacity` INT(11) NOT NULL DEFAULT 0 AFTER `max_capacity`;

-- Update default warehouse details
UPDATE `warehouses` SET 
    `address` = 'Merkez Organize Sanayi Bölgesi, 4. Cadde No: 12, İstanbul', 
    `region` = 'Marmara', 
    `manager_name` = 'Ahmet Demir', 
    `phone` = '+90 212 555 12 34', 
    `is_default` = 1,
    `max_capacity` = 25000,
    `used_capacity` = 8400
WHERE `id` = 1;

UPDATE `warehouses` SET 
    `address` = 'Ege Serbest Bölgesi, B Blok No: 5, İzmir', 
    `region` = 'Ege', 
    `manager_name` = 'Mustafa Kaya', 
    `phone` = '+90 232 444 56 78', 
    `max_capacity` = 15000,
    `used_capacity` = 4200
WHERE `id` = 2;

UPDATE `warehouses` SET 
    `address` = 'Frankfurt Logistics Park, building C, Frankfurt', 
    `region` = 'Avrupa', 
    `manager_name` = 'Hans Müller', 
    `phone` = '+49 69 1234567', 
    `max_capacity` = 10000,
    `used_capacity` = 1500
WHERE `id` = 3;

-- 2. Update inventories unique key constraint
-- Add new unique composite index covering warehouse_id first
SET @exist_new_idx = (SELECT COUNT(*) FROM information_schema.statistics 
                      WHERE INDEX_NAME='unique_product_variant_warehouse' AND TABLE_SCHEMA=DATABASE());
SET @sql_stmt_new = IF(@exist_new_idx > 0, 'SELECT 1', 
    'ALTER TABLE `inventories` ADD UNIQUE KEY `unique_product_variant_warehouse` (`product_id`, `variant_id`, `warehouse_id`)');
PREPARE stmt_new FROM @sql_stmt_new;
EXECUTE stmt_new;
DEALLOCATE PREPARE stmt_new;

-- Now drop the old unique index
SET @exist_idx = (SELECT COUNT(*) FROM information_schema.statistics 
                  WHERE INDEX_NAME='unique_product_inventory' AND TABLE_SCHEMA=DATABASE());
SET @sql_stmt = IF(@exist_idx > 0, 'ALTER TABLE `inventories` DROP INDEX `unique_product_inventory`', 'SELECT 1');
PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Create warehouse_locations table
CREATE TABLE IF NOT EXISTS `warehouse_locations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `warehouse_id` BIGINT(20) UNSIGNED NOT NULL,
    `location_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Konum Kodu örn: A-03-04-B',
    `aisle` VARCHAR(10) NOT NULL COMMENT 'Koridor',
    `rack` VARCHAR(10) NOT NULL COMMENT 'Raf',
    `shelf` VARCHAR(10) NOT NULL COMMENT 'Kat',
    `bin` VARCHAR(10) NOT NULL COMMENT 'Göz',
    `max_capacity` INT(11) NOT NULL DEFAULT 100 COMMENT 'Maks adet kapasitesi',
    `current_capacity` INT(11) NOT NULL DEFAULT 0 COMMENT 'Kullanılan adet',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `warehouse_id` (`warehouse_id`),
    CONSTRAINT `fk_locations_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default locations
INSERT IGNORE INTO `warehouse_locations` (`warehouse_id`, `location_code`, `aisle`, `rack`, `shelf`, `bin`, `max_capacity`, `current_capacity`) VALUES
    (1, 'A-01-01-A', 'A', '01', '01', 'A', 200, 150),
    (1, 'A-01-01-B', 'A', '01', '01', 'B', 200, 80),
    (1, 'B-02-03-C', 'B', '02', '03', 'C', 150, 145),
    (1, 'C-03-04-B', 'C', '03', '04', 'B', 100, 10),
    (2, 'A-01-01-A', 'A', '01', '01', 'A', 200, 20);

-- 4. Create inventory_locations table
CREATE TABLE IF NOT EXISTS `inventory_locations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `inventory_id` BIGINT(20) UNSIGNED NOT NULL,
    `location_id` BIGINT(20) UNSIGNED NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_inv_location` (`inventory_id`, `location_id`),
    CONSTRAINT `fk_inv_loc_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_loc_location` FOREIGN KEY (`location_id`) REFERENCES `warehouse_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create warehouse_transfers table
CREATE TABLE IF NOT EXISTS `warehouse_transfers` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_warehouse_id` BIGINT(20) UNSIGNED NOT NULL,
    `to_warehouse_id` BIGINT(20) UNSIGNED NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, shipped, delivered, completed',
    `created_by_admin` BIGINT(20) UNSIGNED NULL,
    `approved_by_admin` BIGINT(20) UNSIGNED NULL,
    `shipped_at` DATETIME NULL,
    `received_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_transfers_from` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfers_to` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfers_creator` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_transfers_approver` FOREIGN KEY (`approved_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create warehouse_transfer_items table
CREATE TABLE IF NOT EXISTS `warehouse_transfer_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `transfer_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `variant_id` BIGINT(20) UNSIGNED NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_transfer_items_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `warehouse_transfers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create inventory_counts table
CREATE TABLE IF NOT EXISTS `inventory_counts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `warehouse_id` BIGINT(20) UNSIGNED NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft' COMMENT 'draft, in_progress, completed',
    `type` VARCHAR(30) NOT NULL DEFAULT 'full' COMMENT 'full, partial, cycle',
    `created_by_admin` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_counts_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_counts_creator` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inventory_count_items table
CREATE TABLE IF NOT EXISTS `inventory_count_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `count_id` BIGINT(20) UNSIGNED NOT NULL,
    `inventory_id` BIGINT(20) UNSIGNED NOT NULL,
    `expected_quantity` INT(11) NOT NULL DEFAULT 0,
    `actual_quantity` INT(11) NOT NULL DEFAULT 0,
    `difference_quantity` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_count_items_count` FOREIGN KEY (`count_id`) REFERENCES `inventory_counts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_count_items_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Seed WMS RBAC Permissions
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
    ('view_wms', 'Depo Yönetimini Görüntüle'),
    ('manage_locations', 'Depo Lokasyonlarını Yönet'),
    ('manage_transfers', 'Stok Transferlerini Yönet'),
    ('manage_counts', 'Stok Sayımlarını Yönet'),
    ('wms_analytics', 'Depo Analitik Raporları');

-- Seed WMS Roles
INSERT IGNORE INTO `roles` (`name`, `description`, `is_active`) VALUES
    ('warehouse_manager', 'Depo Müdürü', 1),
    ('warehouse_operator', 'Depo Operatörü', 1),
    ('picking_operator', 'Toplama Görevlisi', 1),
    ('packing_operator', 'Paketleme Görevlisi', 1),
    ('inventory_manager', 'Envanter Yöneticisi', 1);

-- Map Permissions to Warehouse Manager & Super Admin
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'warehouse_manager', 'inventory_manager') 
  AND p.name IN ('view_wms', 'manage_locations', 'manage_transfers', 'manage_counts', 'wms_analytics');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p 
WHERE r.name IN ('warehouse_operator', 'picking_operator', 'packing_operator')
  AND p.name IN ('view_wms', 'manage_locations');
