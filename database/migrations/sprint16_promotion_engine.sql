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

SET FOREIGN_KEY_CHECKS = 1;
