# SaintMonarc Veritabanı Dokümantasyonu

Bu doküman, SaintMonarc e-ticaret platformu için tasarlanan kurumsal veri tabanı şemasının yapısını, veri tiplerini, indeks optimizasyonlarını ve iş mantığı gerekçelerini açıklamaktadır.

---

## 1. Genel Tasarım İlkeleri

### Güvenlik ve Bütünlük
- **InnoDB Depolama Motoru**: İşlemsel bütünlüğü (ACID) ve Yabancı Anahtar (Foreign Key) kısıtlamalarını garanti eder.
- **Parametrik ve Prepared İfadeler**: SQL Injection riskini ortadan kaldırmak için sorgular uygulama seviyesinde tamamen PDO prepare yapısı ile çalıştırılmalıdır.
- **Karakter Seti (utf8mb4_unicode_ci)**: Emojiler dahil tüm dil karakterlerini ve arama hassasiyetini destekler.

### Performans ve İndeksleme
- Büyük hacimli arama/filtreleme yapılacak alanlarda indeksler tanımlanmıştır:
  - Tekil ve Bileşik İndeksler (Composite Indexes), finansal raporlamalar için tarih, sipariş durumu ve müşteri kırılımlarında hızlı sonuç vermeyi sağlar.
  - Sık aranan alanlar (`sku`, `slug`, `email`, `status`) indekslenmiştir.

---

## 2. Modüler Tablo Detayları

### 2.1 Çekirdek (Core & Users)
- **`users`**: Platforma üye olan müşterilerin kimlik bilgilerini tutar. Güvenlik gerekçesiyle şifre Argon2id/Bcrypt formatında saklanacaktır.
- **`user_profiles`**: Müşterilerin isim, telefon gibi kişisel verilerini barındırır.
- **`admins` & `roles` & `permissions`**: RBAC (Rol Tabanlı Yetkilendirme) mimarisini uygular. Bir yöneticinin birden fazla rolü, bir rolün ise birden fazla yetkisi olabilir (`role_permissions`, `admin_roles` ara tabloları ile çözülmüştür).
- **`sessions`**: Oturum güvenliğini ve kullanıcı/yönetici cihaz eşleştirmesini sağlar.
- **`activity_logs` & `audit_logs`**: KVKK gerekliliklerine uygun şekilde kimin, hangi veriyi, ne zaman değiştirdiğini (`old_values` ve `new_values` JSON formatında) denetler.

### 2.2 Ürünler ve Envanter (Products & Inventory)
- **Çoklu Dil Desteği (`product_translations`, `category_translations`)**: Dil bağımlı içerikler (`name`, `description`) ana tablodan ayrılmıştır. Bu sayede platform yeni dillere mimariyi bozmadan genişleyebilir.
- **Varyant Sistemi (`product_variants`, `attributes`, `attribute_values`, `product_variant_option_values`)**:
  - `attributes` (örn: Beden, Renk) ve `attribute_values` (örn: XL, Siyah) sınırsız varyant türetmeyi sağlar.
  - Her varyantın kendine ait benzersiz bir `sku` kodu, fiyatı, karşılaştırma fiyatı ve lojistik ağırlığı bulunur.
- **Envanter Kontrolü (`inventories`, `inventory_movements`)**:
  - `inventories` her ürün veya varyantın fiziksel stok adedini ve sipariş anında kilitlenen rezerve stoku takip eder.
  - `inventory_movements` (Stok Hareketleri) tablosu, her stok giriş/çıkışını (satış, iade, sayım) kayıt altına alarak stok geçmişini takip eder.

### 2.3 Sipariş ve Finans (Shopping & Orders)
- **`orders` & `order_items`**: Sipariş verilerini barındırır. Sipariş anındaki fiyatlar, vergi oranları, müşteri adresleri ve döviz kuru bilgileri tarihsel tutarlılık için sipariş tablolarına kopyalanır (snapshot).
- **`invoices` & `refunds`**: Finansal raporlama ve muhasebe uyumluluğu için fatura ve iade işlemlerini takip eder.
- **`payment_transactions`**: Ödeme kanallarından gelen başarılı/başarısız tüm işlemleri ve yanıt paketlerini (JSON payload) loglar.

---

## 3. Finansal Raporlama ve Dışa Aktarım (Export) Hazırlığı

### Hızlı Rapor Filtreleme
Finansal raporlama modülü, aşağıdaki bileşik indeksleri kullanarak günlük, haftalık, aylık veya yıllık sorguları milisaniyeler içinde gerçekleştirebilir:
- `orders` tablosundaki `idx_orders_created` (`created_at`) ve `idx_orders_status` (`status`) indeksleri, belirli tarihler arasındaki başarılı satışları filtreler.
- `invoices` tablosundaki `idx_invoices_issue_date` faturalandırılmış ciro raporlamalarını hızlandırır.

### Export Yapısı
`orders`, `inventories` ve `products` tablolarındaki düzleştirilmiş ilişkiler (JOIN), CSV ve Excel çıktısı için optimize edilmiştir. Örneğin, bir envanter raporu çekilirken:
```sql
SELECT p.sku, pt.name, pv.sku AS variant_sku, i.stock, i.reserved_stock
FROM inventories i
LEFT JOIN products p ON i.product_id = p.id
LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
LEFT JOIN product_variants pv ON i.variant_id = pv.id;
```
Sorgu tamamen birincil anahtarlar ve yabancı anahtar indeksleri üzerinden aktığı için yüksek performanslı çalışır.

---

## 4. Yasal Uyum (KVKK & Onay Yönetimi)

Mevzuata uyumluluk (KVKK, Gizlilik Politikası, Kullanım Şartları ve Ticari Elektronik İleti Onayı) veritabanı düzeyinde şu şekilde güvenceye alınır:
- **`legal_documents`**: Admin panelinden düzenlenebilen yasal metinleri ve bunların versiyonlarını barındırır.
- **`user_consents`**: Müşterinin hangi yasal metni, hangi sürümüyle, hangi tarihte, hangi IP adresi ve tarayıcı bilgisi (User Agent) ile kabul ettiğini benzersiz şekilde (`unique_user_consent`) kaydeder.
