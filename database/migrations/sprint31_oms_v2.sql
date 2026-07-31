-- Sprint 31: Enterprise OMS V2 - Database Upgrades
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. Multi-Warehouse Support
CREATE TABLE IF NOT EXISTS `warehouses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL COMMENT 'Depo adı',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Depo kodu',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Aktif/Pasif',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Warehouses
INSERT IGNORE INTO `warehouses` (`id`, `name`, `code`, `is_active`) VALUES
    (1, 'Merkez Depo', 'MERKEZ', 1),
    (2, 'Ege Dağıtım Merkezi', 'EGE_DIST', 1),
    (3, 'Avrupa Lojistik Deposu', 'EURO_LOG', 1);

-- Modify inventories to support warehouses
ALTER TABLE `inventories` 
    ADD COLUMN IF NOT EXISTS `warehouse_id` BIGINT(20) UNSIGNED NULL AFTER `variant_id`;

-- Update existing inventories to point to Merkez Depo (ID 1)
UPDATE `inventories` SET `warehouse_id` = 1 WHERE `warehouse_id` IS NULL;

-- Add Foreign Key constraint for warehouse_id if not exists
SET @exist_fk = (SELECT COUNT(*) FROM information_schema.table_constraints 
                 WHERE constraint_name='fk_inventories_warehouse' AND table_schema=DATABASE());
SET @sql_stmt = IF(@exist_fk > 0, 'SELECT 1', 
    'ALTER TABLE `inventories` ADD CONSTRAINT `fk_inventories_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL');
PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. SLA & Delay Tracking
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `sla_due_at` DATETIME NULL COMMENT 'SLA bitiş tarihi' AFTER `grand_total`,
    ADD COLUMN IF NOT EXISTS `is_delayed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'SLA gecikti mi' AFTER `sla_due_at`;

-- 3. Order Merging
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `merged_into_id` BIGINT(20) UNSIGNED NULL COMMENT 'Birleştirildiği Sipariş ID' AFTER `is_delayed`;

SET @exist_fk_merge = (SELECT COUNT(*) FROM information_schema.table_constraints 
                       WHERE constraint_name='fk_orders_merged_into' AND table_schema=DATABASE());
SET @sql_stmt_merge = IF(@exist_fk_merge > 0, 'SELECT 1', 
    'ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_merged_into` FOREIGN KEY (`merged_into_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL');
PREPARE stmt2 FROM @sql_stmt_merge;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 4. Partial Shipments
CREATE TABLE IF NOT EXISTS `order_shipment_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `shipment_id` BIGINT(20) UNSIGNED NOT NULL,
    `order_item_id` BIGINT(20) UNSIGNED NOT NULL,
    `quantity_shipped` INT(11) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_shipment_items_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_shipment_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
