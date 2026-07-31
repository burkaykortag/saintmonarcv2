-- Sprint 17: Yapay Zeka Öneri Motoru (AI Recommendation Engine)
-- UTF-8 MB4 destekli, Türkçe karakter uyumlu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. AI Recommendations Tablosu
CREATE TABLE IF NOT EXISTS `ai_recommendations` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL COMMENT 'product_campaign, category_discount, aging_stock, cross_sell_bundle, custom_campaign',
    `title` VARCHAR(255) NOT NULL COMMENT 'Öneri Başlığı',
    `description` TEXT NOT NULL COMMENT 'Öneri Açıklaması',
    `payload` LONGTEXT NULL COMMENT 'Öneriye ait JSON veri (örn: ürün_id, kategori_id, indirim_oranları)',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, applied, dismissed',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ai_recs_type` (`type`),
    KEY `idx_ai_recs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. RBAC Yetki Kaydı
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('ai_recommendations', 'Yapay Zeka Öneri Motorunu Yönet');

-- Süper admin ve pazarlama yöneticisine atama yapalım
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator', 'marketing_manager')
  AND p.name = 'ai_recommendations';

SET FOREIGN_KEY_CHECKS = 1;
