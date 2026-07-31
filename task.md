# SaintMonarc Sprint 15 - CRM Görev Listesi

- [x] Veritabanı şema tasarımı ve `sprint15_crm.sql` ile `migration.sql` oluşturularak veritabanında çalıştırılması
- [x] RBAC İzin kayıtlarının permissions tablosuna eklenmesi
- [x] `CustomerRepository` veri erişim sınıfının raw PHP + PDO ile yazılması
- [x] `CustomerService` iş kuralları (Sadakat Puanı, Sanal Cüzdan, Otomatik RFM segmentasyonu) sınıfının yazılması
- [x] DI Container (`Application.php`) içerisine repository ve service sınıf singleton tanımlarının yapılması
- [x] `CustomerController` denetleyicisinin yazılması
- [x] REST API rotalarının (`routes/api.php`) ve admin rotalarının (`routes/admin.php`) eklenmesi
- [x] Sidebar menüsüne "Müşteriler" bağlantısının ve rbac yetki kontrolünün eklenmesi
- [x] Arayüz görünümlerinin (index, show, create, edit, groups, segments) yazılması
- [x] 35 senaryoluk `test_crm.php` CLI test betiğinin yazılması ve başarıyla çalıştırılması
