# SaintMonarc Veritabanı Mimari Denetim Raporu (Database Review Report)

Bu rapor, SaintMonarc veritabanı şemasının kurumsal standartlar, güvenlik, genişletilebilirlik ve performans açılarından denetlenmesi sonucu hazırlanan analizleri ve yapılan şema iyileştirmelerini içermektedir.

---

## 1. Tespit Edilen Zayıflıklar ve İyileştirme Alanları

### 1.1 Güvenlik ve Kimlik Doğrulama (Sprint 2 Uyum)
- **Zayıflık**: Kullanıcı tablosunda e-posta doğrulama, başarısız giriş denemeleri (brute-force koruması), iki aşamalı doğrulama (2FA) ve sosyal medya (OAuth) ile giriş desteği bulunmuyordu.
- **İyileştirme**: 
  - `users` tablosuna `email_verified_at`, `two_factor_secret`, `two_factor_recovery_codes`, `failed_login_attempts` ve `lockout_until` kolonları eklendi.
  - Sosyal girişler için `user_oauth_providers` tablosu oluşturuldu.
  - Güvenli oturum ve cihaz takibi için `user_login_histories` ve `user_devices` tabloları eklendi.

### 1.2 Gelecek Pazaryeri (Marketplace) Entegrasyonu
- **Zayıflık**: Ürünler doğrudan platform sahibine aitmiş gibi modellenmişti. Çok satıcılı (multi-vendor) bir yapı için satıcı tanımı yoktu.
- **İyileştirme**: `vendors` (Satıcılar) tablosu eklendi. `products` ve `orders` tablolarına `vendor_id` ilişkileri eklenerek çok satıcılı mimari altyapısı hazırlandı.

### 1.3 Modülerlik ve Eklenti (Plugin) Sistemi
- **Zayıflık**: Sisteme yüklenecek eklentilerin, modüllerin çalışma durumlarını veya ayarlarını yönetebileceği dinamik bir kontrol tablosu eksikti.
- **İyileştirme**: Sürümleri ve durumları (`is_enabled`) takip eden `plugins` tablosu eklendi.

### 1.4 Mobil Uygulama ve Push Bildirim Desteği
- **Zayıflık**: Mobil uygulamalardan (iOS/Android) push bildirim göndermek için gerekli olan cihaz token kayıt alanı şemada yoktu.
- **İyileştirme**: `user_devices` tablosu eklenerek mobil ve web bildirim cihaz tokenlarının takibi sağlandı.

### 1.5 Çoklu Dil Altyapısının Genişletilmesi
- **Zayıflık**: Ürün ve kategoriler çevrilebiliyordu ancak Marka (`brands`) ve Kargo Yöntemi (`shipping_methods`) isimleri çok dilli değildi.
- **İyileştirme**: `brand_translations` ve `shipping_method_translations` tabloları eklenerek tüm müşteri arayüzü dilden bağımsız hale getirildi.

---

## 2. Şema Yapısı İyileştirme Detayları (Özet)

| Tablo Adı | Eklenen/Güncellenen Yapı | Açıklama |
| :--- | :--- | :--- |
| `users` | `email_verified_at`, `two_factor_*`, `lockout_*` | Kimlik doğrulama güvenliği artırıldı. |
| `vendors` | **Yeni Tablo** | Gelecekteki pazaryeri uyumluluğu sağlandı. |
| `user_login_histories` | **Yeni Tablo** | Güvenlik denetimleri (IP, Tarayıcı, Cihaz takibi) eklendi. |
| `user_oauth_providers` | **Yeni Tablo** | Google/Apple sosyal oturum açma desteği sağlandı. |
| `user_devices` | **Yeni Tablo** | Mobil uygulama push bildirim ve cihaz eşleştirme altyapısı kuruldu. |
| `plugins` | **Yeni Tablo** | Dinamik modül ve eklenti yönetimi sağlandı. |
| `brand_translations` | **Yeni Tablo** | Markaların çok dilli olması sağlandı. |
| `shipping_method_translations` | **Yeni Tablo** | Kargo seçeneklerinin çok dilli olması sağlandı. |

---

## 3. Canlı Ortam Güvenliği (Seed Temizliği)
Geliştirme ortamı tohum verilerinde bulunan `admin` kullanıcısının şifresi, sadece yerel test amaçlı kullanılacak şekilde dokümante edilmiş ve üretim (production) kurulumları için ilk kurulum sihirbazının (setup wizard) kullanılacağı belirtilmiştir. Canlı kurulumlarda bu tohum verileri yerine kullanıcıdan dinamik kimlik bilgisi alan kod katmanı devreye girecektir.
