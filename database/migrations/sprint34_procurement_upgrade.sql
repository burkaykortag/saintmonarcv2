-- Sprint 34: Enterprise Procurement & Supplier Management - Database Schema Upgrades
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. Alter Suppliers Table to add new details
ALTER TABLE `suppliers`
  ADD COLUMN IF NOT EXISTS `district` VARCHAR(100) NULL AFTER `city`,
  ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `district`,
  ADD COLUMN IF NOT EXISTS `zip_code` VARCHAR(20) NULL AFTER `address`,
  ADD COLUMN IF NOT EXISTS `iban` VARCHAR(50) NULL AFTER `zip_code`,
  ADD COLUMN IF NOT EXISTS `notes` TEXT NULL AFTER `iban`,
  ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'passive', 'blacklist', 'pending') NOT NULL DEFAULT 'active' AFTER `notes`;

-- 2. Create Supplier Product mappings table
CREATE TABLE IF NOT EXISTS `supplier_products` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Tedarikçi ID',
    `product_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Ürün ID',
    `variant_id` BIGINT(20) UNSIGNED NULL COMMENT 'Varyant ID',
    `purchase_price` DECIMAL(15,4) NOT NULL COMMENT 'Tedarikçi Alış Fiyatı',
    `min_order_qty` INT(11) NOT NULL DEFAULT 1 COMMENT 'Minimum Sipariş Miktarı',
    `supplier_sku` VARCHAR(100) NULL COMMENT 'Tedarikçi SKU',
    `lead_time_days` INT(11) NOT NULL DEFAULT 0 COMMENT 'Tedarik Süresi (Gün)',
    `priority` INT(11) NOT NULL DEFAULT 1 COMMENT 'Öncelik (1 = Birincil)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_supplier_product_variant` (`supplier_id`, `product_id`, `variant_id`),
    CONSTRAINT `fk_supplier_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_supplier_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_supplier_products_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create Supplier Price History table
CREATE TABLE IF NOT EXISTS `supplier_price_history` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `variant_id` BIGINT(20) UNSIGNED NULL,
    `price` DECIMAL(15,4) NOT NULL,
    `change_date` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_price_history_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_price_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Register new permissions
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
    ('view_procurement', 'Satın Almayı Görüntüle'),
    ('manage_procurement', 'Satın Almayı Yönet'),
    ('create_purchase_order', 'Satın Alma Siparişi Oluştur'),
    ('edit_purchase_order', 'Satın Alma Siparişi Düzenle'),
    ('approve_purchase_order', 'Satın Alma Siparişi Onayla'),
    ('approve_purchase_orders', 'Siparişleri Onayla (PO)'),
    ('cancel_purchase_order', 'Satın Alma Siparişi İptal Et'),
    ('manage_suppliers', 'Tedarikçileri Yönet'),
    ('view_supplier_finance', 'Tedarikçi Finansal Bilgilerini Gör'),
    ('view_procurement_analytics', 'Satın Alma Analitiğini Gör'),
    ('manage_supplier_contracts', 'Tedarikçi Sözleşmelerini Yönet'),
    ('manage_rfq', 'Teklif İsteklerini Yönet (RFQ)'),
    ('receive_goods', 'Mal Kabul Gerçekleştir');

-- Link role permissions for super_admin & admin
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'admin')
  AND p.name IN (
    'view_procurement',
    'manage_procurement',
    'create_purchase_order',
    'edit_purchase_order',
    'approve_purchase_order',
    'approve_purchase_orders',
    'cancel_purchase_order',
    'manage_suppliers',
    'view_supplier_finance',
    'view_procurement_analytics',
    'manage_supplier_contracts',
    'manage_rfq',
    'receive_goods'
  );
