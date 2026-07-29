SET FOREIGN_KEY_CHECKS = 0;

-- Ensure default language exists
INSERT INTO `languages` (id, code, name, is_default, is_active)
VALUES (1, 'tr', 'Türkçe', 1, 1)
ON DUPLICATE KEY UPDATE code='tr';

-- Ensure default currency exists
INSERT INTO `currencies` (id, code, symbol, exchange_rate, is_default, is_active)
VALUES (1, 'TRY', '₺', 1.000000, 1, 1)
ON DUPLICATE KEY UPDATE code='TRY';

-- Ensure default payment method exists
INSERT INTO `payment_methods` (id, code, name, is_active)
VALUES (1, 'transfer', 'Havale / EFT', 1)
ON DUPLICATE KEY UPDATE code='transfer';

-- Seed categories
INSERT INTO `categories` (id, slug, is_active, sort_order) VALUES
(1, 'elektronik', 1, 1),
(2, 'moda', 1, 2),
(3, 'kozmetik', 1, 3),
(4, 'ev-yasam', 1, 4)
ON DUPLICATE KEY UPDATE slug=VALUES(slug);

INSERT INTO `category_translations` (category_id, language_id, name, description) VALUES
(1, 1, 'Elektronik', 'Cep telefonları, bilgisayarlar ve aksesuarlar'),
(2, 1, 'Moda & Giyim', 'Elbise, ayakkabı ve aksesuar çeşitleri'),
(3, 1, 'Kozmetik & Kişisel Bakım', 'Parfüm, makyaj ve cilt bakım ürünleri'),
(4, 1, 'Ev & Yaşam', 'Mobilya, dekorasyon ve ev gereçleri')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed products
INSERT INTO `products` (id, sku, slug, price, compare_at_price, cost_price, is_active) VALUES
(1, 'PRD-IPHONE15', 'iphone-15-pro', 64999.00, 69999.00, 52000.00, 1),
(2, 'PRD-MACBOOKM3', 'macbook-pro-m3', 84999.00, 89999.00, 70000.00, 1),
(3, 'PRD-TSHIRT', 'pamuklu-tshirt', 299.90, 399.90, 120.00, 1),
(4, 'PRD-JEANS', 'slim-fit-jean', 799.90, 999.90, 350.00, 1),
(5, 'PRD-PARFUM', 'blue-channel-parfum', 3499.00, 3999.00, 1800.00, 1),
(6, 'PRD-KREM', 'nemlendirici-krem', 450.00, 550.00, 180.00, 1),
(7, 'PRD-KOLTUK', 'modern-kose-koltugu', 18999.00, 21999.00, 12000.00, 1),
(8, 'PRD-AVIZE', 'sarkit-avize-gold', 1299.00, 1499.00, 600.00, 1),
(9, 'PRD-OUTOFSTOCK', 'stoksuz-urun-test', 99.00, 129.00, 40.00, 1),
(10, 'PRD-CRITICAL', 'kritik-stok-urun', 499.00, 599.00, 200.00, 1)
ON DUPLICATE KEY UPDATE price=VALUES(price);

INSERT INTO `product_translations` (product_id, language_id, name, description, short_description) VALUES
(1, 1, 'iPhone 15 Pro Max 256 GB', 'Apple en son teknoloji amiral gemisi cep telefonu', 'iPhone 15 Pro Max'),
(2, 1, 'MacBook Pro M3 Max 16"', 'Üst düzey performans sunan profesyonel bilgisayar', 'MacBook Pro M3'),
(3, 1, 'Erkek %100 Pamuklu T-Shirt', 'Günlük kullanıma uygun rahat kesim pamuklu t-shirt', 'Pamuklu T-Shirt'),
(4, 1, 'Slim Fit Mavi Jean Pantolon', 'Modern şık tasarım dar kesim pantolon', 'Mavi Jean'),
(5, 1, 'Bleu de Chanel Edp 100 ml', 'Maskülen, kalıcı ve odunsu erkek parfümü', 'Chanel Erkek Parfümü'),
(6, 1, 'Cilt Yenileyici Nemlendirici Krem', 'Kuru ciltler için yoğun nem desteği sağlayan krem', 'Nemlendirici Krem'),
(7, 1, 'Modern Köşe Koltuk Takımı', 'Evler için konforlu ve şık modern köşe koltuk', 'Köşe Koltuk'),
(8, 1, 'Retro Sarkıt Avize Gold', 'Altın kaplama lüks dekoratif salon avizesi', 'Sarkıt Avize'),
(9, 1, 'Stoksuz Test Ürünü', 'Stok seviyelerini denetlemek için stoksuz bırakılan ürün', 'Stoksuz Ürün'),
(10, 1, 'Kritik Stok Test Ürünü', 'Kritik seviyeyi test etmek amacıyla 2 adet stok verilen ürün', 'Kritik Ürün')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Mapping products to categories
INSERT INTO `product_category_relations` (product_id, category_id) VALUES
(1, 1), (2, 1),
(3, 2), (4, 2),
(5, 3), (6, 3),
(7, 4), (8, 4),
(9, 2), (10, 3)
ON DUPLICATE KEY UPDATE category_id=VALUES(category_id);

-- Seed inventories (Stock Statuses)
INSERT INTO `inventories` (product_id, variant_id, stock, reserved_stock) VALUES
(1, NULL, 45, 2),
(2, NULL, 28, 1),
(3, NULL, 150, 0),
(4, NULL, 90, 0),
(5, NULL, 34, 1),
(6, NULL, 120, 2),
(7, NULL, 8, 0),
(8, NULL, 15, 0),
(9, NULL, 0, 0), -- Out of stock
(10, NULL, 2, 0) -- Critical stock (< 5)
ON DUPLICATE KEY UPDATE stock=VALUES(stock);

-- Seed users (if count is low)
INSERT INTO `users` (id, email, password, status, email_verified_at) VALUES
(10, 'user1@saintmonarc.test', 'hash', 'active', NOW()),
(11, 'user2@saintmonarc.test', 'hash', 'active', NOW()),
(12, 'user3@saintmonarc.test', 'hash', 'active', NOW())
ON DUPLICATE KEY UPDATE email=VALUES(email);

INSERT INTO `user_profiles` (user_id, first_name, last_name, phone) VALUES
(10, 'Ali', 'Veli', '05551112233'),
(11, 'Ayşe', 'Yılmaz', '05554445566'),
(12, 'Fatma', 'Kaya', '05557778899')
ON DUPLICATE KEY UPDATE first_name=VALUES(first_name);

-- Seed orders across different dates (Relative to NOW)
INSERT INTO `orders` (id, order_number, user_id, status, subtotal, tax_total, discount_total, shipping_total, grand_total, currency_code, currency_rate, billing_first_name, billing_last_name, billing_address, billing_city, billing_country, billing_zip, shipping_first_name, shipping_last_name, shipping_address, shipping_city, shipping_country, shipping_zip, created_at) VALUES
-- Today (completed)
(1, 'SM-20260728-01', 10, 'delivered', 3499.00, 629.82, 0.00, 0.00, 4128.82, 'TRY', 1.00, 'Ali', 'Veli', 'Çankaya', 'Ankara', 'Türkiye', '06100', 'Ali', 'Veli', 'Çankaya', 'Ankara', 'Türkiye', '06100', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
-- Yesterday (processing)
(2, 'SM-20260727-01', 11, 'processing', 1299.00, 233.82, 100.00, 50.00, 1482.82, 'TRY', 1.00, 'Ayşe', 'Yılmaz', 'Kadıköy', 'İstanbul', 'Türkiye', '34710', 'Ayşe', 'Yılmaz', 'Kadıköy', 'İstanbul', 'Türkiye', '34710', DATE_SUB(NOW(), INTERVAL 26 HOUR)),
-- 3 Days Ago (shipped)
(3, 'SM-20260725-01', 12, 'shipped', 799.90, 143.98, 0.00, 50.00, 993.88, 'TRY', 1.00, 'Fatma', 'Kaya', 'Konak', 'İzmir', 'Türkiye', '35000', 'Fatma', 'Kaya', 'Konak', 'İzmir', 'Türkiye', '35000', DATE_SUB(NOW(), INTERVAL 3 DAY)),
-- 15 Days Ago (pending)
(4, 'SM-20260713-01', 10, 'pending', 64999.00, 11699.82, 2000.00, 0.00, 74698.82, 'TRY', 1.00, 'Ali', 'Veli', 'Çankaya', 'Ankara', 'Türkiye', '06100', 'Ali', 'Veli', 'Çankaya', 'Ankara', 'Türkiye', '06100', DATE_SUB(NOW(), INTERVAL 15 DAY)),
-- 45 Days Ago (delivered)
(5, 'SM-20260613-01', 11, 'delivered', 84999.00, 15299.82, 0.00, 0.00, 100298.82, 'TRY', 1.00, 'Ayşe', 'Yılmaz', 'Kadıköy', 'İstanbul', 'Türkiye', '34710', 'Ayşe', 'Yılmaz', 'Kadıköy', 'İstanbul', 'Türkiye', '34710', DATE_SUB(NOW(), INTERVAL 45 DAY)),
-- 5 Months Ago (cancelled)
(6, 'SM-20260228-01', 12, 'cancelled', 450.00, 81.00, 0.00, 50.00, 581.00, 'TRY', 1.00, 'Fatma', 'Kaya', 'Konak', 'İzmir', 'Türkiye', '35000', 'Fatma', 'Kaya', 'Konak', 'İzmir', 'Türkiye', '35000', DATE_SUB(NOW(), INTERVAL 5 MONTH))
ON DUPLICATE KEY UPDATE grand_total=VALUES(grand_total);

-- Seed order items
INSERT INTO `order_items` (order_id, product_id, product_sku, product_name, quantity, price, tax_amount, total, created_at) VALUES
(1, 5, 'PRD-PARFUM', 'Bleu de Chanel Edp 100 ml', 1, 3499.00, 629.82, 4128.82, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 8, 'PRD-AVIZE', 'Retro Sarkıt Avize Gold', 1, 1299.00, 233.82, 1532.82, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(3, 4, 'PRD-JEANS', 'Slim Fit Mavi Jean Pantolon', 1, 799.90, 143.98, 943.88, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 1, 'PRD-IPHONE15', 'iPhone 15 Pro Max 256 GB', 1, 64999.00, 11699.82, 76698.82, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(5, 2, 'PRD-MACBOOKM3', 'MacBook Pro M3 Max 16"', 1, 84999.00, 15299.82, 100298.82, DATE_SUB(NOW(), INTERVAL 45 DAY)),
(6, 6, 'PRD-KREM', 'Cilt Yenileyici Nemlendirici Krem', 1, 450.00, 81.00, 531.00, DATE_SUB(NOW(), INTERVAL 5 MONTH))
ON DUPLICATE KEY UPDATE total=VALUES(total);

SET FOREIGN_KEY_CHECKS = 1;
