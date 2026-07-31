-- SaintMonarc Sprint 15 - Enterprise CRM Veritabanı Değişiklikleri
-- UTF-8 MB4 destekli, Türkçe karakter uyumlu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Customers Tablosu (Enterprise Seviye)
CREATE TABLE IF NOT EXISTS `customers` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL COMMENT 'Adı',
    `last_name` VARCHAR(100) NOT NULL COMMENT 'Soyadı',
    `email` VARCHAR(191) NOT NULL COMMENT 'E-Posta',
    `phone` VARCHAR(30) NULL COMMENT 'Telefon',
    `password` VARCHAR(255) NULL COMMENT 'Şifre',
    `avatar` VARCHAR(255) NULL COMMENT 'Profil Fotoğrafı',
    `customer_group_id` BIGINT(20) UNSIGNED NULL COMMENT 'Müşteri Grubu',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, passive, suspended, VIP, risky',
    `kvkk_consent` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'KVKK Onayı',
    `email_verified_at` TIMESTAMP NULL,
    `phone_verified_at` TIMESTAMP NULL,
    `last_login_at` TIMESTAMP NULL,
    -- Finansal & Davranışsal Analiz Verileri
    `total_spent` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Yaşam Boyu Değer (LTV)',
    `orders_count` INT NOT NULL DEFAULT 0 COMMENT 'Toplam Sipariş Adedi',
    `average_basket` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Ortalama Sepet Tutarı',
    `rfm_score` VARCHAR(10) NULL COMMENT 'RFM Analiz Skoru (Recency, Frequency, Monetary)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_customers_email` (`email`),
    KEY `idx_customers_group` (`customer_group_id`),
    CONSTRAINT `fk_customers_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Customer Groups (Müşteri Grupları)
-- Eğer tablo yoksa zaten schema'dan oluşturulur veya güncellenir.
-- Varsayılan Grupları Ekle
INSERT IGNORE INTO `customer_groups` (`id`, `name`, `discount_rate`, `created_at`, `updated_at`) VALUES
(1, 'Perakende', 0.00, NOW(), NOW()),
(2, 'VIP', 10.00, NOW(), NOW()),
(3, 'Toptancı', 20.00, NOW(), NOW()),
(4, 'Bayi', 15.00, NOW(), NOW()),
(5, 'Kurumsal', 5.00, NOW(), NOW()),
(6, 'Distribütör', 25.00, NOW(), NOW()),
(7, 'Özel Fiyat Grubu', 0.00, NOW(), NOW());

-- 3. Customer Addresses (Müşteri Adresleri)
CREATE TABLE IF NOT EXISTS `customer_addresses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(100) NOT NULL COMMENT 'Adres Başlığı (örn: Ev, İş)',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `address_line1` VARCHAR(255) NOT NULL,
    `address_line2` VARCHAR(255) NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NOT NULL DEFAULT 'Türkiye',
    `zip_code` VARCHAR(20) NOT NULL,
    `is_default_billing` TINYINT(1) NOT NULL DEFAULT 0,
    `is_default_shipping` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_customer_addresses_customer_id` (`customer_id`),
    CONSTRAINT `fk_customer_addresses_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Customer Notes (Müşteri Notları)
CREATE TABLE IF NOT EXISTS `customer_notes` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `note` TEXT NOT NULL,
    `created_by_admin` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_customer_notes_customer_id` (`customer_id`),
    CONSTRAINT `fk_customer_notes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Customer Tags (Müşteri Etiketleri)
CREATE TABLE IF NOT EXISTS `customer_tags` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `color` VARCHAR(20) DEFAULT '#c5a880',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_customer_tags_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan etiketler
INSERT IGNORE INTO `customer_tags` (`name`, `color`) VALUES
('Yeni Üye', '#0d6efd'),
('VIP Müşteri', '#ffc107'),
('Sadık Alıcı', '#198754'),
('Riskli Üye', '#dc3545'),
('Pasif Müşteri', '#6c757d'),
('Toptan Alıcı', '#6610f2');

-- 6. Customer Tag Relations (Müşteri Etiket İlişkileri)
CREATE TABLE IF NOT EXISTS `customer_tag_relations` (
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `tag_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`customer_id`, `tag_id`),
    CONSTRAINT `fk_ctr_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ctr_tag` FOREIGN KEY (`tag_id`) REFERENCES `customer_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Customer Login History (Giriş Geçmişi)
CREATE TABLE IF NOT EXISTS `customer_login_history` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'success' COMMENT 'success, failed',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_clh_customer` (`customer_id`),
    CONSTRAINT `fk_clh_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Customer Reward Points (Sadakat Puanı)
CREATE TABLE IF NOT EXISTS `customer_reward_points` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `points` INT NOT NULL COMMENT 'Kazanılan/harcanan puan tutarı',
    `description` VARCHAR(255) NOT NULL COMMENT 'İşlem açıklaması',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_crp_customer` (`customer_id`),
    CONSTRAINT `fk_crp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Customer Wallet (Cüzdan)
CREATE TABLE IF NOT EXISTS `customer_wallet` (
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Mevcut Bakiye',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`customer_id`),
    CONSTRAINT `fk_cw_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Customer Wallet Transactions (Cüzdan Hareketleri)
CREATE TABLE IF NOT EXISTS `customer_wallet_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL COMMENT 'Değişim tutarı (+/-)',
    `type` VARCHAR(30) NOT NULL COMMENT 'deposit, withdraw, refund, purchase',
    `description` VARCHAR(255) NOT NULL COMMENT 'Açıklama',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cwt_customer` (`customer_id`),
    CONSTRAINT `fk_cwt_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Customer Documents (Müşteri Belgeleri)
CREATE TABLE IF NOT EXISTS `customer_documents` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL COMMENT 'Belge adı',
    `file_path` VARCHAR(255) NOT NULL COMMENT 'Yol',
    `file_size` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cd_customer` (`customer_id`),
    CONSTRAINT `fk_cd_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Customer Activity Logs (Müşteri Aktivite Günlüğü)
CREATE TABLE IF NOT EXISTS `customer_activity_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'örn: page_view, cart_add, wishlist_add',
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cal_customer` (`customer_id`),
    CONSTRAINT `fk_cal_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Customer Segments (Dinamik Segmentler)
CREATE TABLE IF NOT EXISTS `customer_segments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT 'Segment Adı',
    `description` VARCHAR(255) NULL,
    `rules` LONGTEXT NOT NULL COMMENT 'Kurallar JSON',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_cs_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan Segmentleri Ekle
INSERT IGNORE INTO `customer_segments` (`id`, `name`, `description`, `rules`, `created_at`) VALUES
(1, 'Son 30 Günde Alışveriş Yapanlar', 'Son 30 gün içinde sipariş vermiş olanlar', '{"days_since_last_order": 30}', NOW()),
(2, 'VIP Değerliler (100.000 TL Üzeri)', 'Toplam harcaması 100.000 TL ve üzeri olanlar', '{"min_total_spent": 100000}', NOW()),
(3, 'Sadık Alıcılar (En az 10 Sipariş)', 'En az 10 sipariş vermiş olanlar', '{"min_orders_count": 10}', NOW()),
(4, 'Hiç Sipariş Vermeyenler', 'Hiç siparişi bulunmayanlar', '{"orders_count": 0}', NOW()),
(5, 'Pasif Müşteriler (90 Gündür Giriş Yapmayan)', 'Son 90 gündür sisteme giriş yapmamış olanlar', '{"days_since_last_login": 90}', NOW());

-- 14. Customer Segment Relations (Müşteri Segment İlişkileri)
CREATE TABLE IF NOT EXISTS `customer_segment_relations` (
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `segment_id` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`customer_id`, `segment_id`),
    CONSTRAINT `fk_csr_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_csr_segment` FOREIGN KEY (`segment_id`) REFERENCES `customer_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. RBAC İzinleri
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_customers', 'Müşterileri Görüntüle'),
('create_customers', 'Müşteri Ekle'),
('edit_customers', 'Müşteri Düzenle'),
('delete_customers', 'Müşteri Sil'),
('export_customers', 'Müşteri Dışa Aktar'),
('customer_wallet', 'Müşteri Cüzdanını Yönet'),
('customer_reward', 'Müşteri Puanlarını Yönet'),
('customer_segments', 'Müşteri Segmentlerini Yönet'),
('customer_notes', 'Müşteri Notlarını Yönet'),
('customer_documents', 'Müşteri Belgelerini Yönet');

-- İzinleri İlgili Rollere Atama
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator', 'marketing_manager', 'finance_manager')
  AND p.name IN (
    'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
    'export_customers', 'customer_wallet', 'customer_reward', 'customer_segments', 
    'customer_notes', 'customer_documents'
  );

SET FOREIGN_KEY_CHECKS = 1;
