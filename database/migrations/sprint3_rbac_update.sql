-- ==========================================================
-- SPRINT 3 RBAC UPDATE
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Alter Roles Table
ALTER TABLE `roles` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `description`;
ALTER TABLE `roles` ADD COLUMN `priority` INT NOT NULL DEFAULT 0 AFTER `is_active`;

-- 2. Seed/Reset Permissions
TRUNCATE TABLE `role_permissions`;
TRUNCATE TABLE `permissions`;

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
-- Dashboard
(1, 'view_dashboard', 'Dashboard görüntüleme yetkisi'),
-- Ürünler
(2, 'view_products', 'Ürünleri listeleme yetkisi'),
(3, 'create_products', 'Ürün ekleme yetkisi'),
(4, 'edit_products', 'Ürün düzenleme yetkisi'),
(5, 'delete_products', 'Ürün silme yetkisi'),
(6, 'export_products', 'Ürünleri dışa aktarma yetkisi'),
(7, 'import_products', 'Ürünleri içe aktarma yetkisi'),
-- Siparişler
(8, 'view_orders', 'Siparişleri listeleme yetkisi'),
(9, 'update_orders', 'Sipariş durumunu güncelleme yetkisi'),
(10, 'cancel_orders', 'Sipariş iptal yetkisi'),
(11, 'refund_orders', 'Sipariş iade yetkisi'),
(12, 'print_orders', 'Sipariş fişi yazdırma yetkisi'),
(13, 'export_orders', 'Siparişleri Excel\'e aktarma yetkisi'),
-- Finans
(14, 'view_finance', 'Finans sayfasını görüntüleme yetkisi'),
(15, 'report_finance', 'Finansal rapor alma yetkisi'),
(16, 'export_finance', 'Finans verilerini dışa aktarma yetkisi'),
-- Kullanıcılar (Müşteriler & Yöneticiler)
(17, 'view_users', 'Kullanıcıları listeleme yetkisi'),
(18, 'create_users', 'Kullanıcı ekleme yetkisi'),
(19, 'edit_users', 'Kullanıcı düzenleme yetkisi'),
(20, 'delete_users', 'Kullanıcı silme yetkisi'),
-- Diğer Modüller
(21, 'manage_banners', 'Banner ve slider yönetimi yetkisi'),
(22, 'manage_seo', 'SEO meta verileri ve yönlendirme yönetimi yetkisi'),
(23, 'manage_cms', 'Statik sayfalar ve CMS yönetimi yetkisi'),
(24, 'manage_themes', 'Tema ve arayüz ayarları yönetimi yetkisi'),
(25, 'manage_coupons', 'Kupon ve promosyon yönetimi yetkisi'),
(26, 'manage_shipping', 'Kargo firmaları ve kargo fiyatlandırma yetkisi'),
(27, 'manage_payment', 'Ödeme yöntemleri ve iyzico/stripe ayarları yetkisi'),
(28, 'manage_sms', 'SMS şablonları ve kampanya yönetimi yetkisi'),
(29, 'manage_email', 'Toplu e-posta şablonları ve bülten yönetimi yetkisi'),
(30, 'manage_ai', 'Yapay zekâ asistanı logları ve ayarları yetkisi'),
(31, 'manage_settings', 'Genel sistem ayarları yetkisi'),
(32, 'manage_plugins', 'Eklenti ve modül yönetimi yetkisi'),
(33, 'manage_api', 'REST API entegrasyonu ve token yönetimi yetkisi'),
(34, 'view_logs', 'Sistem logları ve denetim kayıtları yetkisi');

-- 3. Reset and Seed Roles
TRUNCATE TABLE `admin_roles`;
TRUNCATE TABLE `roles`;

INSERT INTO `roles` (`id`, `name`, `description`, `is_active`, `priority`) VALUES
(1, 'super_admin', 'Tam yetkili sistem yöneticisi (Bypass yetki kontrolü)', 1, 100),
(2, 'administrator', 'Genel yönetici hesabı', 1, 90),
(3, 'finance_manager', 'Finansal yönetim ve raporlama sorumlusu', 1, 80),
(4, 'order_manager', 'Sipariş ve kargo süreçleri yöneticisi', 1, 80),
(5, 'product_manager', 'Ürün ve kategori yöneticisi', 1, 80),
(6, 'customer_support', 'Müşteri ilişkileri ve destek temsilcisi', 1, 70),
(7, 'editor', 'Blog, sayfa ve içerik editörü', 1, 70),
(8, 'marketing_manager', 'Kupon, e-posta ve kampanya yöneticisi', 1, 70),
(9, 'seo_specialist', 'SEO meta ve yönlendirme uzmanı', 1, 60),
(10, 'warehouse_manager', 'Depo, stok ve envanter görevlisi', 1, 60);

-- Assign All Permissions to administrator (id 2) for staging demo
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`;

-- Specific mappings for other roles (Finance, Product, support, etc.)
-- Finance permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), (3, 14), (3, 15), (3, 16);
-- Product Manager
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(5, 1), (5, 2), (5, 3), (5, 4), (5, 5), (5, 6), (5, 7);
-- Customer Support
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(6, 1), (6, 8), (6, 9), (6, 12);

-- Map dev_admin to super_admin (id 1)
INSERT INTO `admin_roles` (`admin_id`, `role_id`) VALUES
(1, 1);

SET FOREIGN_KEY_CHECKS = 1;
