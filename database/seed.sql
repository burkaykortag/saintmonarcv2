-- ==========================================================
-- SAINTMONARC SEED DATA (DEVELOPMENT ONLY)
-- Warning: Do NOT run this seed file in production environments.
-- Production administrators should be created during initial setup wizard.
-- ==========================================================

-- 1. LOCALIZATION
INSERT INTO `languages` (`id`, `code`, `name`, `is_default`, `is_active`) VALUES
(1, 'tr', 'Türkçe', 1, 1),
(2, 'en', 'English', 0, 1);

INSERT INTO `currencies` (`id`, `code`, `symbol`, `exchange_rate`, `is_default`, `is_active`) VALUES
(1, 'TRY', '₺', 1.000000, 1, 1),
(2, 'USD', '$', 34.250000, 0, 1),
(3, 'EUR', '€', 37.100000, 0, 1);

-- 2. ROLES & PERMISSIONS
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'super_admin', 'Full system control'),
(2, 'editor', 'Product catalog and content editor'),
(3, 'support', 'Customer support and order tracking');

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'access_admin_panel', 'Allows access to backend control panel'),
(2, 'manage_products', 'Create, update, delete products'),
(3, 'manage_categories', 'Create, update, delete categories'),
(4, 'manage_brands', 'Manage brands'),
(5, 'view_orders', 'View order list and details'),
(6, 'manage_orders', 'Change order statuses'),
(7, 'manage_refunds', 'Manage refunds'),
(8, 'view_finance_reports', 'View analytical financial statements'),
(9, 'manage_customers', 'Manage user accounts and customer groups'),
(10, 'manage_settings', 'Modify system configurations and active themes'),
(11, 'manage_users', 'Manage admin personnel and RBAC role assignments');

-- Role-Permission Mapping
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11),
(2, 1), (2, 2), (2, 3), (2, 4),
(3, 1), (3, 5), (3, 6), (3, 9);

-- 3. DEVELOPMENT DEMO ADMINISTRATOR (Only for local sandboxes)
-- Plaintext Password: 'SaintMonarcAdmin2026'
INSERT INTO `admins` (`id`, `username`, `email`, `password`, `is_super`, `is_active`) VALUES
(1, 'dev_admin', 'dev@saintmonarc.test', '$2y$10$w6xM5vsz9w9B7sW1J64hA.t4K07gJvjU5oUsh72qIe9gWfQ.F6vR6', 1, 1);

INSERT INTO `admin_roles` (`admin_id`, `role_id`) VALUES
(1, 1);

-- 4. SYSTEM & GENERAL CONFIGURATION GROUPS
INSERT INTO `setting_groups` (`id`, `name`) VALUES
(1, 'general'),
(2, 'payment'),
(3, 'shipping'),
(4, 'seo'),
(5, 'legal');

INSERT INTO `settings` (`group_id`, `key`, `value`) VALUES
(1, 'store_name', 'SaintMonarc Boutique'),
(1, 'store_email', 'info@saintmonarc.com'),
(1, 'default_language', 'tr'),
(1, 'default_currency', 'TRY'),
(2, 'bank_transfer_enabled', '1'),
(3, 'free_shipping_threshold', '1000.00'),
(4, 'meta_title_default', 'SaintMonarc E-Commerce Platform'),
(4, 'meta_description_default', 'Luxury online shopping destination.');

-- 5. LEGAL DOCUMENTS BASELINE
INSERT INTO `legal_documents` (`id`, `type`, `version`, `content`) VALUES
(1, 'kvkk', '1.0', 'Kişisel Verilerin Korunması Kanunu metnidir.'),
(2, 'privacy_policy', '1.0', 'Gizlilik Politikası metnidir.'),
(3, 'terms_of_service', '1.0', 'Kullanıcı Sözleşmesi metnidir.'),
(4, 'marketing_consent', '1.0', 'Pazarlama İletişimi İzni metnidir.');

-- 6. CUSTOMER GROUPS & DEMO USERS
INSERT INTO `customer_groups` (`id`, `name`, `discount_rate`) VALUES
(1, 'Standart', 0.00),
(2, 'VIP', 10.00);

-- Plaintext Password: 'UserPass2026'
INSERT INTO `users` (`id`, `email`, `password`, `status`, `customer_group_id`) VALUES
(1, 'customer1@saintmonarc.test', '$2y$10$tZ8kR0u/z/aGk3HhLhWJLuYk8X478i6R9Yc4oN8uF5x8N2y4n3aH6', 'active', 1),
(2, 'customer2@saintmonarc.test', '$2y$10$tZ8kR0u/z/aGk3HhLhWJLuYk8X478i6R9Yc4oN8uF5x8N2y4n3aH6', 'active', 2);

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `phone`, `gender`) VALUES
(1, 'Volkan', 'Demir', '+905559998877', 'male'),
(2, 'Selin', 'Yazar', '+905556667788', 'female');

-- 7. SHIPPING & LOGISTICS
INSERT INTO `shipping_methods` (`id`, `price`, `is_active`) VALUES
(1, 69.90, 1),
(2, 0.00, 1);

INSERT INTO `shipping_method_translations` (`shipping_method_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'Standart Kargo', 'Yurtiçi Kargo ile 3 iş gününde teslimat'),
(1, 2, 'Standard Shipping', 'Delivery within 3 business days via carrier'),
(2, 1, 'Ücretsiz Kargo', '1000 TL üzeri alışverişlerde ücretsiz kargo'),
(2, 2, 'Free Shipping', 'Free delivery on orders above 1000 TL');

-- 8. PAYMENT CHANNELS
INSERT INTO `payment_methods` (`id`, `code`, `name`, `is_active`) VALUES
(1, 'bank_transfer', 'Banka Havalesi', 1),
(2, 'credit_card', 'Kredi Kartı', 1);

-- 9. BRANDS & CATEGORIES
INSERT INTO `brands` (`id`, `slug`, `logo`, `is_active`) VALUES
(1, 'monarc-prime', NULL, 1);

INSERT INTO `brand_translations` (`brand_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'Monarc Prime', 'SaintMonarc Elite tasarım koleksiyonu'),
(1, 2, 'Monarc Prime', 'SaintMonarc Elite premium fashion line');

INSERT INTO `categories` (`id`, `parent_id`, `slug`, `is_active`, `sort_order`) VALUES
(1, NULL, 'giyim', 1, 1);

INSERT INTO `category_translations` (`category_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'Giyim', 'Erkek ve Kadın giyim modelleri'),
(1, 2, 'Clothing', 'Men and Women clothing categories');

-- 10. PLUGINS
INSERT INTO `plugins` (`id`, `name`, `version`, `is_enabled`, `settings`) VALUES
(1, 'analytics_dashboard', '1.0.0', 1, '{"tracking_id": "UA-12345678-1"}');
