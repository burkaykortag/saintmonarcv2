-- Sprint 33: Enterprise Procurement & Supplier Management - Database Schema Updates
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. Suppliers Table
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_name` VARCHAR(200) NOT NULL COMMENT 'Tedarikçi Şirket Adı',
    `tax_number` VARCHAR(50) NULL COMMENT 'Vergi Numarası',
    `tax_office` VARCHAR(100) NULL COMMENT 'Vergi Dairesi',
    `contact_name` VARCHAR(100) NULL COMMENT 'Temsilci/Yetkili Kişi',
    `phone` VARCHAR(50) NULL COMMENT 'Telefon',
    `email` VARCHAR(100) NULL COMMENT 'E-Posta',
    `country` VARCHAR(100) NULL COMMENT 'Ülke',
    `city` VARCHAR(100) NULL COMMENT 'Şehir',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY' COMMENT 'Döviz Birimi',
    `payment_terms` VARCHAR(100) NULL COMMENT 'Ödeme Koşulları',
    `lead_time` INT(11) NOT NULL DEFAULT 0 COMMENT 'Ortalama Teslim Süresi (Gün)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Aktif / Pasif',
    `score` DECIMAL(3,2) NOT NULL DEFAULT 5.00 COMMENT 'AI Performans Puanı (5 üzerinden)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_suppliers_company_name` (`company_name`),
    KEY `idx_suppliers_tax_number` (`tax_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Purchase Orders (PO) Table
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_number` VARCHAR(50) NOT NULL COMMENT 'Sipariş Numarası',
    `supplier_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Tedarikçi ID',
    `warehouse_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Hedef Depo ID',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY' COMMENT 'Para Birimi',
    `status` ENUM('draft', 'pending_approval', 'approved', 'sent', 'partially_received', 'completed', 'cancelled') NOT NULL DEFAULT 'draft' COMMENT 'Durum',
    `expected_delivery` DATE NULL COMMENT 'Beklenen Teslim Tarihi',
    `tax_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Vergi Toplamı',
    `discount_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'İndirim Toplamı',
    `grand_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Genel Toplam',
    `created_by` BIGINT(20) UNSIGNED NULL COMMENT 'Oluşturan Yönetici',
    `approved_by` BIGINT(20) UNSIGNED NULL COMMENT 'Onaylayan Yönetici',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_po_number` (`po_number`),
    KEY `fk_purchase_orders_supplier` (`supplier_id`),
    KEY `fk_purchase_orders_warehouse` (`warehouse_id`),
    CONSTRAINT `fk_purchase_orders_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_purchase_orders_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Purchase Order Items Table
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_order_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'PO ID',
    `product_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Ürün ID',
    `variant_id` BIGINT(20) UNSIGNED NULL COMMENT 'Varyant ID',
    `quantity` INT(11) NOT NULL COMMENT 'Sipariş Adeti',
    `received_quantity` INT(11) NOT NULL DEFAULT 0 COMMENT 'Mal Kabulü Yapılan Adet',
    `price` DECIMAL(15,4) NOT NULL COMMENT 'Birim Alış Fiyatı',
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00 COMMENT 'KDV Oranı',
    `discount_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'İndirim Tutarı',
    `total` DECIMAL(15,4) NOT NULL COMMENT 'Satır Toplamı',
    PRIMARY KEY (`id`),
    KEY `fk_po_items_po` (`purchase_order_id`),
    KEY `fk_po_items_product` (`product_id`),
    KEY `fk_po_items_variant` (`variant_id`),
    CONSTRAINT `fk_po_items_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_po_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_po_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. RFQs Table
CREATE TABLE IF NOT EXISTS `rfqs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Ürün ID',
    `variant_id` BIGINT(20) UNSIGNED NULL COMMENT 'Varyant ID',
    `quantity` INT(11) NOT NULL COMMENT 'İstenen Adet',
    `title` VARCHAR(200) NOT NULL COMMENT 'Talep Başlığı',
    `description` TEXT NULL COMMENT 'Detaylar / Teknik Şartname',
    `status` ENUM('active', 'compared', 'completed') NOT NULL DEFAULT 'active' COMMENT 'Durum',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_rfqs_product` (`product_id`),
    CONSTRAINT `fk_rfqs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. RFQ Responses Table
CREATE TABLE IF NOT EXISTS `rfq_responses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `rfq_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'RFQ ID',
    `supplier_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Tedarikçi ID',
    `price` DECIMAL(15,4) NOT NULL COMMENT 'Teklif Edilen Birim Fiyat',
    `delivery_lead_time` INT(11) NOT NULL COMMENT 'Teslimat Süresi (Gün)',
    `is_recommended` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'AI Önerilen Teklif mi?',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_rfq_responses_rfq` (`rfq_id`),
    KEY `fk_rfq_responses_supplier` (`supplier_id`),
    CONSTRAINT `fk_rfq_responses_rfq` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rfq_responses_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Goods Receipts (Mal Kabul) Table
CREATE TABLE IF NOT EXISTS `goods_receipts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_order_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'PO ID',
    `received_by` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Mal Kabul Sorumlusu (Admin)',
    `notes` TEXT NULL COMMENT 'Mal Kabul Notları',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_goods_receipts_po` (`purchase_order_id`),
    KEY `fk_goods_receipts_admin` (`received_by`),
    CONSTRAINT `fk_goods_receipts_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_goods_receipts_admin` FOREIGN KEY (`received_by`) REFERENCES `admins` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Goods Receipt Items Table
CREATE TABLE IF NOT EXISTS `goods_receipt_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `goods_receipt_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Kabul ID',
    `product_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Ürün ID',
    `variant_id` BIGINT(20) UNSIGNED NULL COMMENT 'Varyant ID',
    `quantity` INT(11) NOT NULL COMMENT 'Kabul Edilen Miktar',
    `damaged_quantity` INT(11) NOT NULL DEFAULT 0 COMMENT 'Hasarlı Miktar',
    `missing_quantity` INT(11) NOT NULL DEFAULT 0 COMMENT 'Eksik Miktar',
    `lot_number` VARCHAR(100) NULL COMMENT 'Lot / Parti Numarası',
    `serial_number` VARCHAR(100) NULL COMMENT 'Seri Numarası',
    `batch_number` VARCHAR(100) NULL COMMENT 'Batch Numarası',
    `expire_date` DATE NULL COMMENT 'Son Kullanma Tarihi',
    `photo_path` VARCHAR(500) NULL COMMENT 'Hasar/Teslimat Fotoğrafı',
    PRIMARY KEY (`id`),
    KEY `fk_receipt_items_receipt` (`goods_receipt_id`),
    CONSTRAINT `fk_receipt_items_receipt` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Supplier Contracts Table
CREATE TABLE IF NOT EXISTS `supplier_contracts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Tedarikçi ID',
    `title` VARCHAR(200) NOT NULL COMMENT 'Sözleşme Başlığı',
    `start_date` DATE NOT NULL COMMENT 'Başlangıç Tarihi',
    `end_date` DATE NOT NULL COMMENT 'Bitiş Tarihi',
    `renewal_date` DATE NULL COMMENT 'Yenileme Tarihi',
    `file_path` VARCHAR(500) NULL COMMENT 'Sözleşme PDF Yolu',
    `status` ENUM('active', 'expired', 'renewed') NOT NULL DEFAULT 'active' COMMENT 'Durum',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_contracts_supplier` (`supplier_id`),
    CONSTRAINT `fk_contracts_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Supplier Documents Table
CREATE TABLE IF NOT EXISTS `supplier_documents` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Tedarikçi ID',
    `name` VARCHAR(200) NOT NULL COMMENT 'Belge Adı',
    `file_path` VARCHAR(500) NOT NULL COMMENT 'Belge Dosya Yolu',
    `file_type` VARCHAR(50) NOT NULL COMMENT 'Belge Türü (pdf, docx, etc)',
    `file_size` BIGINT(20) UNSIGNED NULL COMMENT 'Dosya Boyutu (byte)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_docs_supplier` (`supplier_id`),
    CONSTRAINT `fk_docs_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Supplier Payments Table
CREATE TABLE IF NOT EXISTS `supplier_payments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_order_id` BIGINT(20) UNSIGNED NULL COMMENT 'PO ID',
    `supplier_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Tedarikçi ID',
    `amount` DECIMAL(15,4) NOT NULL COMMENT 'Tutar',
    `payment_date` DATE NOT NULL COMMENT 'Ödeme Vade Tarihi',
    `status` ENUM('pending', 'paid', 'partial', 'overdue') NOT NULL DEFAULT 'pending' COMMENT 'Ödeme Durumu',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_payments_po` (`purchase_order_id`),
    KEY `fk_payments_supplier` (`supplier_id`),
    CONSTRAINT `fk_payments_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payments_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. RBAC Permissions Registration
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
    ('view_procurement', 'Satın Almayı Görüntüle'),
    ('manage_procurement', 'Satın Almayı Yönet'),
    ('approve_purchase_orders', 'Siparişleri Onayla (PO)'),
    ('manage_suppliers', 'Tedarikçileri Yönet'),
    ('manage_rfq', 'Teklif İsteklerini Yönet (RFQ)'),
    ('receive_goods', 'Mal Kabul Gerçekleştir'),
    ('view_purchase_analytics', 'Satın Alma Analitiğini Gör'),
    ('manage_supplier_contracts', 'Tedarikçi Sözleşmelerini Yönet');

-- Link role permissions for super_admin & admin
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'admin')
  AND p.name IN (
    'view_procurement',
    'manage_procurement',
    'approve_purchase_orders',
    'manage_suppliers',
    'manage_rfq',
    'receive_goods',
    'view_purchase_analytics',
    'manage_supplier_contracts'
  );
