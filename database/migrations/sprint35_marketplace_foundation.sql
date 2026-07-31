-- Sprint 35: Multi-Vendor Marketplace Foundation & VEYRA Platform Upgrade
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. Vendors table update/ensure
CREATE TABLE IF NOT EXISTS `vendors` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `logo` VARCHAR(255) NULL,
    `banner` VARCHAR(255) NULL,
    `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NULL,
    `status` ENUM('pending', 'under_review', 'active', 'suspended', 'rejected', 'blacklisted') NOT NULL DEFAULT 'pending',
    `commission_type` ENUM('flat', 'percentage', 'dynamic') NOT NULL DEFAULT 'percentage',
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    `tax_number` VARCHAR(50) NULL,
    `tax_office` VARCHAR(100) NULL,
    `company_title` VARCHAR(200) NULL,
    `iban` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_vendors_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns to vendors table if table already existed from Sprint 23
SET @exist_title := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vendors' AND COLUMN_NAME = 'company_title');
SET @sql_title := IF(@exist_title = 0, 'ALTER TABLE `vendors` ADD COLUMN `company_title` VARCHAR(200) NULL, ADD COLUMN `tax_number` VARCHAR(50) NULL, ADD COLUMN `tax_office` VARCHAR(100) NULL, ADD COLUMN `iban` VARCHAR(50) NULL', 'SELECT 1');
PREPARE stmt_title FROM @sql_title; EXECUTE stmt_title; DEALLOCATE PREPARE stmt_title;

-- Seed SaintMonarc as Vendor ID 1
INSERT IGNORE INTO `vendors` (`id`, `name`, `slug`, `email`, `status`, `commission_rate`, `company_title`) 
VALUES (1, 'SaintMonarc Official Store', 'saintmonarc', 'official@saintmonarc.com', 'active', 0.00, 'SaintMonarc A.Ş.');

-- 2. Add vendor_id and approval_status to products if not present
SET @exist_vendor_id := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'vendor_id');
SET @sql1 := IF(@exist_vendor_id = 0, 'ALTER TABLE `products` ADD COLUMN `vendor_id` BIGINT(20) UNSIGNED NULL DEFAULT 1 AFTER `brand_id`, ADD INDEX `idx_products_vendor` (`vendor_id`)', 'SELECT 1');
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @exist_approval := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'approval_status');
SET @sql2 := IF(@exist_approval = 0, 'ALTER TABLE `products` ADD COLUMN `approval_status` ENUM(\'draft\', \'pending_review\', \'approved\', \'published\', \'rejected\', \'suspended\') NOT NULL DEFAULT \'approved\' AFTER `status`', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Update existing products to vendor_id = 1 if NULL
UPDATE `products` SET `vendor_id` = 1 WHERE `vendor_id` IS NULL;

-- 3. Vendor Users Table
CREATE TABLE IF NOT EXISTS `vendor_users` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `role` VARCHAR(50) NOT NULL DEFAULT 'owner', -- 'owner', 'manager', 'staff'
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_users_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Vendor Orders Table (Split Orders per Vendor)
CREATE TABLE IF NOT EXISTS `vendor_orders` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `order_number` VARCHAR(50) NOT NULL,
    `item_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    `commission_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payout_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `tracking_number` VARCHAR(100) NULL,
    `cargo_company` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vendor_orders_vendor` (`vendor_id`),
    KEY `idx_vendor_orders_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns to vendor_orders if table existed from Sprint 23
SET @exist_onum := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vendor_orders' AND COLUMN_NAME = 'order_number');
SET @sql_onum := IF(@exist_onum = 0, 'ALTER TABLE `vendor_orders` ADD COLUMN `order_number` VARCHAR(50) NULL AFTER `order_id`, ADD COLUMN `tax_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`, ADD COLUMN `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00 AFTER `tax_total`, ADD COLUMN `tracking_number` VARCHAR(100) NULL AFTER `status`, ADD COLUMN `cargo_company` VARCHAR(100) NULL AFTER `tracking_number`', 'SELECT 1');
PREPARE stmt_onum FROM @sql_onum; EXECUTE stmt_onum; DEALLOCATE PREPARE stmt_onum;

-- 5. Vendor Wallet Table
CREATE TABLE IF NOT EXISTS `vendor_wallet` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `pending_payout` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_earned` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `last_payout_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_wallet_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed wallet for SaintMonarc
INSERT IGNORE INTO `vendor_wallet` (`vendor_id`, `balance`, `pending_payout`) VALUES (1, 0.00, 0.00);

-- 6. Vendor Wallet Transactions Table
CREATE TABLE IF NOT EXISTS `vendor_wallet_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `reference_type` ENUM('order', 'payout', 'refund', 'penalty', 'adjustment') NOT NULL,
    `reference_id` BIGINT(20) UNSIGNED NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wallet_tx_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Vendor Payouts Table
CREATE TABLE IF NOT EXISTS `vendor_payouts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `iban` VARCHAR(50) NOT NULL,
    `bank_name` VARCHAR(100) NULL,
    `account_holder` VARCHAR(150) NULL,
    `status` ENUM('pending', 'approved', 'processing', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
    `receipt_file` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vendor_payouts_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Vendor Onboarding Applications Table
CREATE TABLE IF NOT EXISTS `vendor_applications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_name` VARCHAR(150) NOT NULL,
    `contact_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `tax_number` VARCHAR(50) NULL,
    `tax_office` VARCHAR(100) NULL,
    `city` VARCHAR(100) NULL,
    `district` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `iban` VARCHAR(50) NULL,
    `category` VARCHAR(100) NULL,
    `status` ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vendor_apps_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. RBAC Permissions for Marketplace Platform & Vendors
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
    ('view_marketplace', 'Pazaryeri Genel Bakış'),
    ('manage_vendors', 'Satıcı Hesap Yönetimi'),
    ('approve_vendors', 'Satıcı Başvuru Onayları'),
    ('moderate_products', 'Pazaryeri Ürün Moderasyonu'),
    ('manage_commissions', 'Komisyon Oranları Yönetimi'),
    ('view_platform_finance', 'Pazaryeri Platform Finans Raporları'),
    ('vendor_panel_access', 'Satıcı Paneli Erişimi'),
    ('vendor_manage_products', 'Satıcı Kendi Ürünlerini Yönetme'),
    ('vendor_manage_orders', 'Satıcı Kendi Siparişlerini Yönetme'),
    ('vendor_view_finance', 'Satıcı Kendi Finans/Cüzdanını Görme');

INSERT IGNORE INTO `roles` (`name`, `description`) VALUES
    ('platform_admin', 'Pazaryeri Platform Yöneticisi'),
    ('vendor_admin', 'Satıcı Yöneticisi'),
    ('vendor_staff', 'Satıcı Personeli');

-- Assign permissions to platform_admin and super_admin
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'admin', 'platform_admin') 
  AND p.name IN ('view_marketplace', 'manage_vendors', 'approve_vendors', 'moderate_products', 'manage_commissions', 'view_platform_finance');
