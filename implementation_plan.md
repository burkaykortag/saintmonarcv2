# SaintMonarc Sprint 15 - Enterprise CRM (Müşteri Yönetimi) İmplementasyon Planı

## Hedef
SaintMonarc V1.0 için kurumsal düzeyde bir Müşteri İlişkileri Yönetimi (CRM) modülü geliştirmek. Bu modül; detaylı müşteri kartı yönetimi, sanal cüzdan, sadakat puanı kazanımı, dinamik RFM segmentasyonu, KVKK onay süreci, gelişmiş filtreleme ve REST API entegrasyonu içermektedir.

## Veritabanı Değişiklikleri
Aşağıdaki tablolar UTF-8 MB4 uyumlu ve Türkçe karakter destekli olarak oluşturulmuştur:
- `customers`: Müşteri bilgilerini, RFM skorlarını, KVKK izinlerini tutan ana tablo.
- `customer_groups`: Müşteri grupları (VIP, Toptancı, Perakende vb.) ve bunlara bağlı indirim oranları.
- `customer_addresses`: Müşterilere ait çoklu teslimat/fatura adresleri.
- `customer_notes`: Yöneticiler tarafından girilen dahili müşteri notları.
- `customer_tags` & `customer_tag_relations`: Müşterileri etiketlemek için esnek etiket yapısı.
- `customer_login_history`: Oturum açma günlükleri (IP, cihaz, tarih).
- `customer_reward_points`: Sadakat puanı kazanım/harcama hareketleri.
- `customer_wallet` & `customer_wallet_transactions`: Sanal cüzdan bakiye ve bakiye hareketleri.
- `customer_documents`: Müşteri belgeleri (KVKK izin formları, kimlik fotokopileri vb.).
- `customer_activity_logs`: Müşteri davranışsal aktiviteleri (sayfa gezme vb.).
- `customer_segments` & `customer_segment_relations`: Dinamik kurallara dayalı segment tanımları ve atamaları.

## Planlanan Değişiklikler

### 1. Repository & Service Katmanı
- [NEW] `App\Repositories\CustomerRepository`: Tüm veri tabanı işlemlerini raw PHP + PDO ile yürütür.
- [NEW] `App\Services\CustomerService`: İş kurallarını (Sadakat puan kazanımı, cüzdan bakiye düşümü/artırımı, RFM skorlama motoru, otomatik etiketleme) yürütür.

### 2. Denetleyici ve Rotalar
- [NEW] `App\Controllers\CustomerController`: Admin paneli CRUD arayüzü, cüzdan/puan formları, segment düzenleyicileri ve REST API uç noktaları.
- [MODIFY] `routes/admin.php`: CRM rotaları ve yetkilendirme katmanı.
- [MODIFY] `routes/api.php`: REST API `/api/customers` rotaları.

### 3. Arayüz Görünümleri (Views)
- [NEW] `resources/views/admin/customers/index.php`: Liste/Kart modlu gelişmiş arama ve filtreleme ekranı.
- [NEW] `resources/views/admin/customers/show.php`: Sekmeli yapıya sahip zengin müşteri kartı ve cüzdan/puan yönetim ekranı.
- [NEW] `resources/views/admin/customers/create.php` & `edit.php`: Müşteri ekleme/düzenleme formları.
- [NEW] `resources/views/admin/customers/groups.php`: Özel indirim tanımlı müşteri grupları.
- [NEW] `resources/views/admin/customers/segments.php`: Dinamik RFM kural tanımlama ekranı.

## Doğrulama Planı
- **CLI Testleri (`test_crm.php`)**: Şema varlığı, veri CRUD işlemleri, cüzdan bakiyeleri, sadakat puanları, dinamik segmentasyon tetiklemeleri, API entegrasyonu ve syntax kontrollerini kapsayan 35 test senaryosu.
