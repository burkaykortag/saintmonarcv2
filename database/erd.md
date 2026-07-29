# SaintMonarc Varlık İlişki Diyagramı (ERD)

Bu belge, SaintMonarc e-ticaret platformunun veritabanı şemasını görselleştiren Mermaid.js diyagramlarını içermektedir. Büyük şemayı daha anlaşılır kılmak için mantıksal modüllere bölünmüştür.

## 1. Çekirdek ve Kullanıcı Yönetimi (Core & Users)

```mermaid
erDiagram
    users ||--|| user_profiles : "sahiptir"
    users }|--o| customer_groups : "üyedir"
    admins }|--o| admin_roles : "atanmıştır"
    roles ||--o{ admin_roles : "sahiptir"
    roles ||--o{ role_permissions : "yetkilendirir"
    permissions ||--o{ role_permissions : "verilir"
    users ||--o{ sessions : "açabilir"
    admins ||--o{ sessions : "açabilir"

    users {
        bigint id PK
        string email UK
        string password
        string status
        timestamp created_at
    }
    user_profiles {
        bigint id PK
        bigint user_id FK
        string first_name
        string last_name
        string phone
    }
    customer_groups {
        bigint id PK
        string name UK
        decimal discount_rate
    }
    admins {
        bigint id PK
        string username UK
        string email UK
        string password
        boolean is_super
    }
    roles {
        bigint id PK
        string name UK
        string description
    }
    permissions {
        bigint id PK
        string name UK
        string description
    }
```

## 2. Ürün ve Envanter Yönetimi (Products & Inventory)

```mermaid
erDiagram
    products }|--o| brands : "üretir"
    products ||--o{ product_translations : "çevrilir"
    products ||--o{ product_category_relations : "ait"
    categories ||--o{ product_category_relations : "içerir"
    categories ||--o{ category_translations : "çevrilir"
    products ||--o{ product_variants : "varyant_oluşturur"
    product_variants ||--o{ product_variant_option_values : "seçeneği"
    attribute_values ||--o{ product_variant_option_values : "değeri"
    attributes ||--o{ attribute_values : "içerir"
    products ||--o{ inventories : "stoklanır"
    product_variants ||--o{ inventories : "stoklanır"
    inventories ||--o{ inventory_movements : "hareket_görür"

    products {
        bigint id PK
        bigint brand_id FK
        string sku UK
        string slug UK
        decimal price
    }
    product_variants {
        bigint id PK
        bigint product_id FK
        string sku UK
        decimal price
        decimal weight
    }
    categories {
        bigint id PK
        bigint parent_id FK
        string slug UK
    }
    inventories {
        bigint id PK
        bigint product_id FK
        bigint variant_id FK
        int stock
        int reserved_stock
    }
    inventory_movements {
        bigint id PK
        bigint inventory_id FK
        int quantity
        string type
    }
```

## 3. Sipariş ve Satış Yönetimi (Shopping & Orders)

```mermaid
erDiagram
    users ||--o{ orders : "verir"
    orders ||--o{ order_items : "barındırır"
    order_items ||--o| products : "referans"
    order_items ||--o| product_variants : "referans"
    orders ||--o{ order_status_history : "izlenir"
    orders ||--|| invoices : "faturalandırılır"
    orders ||--o{ refunds : "iade_edilir"
    orders ||--o{ payment_transactions : "ödenir"
    orders ||--o{ shipments : "gönderilir"
    shipments ||--o{ shipment_tracking : "takip_edilir"

    orders {
        bigint id PK
        string order_number UK
        bigint user_id FK
        string status
        decimal grand_total
    }
    order_items {
        bigint id PK
        bigint order_id FK
        bigint product_id FK
        bigint variant_id FK
        int quantity
        decimal price
    }
    invoices {
        bigint id PK
        bigint order_id FK
        string invoice_number UK
        date issue_date
    }
    payment_transactions {
        bigint id PK
        bigint order_id FK
        string transaction_reference UK
        decimal amount
        string status
    }
    shipments {
        bigint id PK
        bigint order_id FK
        string tracking_number
        string status
    }
```

## 4. Yasal Uyum ve KVKK (Legal Compliance)

```mermaid
erDiagram
    users ||--o{ user_consents : "onaylar"
    legal_documents ||--o{ user_consents : "belgeler"

    legal_documents {
        bigint id PK
        string type UK
        string version
        longtext content
    }
    user_consents {
        bigint id PK
        bigint user_id FK
        bigint legal_document_id FK
        string accepted_version
        string ip_address
        string user_agent
        timestamp accepted_at
    }
```
