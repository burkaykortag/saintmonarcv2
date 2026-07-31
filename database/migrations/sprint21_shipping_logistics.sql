-- Sprint 21: Enterprise Lojistik & Kargo Yönetimi (Logistics & Shipping Enterprise)
-- UTF-8 MB4 destekli, Türkçe karakter uyumlu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. shipping_companies (Kargo Firmaları)
CREATE TABLE IF NOT EXISTS `shipping_companies` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL COMMENT 'Yurtiçi, MNG, PTT vb.',
    `code` VARCHAR(50) NOT NULL,
    `tax_number` VARCHAR(50) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_comp_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. shipping_services (Kargo Servisleri/Hizmet Türleri)
CREATE TABLE IF NOT EXISTS `shipping_services` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'Standart, Ekspres, Aynı Gün vb.',
    `code` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ship_serv_comp` (`company_id`),
    CONSTRAINT `fk_ship_serv_comp` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. shipping_regions (Coğrafi Bölgeler)
CREATE TABLE IF NOT EXISTS `shipping_regions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. shipping_zones (Kargo Teslimat Bölgeleri/Zone'lar)
CREATE TABLE IF NOT EXISTS `shipping_zones` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `country_code` VARCHAR(2) NOT NULL DEFAULT 'TR',
    `city_name` VARCHAR(100) NULL,
    `district_name` VARCHAR(100) NULL,
    `zip_code` VARCHAR(20) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. shipping_zone_prices (Bölge & Desi Fiyat Matrisi)
CREATE TABLE IF NOT EXISTS `shipping_zone_prices` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `zone_id` BIGINT(20) UNSIGNED NOT NULL,
    `service_id` BIGINT(20) UNSIGNED NOT NULL,
    `min_desi` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `max_desi` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_zone_price_zone` (`zone_id`),
    KEY `idx_zone_price_serv` (`service_id`),
    CONSTRAINT `fk_zone_price_zone` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_zone_price_serv` FOREIGN KEY (`service_id`) REFERENCES `shipping_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. shipping_rules (Kargo Hesaplama Kuralları)
CREATE TABLE IF NOT EXISTS `shipping_rules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `min_order_amount` DECIMAL(15,4) NULL,
    `max_order_amount` DECIMAL(15,4) NULL,
    `min_weight` DECIMAL(10,2) NULL,
    `max_weight` DECIMAL(10,2) NULL,
    `min_desi` DECIMAL(10,2) NULL,
    `max_desi` DECIMAL(10,2) NULL,
    `free_shipping_limit` DECIMAL(15,4) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. shipping_methods (Kargo Teslimat Yöntemleri)
CREATE TABLE IF NOT EXISTS `shipping_methods` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_method_serv` FOREIGN KEY (`service_id`) REFERENCES `shipping_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. shipping_packages (Kargo Paketleri / Gönderiler)
CREATE TABLE IF NOT EXISTS `shipping_packages` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `service_id` BIGINT(20) UNSIGNED NOT NULL,
    `tracking_number` VARCHAR(100) NULL,
    `desi` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `weight` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `package_count` INT NOT NULL DEFAULT 1,
    `shipping_cost` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `qr_code` TEXT NULL,
    `barcode` VARCHAR(100) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ship_pkg_order` (`order_id`),
    CONSTRAINT `fk_ship_pkg_serv` FOREIGN KEY (`service_id`) REFERENCES `shipping_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. shipping_package_items (Paket İçeriği)
CREATE TABLE IF NOT EXISTS `shipping_package_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_pkg_item_pkg` (`package_id`),
    CONSTRAINT `fk_pkg_item_pkg` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. shipping_labels (Kargo Etiketleri)
CREATE TABLE IF NOT EXISTS `shipping_labels` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `label_path` VARCHAR(255) NOT NULL,
    `format` VARCHAR(20) NOT NULL DEFAULT 'pdf',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_label_pkg` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. shipping_pickups (Kargo Kurye Çağırma/Pickup Kayıtları)
CREATE TABLE IF NOT EXISTS `shipping_pickups` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT(20) UNSIGNED NOT NULL,
    `pickup_date` DATE NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'requested',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_pickup_comp` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. shipping_tracking (Kargo Takip Durumları)
CREATE TABLE IF NOT EXISTS `shipping_tracking` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `tracking_number` VARCHAR(100) NOT NULL,
    `latest_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `estimated_delivery` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_track_num` (`tracking_number`),
    CONSTRAINT `fk_ship_track_pkg` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. shipping_tracking_events (Kargo Takip Hareketleri)
CREATE TABLE IF NOT EXISTS `shipping_tracking_events` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tracking_id` BIGINT(20) UNSIGNED NOT NULL,
    `status` VARCHAR(50) NOT NULL COMMENT 'shipped, in_transit, at_branch, out_for_delivery, delivered, returned',
    `location` VARCHAR(150) NULL,
    `description` VARCHAR(255) NULL,
    `event_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_track_evt_track` (`tracking_id`),
    CONSTRAINT `fk_track_evt_track` FOREIGN KEY (`tracking_id`) REFERENCES `shipping_tracking` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. shipping_statuses (Sevkiyat Statüleri Tanımları)
CREATE TABLE IF NOT EXISTS `shipping_statuses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_status_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. shipping_returns (Kargo İade Yönetimi)
CREATE TABLE IF NOT EXISTS `shipping_returns` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `return_number` VARCHAR(100) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'requested' COMMENT 'requested, approved, rejected, warehouse_in, completed',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_ret_num` (`return_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. shipping_return_items (İade Kalemleri)
CREATE TABLE IF NOT EXISTS `shipping_return_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `return_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_ret_item_ret` (`return_id`),
    CONSTRAINT `fk_ret_item_ret` FOREIGN KEY (`return_id`) REFERENCES `shipping_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. shipping_claims (Kargo Tazmin/Kayıp Hasar Talepleri)
CREATE TABLE IF NOT EXISTS `shipping_claims` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `claim_type` VARCHAR(50) NOT NULL COMMENT 'damaged, lost',
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_claim_pkg` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. shipping_insurances (Kargo Sigorta Kayıtları)
CREATE TABLE IF NOT EXISTS `shipping_insurances` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `insurance_value` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `insurance_fee` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_ins_pkg` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. shipping_documents (Sevkiyat Evrakları)
CREATE TABLE IF NOT EXISTS `shipping_documents` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_doc_pkg` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. shipping_notifications (Sevkiyat Bildirimleri)
CREATE TABLE IF NOT EXISTS `shipping_notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'email, sms, push',
    `message` TEXT NOT NULL,
    `sent_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_notif_pkg` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. shipping_api_logs (API İstek Logları)
CREATE TABLE IF NOT EXISTS `shipping_api_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT(20) UNSIGNED NOT NULL,
    `request_type` VARCHAR(100) NOT NULL,
    `payload` LONGTEXT NULL,
    `response` LONGTEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_api_log_comp` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. shipping_statistics (Lojistik İstatistikleri)
CREATE TABLE IF NOT EXISTS `shipping_statistics` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `metric_name` VARCHAR(100) NOT NULL,
    `metric_value` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `recorded_date` DATE NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_stat_metric` (`metric_name`, `recorded_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. shipping_reports (Lojistik Raporları)
CREATE TABLE IF NOT EXISTS `shipping_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. shipping_cache (Lojistik Önbellek)
CREATE TABLE IF NOT EXISTS `shipping_cache` (
    `cache_key` VARCHAR(191) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. shipping_translations (Dil Çevirileri)
CREATE TABLE IF NOT EXISTS `shipping_translations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `shipping_method_id` BIGINT(20) UNSIGNED NOT NULL,
    `language_id` BIGINT(20) UNSIGNED NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_trans` (`shipping_method_id`, `language_id`),
    CONSTRAINT `fk_ship_trans_method` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. shipping_integrations (Kargo API Entegrasyon Tanımları)
CREATE TABLE IF NOT EXISTS `shipping_integrations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT(20) UNSIGNED NOT NULL,
    `api_url` VARCHAR(255) NOT NULL,
    `username` VARCHAR(100) NULL,
    `password` VARCHAR(100) NULL,
    `api_key` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_int_comp` (`company_id`),
    CONSTRAINT `fk_ship_int_comp` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RBAC İzinleri
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_shipping', 'Sevkiyat Panelini Görüntüle'),
('manage_shipping', 'Sevkiyat ve Kargo Yönetimi'),
('manage_shipping_rules', 'Kargo Ücret ve Puan Kurallarını Yönet'),
('manage_shipping_companies', 'Kargo Firmalarını Yönet'),
('manage_returns', 'Kargo İadelerini Yönet'),
('manage_labels', 'Kargo Etiketlerini Yönet'),
('shipping_reports', 'Lojistik Raporlarını Görüntüle'),
('shipping_statistics', 'Lojistik İstatistiklerini Görüntüle'),
('shipping_integrations', 'Kargo Entegrasyonlarını Yönet');

-- Süper admin ve admin'e lojistik izinlerinin atanması
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator')
  AND p.name IN (
    'view_shipping', 'manage_shipping', 'manage_shipping_rules', 'manage_shipping_companies',
    'manage_returns', 'manage_labels', 'shipping_reports', 'shipping_statistics', 'shipping_integrations'
  );

SET FOREIGN_KEY_CHECKS = 1;
