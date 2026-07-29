-- Sprint 13: Gelişmiş Ürün Yönetimi 2.0 - Veritabanı Güncellemeleri
-- SaintMonarc V1.0
-- Karakter Seti: UTF8MB4
-- Çalıştır: mysql -u root saintmonarc < sprint13_product_enterprise_2.sql

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';

-- -------------------------------------------------------------------
-- 1. products tablosuna eksik kolonlar (IF NOT EXISTS ile güvenli)
-- -------------------------------------------------------------------
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `is_deal`       TINYINT(1)  NOT NULL DEFAULT 0    COMMENT 'Fırsat ürünü'        AFTER `is_featured`,
    ADD COLUMN IF NOT EXISTS `available_from` DATETIME   NULL                  COMMENT 'Satışa açılış tarihi' AFTER `is_deal`,
    ADD COLUMN IF NOT EXISTS `available_to`  DATETIME   NULL                  COMMENT 'Satıştan kalkış tarihi' AFTER `available_from`;

-- -------------------------------------------------------------------
-- 2. product_translations tablosuna çeviri alanları
-- -------------------------------------------------------------------
ALTER TABLE `product_translations`
    ADD COLUMN IF NOT EXISTS `box_content`   TEXT NULL COMMENT 'Kutu içeriği'    AFTER `delivery_info`,
    ADD COLUMN IF NOT EXISTS `return_policy` TEXT NULL COMMENT 'İade koşulları'  AFTER `box_content`;

-- -------------------------------------------------------------------
-- 3. product_documents tablosu (varsa atla)
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_documents` (
    `id`          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id`  BIGINT(20) UNSIGNED NOT NULL,
    `name`        VARCHAR(200)        NOT NULL                     COMMENT 'Döküman adı',
    `file_path`   VARCHAR(500)        NOT NULL                     COMMENT 'Dosya yolu',
    `file_type`   VARCHAR(50)         NOT NULL DEFAULT 'pdf'       COMMENT 'pdf/word/excel/zip',
    `file_size`   BIGINT(20)          NULL                         COMMENT 'Dosya boyutu (byte)',
    `sort_order`  INT(11)             NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_documents_product_id` (`product_id`),
    CONSTRAINT `fk_product_documents_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ürün belgeleri (PDF, Word, Excel, ZIP)';

-- -------------------------------------------------------------------
-- 4. RBAC izinleri (mevcut olanlara dokunmadan ekle)
-- -------------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
    ('view_products',         'Ürünleri Görüntüle'),
    ('create_products',       'Ürün Ekle'),
    ('edit_products',         'Ürün Düzenle'),
    ('delete_products',       'Ürün Sil'),
    ('restore_products',      'Ürün Geri Yükle'),
    ('force_delete_products', 'Ürünü Kalıcı Sil'),
    ('duplicate_products',    'Ürün Kopyala'),
    ('bulk_products',         'Toplu Ürün İşlemleri'),
    ('import_products',       'Ürün İçe Aktar'),
    ('export_products',       'Ürün Dışa Aktar'),
    ('audit_products',        'Ürün Denetim Raporları');

-- Süper admin ve admin rollerine tüm ürün izinlerini ver
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator', 'product_manager')
  AND p.name IN (
    'view_products','create_products','edit_products','delete_products',
    'restore_products','force_delete_products','duplicate_products',
    'bulk_products','import_products','export_products','audit_products'
  );

SELECT 'Sprint 13 migration tamamlandı!' AS result;
