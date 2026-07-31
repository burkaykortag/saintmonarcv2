-- SaintMonarc Sprint 16 - Enterprise Kampanya & İndirim Motoru Veritabanı Şeması
-- UTF-8 MB4 destekli, Türkçe karakter uyumlu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Promotions (Kampanyalar Ana Tablosu)
CREATE TABLE IF NOT EXISTS `promotions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL COMMENT 'percentage, fixed_cart, fixed_product, category_discount, brand_discount, x_get_y, buy_2_get_1, flash_sale, happy_hour, free_shipping, gift_product, birthday, new_member, vip, wholesale',
    `code` VARCHAR(100) NULL COMMENT 'Kampanya kodu (opsiyonel)',
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, active, passive, expired, scheduled',
    `priority` INT NOT NULL DEFAULT 0 COMMENT 'Öncelik sırası',
    `is_exclusive` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Başka kampanyalarla birleşebilir mi? (1: Birleşemez, 0: Birleşebilir)',
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_promotions_type` (`type`),
    KEY `idx_promotions_status` (`status`),
    KEY `idx_promotions_dates` (`start_date`, `end_date`),
    KEY `fk_promotions_created_by` (`created_by`),
    CONSTRAINT `fk_promotions_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Promotion Translations (Kampanya Dil Çevirileri)
CREATE TABLE IF NOT EXISTS `promotion_translations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `language_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1: TR, 2: EN vb.',
    `name` VARCHAR(255) NOT NULL COMMENT 'Kampanya Adı',
    `description` TEXT NULL COMMENT 'Kampanya Açıklaması',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_promotion_trans_lang` (`promotion_id`, `language_id`),
    CONSTRAINT `fk_promotion_trans_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Promotion Conditions (Kampanya Kuralları / Koşulları)
CREATE TABLE IF NOT EXISTS `promotion_conditions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `rule_type` VARCHAR(50) NOT NULL COMMENT 'min_cart, max_cart, min_items, device, customer_group, segment, city, country, time_range, day_of_week, currency, ip_filter',
    `operator` VARCHAR(20) NOT NULL DEFAULT '=' COMMENT 'operator: =, >=, <=, IN, NOT IN, vb.',
    `value` TEXT NOT NULL COMMENT 'Kural değerleri (JSON veya String)',
    `group_operator` VARCHAR(10) NOT NULL DEFAULT 'AND' COMMENT 'AND, OR',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_promotion_cond_promo` (`promotion_id`),
    CONSTRAINT `fk_promotion_cond_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Promotion Actions (Kampanya İndirim Aksiyonları)
CREATE TABLE IF NOT EXISTS `promotion_actions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'discount_percentage, discount_fixed, free_shipping, add_gift_product, add_gift_points, add_gift_coupon',
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'İndirim tutarı veya oranı',
    `target_type` VARCHAR(50) NOT NULL DEFAULT 'cart' COMMENT 'cart, product, category, brand',
    `target_ids` TEXT NULL COMMENT 'İndirim uygulanacak ürün/kategori/marka ID listesi',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_promotion_action_promo` (`promotion_id`),
    CONSTRAINT `fk_promotion_action_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Promotion Products (İlişkili Ürünler)
CREATE TABLE IF NOT EXISTS `promotion_products` (
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`promotion_id`, `product_id`),
    CONSTRAINT `fk_pp_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Promotion Categories (İlişkili Kategoriler)
CREATE TABLE IF NOT EXISTS `promotion_categories` (
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `category_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`promotion_id`, `category_id`),
    CONSTRAINT `fk_pc_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Promotion Brands (İlişkili Markalar)
CREATE TABLE IF NOT EXISTS `promotion_brands` (
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `brand_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`promotion_id`, `brand_id`),
    CONSTRAINT `fk_pb_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pb_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Promotion Customer Groups (İlişkili Müşteri Grupları)
CREATE TABLE IF NOT EXISTS `promotion_customer_groups` (
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `customer_group_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`promotion_id`, `customer_group_id`),
    CONSTRAINT `fk_pcg_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pcg_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Promotion Segments (İlişkili CRM Segmentleri)
CREATE TABLE IF NOT EXISTS `promotion_segments` (
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `segment_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`promotion_id`, `segment_id`),
    CONSTRAINT `fk_ps_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ps_segment` FOREIGN KEY (`segment_id`) REFERENCES `customer_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Promotion Coupons (Kuponlar)
CREATE TABLE IF NOT EXISTS `promotion_coupons` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `code` VARCHAR(50) NOT NULL COMMENT 'Kupon Kodu (örn: SAVE10)',
    `usage_type` VARCHAR(20) NOT NULL DEFAULT 'multiple' COMMENT 'single, multiple',
    `total_limit` INT NOT NULL DEFAULT 0 COMMENT 'Maksimum toplam kullanım (0: sınırsız)',
    `user_limit` INT NOT NULL DEFAULT 1 COMMENT 'Kullanıcı başı limit',
    `used_count` INT NOT NULL DEFAULT 0,
    `min_cart_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `max_discount_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Alabileceği maksimum indirim tutarı',
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_coupons_code` (`code`),
    CONSTRAINT `fk_coupons_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Promotion Coupon Usages (Kupon Kullanımları)
CREATE TABLE IF NOT EXISTS `promotion_coupon_usages` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `coupon_id` BIGINT(20) UNSIGNED NOT NULL,
    `user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'users tablosuna foreign key',
    `order_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'orders tablosuna foreign key',
    `discount_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_coupon_usages_coupon` (`coupon_id`),
    KEY `idx_coupon_usages_user` (`user_id`),
    CONSTRAINT `fk_usages_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `promotion_coupons` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_usages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Promotion Gifts (Hediye Eşyaları)
CREATE TABLE IF NOT EXISTS `promotion_gifts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `gift_type` VARCHAR(30) NOT NULL COMMENT 'product, coupon, points, free_shipping',
    `target_id` BIGINT(20) UNSIGNED NULL COMMENT 'Hediye ürün id veya kupon id',
    `quantity` INT NOT NULL DEFAULT 1,
    `points` INT NULL COMMENT 'Hediye puan miktarı',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_gifts_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Promotion Logs (Kampanya Log Defteri)
CREATE TABLE IF NOT EXISTS `promotion_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `user_id` BIGINT(20) UNSIGNED NULL,
    `order_id` BIGINT(20) UNSIGNED NULL,
    `discount_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_pl_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Promotion Schedules (Kampanya Takvim Tanımları)
CREATE TABLE IF NOT EXISTS `promotion_schedules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `day_of_week` TINYINT(1) NULL COMMENT '0: Pazar, 1: Pazartesi vb.',
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_sched_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Promotion Usage Limits (Kampanya Kullanım Sınırları)
CREATE TABLE IF NOT EXISTS `promotion_usage_limits` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `max_total_usage` INT NOT NULL DEFAULT 0 COMMENT 'Toplam maks kullanım',
    `max_user_usage` INT NOT NULL DEFAULT 1 COMMENT 'Kişi başı maks kullanım',
    `current_usage` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_limits_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Promotion Banner Relations (Kampanya Banner İlişkileri)
CREATE TABLE IF NOT EXISTS `promotion_banner_relations` (
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `banner_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`promotion_id`, `banner_id`),
    CONSTRAINT `fk_pbr_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Promotion Notifications (Kampanya Bildirim İlişkileri)
CREATE TABLE IF NOT EXISTS `promotion_notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `channel` VARCHAR(20) NOT NULL COMMENT 'sms, email, push',
    `message_template` TEXT NOT NULL,
    `sent_count` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_pnot_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Promotion Priority Rules (Kampanya Öncelik Kuralları)
CREATE TABLE IF NOT EXISTS `promotion_priority_rules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `priority` INT NOT NULL DEFAULT 0,
    `apply_mode` VARCHAR(30) NOT NULL DEFAULT 'highest_discount' COMMENT 'highest_discount, priority_order',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_prr_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Promotion Conflicts (Çakışma Önleme Tanımları)
CREATE TABLE IF NOT EXISTS `promotion_conflicts` (
    `promotion_id_1` BIGINT(20) UNSIGNED NOT NULL,
    `promotion_id_2` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`promotion_id_1`, `promotion_id_2`),
    CONSTRAINT `fk_conf_promo1` FOREIGN KEY (`promotion_id_1`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_conf_promo2` FOREIGN KEY (`promotion_id_2`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Promotion Statistics (İstatistikler)
CREATE TABLE IF NOT EXISTS `promotion_statistics` (
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `views` INT NOT NULL DEFAULT 0,
    `clicks` INT NOT NULL DEFAULT 0,
    `conversions` INT NOT NULL DEFAULT 0,
    `total_discount_given` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `total_revenue_generated` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `roi` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Yatırım Getirisi Oranı (%)',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`promotion_id`),
    CONSTRAINT `fk_stats_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Promotion History (Kampanya Geçmişi)
CREATE TABLE IF NOT EXISTS `promotion_history` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `promotion_id` BIGINT(20) UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'create, edit, status_change, run',
    `admin_id` BIGINT(20) UNSIGNED NULL,
    `payload` LONGTEXT NULL COMMENT 'Detaylar JSON',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_hist_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Promotion Preview Cache (Önizleme Simülatör Cache)
CREATE TABLE IF NOT EXISTS `promotion_preview_cache` (
    `cache_key` VARCHAR(191) NOT NULL,
    `payload` LONGTEXT NOT NULL COMMENT 'Hesaplama Sonuçları JSON',
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. RBAC İzinleri
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_promotions', 'Kampanyaları Görüntüle'),
('create_promotions', 'Kampanya Oluştur'),
('edit_promotions', 'Kampanya Düzenle'),
('delete_promotions', 'Kampanya Sil'),
('duplicate_promotions', 'Kampanya Kopyala'),
('promotion_reports', 'Kampanya Raporları'),
('promotion_preview', 'Kampanya Simülatörü ve Önizleme'),
('coupon_management', 'Kupon Yönetimi'),
('coupon_reports', 'Kupon Raporları'),
('flash_sale_management', 'Flaş Satış Yönetimi');

-- İzinlerin süper admin ve admin'e atanması
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator', 'marketing_manager')
  AND p.name IN (
    'view_promotions', 'create_promotions', 'edit_promotions', 'delete_promotions',
    'duplicate_promotions', 'promotion_reports', 'promotion_preview', 
    'coupon_management', 'coupon_reports', 'flash_sale_management'
  );

-- AI Recommendations (Sprint 17)
CREATE TABLE IF NOT EXISTS `ai_recommendations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL COMMENT 'product_campaign, category_discount, aging_stock, cross_sell_bundle, custom_campaign',
    `title` VARCHAR(255) NOT NULL COMMENT 'Öneri Başlığı',
    `description` TEXT NOT NULL COMMENT 'Öneri Açıklaması',
    `payload` LONGTEXT NULL COMMENT 'Öneriye ait JSON veri',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, applied, dismissed',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ai_recs_type` (`type`),
    KEY `idx_ai_recs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('ai_recommendations', 'Yapay Zeka Öneri Motorunu Yönet');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator', 'marketing_manager')
  AND p.name = 'ai_recommendations';

-- AI Recommendations End

-- Enterprise Finance & Accounting (Sprint 20)
CREATE TABLE IF NOT EXISTS `financial_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_fin_acc_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_fin_tx_acc` (`account_id`),
    CONSTRAINT `fk_fin_tx_acc` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `accounting_journals` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_acc_journal_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `accounting_entries` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `journal_id` BIGINT(20) UNSIGNED NOT NULL,
    `entry_number` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `entry_date` DATE NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_acc_entry_journal` (`journal_id`),
    CONSTRAINT `fk_acc_entry_journal` FOREIGN KEY (`journal_id`) REFERENCES `accounting_journals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `account_code` VARCHAR(50) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_cust_acc_cust` (`customer_id`),
    KEY `idx_cust_acc_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `supplier_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_name` VARCHAR(150) NOT NULL,
    `tax_number` VARCHAR(50) NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_name` VARCHAR(100) NOT NULL,
    `branch_name` VARCHAR(100) NULL,
    `account_number` VARCHAR(100) NOT NULL,
    `iban` VARCHAR(100) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_bank_iban` (`iban`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_account_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_bank_tx_acc` (`bank_account_id`),
    CONSTRAINT `fk_bank_tx_acc` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cash_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cash_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `cash_account_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cash_tx_acc` (`cash_account_id`),
    CONSTRAINT `fk_cash_tx_acc` FOREIGN KEY (`cash_account_id`) REFERENCES `cash_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `expense_date` DATE NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_expense_cat` (`category_id`),
    CONSTRAINT `fk_expense_cat` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `revenue_categories` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `revenues` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `revenue_date` DATE NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_revenue_cat` (`category_id`),
    CONSTRAINT `fk_revenue_cat` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_pay_method_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_method_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `status` VARCHAR(50) NOT NULL DEFAULT 'completed',
    `reference_code` VARCHAR(100) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pay_tx_method` (`payment_method_id`),
    CONSTRAINT `fk_pay_tx_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `installments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_transaction_id` BIGINT(20) UNSIGNED NOT NULL,
    `installment_number` INT NOT NULL,
    `due_date` DATE NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_inst_tx` (`payment_transaction_id`),
    CONSTRAINT `fk_inst_tx` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tax_rates` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_tax_rate_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tax_rules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tax_rate_id` BIGINT(20) UNSIGNED NOT NULL,
    `country_code` VARCHAR(2) NOT NULL DEFAULT 'TR',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tax_rule_rate` (`tax_rate_id`),
    CONSTRAINT `fk_tax_rule_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL,
    `order_id` BIGINT(20) UNSIGNED NULL,
    `customer_id` BIGINT(20) UNSIGNED NULL,
    `invoice_type` VARCHAR(50) NOT NULL DEFAULT 'sales',
    `sub_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `grand_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
    `invoice_date` DATE NOT NULL,
    `uuid` VARCHAR(100) NULL,
    `qr_code` TEXT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_invoice_num` (`invoice_number`),
    KEY `idx_invoice_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `invoices`
    MODIFY COLUMN `order_id` BIGINT(20) UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `customer_id` BIGINT(20) UNSIGNED NULL AFTER `order_id`,
    ADD COLUMN IF NOT EXISTS `invoice_type` VARCHAR(50) NOT NULL DEFAULT 'sales' AFTER `customer_id`,
    ADD COLUMN IF NOT EXISTS `sub_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 AFTER `invoice_type`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) NOT NULL DEFAULT 'draft' AFTER `grand_total`,
    ADD COLUMN IF NOT EXISTS `invoice_date` DATE NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `uuid` VARCHAR(100) NULL AFTER `invoice_date`,
    ADD COLUMN IF NOT EXISTS `qr_code` TEXT NULL AFTER `uuid`,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL AFTER `updated_at`;

CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `total_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    PRIMARY KEY (`id`),
    KEY `idx_inv_item_inv` (`invoice_id`),
    CONSTRAINT `fk_inv_item_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_payments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `payment_transaction_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_inv_pay_inv` (`invoice_id`),
    CONSTRAINT `fk_inv_pay_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_pay_tx` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `credit_notes` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_credit_note_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `debit_notes` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_debit_note_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `einvoice_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `gib_status` VARCHAR(100) NULL,
    `gib_code` VARCHAR(50) NULL,
    `response_payload` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_einv_log_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `earchive_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `archive_status` VARCHAR(100) NULL,
    `response_payload` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_earch_log_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `edispatch_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `gib_status` VARCHAR(100) NULL,
    `response_payload` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `profit_loss_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `total_revenue` DECIMAL(15,4) NOT NULL,
    `total_expense` DECIMAL(15,4) NOT NULL,
    `net_profit` DECIMAL(15,4) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `balance_sheet_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_date` DATE NOT NULL,
    `total_assets` DECIMAL(15,4) NOT NULL,
    `total_liabilities` DECIMAL(15,4) NOT NULL,
    `total_equity` DECIMAL(15,4) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trial_balance` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_code` VARCHAR(50) NOT NULL,
    `debit_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `credit_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `period` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_plans` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `budget_plan_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `allocated_amount` DECIMAL(15,4) NOT NULL,
    `spent_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_budget_item_plan` FOREIGN KEY (`budget_plan_id`) REFERENCES `budget_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `currencies_history` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(3) NOT NULL,
    `rate` DECIMAL(15,6) NOT NULL,
    `recorded_date` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_curr_code_date` (`code`, `recorded_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `action` VARCHAR(100) NOT NULL,
    `payload` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_finance', 'Finans Panelini Görüntüle'),
('manage_finance', 'Finans Sistemini Yönet'),
('manage_accounts', 'Hesapları Yönet'),
('manage_cash', 'Kasaları Yönet'),
('manage_bank', 'Bankaları Yönet'),
('manage_expenses', 'Giderleri Yönet'),
('manage_revenues', 'Gelirleri Yönet'),
('manage_invoices', 'Faturaları Yönet'),
('manage_payments', 'Ödemeleri Yönet'),
('financial_reports', 'Finansal Raporlama'),
('tax_management', 'Vergi Yönetimi');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator')
  AND p.name IN (
    'view_finance', 'manage_finance', 'manage_accounts', 'manage_cash', 'manage_bank',
    'manage_expenses', 'manage_revenues', 'manage_invoices', 'manage_payments',
    'financial_reports', 'tax_management'
  );

-- Enterprise Finance & Accounting End

-- Enterprise Logistics & Shipping (Sprint 21)
CREATE TABLE IF NOT EXISTS `shipping_companies` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
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

CREATE TABLE IF NOT EXISTS `shipping_services` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
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
    CONSTRAINT `fk_ship_serv_comp_mig` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_zone_price_zone_mig` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_zone_price_serv_mig` FOREIGN KEY (`service_id`) REFERENCES `shipping_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_ship_method_serv_mig` FOREIGN KEY (`service_id`) REFERENCES `shipping_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_ship_pkg_serv_mig` FOREIGN KEY (`service_id`) REFERENCES `shipping_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_package_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_pkg_item_pkg` (`package_id`),
    CONSTRAINT `fk_pkg_item_pkg_mig` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_ship_label_pkg_mig` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_ship_pickup_comp_mig` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_ship_track_pkg_mig` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_tracking_events` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tracking_id` BIGINT(20) UNSIGNED NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `location` VARCHAR(150) NULL,
    `description` VARCHAR(255) NULL,
    `event_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_track_evt_track` (`tracking_id`),
    CONSTRAINT `fk_track_evt_track_mig` FOREIGN KEY (`tracking_id`) REFERENCES `shipping_tracking` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_statuses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_status_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_returns` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `return_number` VARCHAR(100) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'requested',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_ret_num` (`return_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_return_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `return_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_ret_item_ret` (`return_id`),
    CONSTRAINT `fk_ret_item_ret_mig` FOREIGN KEY (`return_id`) REFERENCES `shipping_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_claims` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `claim_type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_claim_pkg_mig` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_insurances` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `insurance_value` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `insurance_fee` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_ins_pkg_mig` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_documents` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_doc_pkg_mig` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `sent_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_notif_pkg_mig` FOREIGN KEY (`package_id`) REFERENCES `shipping_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_api_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT(20) UNSIGNED NOT NULL,
    `request_type` VARCHAR(100) NOT NULL,
    `payload` LONGTEXT NULL,
    `response` LONGTEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ship_api_log_comp_mig` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_statistics` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `metric_name` VARCHAR(100) NOT NULL,
    `metric_value` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `recorded_date` DATE NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_stat_metric` (`metric_name`, `recorded_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_cache` (
    `cache_key` VARCHAR(191) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_translations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `shipping_method_id` BIGINT(20) UNSIGNED NOT NULL,
    `language_id` BIGINT(20) UNSIGNED NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ship_trans` (`shipping_method_id`, `language_id`),
    CONSTRAINT `fk_ship_trans_method_mig` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_ship_int_comp_mig` FOREIGN KEY (`company_id`) REFERENCES `shipping_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator')
  AND p.name IN (
    'view_shipping', 'manage_shipping', 'manage_shipping_rules', 'manage_shipping_companies',
    'manage_returns', 'manage_labels', 'shipping_reports', 'shipping_statistics', 'shipping_integrations'
  );

-- Enterprise Logistics & Shipping End

SET FOREIGN_KEY_CHECKS = 1;
