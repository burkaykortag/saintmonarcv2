# SPRINT 44 ADMIN PANEL DEEP AUDIT REPORT

## 📊 GENEL METRİKLER VE SONUÇ ÖZETİ

| Metrik | Değer |
| :--- | :---: |
| **TOTAL ADMIN SCREENS** | 134 |
| **TOTAL ADMIN ROUTES** | 281 |
| **TOTAL BUTTONS INVENTORIED** | 13129 |
| **TOTAL FORMS INVENTORIED** | 154 |
| **TOTAL CRUD FLOWS** | 11 |
| **TOTAL API ENDPOINTS** | 29 |
| **TOTAL WORKING ITEMS** | 142 |
| **TOTAL BROKEN ITEMS** | 6 |
| **TOTAL BLOCKED ITEMS** | 0 |
| **TOTAL NOT TESTED** | 0 |

### 🐛 BUG KATEGORİ DAĞILIMI

- **CRITICAL**: 0
- **HIGH**: 1
- **MEDIUM**: 0
- **LOW**: 5

### 🔍 DETAYLI HATALI ALAN SAYILARI

- **BROKEN ROUTE COUNT**: 6
- **BROKEN BUTTON COUNT**: 0
- **BROKEN FORM COUNT**: 0
- **BROKEN CRUD COUNT**: 0
- **BROKEN JS COUNT**: 0
- **BROKEN API COUNT**: 0
- **BROKEN RBAC COUNT**: 0
- **BROKEN RESPONSIVE COUNT**: 0
- **PHP ERROR COUNT**: 0
- **CONSOLE ERROR COUNT**: 0

---

## 📋 FAZ 15 – DASHBOARD SCREEN AUDIT MATRIX

| Modül | Ekran | Route | Açılıyor | CRUD | Form | JS | API | RBAC | Responsive | Sonuç |
|------|------|------|----------|------|------|----|-----|------|------------|------|
| Login | /admin/login | GET /admin/login | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| General | /admin | GET /admin | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Dashboard | /admin/dashboard | GET /admin/dashboard | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Components | /admin/components | GET /admin/components | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Roles | /admin/roles | GET /admin/roles | EVET | KONTROL EDİLDİ | MEVCUT (35) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Roles | /admin/roles/create | GET /admin/roles/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Roles | /admin/roles/edit | GET /admin/roles/edit | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Media | /admin/media | GET /admin/media | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Media | /admin/media/list-json | GET /admin/media/list-json | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Media | /admin/media/list-ajax | GET /admin/media/list-ajax | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Categories | /admin/categories | GET /admin/categories | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Categories | /admin/categories/create | GET /admin/categories/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Categories | /admin/categories/edit | GET /admin/categories/edit | HAYIR (302) | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **BROKEN** |
| Categories | /admin/categories/export | GET /admin/categories/export | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Brands | /admin/brands | GET /admin/brands | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Brands | /admin/brands/create | GET /admin/brands/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Brands | /admin/brands/edit | GET /admin/brands/edit | HAYIR (302) | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **BROKEN** |
| Brands | /admin/brands/export | GET /admin/brands/export | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Products | /admin/products | GET /admin/products | EVET | KONTROL EDİLDİ | MEVCUT (60) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Products | /admin/products/reports | GET /admin/products/reports | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Products | /admin/products/create | GET /admin/products/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Products | /admin/products/edit | GET /admin/products/edit | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Products | /admin/products/import/mapping | GET /admin/products/import/mapping | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Products | /admin/products/export | GET /admin/products/export | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Attributes | /admin/attributes | GET /admin/attributes | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Attributes | /admin/attributes/create | GET /admin/attributes/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Attributes | /admin/attributes/edit | GET /admin/attributes/edit | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Attributes | /admin/attributes/sets | GET /admin/attributes/sets | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Attributes | /admin/attributes/sets/create | GET /admin/attributes/sets/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Attributes | /admin/attributes/sets/edit | GET /admin/attributes/sets/edit | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Variants | /admin/variants | GET /admin/variants | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Variants | /admin/variants/create | GET /admin/variants/create | HAYIR (302) | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **BROKEN** |
| Variants | /admin/variants/edit | GET /admin/variants/edit | HAYIR (500) | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **BROKEN** |
| Variants | /admin/variants/export | GET /admin/variants/export | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders | GET /admin/orders | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/show | GET /admin/orders/show | EVET | KONTROL EDİLDİ | MEVCUT (4) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/create | GET /admin/orders/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/edit | GET /admin/orders/edit | HAYIR (302) | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **BROKEN** |
| Orders | /admin/orders/export | GET /admin/orders/export | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/pdf | GET /admin/orders/pdf | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/reports | GET /admin/orders/reports | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/dashboard | GET /admin/orders/dashboard | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/analytics | GET /admin/orders/analytics | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/packing | GET /admin/orders/packing | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/shipping | GET /admin/orders/shipping | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/payment | GET /admin/orders/payment | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/kanban | GET /admin/orders/kanban | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/merge | GET /admin/orders/merge | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/partial-shipment | GET /admin/orders/partial-shipment | HAYIR (302) | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **BROKEN** |
| Orders | /admin/orders/print-center | GET /admin/orders/print-center | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Orders | /admin/orders/statuses | GET /admin/orders/statuses | EVET | KONTROL EDİLDİ | MEVCUT (3) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers | GET /admin/customers | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/show | GET /admin/customers/show | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/profile | GET /admin/customers/profile | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/timeline | GET /admin/customers/timeline | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/analytics | GET /admin/customers/analytics | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/create | GET /admin/customers/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/edit | GET /admin/customers/edit | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/export | GET /admin/customers/export | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/groups | GET /admin/customers/groups | EVET | KONTROL EDİLDİ | MEVCUT (3) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Customers | /admin/customers/segments | GET /admin/customers/segments | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Promotions | /admin/promotions | GET /admin/promotions | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Promotions | /admin/promotions/create | GET /admin/promotions/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Promotions | /admin/promotions/edit | GET /admin/promotions/edit | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Promotions | /admin/promotions/export | GET /admin/promotions/export | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Promotions | /admin/promotions/calendar | GET /admin/promotions/calendar | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Promotions | /admin/promotions/reports | GET /admin/promotions/reports | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Promotions | /admin/promotions/preview | GET /admin/promotions/preview | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Coupons | /admin/coupons | GET /admin/coupons | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Recommendations | /admin/recommendations | GET /admin/recommendations | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Recommendations | /admin/recommendations/generate | GET /admin/recommendations/generate | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Search | /admin/search | GET /admin/search | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Search | /admin/search/statistics | GET /admin/search/statistics | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Search | /admin/search/synonyms | GET /admin/search/synonyms | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Search | /admin/search/boost | GET /admin/search/boost | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Search | /admin/search/rebuild | GET /admin/search/rebuild | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Search | /admin/search/clear-cache | GET /admin/search/clear-cache | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Finance | /admin/finance | GET /admin/finance | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Accounts | /admin/accounts | GET /admin/accounts | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Invoices | /admin/invoices | GET /admin/invoices | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Expenses | /admin/expenses | GET /admin/expenses | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Revenues | /admin/revenues | GET /admin/revenues | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Reports | /admin/reports/finance | GET /admin/reports/finance | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Shipping | /admin/shipping | GET /admin/shipping | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Shipping | /admin/shipping/companies | GET /admin/shipping/companies | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Shipping | /admin/shipping/companies/edit | GET /admin/shipping/companies/edit | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Shipping | /admin/shipping/shipments | GET /admin/shipping/shipments | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Shipping | /admin/shipping/returns | GET /admin/shipping/returns | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Shipping | /admin/shipping/reports | GET /admin/shipping/reports | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Marketplace | /admin/marketplace/dashboard | GET /admin/marketplace/dashboard | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Marketplace | /admin/marketplace/applications | GET /admin/marketplace/applications | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Marketplace | /admin/marketplace/moderation | GET /admin/marketplace/moderation | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Marketplace | /admin/marketplace/payouts | GET /admin/marketplace/payouts | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Vendors | /admin/vendors | GET /admin/vendors | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Vendors | /admin/vendors/create | GET /admin/vendors/create | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Vendors | /admin/vendors/edit | GET /admin/vendors/edit | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Vendors | /admin/vendors/reports | GET /admin/vendors/reports | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Vendors | /admin/vendors/payments | GET /admin/vendors/payments | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Vendors | /admin/vendors/wallet | GET /admin/vendors/wallet | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Dashboard | /vendor/dashboard | GET /vendor/dashboard | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | AÇIK | UYUMLU | **PASS** |
| Workflows | /admin/workflows | GET /admin/workflows | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Workflows | /admin/workflows/create | GET /admin/workflows/create | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Workflows | /admin/workflows/edit | GET /admin/workflows/edit | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Workflows | /admin/workflows/templates | GET /admin/workflows/templates | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Workflows | /admin/workflows/history | GET /admin/workflows/history | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Workflows | /admin/workflows/logs | GET /admin/workflows/logs | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Categories | /api/categories/tree | GET /api/categories/tree | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |
| Brands | /api/brands | GET /api/brands | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |
| Products | /api/products | GET /api/products | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |
| Attributes | /api/attributes | GET /api/attributes | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |
| Variants | /api/variants | GET /api/variants | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |
| Wms | /admin/wms/dashboard | GET /admin/wms/dashboard | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/warehouses | GET /admin/wms/warehouses | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/locations | GET /admin/wms/locations | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/movements | GET /admin/wms/movements | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/picking | GET /admin/wms/picking | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/packing | GET /admin/wms/packing | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/transfers | GET /admin/wms/transfers | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/counts | GET /admin/wms/counts | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/analytics | GET /admin/wms/analytics | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /admin/wms/ai-assistant | GET /admin/wms/ai-assistant | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Wms | /api/wms/warehouses | GET /api/wms/warehouses | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |
| Wms | /api/wms/inventory | GET /api/wms/inventory | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/dashboard | GET /admin/purchasing/dashboard | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/suppliers | GET /admin/purchasing/suppliers | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/suppliers/show | GET /admin/purchasing/suppliers/show | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/orders | GET /admin/purchasing/orders | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/wizard | GET /admin/purchasing/wizard | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/rfq | GET /admin/purchasing/rfq | EVET | KONTROL EDİLDİ | MEVCUT (2) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/receipts | GET /admin/purchasing/receipts | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/payments | GET /admin/purchasing/payments | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/contracts | GET /admin/purchasing/contracts | EVET | KONTROL EDİLDİ | MEVCUT (1) | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /admin/purchasing/ai-assistant | GET /admin/purchasing/ai-assistant | EVET | KONTROL EDİLDİ | YOK | AKTİF | HAYIR | KORUMALI | UYUMLU | **PASS** |
| Purchasing | /api/purchasing/suppliers | GET /api/purchasing/suppliers | EVET | KONTROL EDİLDİ | YOK | AKTİF | EVET | AÇIK | UYUMLU | **PASS** |

---

## 🚨 DETAYLI BUG / PROBLEM LİSTESİ

### ID: ERR-ROUTE-031
- **MODÜL**: Categories
- **EKRAN**: Categories (/admin/categories/edit)
- **URL**: `/admin/categories/edit`
- **ELEMENT**: Route / Controller View
- **PROBLEM**: Ekran düzgün yüklenemedi veya HTTP hatası döndürdü
- **BEKLENEN**: HTTP 200 OK & Temiz View Render
- **GERÇEK**: HTTP 302
- **HTTP STATUS**: 302
- **SEVERITY**: **LOW**
- **REPRODUCE STEPS**:
  1. Browser ile /admin/categories/edit adresine git
  2. Sunucu yanıtını incele

---

### ID: ERR-ROUTE-040
- **MODÜL**: Brands
- **EKRAN**: Brands (/admin/brands/edit)
- **URL**: `/admin/brands/edit`
- **ELEMENT**: Route / Controller View
- **PROBLEM**: Ekran düzgün yüklenemedi veya HTTP hatası döndürdü
- **BEKLENEN**: HTTP 200 OK & Temiz View Render
- **GERÇEK**: HTTP 302
- **HTTP STATUS**: 302
- **SEVERITY**: **LOW**
- **REPRODUCE STEPS**:
  1. Browser ile /admin/brands/edit adresine git
  2. Sunucu yanıtını incele

---

### ID: ERR-ROUTE-074
- **MODÜL**: Variants
- **EKRAN**: Variants (/admin/variants/create)
- **URL**: `/admin/variants/create`
- **ELEMENT**: Route / Controller View
- **PROBLEM**: Ekran düzgün yüklenemedi veya HTTP hatası döndürdü
- **BEKLENEN**: HTTP 200 OK & Temiz View Render
- **GERÇEK**: HTTP 302
- **HTTP STATUS**: 302
- **SEVERITY**: **LOW**
- **REPRODUCE STEPS**:
  1. Browser ile /admin/variants/create adresine git
  2. Sunucu yanıtını incele

---

### ID: ERR-ROUTE-076
- **MODÜL**: Variants
- **EKRAN**: Variants (/admin/variants/edit)
- **URL**: `/admin/variants/edit`
- **ELEMENT**: Route / Controller View
- **PROBLEM**: Ekran düzgün yüklenemedi veya HTTP hatası döndürdü
- **BEKLENEN**: HTTP 200 OK & Temiz View Render
- **GERÇEK**: HTTP 500
- **HTTP STATUS**: 500
- **SEVERITY**: **HIGH**
- **REPRODUCE STEPS**:
  1. Browser ile /admin/variants/edit adresine git
  2. Sunucu yanıtını incele

---

### ID: ERR-ROUTE-086
- **MODÜL**: Orders
- **EKRAN**: Orders (/admin/orders/edit)
- **URL**: `/admin/orders/edit`
- **ELEMENT**: Route / Controller View
- **PROBLEM**: Ekran düzgün yüklenemedi veya HTTP hatası döndürdü
- **BEKLENEN**: HTTP 200 OK & Temiz View Render
- **GERÇEK**: HTTP 302
- **HTTP STATUS**: 302
- **SEVERITY**: **LOW**
- **REPRODUCE STEPS**:
  1. Browser ile /admin/orders/edit adresine git
  2. Sunucu yanıtını incele

---

### ID: ERR-ROUTE-104
- **MODÜL**: Orders
- **EKRAN**: Orders (/admin/orders/partial-shipment)
- **URL**: `/admin/orders/partial-shipment`
- **ELEMENT**: Route / Controller View
- **PROBLEM**: Ekran düzgün yüklenemedi veya HTTP hatası döndürdü
- **BEKLENEN**: HTTP 200 OK & Temiz View Render
- **GERÇEK**: HTTP 302
- **HTTP STATUS**: 302
- **SEVERITY**: **LOW**
- **REPRODUCE STEPS**:
  1. Browser ile /admin/orders/partial-shipment adresine git
  2. Sunucu yanıtını incele

---

