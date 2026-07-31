-- Sprint 23: Enterprise Marketplace & Vendor Management System Migration
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. vendors table
CREATE TABLE IF NOT EXISTS `vendors` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `logo` VARCHAR(255) NULL,
    `banner` VARCHAR(255) NULL,
    `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NULL,
    `status` ENUM('pending', 'active', 'suspended', 'rejected') NOT NULL DEFAULT 'pending',
    `commission_type` ENUM('flat', 'percentage', 'dynamic') NOT NULL DEFAULT 'percentage',
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
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

-- 2. vendor_users table
CREATE TABLE IF NOT EXISTS `vendor_users` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `role` VARCHAR(50) NOT NULL DEFAULT 'owner',
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_users_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. vendor_addresses table
CREATE TABLE IF NOT EXISTS `vendor_addresses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` ENUM('billing', 'shipping') NOT NULL DEFAULT 'billing',
    `country` VARCHAR(100) NOT NULL DEFAULT 'Türkiye',
    `city` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) NOT NULL,
    `address` TEXT NOT NULL,
    `zip_code` VARCHAR(20) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_addresses_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. vendor_contacts table
CREATE TABLE IF NOT EXISTS `vendor_contacts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `title` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_contacts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. vendor_documents table
CREATE TABLE IF NOT EXISTS `vendor_documents` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_documents_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. vendor_bank_accounts table
CREATE TABLE IF NOT EXISTS `vendor_bank_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `bank_name` VARCHAR(150) NOT NULL,
    `account_holder` VARCHAR(150) NOT NULL,
    `iban` VARCHAR(50) NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_bank_accounts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. vendor_products table
CREATE TABLE IF NOT EXISTS `vendor_products` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. vendor_orders table
CREATE TABLE IF NOT EXISTS `vendor_orders` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `item_price` DECIMAL(12,2) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `commission_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payout_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. vendor_commissions table
CREATE TABLE IF NOT EXISTS `vendor_commissions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `rate` DECIMAL(5,2) NOT NULL,
    `calculated_amount` DECIMAL(12,2) NOT NULL,
    `status` ENUM('pending', 'payout', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_commissions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. vendor_payments table
CREATE TABLE IF NOT EXISTS `vendor_payments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `bank_account_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_date` DATE NULL,
    `transaction_reference` VARCHAR(150) NULL,
    `receipt_pdf` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'processing', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_payments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vendor_payments_bank` FOREIGN KEY (`bank_account_id`) REFERENCES `vendor_bank_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. vendor_wallet table
CREATE TABLE IF NOT EXISTS `vendor_wallet` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `pending_payout` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `last_payout_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_wallet_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. vendor_wallet_transactions table
CREATE TABLE IF NOT EXISTS `vendor_wallet_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `reference_type` ENUM('order', 'payment', 'refund', 'penalty') NOT NULL,
    `reference_id` BIGINT(20) UNSIGNED NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_wallet_transactions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. vendor_shipping_settings table
CREATE TABLE IF NOT EXISTS `vendor_shipping_settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `carrier_id` INT(11) NOT NULL,
    `api_username` VARCHAR(150) NULL,
    `api_password` VARCHAR(150) NULL,
    `api_key` VARCHAR(255) NULL,
    `account_number` VARCHAR(100) NULL,
    `free_shipping_limit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `delivery_time` VARCHAR(100) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_shipping_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. vendor_returns table
CREATE TABLE IF NOT EXISTS `vendor_returns` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `order_item_id` BIGINT(20) UNSIGNED NOT NULL,
    `reason` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    `refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `penalty_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_returns_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. vendor_statistics table
CREATE TABLE IF NOT EXISTS `vendor_statistics` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
    `total_sales` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_orders` INT(11) NOT NULL DEFAULT 0,
    `total_earnings` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_commission` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `active_products` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_statistics_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. vendor_activity_logs table
CREATE TABLE IF NOT EXISTS `vendor_activity_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `user_id` BIGINT(20) UNSIGNED NULL,
    `action` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(50) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_activity_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. vendor_notifications table
CREATE TABLE IF NOT EXISTS `vendor_notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_notifications_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. vendor_contracts table
CREATE TABLE IF NOT EXISTS `vendor_contracts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `contract_file` VARCHAR(255) NOT NULL,
    `signed_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `status` ENUM('active', 'expired', 'terminated') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_contracts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. vendor_api_keys table
CREATE TABLE IF NOT EXISTS `vendor_api_keys` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `api_key` VARCHAR(100) NOT NULL UNIQUE,
    `api_secret` VARCHAR(100) NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_api_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. vendor_settings table
CREATE TABLE IF NOT EXISTS `vendor_settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_vendor_settings_key` (`vendor_id`, `key`),
    CONSTRAINT `fk_vendor_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. vendor_reviews table
CREATE TABLE IF NOT EXISTS `vendor_reviews` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `rating` INT(11) NOT NULL,
    `comment` TEXT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_reviews_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. vendor_messages table
CREATE TABLE IF NOT EXISTS `vendor_messages` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `sender_type` ENUM('admin', 'vendor', 'customer') NOT NULL,
    `sender_id` BIGINT(20) UNSIGNED NOT NULL,
    `recipient_type` ENUM('admin', 'vendor', 'customer') NOT NULL,
    `recipient_id` BIGINT(20) UNSIGNED NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vendor_messages_sender` (`sender_type`, `sender_id`),
    KEY `idx_vendor_messages_recipient` (`recipient_type`, `recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. vendor_files table
CREATE TABLE IF NOT EXISTS `vendor_files` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` BIGINT(20) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_files_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. vendor_logs table
CREATE TABLE IF NOT EXISTS `vendor_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `level` VARCHAR(50) NOT NULL DEFAULT 'info',
    `message` TEXT NOT NULL,
    `context` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vendor_logs_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Seed Permissions
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
    ('view_vendors', 'Satıcıları Listele'),
    ('create_vendor', 'Yeni Satıcı Ekle'),
    ('edit_vendor', 'Satıcı Bilgilerini Düzenle'),
    ('delete_vendor', 'Satıcı Sil'),
    ('vendor_reports', 'Satıcı Analitik Raporları'),
    ('vendor_wallet', 'Satıcı Cüzdan Kontrolü'),
    ('vendor_orders', 'Satıcı Sipariş Yönetimi'),
    ('vendor_products', 'Satıcı Ürünleri'),
    ('vendor_payments', 'Satıcı Ödemeleri (Hak Ediş)'),
    ('vendor_statistics', 'Satıcı Genel Metrikleri');

-- Give permissions to super_admin and admin role
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'admin') AND p.name LIKE '%vendor%';
