-- Sprint 18: Enterprise Arama Motoru (Enterprise Search Engine)
-- UTF-8 MB4 destekli, Türkçe karakter uyumlu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. search_index (Arama İndeksi)
CREATE TABLE IF NOT EXISTS `search_index` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_type` VARCHAR(50) NOT NULL COMMENT 'product, category, brand, page, cms, blog, campaign, customer, order, media',
    `item_id` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NULL,
    `sku` VARCHAR(100) NULL,
    `barcode` VARCHAR(100) NULL,
    `tags` TEXT NULL,
    `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `stock_status` VARCHAR(20) NOT NULL DEFAULT 'in_stock',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_new` TINYINT(1) NOT NULL DEFAULT 0,
    `is_deal` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_search_item` (`item_type`, `item_id`),
    KEY `idx_search_title` (`title`),
    KEY `idx_search_sku_barcode` (`sku`, `barcode`),
    KEY `idx_search_price_stock` (`price`, `stock_status`),
    KEY `idx_search_attributes` (`is_active`, `is_featured`, `is_new`, `is_deal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. search_keywords (Arama Anahtar Kelimeleri)
CREATE TABLE IF NOT EXISTS `search_keywords` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `keyword` VARCHAR(150) NOT NULL,
    `frequency` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_search_keywords_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. search_synonyms (Eş Anlamlı Kelimeler)
CREATE TABLE IF NOT EXISTS `search_synonyms` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_word` VARCHAR(150) NOT NULL COMMENT 'Aranan kelime',
    `target_words` TEXT NOT NULL COMMENT 'Yönlendirilecek eş anlamlı kelimeler (virgülle ayrılmış)',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_synonym_source` (`source_word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. search_redirects (Arama Yönlendirmeleri)
CREATE TABLE IF NOT EXISTS `search_redirects` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `keyword` VARCHAR(150) NOT NULL COMMENT 'Yönlendirilecek arama terimi',
    `redirect_url` VARCHAR(255) NOT NULL COMMENT 'Gidilecek hedef URL',
    `redirect_code` INT NOT NULL DEFAULT 301 COMMENT '301 veya 302',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_redirect_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. search_popular (Popüler Aramalar)
CREATE TABLE IF NOT EXISTS `search_popular` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `keyword` VARCHAR(150) NOT NULL,
    `search_count` INT NOT NULL DEFAULT 1,
    `click_count` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_popular_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. search_logs (Arama Log Defteri)
CREATE TABLE IF NOT EXISTS `search_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `query` VARCHAR(255) NOT NULL,
    `results_count` INT NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `user_id` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_search_logs_query` (`query`),
    KEY `idx_search_logs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. search_statistics (Arama İstatistikleri)
CREATE TABLE IF NOT EXISTS `search_statistics` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `keyword` VARCHAR(150) NOT NULL,
    `total_searches` INT NOT NULL DEFAULT 0,
    `total_clicks` INT NOT NULL DEFAULT 0,
    `total_conversions` INT NOT NULL DEFAULT 0 COMMENT 'Satın alma sayısı',
    `total_cart_additions` INT NOT NULL DEFAULT 0 COMMENT 'Sepete ekleme sayısı',
    `no_result_count` INT NOT NULL DEFAULT 0 COMMENT 'Boş arama sayısı',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_stats_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. search_filters (Arama Filtreleri)
CREATE TABLE IF NOT EXISTS `search_filters` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `filter_name` VARCHAR(100) NOT NULL,
    `filter_type` VARCHAR(50) NOT NULL COMMENT 'category, brand, price, stock, attribute',
    `display_label` VARCHAR(150) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_filters_name_type` (`filter_name`, `filter_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. search_cache (Arama Önbelleği)
CREATE TABLE IF NOT EXISTS `search_cache` (
    `cache_key` VARCHAR(191) NOT NULL,
    `payload` LONGTEXT NOT NULL COMMENT 'Arama sonuçları JSON',
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. search_boost_rules (Öne Çıkarma / Boost Kuralları)
CREATE TABLE IF NOT EXISTS `search_boost_rules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `target_type` VARCHAR(50) NOT NULL COMMENT 'product, category, brand, keyword',
    `target_id` BIGINT(20) UNSIGNED NULL COMMENT 'Ürün/Kategori/Marka ID (keyword ise null)',
    `keyword` VARCHAR(150) NULL COMMENT 'Anahtar kelime (id ise null)',
    `boost_value` DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Çarpan değeri (örn: 1.5)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_boost_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. search_stop_words (Filtrelenecek Kelimeler)
CREATE TABLE IF NOT EXISTS `search_stop_words` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `word` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_stop_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. search_suggestions (Öneri Kelimeler)
CREATE TABLE IF NOT EXISTS `search_suggestions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `query` VARCHAR(150) NOT NULL,
    `suggestion` VARCHAR(150) NOT NULL,
    `frequency` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_suggestion_pair` (`query`, `suggestion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. search_clicks (Arama Tıklamaları)
CREATE TABLE IF NOT EXISTS `search_clicks` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `query` VARCHAR(150) NOT NULL,
    `item_type` VARCHAR(50) NOT NULL,
    `item_id` BIGINT(20) UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_clicks_query` (`query`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. search_history (Arama Geçmişi)
CREATE TABLE IF NOT EXISTS `search_history` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT(20) UNSIGNED NULL,
    `keyword` VARCHAR(150) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_history_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. search_ai_queries (AI Niyet Analizleri)
CREATE TABLE IF NOT EXISTS `search_ai_queries` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `original_query` VARCHAR(255) NOT NULL,
    `resolved_intent` VARCHAR(255) NOT NULL COMMENT 'AI tarafından çözümlenen niyet',
    `proposed_filters` TEXT NULL COMMENT 'Önerilen filtreler JSON',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. search_blacklist (Kara Liste Kelimeler)
CREATE TABLE IF NOT EXISTS `search_blacklist` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `word` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_blacklist_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. search_whitelist (Beyaz Liste Kelimeler)
CREATE TABLE IF NOT EXISTS `search_whitelist` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `word` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_whitelist_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. search_collections (Arama Koleksiyonları)
CREATE TABLE IF NOT EXISTS `search_collections` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `rules` TEXT NOT NULL COMMENT 'Koleksiyon kuralları JSON',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_collections_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. search_index_queue (İndeksleme Kuyruğu)
CREATE TABLE IF NOT EXISTS `search_index_queue` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_type` VARCHAR(50) NOT NULL,
    `item_id` BIGINT(20) UNSIGNED NOT NULL,
    `action` VARCHAR(20) NOT NULL COMMENT 'index, delete',
    `attempts` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_queue_item` (`item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. search_rebuild_logs (İndeks Yeniden Oluşturma Günlükleri)
CREATE TABLE IF NOT EXISTS `search_rebuild_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `started_at` DATETIME NOT NULL,
    `finished_at` DATETIME NULL,
    `total_indexed` INT NOT NULL DEFAULT 0,
    `status` VARCHAR(20) NOT NULL DEFAULT 'running' COMMENT 'running, success, failed',
    `error_message` TEXT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RBAC İzinleri
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_search', 'Arama Motorunu Görüntüle'),
('manage_search', 'Arama Motorunu Yönet'),
('manage_search_index', 'Arama İndeksini Yönet'),
('manage_synonyms', 'Eş Anlamlıları Yönet'),
('manage_stopwords', 'Filtrelenecek Kelimeleri Yönet'),
('manage_boost', 'Boost Kurallarını Yönet'),
('search_reports', 'Arama Raporları'),
('search_rebuild', 'Arama İndeksini Yeniden Oluştur'),
('search_ai', 'AI Akıllı Aramayı Yönet');

-- Yetkilerin süper admin ve admin'e atanması
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator', 'marketing_manager')
  AND p.name IN (
    'view_search', 'manage_search', 'manage_search_index', 'manage_synonyms',
    'manage_stopwords', 'manage_boost', 'search_reports', 'search_rebuild', 'search_ai'
  );

SET FOREIGN_KEY_CHECKS = 1;
