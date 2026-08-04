<?php
// Sidebar bileşeni — sadece bir kez render edilir.
// header.php'den include ile çağrılır; View sistemi tarafından tekrar çağrılmaz.
$rbac    = \Core\Application::getInstance()->getContainer()->get(\App\Services\RbacService::class);
$adminId = $_SESSION['admin_id'] ?? null;

// Role detection
$isAdminSuper = false;
$adminRoles = [];

if ($adminId) {
    $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
    $adminData = $db->query("SELECT is_super FROM admins WHERE id = :id LIMIT 1", [':id' => $adminId]);
    if (!empty($adminData) && $adminData[0]['is_super']) {
        $isAdminSuper = true;
    }
    
    $rolesData = $db->query(
        "SELECT r.name FROM roles r 
         JOIN admin_roles ar ON r.id = ar.role_id 
         WHERE ar.admin_id = :admin_id AND r.is_active = 1",
        [':admin_id' => $adminId]
    );
    $adminRoles = array_column($rolesData, 'name');
}

$isEditor = in_array('editor', $adminRoles, true);
$isDestek = in_array('customer_support', $adminRoles, true);

// Aktif URL tespiti (menü öğelerini vurgular)
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

function getSidebarActiveItem(string $currentUri): string {
    $currentUri = rtrim(str_replace('/SaintMonarc', '', $currentUri), '/');
    if ($currentUri === '/admin' || $currentUri === '/admin/dashboard') {
        return 'dashboard';
    }
    
    // Satış
    if (str_starts_with($currentUri, '/admin/orders/create')) return 'orders_create';
    if (str_starts_with($currentUri, '/admin/orders/dashboard')) return 'orders_dashboard';
    if (str_starts_with($currentUri, '/admin/orders/analytics')) return 'orders_analytics';
    if (str_starts_with($currentUri, '/admin/orders/packing')) return 'orders_packing';
    if (str_starts_with($currentUri, '/admin/orders/payment')) return 'orders_payment';
    if (str_starts_with($currentUri, '/admin/orders/kanban')) return 'orders_kanban';
    if (str_starts_with($currentUri, '/admin/orders/merge')) return 'orders_merge';
    if (str_starts_with($currentUri, '/admin/orders/print-center')) return 'orders_print_center';
    if (str_starts_with($currentUri, '/admin/orders/statuses')) return 'orders_statuses';
    if (str_starts_with($currentUri, '/admin/orders')) return 'orders_list';
    if (str_starts_with($currentUri, '/admin/promotions')) return 'promotions';
    if (str_starts_with($currentUri, '/admin/coupons')) return 'coupons';
    
    // Müşteriler
    if (str_starts_with($currentUri, '/admin/customers/create')) return 'customer_create';
    if (str_starts_with($currentUri, '/admin/customers/segments')) return 'customer_segments';
    if (str_starts_with($currentUri, '/admin/customers/groups')) return 'customer_groups';
    if (str_starts_with($currentUri, '/admin/customers/analytics')) return 'customer_analytics';
    if (str_starts_with($currentUri, '/admin/customers/profile') || 
        str_starts_with($currentUri, '/admin/customers/show') || 
        str_starts_with($currentUri, '/admin/customers/timeline') ||
        str_starts_with($currentUri, '/admin/customers/edit')) return 'customer_list';
    if (str_starts_with($currentUri, '/admin/customers')) return 'customer_list';

    // Ürün Yönetimi
    if (str_starts_with($currentUri, '/admin/products/create')) return 'product_create';
    if (str_starts_with($currentUri, '/admin/products/reports')) return 'product_reports';
    if (str_starts_with($currentUri, '/admin/products/import')) return 'product_import';
    if (str_starts_with($currentUri, '/admin/products/export')) return 'product_export';
    if (str_starts_with($currentUri, '/admin/products')) return 'products';
    if (str_starts_with($currentUri, '/admin/categories')) return 'categories';
    if (str_starts_with($currentUri, '/admin/brands')) return 'brands';
    if (str_starts_with($currentUri, '/admin/attributes')) return 'attributes';
    if (str_starts_with($currentUri, '/admin/variants')) return 'variants';
    if (str_starts_with($currentUri, '/admin/media')) return 'media';

    // Kargo
    if (str_starts_with($currentUri, '/admin/shipping/companies')) return 'shipping_companies';
    if (str_starts_with($currentUri, '/admin/shipping/shipments')) return 'shipping_shipments';
    if (str_starts_with($currentUri, '/admin/shipping/returns')) return 'shipping_returns';
    if (str_starts_with($currentUri, '/admin/shipping/reports')) return 'shipping_reports';
    if (str_starts_with($currentUri, '/admin/shipping')) return 'shipping_dashboard';

    // WMS (Warehouse Management System)
    if (str_starts_with($currentUri, '/admin/wms/dashboard')) return 'wms_dashboard';
    if (str_starts_with($currentUri, '/admin/wms/warehouses')) return 'wms_warehouses';
    if (str_starts_with($currentUri, '/admin/wms/locations')) return 'wms_locations';
    if (str_starts_with($currentUri, '/admin/wms/movements')) return 'wms_movements';
    if (str_starts_with($currentUri, '/admin/wms/picking')) return 'wms_picking';
    if (str_starts_with($currentUri, '/admin/wms/packing')) return 'wms_packing';
    if (str_starts_with($currentUri, '/admin/wms/transfers')) return 'wms_transfers';
    if (str_starts_with($currentUri, '/admin/wms/counts')) return 'wms_counts';
    if (str_starts_with($currentUri, '/admin/wms/analytics')) return 'wms_analytics';
    if (str_starts_with($currentUri, '/admin/wms/ai-assistant')) return 'wms_ai_assistant';
    
    // Procurement (Satın Alma)
    if (str_starts_with($currentUri, '/admin/purchasing/dashboard')) return 'purchasing_dashboard';
    if (str_starts_with($currentUri, '/admin/purchasing/suppliers')) return 'purchasing_suppliers';
    if (str_starts_with($currentUri, '/admin/purchasing/rfq')) return 'purchasing_rfq';
    if (str_starts_with($currentUri, '/admin/purchasing/orders')) return 'purchasing_orders';
    if (str_starts_with($currentUri, '/admin/purchasing/receipts')) return 'purchasing_receipts';
    if (str_starts_with($currentUri, '/admin/purchasing/payments')) return 'purchasing_payments';
    if (str_starts_with($currentUri, '/admin/purchasing/contracts')) return 'purchasing_contracts';
    if (str_starts_with($currentUri, '/admin/purchasing/ai-assistant')) return 'purchasing_ai';
    
    // Finans
    if (str_starts_with($currentUri, '/admin/revenues')) return 'finance_revenues';
    if (str_starts_with($currentUri, '/admin/expenses')) return 'finance_expenses';
    if (str_starts_with($currentUri, '/admin/invoices')) return 'finance_invoices';
    if (str_starts_with($currentUri, '/admin/accounts')) return 'finance_accounts';
    if (str_starts_with($currentUri, '/admin/reports/finance')) return 'finance_reports';
    if (str_starts_with($currentUri, '/admin/finance')) return 'finance_dashboard';
    
    // Sistem
    if (str_starts_with($currentUri, '/admin/roles')) return 'system_roles';
    if (str_starts_with($currentUri, '/admin/logs')) return 'system_logs';
    if (str_starts_with($currentUri, '/admin/workflows')) return 'system_workflows';
    if (str_starts_with($currentUri, '/admin/ai')) return 'system_ai';
    if (str_starts_with($currentUri, '/admin/seo')) return 'system_seo';
    if (str_starts_with($currentUri, '/admin/settings')) return 'system_settings';
    if (str_starts_with($currentUri, '/admin/themes')) return 'system_themes';
    if (str_starts_with($currentUri, '/admin/plugins')) return 'system_plugins';
    if (str_starts_with($currentUri, '/admin/components')) return 'system_components';
    if (str_starts_with($currentUri, '/admin/search')) return 'system_search';
    
    // Pazaryeri (VEYRA Platform)
    if (str_starts_with($currentUri, '/admin/marketplace/dashboard')) return 'marketplace_dashboard';
    if (str_starts_with($currentUri, '/admin/marketplace/applications')) return 'marketplace_applications';
    if (str_starts_with($currentUri, '/admin/marketplace/moderation')) return 'marketplace_moderation';
    if (str_starts_with($currentUri, '/admin/marketplace/payouts')) return 'marketplace_payouts';
    if (str_starts_with($currentUri, '/admin/vendors/reports')) return 'vendor_reports';
    if (str_starts_with($currentUri, '/admin/vendors/payments')) return 'vendor_payments';
    if (str_starts_with($currentUri, '/admin/vendors/wallet')) return 'vendor_wallet';
    if (str_starts_with($currentUri, '/admin/vendors')) return 'vendors';
    
    // Pazarlama & CMS
    if (str_starts_with($currentUri, '/admin/pages')) return 'cms_pages';
    if (str_starts_with($currentUri, '/admin/recommendations')) return 'ai_recommendations';
    
    return '';
}

$activeItem = getSidebarActiveItem($currentUri);

// Navigation menu definition
$menus = [
    'sales' => [
        'id' => 'menu-satis',
        'title' => 'Satış',
        'icon' => 'bi-cart-fill',
        'items' => [
            [
                'type' => 'link',
                'id' => 'orders_dashboard',
                'title' => 'Executive Dashboard',
                'url' => '/admin/orders/dashboard',
                'permission' => 'view_orders',
                'icon' => 'bi-grid-1x2'
            ],
            [
                'type' => 'link',
                'id' => 'orders_list',
                'title' => 'Sipariş Yönetimi',
                'url' => '/admin/orders',
                'permission' => 'view_orders',
                'icon' => 'bi-cart3'
            ],
            [
                'type' => 'link',
                'id' => 'orders_create',
                'title' => 'Yeni Sipariş Oluştur',
                'url' => '/admin/orders/create',
                'permission' => 'create_orders',
                'icon' => 'bi-cart-plus'
            ],
            [
                'type' => 'link',
                'id' => 'orders_analytics',
                'title' => 'Sipariş Analitiği',
                'url' => '/admin/orders/analytics',
                'permission' => 'view_orders',
                'icon' => 'bi-graph-up'
            ],
            [
                'type' => 'link',
                'id' => 'orders_packing',
                'title' => 'Paketleme Merkezi',
                'url' => '/admin/orders/packing',
                'permission' => 'manage_orders',
                'icon' => 'bi-box-seam'
            ],
            [
                'type' => 'link',
                'id' => 'orders_payment',
                'title' => 'Ödemeler',
                'url' => '/admin/orders/payment',
                'permission' => 'view_orders',
                'icon' => 'bi-credit-card-fill'
            ],
            [
                'type' => 'link',
                'id' => 'orders_kanban',
                'title' => 'Sipariş Kanban',
                'url' => '/admin/orders/kanban',
                'permission' => 'view_orders',
                'icon' => 'bi-columns-gap'
            ],
            [
                'type' => 'link',
                'id' => 'orders_merge',
                'title' => 'Sipariş Birleştirme',
                'url' => '/admin/orders/merge',
                'permission' => 'manage_orders',
                'icon' => 'bi-union'
            ],
            [
                'type' => 'link',
                'id' => 'orders_print_center',
                'title' => 'Yazdırma Merkezi',
                'url' => '/admin/orders/print-center',
                'permission' => 'view_orders',
                'icon' => 'bi-printer-fill'
            ],
            [
                'type' => 'link',
                'id' => 'shipping_returns',
                'title' => 'İadeler',
                'url' => '/admin/shipping/returns',
                'permission' => 'manage_returns',
                'icon' => 'bi-arrow-return-left'
            ],
            [
                'type' => 'group',
                'id' => 'menu-kampanyalar',
                'title' => 'Kampanyalar',
                'icon' => 'bi-tag-fill',
                'items' => [
                    [
                        'id' => 'promotions',
                        'title' => 'Promosyon Motoru',
                        'url' => '/admin/promotions',
                        'permission' => 'view_promotions',
                        'icon' => 'bi-percent'
                    ],
                    [
                        'id' => 'coupons',
                        'title' => 'Kupon Yönetimi',
                        'url' => '/admin/coupons',
                        'permission' => 'coupon_management',
                        'icon' => 'bi-ticket-perforated'
                    ],
                    [
                        'id' => 'promotions_calendar',
                        'title' => 'Kampanya Takvimi',
                        'url' => '/admin/promotions/calendar',
                        'permission' => 'view_promotions',
                        'icon' => 'bi-calendar3'
                    ],
                    [
                        'id' => 'promotions_reports',
                        'title' => 'Kampanya Raporları',
                        'url' => '/admin/promotions/reports',
                        'permission' => 'promotion_reports',
                        'icon' => 'bi-bar-chart-line'
                    ],
                    [
                        'id' => 'promotions_preview',
                        'title' => 'Önizleme Aracı',
                        'url' => '/admin/promotions/preview',
                        'permission' => 'promotion_preview',
                        'icon' => 'bi-eye'
                    ]
                ]
            ],
            [
                'type' => 'link',
                'id' => 'orders_statuses',
                'title' => 'Sipariş Durumları',
                'url' => '/admin/orders/statuses',
                'permission' => 'manage_orders',
                'icon' => 'bi-gear'
            ]
        ]
    ],
    'customers' => [
        'id' => 'menu-musteriler',
        'title' => 'Müşteriler',
        'icon' => 'bi-people-fill',
        'items' => [
            [
                'type' => 'link',
                'id' => 'customer_list',
                'title' => 'Müşteri Listesi',
                'url' => '/admin/customers',
                'permission' => 'view_customers',
                'icon' => 'bi-person-lines-fill'
            ],
            [
                'type' => 'link',
                'id' => 'customer_create',
                'title' => 'Yeni Müşteri Ekle',
                'url' => '/admin/customers/create',
                'permission' => 'create_customers',
                'icon' => 'bi-person-plus'
            ],
            [
                'type' => 'link',
                'id' => 'customer_segments',
                'title' => 'Segmentler',
                'url' => '/admin/customers/segments',
                'permission' => 'customer_segments',
                'icon' => 'bi-funnel'
            ],
            [
                'type' => 'link',
                'id' => 'customer_groups',
                'title' => 'Sadakat Programı',
                'url' => '/admin/customers/groups',
                'permission' => 'view_customers',
                'icon' => 'bi-award'
            ],
            [
                'type' => 'link',
                'id' => 'customer_profile',
                'title' => 'Customer 360',
                'url' => '/admin/customers/profile?id=1',
                'permission' => 'view_customers',
                'icon' => 'bi-person-bounding-box'
            ],
            [
                'type' => 'link',
                'id' => 'customer_analytics',
                'title' => 'Analitik',
                'url' => '/admin/customers/analytics',
                'permission' => 'view_customers',
                'icon' => 'bi-bar-chart-line'
            ]
        ]
    ],
    'products' => [
        'id' => 'menu-urunler',
        'title' => 'Ürün Yönetimi',
        'icon' => 'bi-box-seam-fill',
        'items' => [
            [
                'type' => 'link',
                'id' => 'products',
                'title' => 'Ürünler',
                'url' => '/admin/products',
                'permission' => 'view_products',
                'icon' => 'bi-box-seam'
            ],
            [
                'type' => 'link',
                'id' => 'product_create',
                'title' => 'Yeni Ürün Ekle',
                'url' => '/admin/products/create',
                'permission' => 'create_products',
                'icon' => 'bi-plus-circle'
            ],
            [
                'type' => 'link',
                'id' => 'categories',
                'title' => 'Kategoriler',
                'url' => '/admin/categories',
                'permission' => 'view_categories',
                'icon' => 'bi-tags'
            ],
            [
                'type' => 'link',
                'id' => 'brands',
                'title' => 'Markalar',
                'url' => '/admin/brands',
                'permission' => 'view_brands',
                'icon' => 'bi-award'
            ],
            [
                'type' => 'link',
                'id' => 'attributes',
                'title' => 'Özellikler',
                'url' => '/admin/attributes',
                'permission' => 'view_attributes',
                'icon' => 'bi-list-stars'
            ],
            [
                'type' => 'link',
                'id' => 'variants',
                'title' => 'Varyantlar',
                'url' => '/admin/variants',
                'permission' => 'manage_variants',
                'icon' => 'bi-sliders'
            ],
            [
                'type' => 'link',
                'id' => 'media',
                'title' => 'Medya Kütüphanesi',
                'url' => '/admin/media',
                'permission' => 'view_media',
                'icon' => 'bi-images'
            ],
            [
                'type' => 'group',
                'id' => 'menu-pim-import-export',
                'title' => 'Import / Export',
                'icon' => 'bi-arrow-down-up',
                'items' => [
                    [
                        'id' => 'product_import',
                        'title' => 'İçe Aktar (Import)',
                        'url' => '/admin/products/import/mapping',
                        'permission' => 'import_products',
                        'icon' => 'bi-file-earmark-arrow-up'
                    ],
                    [
                        'id' => 'product_export',
                        'title' => 'Dışa Aktar (Export)',
                        'url' => '/admin/products/export',
                        'permission' => 'export_products',
                        'icon' => 'bi-file-earmark-arrow-down'
                    ]
                ]
            ],
            [
                'type' => 'link',
                'id' => 'product_reports',
                'title' => 'Ürün Raporları',
                'url' => '/admin/products/reports',
                'permission' => 'audit_products',
                'icon' => 'bi-file-bar-graph'
            ],
            [
                'type' => 'link',
                'id' => 'ai_recommendations',
                'title' => 'AI Önerileri',
                'url' => '/admin/recommendations',
                'permission' => 'ai_recommendations',
                'icon' => 'bi-stars'
            ]
        ]
    ],
    'shipping' => [
        'id' => 'menu-kargo',
        'title' => 'Kargo',
        'icon' => 'bi-truck-flatbed',
        'items' => [
            [
                'type' => 'link',
                'id' => 'shipping_dashboard',
                'title' => 'Kargo Paneli',
                'url' => '/admin/shipping',
                'permission' => 'view_shipping',
                'icon' => 'bi-speedometer2'
            ],
            [
                'type' => 'link',
                'id' => 'shipping_companies',
                'title' => 'Kargo Firmaları',
                'url' => '/admin/shipping/companies',
                'permission' => 'manage_shipping_companies',
                'icon' => 'bi-building'
            ],
            [
                'type' => 'link',
                'id' => 'shipping_shipments',
                'title' => 'Gönderiler',
                'url' => '/admin/shipping/shipments',
                'permission' => 'manage_shipping',
                'icon' => 'bi-box-seam'
            ],
            [
                'type' => 'link',
                'id' => 'shipping_tracking',
                'title' => 'Takip (OMS)',
                'url' => '/admin/orders/shipping',
                'permission' => 'manage_shipments',
                'icon' => 'bi-geo-alt-fill'
            ],
            [
                'type' => 'link',
                'id' => 'shipping_returns',
                'title' => 'İade Kargoları',
                'url' => '/admin/shipping/returns',
                'permission' => 'manage_returns',
                'icon' => 'bi-arrow-return-left'
            ],
            [
                'type' => 'link',
                'id' => 'shipping_reports',
                'title' => 'Kargo Raporları',
                'url' => '/admin/shipping/reports',
                'permission' => 'shipping_reports',
                'icon' => 'bi-file-earmark-bar-graph'
            ]
        ]
    ],
    'wms' => [
        'id' => 'menu-depo',
        'title' => 'Depo Yönetimi (WMS)',
        'icon' => 'bi-houses-fill',
        'items' => [
            [
                'type' => 'link',
                'id' => 'wms_dashboard',
                'title' => 'Dashboard',
                'url' => '/admin/wms/dashboard',
                'permission' => 'view_wms',
                'icon' => 'bi-speedometer2'
            ],
            [
                'type' => 'link',
                'id' => 'wms_warehouses',
                'title' => 'Depolar',
                'url' => '/admin/wms/warehouses',
                'permission' => 'view_wms',
                'icon' => 'bi-house-gear'
            ],
            [
                'type' => 'link',
                'id' => 'wms_locations',
                'title' => 'Lokasyonlar (Raf)',
                'url' => '/admin/wms/locations',
                'permission' => 'manage_locations',
                'icon' => 'bi-grid-3x3-gap'
            ],
            [
                'type' => 'link',
                'id' => 'wms_movements',
                'title' => 'Stok Hareketleri',
                'url' => '/admin/wms/movements',
                'permission' => 'view_wms',
                'icon' => 'bi-arrow-down-up'
            ],
            [
                'type' => 'link',
                'id' => 'wms_picking',
                'title' => 'Picking (Toplama)',
                'url' => '/admin/wms/picking',
                'permission' => 'view_wms',
                'icon' => 'bi-hand-index-thumb'
            ],
            [
                'type' => 'link',
                'id' => 'wms_packing',
                'title' => 'Packing (Paketleme)',
                'url' => '/admin/wms/packing',
                'permission' => 'view_wms',
                'icon' => 'bi-box'
            ],
            [
                'type' => 'link',
                'id' => 'wms_transfers',
                'title' => 'Transferler',
                'url' => '/admin/wms/transfers',
                'permission' => 'manage_transfers',
                'icon' => 'bi-arrow-left-right'
            ],
            [
                'type' => 'link',
                'id' => 'wms_counts',
                'title' => 'Sayım',
                'url' => '/admin/wms/counts',
                'permission' => 'manage_counts',
                'icon' => 'bi-calculator'
            ],
            [
                'type' => 'link',
                'id' => 'wms_analytics',
                'title' => 'Analitik',
                'url' => '/admin/wms/analytics',
                'permission' => 'wms_analytics',
                'icon' => 'bi-bar-chart-line'
            ],
            [
                'type' => 'link',
                'id' => 'wms_ai_assistant',
                'title' => 'AI Asistan',
                'url' => '/admin/wms/ai-assistant',
                'permission' => 'view_wms',
                'icon' => 'bi-stars'
            ]
        ]
    ],
    'purchasing' => [
        'id' => 'menu-purchasing',
        'title' => 'Satın Alma',
        'icon' => 'bi-box-seam-fill',
        'items' => [
            [
                'type' => 'link',
                'id' => 'purchasing_dashboard',
                'title' => 'Dashboard',
                'url' => '/admin/purchasing/dashboard',
                'permission' => 'view_procurement',
                'icon' => 'bi-speedometer2'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_suppliers',
                'title' => 'Tedarikçiler',
                'url' => '/admin/purchasing/suppliers',
                'permission' => 'manage_suppliers',
                'icon' => 'bi-people'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_rfq',
                'title' => 'Teklifler (RFQ)',
                'url' => '/admin/purchasing/rfq',
                'permission' => 'manage_rfq',
                'icon' => 'bi-chat-left-quote'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_orders',
                'title' => 'Satın Alma Siparişleri',
                'url' => '/admin/purchasing/orders',
                'permission' => 'view_procurement',
                'icon' => 'bi-cart-check'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_receipts',
                'title' => 'Mal Kabul',
                'url' => '/admin/purchasing/receipts',
                'permission' => 'receive_goods',
                'icon' => 'bi-box-arrow-in-down'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_payments',
                'title' => 'Ödemeler',
                'url' => '/admin/purchasing/payments',
                'permission' => 'view_procurement',
                'icon' => 'bi-cash-coin'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_contracts',
                'title' => 'Sözleşmeler',
                'url' => '/admin/purchasing/contracts',
                'permission' => 'manage_supplier_contracts',
                'icon' => 'bi-file-earmark-text'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_documents',
                'title' => 'Belgeler',
                'url' => '/admin/purchasing/suppliers',
                'permission' => 'manage_suppliers',
                'icon' => 'bi-folder2-open'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_analytics',
                'title' => 'Analitik',
                'url' => '/admin/purchasing/dashboard',
                'permission' => 'view_purchase_analytics',
                'icon' => 'bi-bar-chart-line'
            ],
            [
                'type' => 'link',
                'id' => 'purchasing_ai',
                'title' => 'AI Satın Alma',
                'url' => '/admin/purchasing/ai-assistant',
                'permission' => 'view_procurement',
                'icon' => 'bi-stars'
            ]
        ]
    ],
    'finance' => [
        'id' => 'menu-finans',
        'title' => 'Finans',
        'icon' => 'bi-wallet2',
        'items' => [
            [
                'type' => 'link',
                'id' => 'finance_dashboard',
                'title' => 'Finans Paneli',
                'url' => '/admin/finance',
                'permission' => 'view_finance',
                'icon' => 'bi-speedometer2'
            ],
            [
                'type' => 'link',
                'id' => 'finance_revenues',
                'title' => 'Gelir',
                'url' => '/admin/revenues',
                'permission' => 'manage_revenues',
                'icon' => 'bi-graph-up-arrow'
            ],
            [
                'type' => 'link',
                'id' => 'finance_expenses',
                'title' => 'Gider',
                'url' => '/admin/expenses',
                'permission' => 'manage_expenses',
                'icon' => 'bi-graph-down-arrow'
            ],
            [
                'type' => 'link',
                'id' => 'finance_invoices',
                'title' => 'Muhasebe (Faturalar)',
                'url' => '/admin/invoices',
                'permission' => 'manage_invoices',
                'icon' => 'bi-receipt'
            ],
            [
                'type' => 'link',
                'id' => 'finance_accounts',
                'title' => 'Cari Hesaplar',
                'url' => '/admin/accounts',
                'permission' => 'manage_accounts',
                'icon' => 'bi-journal-text'
            ],
            [
                'type' => 'link',
                'id' => 'finance_reports',
                'title' => 'Finans Raporları',
                'url' => '/admin/reports/finance',
                'permission' => 'financial_reports',
                'icon' => 'bi-file-earmark-ruled'
            ]
        ]
    ],
    'vendors' => [
        'id' => 'menu-pazaryeri',
        'title' => 'Pazaryeri Platform',
        'icon' => 'bi-shop',
        'items' => [
            [
                'type' => 'link',
                'id' => 'marketplace_dashboard',
                'title' => 'Pazaryeri Genel Bakış',
                'url' => '/admin/marketplace/dashboard',
                'permission' => 'view_marketplace',
                'icon' => 'bi-speedometer2'
            ],
            [
                'type' => 'link',
                'id' => 'marketplace_applications',
                'title' => 'Satıcı Başvuruları',
                'url' => '/admin/marketplace/applications',
                'permission' => 'approve_vendors',
                'icon' => 'bi-person-plus'
            ],
            [
                'type' => 'link',
                'id' => 'marketplace_moderation',
                'title' => 'Ürün Moderasyonu',
                'url' => '/admin/marketplace/moderation',
                'permission' => 'moderate_products',
                'icon' => 'bi-shield-check'
            ],
            [
                'type' => 'link',
                'id' => 'vendors',
                'title' => 'Tüm Satıcılar',
                'url' => '/admin/vendors',
                'permission' => 'view_vendors',
                'icon' => 'bi-people'
            ],
            [
                'type' => 'link',
                'id' => 'marketplace_payouts',
                'title' => 'Hakediş Ödemeleri',
                'url' => '/admin/marketplace/payouts',
                'permission' => 'view_platform_finance',
                'icon' => 'bi-cash-coin'
            ],
            [
                'type' => 'link',
                'id' => 'vendor_wallet',
                'title' => 'Satıcı Cüzdanları',
                'url' => '/admin/vendors/wallet',
                'permission' => 'vendor_wallet',
                'icon' => 'bi-wallet'
            ],
            [
                'type' => 'link',
                'id' => 'vendor_reports',
                'title' => 'Satıcı Analitiği',
                'url' => '/admin/vendors/reports',
                'permission' => 'vendor_reports',
                'icon' => 'bi-bar-chart-line'
            ]
        ]
    ],
    'system' => [
        'id' => 'menu-sistem',
        'title' => 'Sistem',
        'icon' => 'bi-sliders',
        'items' => [
            [
                'type' => 'link',
                'id' => 'admin_users',
                'title' => 'Yönetici Kullanıcıları',
                'url' => '/admin/users',
                'permission' => 'manage_users',
                'icon' => 'bi-people'
            ],
            [
                'type' => 'link',
                'id' => 'system_roles',
                'title' => 'Roller ve Yetkiler',
                'url' => '/admin/roles',
                'permission' => 'manage_users',
                'icon' => 'bi-shield-lock'
            ],
            [
                'type' => 'link',
                'id' => 'system_logs',
                'title' => 'Sistem Logları',
                'url' => '/admin/logs',
                'permission' => 'view_logs',
                'icon' => 'bi-list-stars'
            ],
            [
                'type' => 'group',
                'id' => 'menu-system-workflows',
                'title' => 'Workflow',
                'icon' => 'bi-diagram-3',
                'items' => [
                    [
                        'id' => 'system_workflows',
                        'title' => 'İş Akışları',
                        'url' => '/admin/workflows',
                        'permission' => 'view_workflows',
                        'icon' => 'bi-diagram-3-fill'
                    ],
                    [
                        'id' => 'system_workflows_templates',
                        'title' => 'Akış Şablonları',
                        'url' => '/admin/workflows/templates',
                        'permission' => 'workflow_templates',
                        'icon' => 'bi-layout-text-window-reverse'
                    ],
                    [
                        'id' => 'system_workflows_history',
                        'title' => 'Çalışma Geçmişi',
                        'url' => '/admin/workflows/history',
                        'permission' => 'workflow_reports',
                        'icon' => 'bi-clock-history'
                    ],
                    [
                        'id' => 'system_workflows_logs',
                        'title' => 'Akış Logları',
                        'url' => '/admin/workflows/logs',
                        'permission' => 'workflow_logs',
                        'icon' => 'bi-journal-code'
                    ]
                ]
            ],
            [
                'type' => 'link',
                'id' => 'system_ai',
                'title' => 'Yapay Zekâ (AI)',
                'url' => '/admin/ai',
                'permission' => 'manage_ai',
                'icon' => 'bi-cpu'
            ],
            [
                'type' => 'link',
                'id' => 'system_seo',
                'title' => 'SEO Ayarları',
                'url' => '/admin/seo',
                'permission' => 'manage_seo',
                'icon' => 'bi-search-heart'
            ],
            [
                'type' => 'link',
                'id' => 'system_search',
                'title' => 'Arama Motoru (PIM)',
                'url' => '/admin/search',
                'permission' => 'view_search',
                'icon' => 'bi-search'
            ],
            [
                'type' => 'link',
                'id' => 'cms_pages',
                'title' => 'İçerik Yönetimi (CMS)',
                'url' => '/admin/pages',
                'permission' => 'manage_cms',
                'icon' => 'bi-file-earmark-text'
            ],
            [
                'type' => 'link',
                'id' => 'system_settings',
                'title' => 'Genel Ayarlar',
                'url' => '/admin/settings',
                'permission' => 'manage_settings',
                'icon' => 'bi-sliders'
            ],
            [
                'type' => 'link',
                'id' => 'system_themes',
                'title' => 'Tema Yönetimi',
                'url' => '/admin/themes',
                'permission' => 'manage_themes',
                'icon' => 'bi-palette'
            ],
            [
                'type' => 'link',
                'id' => 'system_plugins',
                'title' => 'Eklenti Yönetimi',
                'url' => '/admin/plugins',
                'permission' => 'manage_plugins',
                'icon' => 'bi-plugin'
            ],
            [
                'type' => 'link',
                'id' => 'system_components',
                'title' => 'Design System',
                'url' => '/SaintMonarc/admin/components',
                'permission' => 'manage_settings',
                'icon' => 'bi-cpu-fill'
            ]
        ]
    ]
];

// Open collapsible parents based on active items
$openCategory = null;
$openSubgroup = null;

foreach ($menus as $catKey => $cat) {
    foreach ($cat['items'] as $item) {
        if ($item['type'] === 'link' && $item['id'] === $activeItem) {
            $openCategory = $cat['id'];
            break;
        } elseif ($item['type'] === 'group') {
            foreach ($item['items'] as $subItem) {
                if ($subItem['id'] === $activeItem) {
                    $openCategory = $cat['id'];
                    $openSubgroup = $item['id'];
                    break 2;
                }
            }
        }
    }
}

// Function to check if current user can see an item
$canSeeItem = function(array $item) use ($rbac, $adminId, $isAdminSuper, $isEditor, $isDestek): bool {
    if ($isAdminSuper) {
        return true;
    }
    
    // Editor role restrictions: only allow content items
    if ($isEditor) {
        $contentIds = [
            'products', 'categories', 'brands', 'attributes', 'variants', 'media', 
            'product_import', 'product_export', 'product_reports',
            'cms_pages', 'system_seo'
        ];
        if ($item['type'] === 'link' && !in_array($item['id'], $contentIds, true)) {
            return false;
        }
        if ($item['type'] === 'group') {
            $hasContent = false;
            foreach ($item['items'] as $sub) {
                if (in_array($sub['id'], $contentIds, true)) {
                    $hasContent = true;
                    break;
                }
            }
            if (!$hasContent) return false;
        }
    }
    
    // Destek role restrictions: only allow sales and customer items
    if ($isDestek) {
        $destekIds = [
            'orders_dashboard', 'orders_list', 'orders_analytics', 'orders_packing', 'orders_payment', 'orders_statuses', 'shipping_returns',
            'customer_list', 'customer_segments', 'customer_groups', 'customer_profile', 'customer_analytics'
        ];
        if ($item['type'] === 'link' && !in_array($item['id'], $destekIds, true)) {
            return false;
        }
        if ($item['type'] === 'group') {
            $hasDestek = false;
            foreach ($item['items'] as $sub) {
                if (in_array($sub['id'], $destekIds, true)) {
                    $hasDestek = true;
                    break;
                }
            }
            if (!$hasDestek) return false;
        }
    }
    
    // Default permission checking
    if ($item['type'] === 'link') {
        return $rbac->adminHasPermission((int)$adminId, $item['permission']);
    } elseif ($item['type'] === 'group') {
        foreach ($item['items'] as $subItem) {
            if ($rbac->adminHasPermission((int)$adminId, $subItem['permission'])) {
                return true;
            }
        }
        return false;
    }
    return false;
};

// Filter categories to hide empty ones
$filteredMenus = [];
foreach ($menus as $catKey => $cat) {
    if ($isEditor && !in_array($catKey, ['products', 'system'], true)) {
        continue;
    }
    if ($isDestek && !in_array($catKey, ['sales', 'customers'], true)) {
        continue;
    }
    
    $filteredItems = [];
    foreach ($cat['items'] as $item) {
        if ($canSeeItem($item)) {
            if ($item['type'] === 'group') {
                $filteredSubItems = [];
                foreach ($item['items'] as $subItem) {
                    if ($isAdminSuper || $rbac->adminHasPermission((int)$adminId, $subItem['permission'])) {
                        if ($isEditor && !in_array($subItem['id'], ['cms_pages', 'system_seo', 'product_import', 'product_export'], true)) {
                            continue;
                        }
                        if ($isDestek && !in_array($subItem['id'], ['promotions', 'coupons'], true)) {
                            continue;
                        }
                        $filteredSubItems[] = $subItem;
                    }
                }
                if (!empty($filteredSubItems)) {
                    $item['items'] = $filteredSubItems;
                    $filteredItems[] = $item;
                }
            } else {
                $filteredItems[] = $item;
            }
        }
    }
    if (!empty($filteredItems)) {
        $cat['items'] = $filteredItems;
        $filteredMenus[$catKey] = $cat;
    }
}
?>

<style>
/* Custom nested menu styles for multi-level sidebar */
#sidebar-wrapper .list-group-item.nested-item {
    padding-left: 40px;
    font-size: 13px;
    background-color: rgba(0, 0, 0, 0.1);
}
#sidebar-wrapper .list-group-item.nested-item:hover,
#sidebar-wrapper .list-group-item.nested-item.active {
    padding-left: 44px;
    color: var(--sm-gold);
    background-color: rgba(255, 255, 255, 0.02);
}
.nested-category {
    font-size: 13px !important;
    padding: 10px 24px 10px 32px !important;
    color: #94a3b8 !important;
    font-weight: 500 !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: color 0.2s ease;
}
.nested-category:hover {
    color: var(--sm-gold) !important;
    background-color: rgba(255, 255, 255, 0.01);
}
.nested-category .bi-chevron-down {
    transition: transform 0.2s ease;
    font-size: 8px;
}
.nested-category[aria-expanded="false"] .bi-chevron-down {
    transform: rotate(-90deg);
}
.sidebar-submenu-bg {
    background-color: rgba(0, 0, 0, 0.12);
}
.sidebar-subsubmenu-bg {
    background-color: rgba(0, 0, 0, 0.2);
}
#sidebar-wrapper .menu-category:not(.collapsed) {
    color: var(--sm-gold);
    border-left: 2px solid var(--sm-gold);
    padding-left: 22px;
}
#sidebar-wrapper .nested-category:not(.collapsed) {
    color: var(--sm-gold) !important;
}
</style>

<div id="sidebar-wrapper">
    <div class="sidebar-heading">SAINTMONARC</div>
    <div class="list-group list-group-flush">

        <!-- Dashboard -->
        <a href="<?= url('/admin') ?>" class="list-group-item<?= ($activeItem === 'dashboard') ? ' active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <?php foreach ($filteredMenus as $catKey => $cat): 
            $isCatOpen = ($openCategory === $cat['id']);
        ?>
            <!-- <?= htmlspecialchars($cat['title']) ?> -->
            <a href="#<?= $cat['id'] ?>" class="menu-category text-decoration-none<?= $isCatOpen ? '' : ' collapsed' ?>"
               data-bs-toggle="collapse" role="button"
               aria-expanded="<?= $isCatOpen ? 'true' : 'false' ?>" aria-controls="<?= $cat['id'] ?>">
                <span><?= htmlspecialchars($cat['title']) ?></span>
                <i class="bi bi-chevron-down"></i>
            </a>
            <div class="collapse<?= $isCatOpen ? ' show' : '' ?>" id="<?= $cat['id'] ?>">
                <div class="sidebar-submenu-bg">
                    <?php foreach ($cat['items'] as $item): ?>
                        <?php if ($item['type'] === 'link'): ?>
                            <a href="<?= url($item['url']) ?>" class="list-group-item<?= ($activeItem === $item['id']) ? ' active' : '' ?>">
                                <i class="<?= $item['icon'] ?>"></i> <?= htmlspecialchars($item['title']) ?>
                            </a>
                        <?php elseif ($item['type'] === 'group'): 
                            $isGroupOpen = ($openSubgroup === $item['id']);
                        ?>
                            <!-- Collapsible Sub-group (Double-nested collapse) -->
                            <a href="#<?= $item['id'] ?>" class="list-group-item nested-category text-decoration-none<?= $isGroupOpen ? '' : ' collapsed' ?>"
                               data-bs-toggle="collapse" role="button"
                               aria-expanded="<?= $isGroupOpen ? 'true' : 'false' ?>" aria-controls="<?= $item['id'] ?>">
                                <span><i class="<?= $item['icon'] ?>"></i> <?= htmlspecialchars($item['title']) ?></span>
                                <i class="bi bi-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse<?= $isGroupOpen ? ' show' : '' ?> sidebar-subsubmenu-bg" id="<?= $item['id'] ?>">
                                <?php foreach ($item['items'] as $subItem): ?>
                                    <a href="<?= url($subItem['url']) ?>" class="list-group-item nested-item<?= ($activeItem === $subItem['id']) ? ' active' : '' ?>">
                                        <i class="<?= $subItem['icon'] ?>"></i> <?= htmlspecialchars($subItem['title']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>
