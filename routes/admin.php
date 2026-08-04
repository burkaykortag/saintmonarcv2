<?php

declare(strict_types=1);

// $router is loaded in public/index.php

use App\Controllers\AdminAuthController;
use App\Controllers\RoleController;
use App\Controllers\AdminUserController;
use App\Controllers\AdminDashboardController;
use App\Controllers\MediaController;
use App\Controllers\CategoryController;
use App\Controllers\BrandController;
use App\Controllers\ProductController;
use App\Controllers\ProductReportController;
use App\Controllers\AttributeController;
use App\Controllers\VariantController;
use App\Controllers\CustomerController;
use App\Controllers\PromotionController;
use App\Controllers\AiRecommendationController;
use App\Controllers\SearchController;
use App\Controllers\VendorController;
use App\Controllers\MarketplaceAdminController;
use App\Controllers\FinanceController;
use App\Controllers\ShippingController;
use App\Controllers\WorkflowController;
use App\Controllers\OrderController;
use App\Controllers\WarehouseController;
use App\Controllers\ProcurementController;


// Guest Routes
$router->get('/admin/login', [AdminAuthController::class, 'showLogin'], ['guest']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/logout', [AdminAuthController::class, 'logout']);

// Admin Dashboard (Requires admin authentication)
$router->get('/admin', [AdminDashboardController::class, 'index'], ['admin']);
$router->get('/admin/dashboard', [AdminDashboardController::class, 'index'], ['admin']);
$router->get('/admin/components', [AdminDashboardController::class, 'components'], ['admin']);

// RBAC Management Routes (Protected by admin auth and specific manage_users permission)
$router->get('/admin/roles', [RoleController::class, 'index'], ['admin', 'permission:manage_users']);
$router->get('/admin/roles/create', [RoleController::class, 'showCreate'], ['admin', 'permission:manage_users']);
$router->post('/admin/roles/create', [RoleController::class, 'store'], ['admin', 'permission:manage_users', 'csrf']);
$router->get('/admin/roles/edit', [RoleController::class, 'showEdit'], ['admin', 'permission:manage_users']);
$router->post('/admin/roles/edit', [RoleController::class, 'update'], ['admin', 'permission:manage_users', 'csrf']);
$router->post('/admin/roles/duplicate', [RoleController::class, 'duplicate'], ['admin', 'permission:manage_users', 'csrf']);
$router->post('/admin/roles/delete', [RoleController::class, 'delete'], ['admin', 'permission:manage_users', 'csrf']);
$router->post('/admin/roles/toggle', [RoleController::class, 'toggleStatus'], ['admin', 'permission:manage_users', 'csrf']);

// Admin User Management Routes
$router->get('/admin/users', [AdminUserController::class, 'index'], ['admin', 'permission:manage_users']);
$router->get('/admin/users/create', [AdminUserController::class, 'showCreate'], ['admin', 'permission:manage_users']);
$router->post('/admin/users/create', [AdminUserController::class, 'store'], ['admin', 'permission:manage_users', 'csrf']);
$router->get('/admin/users/edit', [AdminUserController::class, 'showEdit'], ['admin', 'permission:manage_users']);
$router->post('/admin/users/edit', [AdminUserController::class, 'update'], ['admin', 'permission:manage_users', 'csrf']);
$router->post('/admin/users/delete', [AdminUserController::class, 'delete'], ['admin', 'permission:manage_users', 'csrf']);
$router->get('/admin/users/impersonate', [AdminUserController::class, 'impersonate'], ['admin', 'permission:manage_users']);
$router->post('/admin/users/impersonate', [AdminUserController::class, 'impersonate'], ['admin', 'permission:manage_users', 'csrf']);
$router->get('/admin/users/revert-impersonation', [AdminUserController::class, 'revertImpersonation'], ['admin']);
$router->post('/admin/users/revert-impersonation', [AdminUserController::class, 'revertImpersonation'], ['admin', 'csrf']);

// Media Library Routes (Protected by admin auth and RBAC media permissions)
$router->get('/admin/media', [MediaController::class, 'index'], ['admin', 'permission:view_media']);
$router->get('/admin/media/list-json', [MediaController::class, 'listAjax'], ['admin', 'permission:view_media']);
$router->get('/admin/media/list-ajax', [MediaController::class, 'listAjax'], ['admin', 'permission:view_media']);
$router->post('/admin/media/upload', [MediaController::class, 'uploadAjax'], ['admin', 'permission:upload_media', 'csrf']);
$router->post('/admin/media/upload-ajax', [MediaController::class, 'uploadAjax'], ['admin', 'permission:upload_media', 'csrf']);
$router->post('/admin/media/folder/create', [MediaController::class, 'createFolderAjax'], ['admin', 'permission:edit_media', 'csrf']);
$router->post('/admin/media/folder/create-ajax', [MediaController::class, 'createFolderAjax'], ['admin', 'permission:edit_media', 'csrf']);
$router->post('/admin/media/folder/delete', [MediaController::class, 'deleteFolderAjax'], ['admin', 'permission:delete_media', 'csrf']);
$router->post('/admin/media/folder/delete-ajax', [MediaController::class, 'deleteFolderAjax'], ['admin', 'permission:delete_media', 'csrf']);
$router->post('/admin/media/edit', [MediaController::class, 'saveSeo'], ['admin', 'permission:edit_media', 'csrf']);
$router->post('/admin/media/save-seo', [MediaController::class, 'saveSeo'], ['admin', 'permission:edit_media', 'csrf']);
$router->post('/admin/media/bulk', [MediaController::class, 'bulkActionAjax'], ['admin', 'permission:bulk_media', 'csrf']);
$router->post('/admin/media/bulk-ajax', [MediaController::class, 'bulkActionAjax'], ['admin', 'permission:bulk_media', 'csrf']);

// Category Management Routes (Enterprise Category Management)
$router->get('/admin/categories', [CategoryController::class, 'index'], ['admin', 'permission:view_categories']);
$router->get('/admin/categories/create', [CategoryController::class, 'showCreate'], ['admin', 'permission:create_categories']);
$router->post('/admin/categories/create', [CategoryController::class, 'store'], ['admin', 'permission:create_categories', 'csrf']);
$router->get('/admin/categories/edit', [CategoryController::class, 'showEdit'], ['admin', 'permission:edit_categories']);
$router->post('/admin/categories/edit', [CategoryController::class, 'update'], ['admin', 'permission:edit_categories', 'csrf']);
$router->post('/admin/categories/delete', [CategoryController::class, 'delete'], ['admin', 'permission:delete_categories', 'csrf']);
$router->post('/admin/categories/sort', [CategoryController::class, 'sort'], ['admin', 'permission:edit_categories', 'csrf']);
$router->post('/admin/categories/bulk', [CategoryController::class, 'bulk'], ['admin', 'permission:edit_categories', 'csrf']);
$router->get('/admin/categories/export', [CategoryController::class, 'export'], ['admin', 'permission:view_categories']);

// Brand Management Routes (Enterprise Brand Management)
$router->get('/admin/brands', [BrandController::class, 'index'], ['admin', 'permission:view_brands']);
$router->get('/admin/brands/create', [BrandController::class, 'showCreate'], ['admin', 'permission:create_brands']);
$router->post('/admin/brands/create', [BrandController::class, 'store'], ['admin', 'permission:create_brands', 'csrf']);
$router->get('/admin/brands/edit', [BrandController::class, 'showEdit'], ['admin', 'permission:edit_brands']);
$router->post('/admin/brands/edit', [BrandController::class, 'update'], ['admin', 'permission:edit_brands', 'csrf']);
$router->post('/admin/brands/delete', [BrandController::class, 'delete'], ['admin', 'permission:delete_brands', 'csrf']);
$router->post('/admin/brands/sort', [BrandController::class, 'sort'], ['admin', 'permission:edit_brands', 'csrf']);
$router->post('/admin/brands/bulk', [BrandController::class, 'bulk'], ['admin', 'permission:edit_brands', 'csrf']);
$router->get('/admin/brands/export', [BrandController::class, 'export'], ['admin', 'permission:export_brands']);

// Product Management Routes (Enterprise Product Management)
$router->get('/admin/products', [ProductController::class, 'index'], ['admin', 'permission:view_products']);
$router->get('/admin/products/reports', [ProductReportController::class, 'index'], ['admin', 'permission:audit_products']);
$router->get('/admin/products/create', [ProductController::class, 'showCreate'], ['admin', 'permission:create_products']);
$router->post('/admin/products/create', [ProductController::class, 'store'], ['admin', 'permission:create_products', 'csrf']);
$router->get('/admin/products/edit', [ProductController::class, 'showEdit'], ['admin', 'permission:edit_products']);
$router->post('/admin/products/edit', [ProductController::class, 'update'], ['admin', 'permission:edit_products', 'csrf']);
$router->post('/admin/products/delete', [ProductController::class, 'delete'], ['admin', 'permission:delete_products', 'csrf']);
$router->post('/admin/products/restore', [ProductController::class, 'restore'], ['admin', 'permission:restore_products', 'csrf']);
$router->post('/admin/products/force-delete', [ProductController::class, 'forceDelete'], ['admin', 'permission:delete_products', 'csrf']);
$router->post('/admin/products/duplicate', [ProductController::class, 'duplicate'], ['admin', 'permission:duplicate_products', 'csrf']);
$router->post('/admin/products/bulk', [ProductController::class, 'bulk'], ['admin', 'permission:bulk_products', 'csrf']);
$router->post('/admin/products/import', [ProductController::class, 'import'], ['admin', 'permission:import_products', 'csrf']);
$router->get('/admin/products/import/mapping', [ProductController::class, 'showImportMapping'], ['admin', 'permission:import_products']);
$router->post('/admin/products/import/process', [ProductController::class, 'processImport'], ['admin', 'permission:import_products', 'csrf']);
$router->get('/admin/products/export', [ProductController::class, 'export'], ['admin', 'permission:export_products']);

// Attribute Management Routes (Enterprise Attribute Management)
$router->get('/admin/attributes', [AttributeController::class, 'index'], ['admin', 'permission:view_attributes']);
$router->get('/admin/attributes/create', [AttributeController::class, 'showCreate'], ['admin', 'permission:create_attributes']);
$router->post('/admin/attributes/create', [AttributeController::class, 'store'], ['admin', 'permission:create_attributes', 'csrf']);
$router->get('/admin/attributes/edit', [AttributeController::class, 'showEdit'], ['admin', 'permission:edit_attributes']);
$router->post('/admin/attributes/edit', [AttributeController::class, 'update'], ['admin', 'permission:edit_attributes', 'csrf']);
$router->post('/admin/attributes/delete', [AttributeController::class, 'delete'], ['admin', 'permission:delete_attributes', 'csrf']);

// Attribute Sets Routes
$router->get('/admin/attributes/sets', [AttributeController::class, 'indexSets'], ['admin', 'permission:view_attributes']);
$router->get('/admin/attributes/sets/create', [AttributeController::class, 'showCreateSet'], ['admin', 'permission:create_attributes']);
$router->post('/admin/attributes/sets/create', [AttributeController::class, 'storeSet'], ['admin', 'permission:create_attributes', 'csrf']);
$router->get('/admin/attributes/sets/edit', [AttributeController::class, 'showEditSet'], ['admin', 'permission:edit_attributes']);
$router->post('/admin/attributes/sets/edit', [AttributeController::class, 'updateSet'], ['admin', 'permission:edit_attributes', 'csrf']);
$router->post('/admin/attributes/sets/delete', [AttributeController::class, 'deleteSet'], ['admin', 'permission:delete_attributes', 'csrf']);

// Variant Management Routes (Enterprise Variant Management)
$router->get('/admin/variants', [VariantController::class, 'index'], ['admin', 'permission:manage_variants']);
$router->get('/admin/variants/create', [VariantController::class, 'showCreate'], ['admin', 'permission:manage_variants']);
$router->post('/admin/variants/create', [VariantController::class, 'store'], ['admin', 'permission:manage_variants', 'csrf']);
$router->get('/admin/variants/edit', [VariantController::class, 'showEdit'], ['admin', 'permission:manage_variants']);
$router->post('/admin/variants/edit', [VariantController::class, 'update'], ['admin', 'permission:manage_variants', 'csrf']);
$router->post('/admin/variants/delete', [VariantController::class, 'delete'], ['admin', 'permission:manage_variants', 'csrf']);
$router->post('/admin/variants/generate-combinations', [VariantController::class, 'generateCombinations'], ['admin', 'permission:manage_variants', 'csrf']);
$router->post('/admin/variants/bulk', [VariantController::class, 'bulk'], ['admin', 'permission:manage_variants', 'csrf']);
$router->get('/admin/variants/export', [VariantController::class, 'export'], ['admin', 'permission:manage_variants']);

// Order Management Routes (Enterprise Order Management)
$router->get('/admin/orders', [OrderController::class, 'index'], ['admin', 'permission:view_orders']);
$router->get('/admin/orders/show', [OrderController::class, 'show'], ['admin', 'permission:view_orders']);
$router->get('/admin/orders/create', [OrderController::class, 'showCreate'], ['admin', 'permission:create_orders']);
$router->post('/admin/orders/create', [OrderController::class, 'store'], ['admin', 'permission:create_orders', 'csrf']);
$router->get('/admin/orders/edit', [OrderController::class, 'showEdit'], ['admin', 'permission:edit_orders']);
$router->post('/admin/orders/edit', [OrderController::class, 'update'], ['admin', 'permission:edit_orders', 'csrf']);
$router->post('/admin/orders/delete', [OrderController::class, 'delete'], ['admin', 'permission:delete_orders', 'csrf']);
$router->post('/admin/orders/restore', [OrderController::class, 'restore'], ['admin', 'permission:view_orders', 'csrf']);
$router->post('/admin/orders/force-delete', [OrderController::class, 'forceDelete'], ['admin', 'permission:delete_orders', 'csrf']);
$router->post('/admin/orders/duplicate', [OrderController::class, 'duplicate'], ['admin', 'permission:create_orders', 'csrf']);
$router->post('/admin/orders/bulk', [OrderController::class, 'bulk'], ['admin', 'permission:manage_orders', 'csrf']);
$router->get('/admin/orders/export', [OrderController::class, 'export'], ['admin', 'permission:export_orders']);
$router->get('/admin/orders/pdf', [OrderController::class, 'generatePdf'], ['admin', 'permission:view_orders']);
$router->get('/admin/orders/reports', [OrderController::class, 'reports'], ['admin', 'permission:view_orders']);

// Sprint 30 – Enterprise OMS Centers
$router->get('/admin/orders/dashboard', [OrderController::class, 'dashboard'], ['admin', 'permission:view_orders']);
$router->get('/admin/orders/analytics', [OrderController::class, 'analytics'], ['admin', 'permission:view_orders']);
$router->get('/admin/orders/packing',   [OrderController::class, 'packing'],   ['admin', 'permission:manage_orders']);
$router->get('/admin/orders/shipping',  [OrderController::class, 'shipping'],  ['admin', 'permission:manage_shipments']);
$router->get('/admin/orders/payment',   [OrderController::class, 'payment'],   ['admin', 'permission:view_orders']);

// Sprint 31 – Enterprise OMS V2
$router->get('/admin/orders/kanban',           [OrderController::class, 'kanban'],             ['admin', 'permission:view_orders']);
$router->get('/admin/orders/merge',            [OrderController::class, 'showMerge'],          ['admin', 'permission:manage_orders']);
$router->post('/admin/orders/merge',           [OrderController::class, 'merge'],              ['admin', 'permission:manage_orders', 'csrf']);
$router->get('/admin/orders/partial-shipment', [OrderController::class, 'showPartialShipment'],['admin', 'permission:manage_shipments']);
$router->post('/admin/orders/partial-shipment',[OrderController::class, 'createPartialShipment'],['admin', 'permission:manage_shipments', 'csrf']);
$router->get('/admin/orders/print-center',     [OrderController::class, 'printCenter'],        ['admin', 'permission:view_orders']);

// Order Statuses Management
$router->get('/admin/orders/statuses', [OrderController::class, 'showStatuses'], ['admin', 'permission:manage_orders']);
$router->post('/admin/orders/statuses/create', [OrderController::class, 'storeStatus'], ['admin', 'permission:manage_orders', 'csrf']);
$router->post('/admin/orders/statuses/update', [OrderController::class, 'updateStatus'], ['admin', 'permission:manage_orders', 'csrf']);
$router->post('/admin/orders/statuses/delete', [OrderController::class, 'deleteStatus'], ['admin', 'permission:manage_orders', 'csrf']);

// Shipping & Refunds & Notes
$router->post('/admin/orders/add-shipment', [OrderController::class, 'addShipment'], ['admin', 'permission:manage_shipments', 'csrf']);
$router->post('/admin/orders/add-refund', [OrderController::class, 'addRefund'], ['admin', 'permission:manage_returns', 'csrf']);
$router->post('/admin/orders/add-note', [OrderController::class, 'addNote'], ['admin', 'permission:view_orders', 'csrf']);

$router->get('/admin/customers', [CustomerController::class, 'index'], ['admin', 'permission:view_customers']);
$router->get('/admin/customers/show', [CustomerController::class, 'show'], ['admin', 'permission:view_customers']);
$router->get('/admin/customers/profile', [CustomerController::class, 'show'], ['admin', 'permission:view_customers']);
$router->get('/admin/customers/timeline', [CustomerController::class, 'show'], ['admin', 'permission:view_customers']);
$router->get('/admin/customers/analytics', [CustomerController::class, 'show'], ['admin', 'permission:view_customers']);
$router->get('/admin/customers/create', [CustomerController::class, 'showCreate'], ['admin', 'permission:create_customers']);
$router->post('/admin/customers/create', [CustomerController::class, 'store'], ['admin', 'permission:create_customers', 'csrf']);
$router->get('/admin/customers/edit', [CustomerController::class, 'showEdit'], ['admin', 'permission:edit_customers']);
$router->post('/admin/customers/edit', [CustomerController::class, 'update'], ['admin', 'permission:edit_customers', 'csrf']);
$router->post('/admin/customers/delete', [CustomerController::class, 'delete'], ['admin', 'permission:delete_customers', 'csrf']);
$router->post('/admin/customers/restore', [CustomerController::class, 'restore'], ['admin', 'permission:view_customers', 'csrf']);
$router->post('/admin/customers/force-delete', [CustomerController::class, 'forceDelete'], ['admin', 'permission:delete_customers', 'csrf']);
$router->get('/admin/customers/export', [CustomerController::class, 'export'], ['admin', 'permission:export_customers']);
$router->post('/admin/customers/bulk', [CustomerController::class, 'bulk'], ['admin', 'permission:edit_customers', 'csrf']);

// Groups Management
$router->get('/admin/customers/groups', [CustomerController::class, 'indexGroups'], ['admin', 'permission:view_customers']);
$router->post('/admin/customers/groups/create', [CustomerController::class, 'storeGroup'], ['admin', 'permission:create_customers', 'csrf']);
$router->post('/admin/customers/groups/update', [CustomerController::class, 'updateGroup'], ['admin', 'permission:edit_customers', 'csrf']);
$router->post('/admin/customers/groups/delete', [CustomerController::class, 'deleteGroup'], ['admin', 'permission:delete_customers', 'csrf']);

// Segments Management
$router->get('/admin/customers/segments', [CustomerController::class, 'indexSegments'], ['admin', 'permission:customer_segments']);
$router->post('/admin/customers/segments/create', [CustomerController::class, 'storeSegment'], ['admin', 'permission:customer_segments', 'csrf']);
$router->post('/admin/customers/segments/delete', [CustomerController::class, 'deleteSegment'], ['admin', 'permission:customer_segments', 'csrf']);

// Sub-actions (Wallet, Reward, Note, Document, Address)
$router->post('/admin/customers/wallet', [CustomerController::class, 'handleWallet'], ['admin', 'permission:customer_wallet', 'csrf']);
$router->post('/admin/customers/reward', [CustomerController::class, 'handleReward'], ['admin', 'permission:customer_reward', 'csrf']);
$router->post('/admin/customers/note', [CustomerController::class, 'addNote'], ['admin', 'permission:customer_notes', 'csrf']);
$router->post('/admin/customers/document', [CustomerController::class, 'uploadDocument'], ['admin', 'permission:customer_documents', 'csrf']);
$router->post('/admin/customers/address', [CustomerController::class, 'addAddress'], ['admin', 'permission:edit_customers', 'csrf']);

// Promotion & Coupon Management Routes (Sprint 16)
$router->get('/admin/promotions', [PromotionController::class, 'index'], ['admin', 'permission:view_promotions']);
$router->get('/admin/promotions/create', [PromotionController::class, 'showCreate'], ['admin', 'permission:create_promotions']);
$router->post('/admin/promotions/create', [PromotionController::class, 'store'], ['admin', 'permission:create_promotions', 'csrf']);
$router->get('/admin/promotions/edit', [PromotionController::class, 'showEdit'], ['admin', 'permission:edit_promotions']);
$router->post('/admin/promotions/edit', [PromotionController::class, 'update'], ['admin', 'permission:edit_promotions', 'csrf']);
$router->post('/admin/promotions/duplicate', [PromotionController::class, 'duplicate'], ['admin', 'permission:duplicate_promotions', 'csrf']);
$router->post('/admin/promotions/delete', [PromotionController::class, 'delete'], ['admin', 'permission:delete_promotions', 'csrf']);
$router->post('/admin/promotions/restore', [PromotionController::class, 'restore'], ['admin', 'permission:view_promotions', 'csrf']);
$router->post('/admin/promotions/force-delete', [PromotionController::class, 'forceDelete'], ['admin', 'permission:delete_promotions', 'csrf']);
$router->get('/admin/promotions/export', [PromotionController::class, 'export'], ['admin', 'permission:view_promotions']);
$router->post('/admin/promotions/bulk', [PromotionController::class, 'bulk'], ['admin', 'permission:edit_promotions', 'csrf']);
$router->get('/admin/promotions/calendar', [PromotionController::class, 'calendar'], ['admin', 'permission:view_promotions']);
$router->get('/admin/promotions/reports', [PromotionController::class, 'reports'], ['admin', 'permission:promotion_reports']);
$router->get('/admin/promotions/preview', [PromotionController::class, 'preview'], ['admin', 'permission:promotion_preview']);

// Coupon Routes
$router->get('/admin/coupons', [PromotionController::class, 'indexCoupons'], ['admin', 'permission:coupon_management']);
$router->post('/admin/coupons/create', [PromotionController::class, 'storeCoupon'], ['admin', 'permission:coupon_management', 'csrf']);
$router->post('/admin/coupons/delete', [PromotionController::class, 'deleteCoupon'], ['admin', 'permission:coupon_management', 'csrf']);

// AI Recommendation Routes (Sprint 17)
$router->get('/admin/recommendations', [AiRecommendationController::class, 'index'], ['admin', 'permission:ai_recommendations']);
$router->get('/admin/recommendations/generate', [AiRecommendationController::class, 'generate'], ['admin', 'permission:ai_recommendations']);
$router->post('/admin/recommendations/apply', [AiRecommendationController::class, 'apply'], ['admin', 'permission:ai_recommendations', 'csrf']);
$router->post('/admin/recommendations/dismiss', [AiRecommendationController::class, 'dismiss'], ['admin', 'permission:ai_recommendations', 'csrf']);

// Enterprise Search Engine Routes (Sprint 18)
$router->get('/admin/search', [SearchController::class, 'index'], ['admin', 'permission:view_search']);
$router->get('/admin/search/statistics', [SearchController::class, 'statistics'], ['admin', 'permission:search_reports']);
$router->get('/admin/search/synonyms', [SearchController::class, 'synonyms'], ['admin', 'permission:manage_synonyms']);
$router->get('/admin/search/boost', [SearchController::class, 'boost'], ['admin', 'permission:manage_boost']);
$router->get('/admin/search/rebuild', [SearchController::class, 'rebuild'], ['admin', 'permission:search_rebuild']);
$router->get('/admin/search/clear-cache', [SearchController::class, 'clearCache'], ['admin', 'permission:manage_search']);
$router->post('/admin/search/synonyms/create', [SearchController::class, 'storeSynonym'], ['admin', 'permission:manage_synonyms', 'csrf']);
$router->post('/admin/search/synonyms/delete', [SearchController::class, 'deleteSynonym'], ['admin', 'permission:manage_synonyms', 'csrf']);
$router->post('/admin/search/stopwords/create', [SearchController::class, 'storeStopWord'], ['admin', 'permission:manage_stopwords', 'csrf']);
$router->post('/admin/search/stopwords/delete', [SearchController::class, 'deleteStopWord'], ['admin', 'permission:manage_stopwords', 'csrf']);
$router->post('/admin/search/redirects/create', [SearchController::class, 'storeRedirect'], ['admin', 'permission:manage_search', 'csrf']);
$router->post('/admin/search/redirects/delete', [SearchController::class, 'deleteRedirect'], ['admin', 'permission:manage_search', 'csrf']);
$router->post('/admin/search/boost/create', [SearchController::class, 'storeBoost'], ['admin', 'permission:manage_boost', 'csrf']);
$router->post('/admin/search/boost/delete', [SearchController::class, 'deleteBoost'], ['admin', 'permission:manage_boost', 'csrf']);

// Enterprise Finance & Accounting Routes (Sprint 20)
$router->get('/admin/finance', [FinanceController::class, 'index'], ['admin', 'permission:view_finance']);
$router->get('/admin/accounts', [FinanceController::class, 'accounts'], ['admin', 'permission:manage_accounts']);
$router->get('/admin/invoices', [FinanceController::class, 'invoices'], ['admin', 'permission:manage_invoices']);
$router->get('/admin/expenses', [FinanceController::class, 'expenses'], ['admin', 'permission:manage_expenses']);
$router->post('/admin/expenses/create', [FinanceController::class, 'storeExpense'], ['admin', 'permission:manage_expenses', 'csrf']);
$router->get('/admin/revenues', [FinanceController::class, 'revenues'], ['admin', 'permission:manage_revenues']);
$router->post('/admin/revenues/create', [FinanceController::class, 'storeRevenue'], ['admin', 'permission:manage_revenues', 'csrf']);
$router->get('/admin/reports/finance', [FinanceController::class, 'reports'], ['admin', 'permission:financial_reports']);

// Enterprise Logistics & Shipping Routes (Sprint 21)
$router->get('/admin/shipping', [ShippingController::class, 'index'], ['admin', 'permission:view_shipping']);
$router->get('/admin/shipping/companies', [ShippingController::class, 'companies'], ['admin', 'permission:manage_shipping_companies']);
$router->post('/admin/shipping/companies/create', [ShippingController::class, 'storeCompany'], ['admin', 'permission:manage_shipping_companies', 'csrf']);
$router->get('/admin/shipping/companies/edit', [ShippingController::class, 'editCompany'], ['admin', 'permission:manage_shipping_companies']);
$router->post('/admin/shipping/companies/update', [ShippingController::class, 'updateCompany'], ['admin', 'permission:manage_shipping_companies', 'csrf']);
$router->post('/admin/shipping/companies/integration', [ShippingController::class, 'updateIntegration'], ['admin', 'permission:shipping_integrations', 'csrf']);
$router->get('/admin/shipping/shipments', [ShippingController::class, 'shipments'], ['admin', 'permission:manage_shipping']);
$router->post('/admin/shipping/shipments/create', [ShippingController::class, 'storeShipment'], ['admin', 'permission:manage_shipping', 'csrf']);
$router->get('/admin/shipping/returns', [ShippingController::class, 'returns'], ['admin', 'permission:manage_returns']);
$router->post('/admin/shipping/returns/update', [ShippingController::class, 'updateReturnStatus'], ['admin', 'permission:manage_returns', 'csrf']);
$router->get('/admin/shipping/reports', [ShippingController::class, 'reports'], ['admin', 'permission:shipping_reports']);
// Marketplace & Vendor Routes (Sprint 35 VEYRA Platform Upgrade)
$router->get('/admin/marketplace/dashboard',    [MarketplaceAdminController::class, 'dashboard'],    ['admin', 'permission:view_marketplace']);
$router->get('/admin/marketplace/applications', [MarketplaceAdminController::class, 'applications'], ['admin', 'permission:approve_vendors']);
$router->post('/admin/marketplace/applications/approve', [MarketplaceAdminController::class, 'approveApplication'], ['admin', 'permission:approve_vendors', 'csrf']);
$router->post('/admin/marketplace/applications/reject',  [MarketplaceAdminController::class, 'rejectApplication'],  ['admin', 'permission:approve_vendors', 'csrf']);
$router->get('/admin/marketplace/moderation',   [MarketplaceAdminController::class, 'moderation'],   ['admin', 'permission:moderate_products']);
$router->post('/admin/marketplace/moderation/action', [MarketplaceAdminController::class, 'moderateProductAction'], ['admin', 'permission:moderate_products', 'csrf']);
$router->get('/admin/marketplace/payouts',      [MarketplaceAdminController::class, 'payouts'],      ['admin', 'permission:view_platform_finance']);
$router->post('/admin/marketplace/payouts/process', [MarketplaceAdminController::class, 'processPayoutAction'], ['admin', 'permission:view_platform_finance', 'csrf']);

$router->get('/admin/vendors', [VendorController::class, 'index'], ['admin', 'permission:view_vendors']);
$router->get('/admin/vendors/create', [VendorController::class, 'create'], ['admin', 'permission:create_vendor']);
$router->post('/admin/vendors/create', [VendorController::class, 'store'], ['admin', 'permission:create_vendor', 'csrf']);
$router->get('/admin/vendors/edit', [VendorController::class, 'edit'], ['admin', 'permission:edit_vendor']);
$router->post('/admin/vendors/update', [VendorController::class, 'update'], ['admin', 'permission:edit_vendor', 'csrf']);
$router->get('/admin/vendors/reports', [VendorController::class, 'reports'], ['admin', 'permission:vendor_reports']);
$router->get('/admin/vendors/payments', [VendorController::class, 'payments'], ['admin', 'permission:vendor_payments']);
$router->get('/admin/vendors/wallet', [VendorController::class, 'wallet'], ['admin', 'permission:vendor_wallet']);
$router->post('/admin/vendors/payout-request', [VendorController::class, 'requestPayout'], ['admin', 'permission:vendor_wallet', 'csrf']);

// Vendor Portal Routes (/vendor)
$router->get('/vendor/dashboard', [VendorController::class, 'vendorDashboard']);
$router->post('/vendor/apply', [VendorController::class, 'submitApplication']);

// Workflow Automation Routes
$router->get('/admin/workflows', [WorkflowController::class, 'index'], ['admin', 'permission:view_workflows']);
$router->get('/admin/workflows/create', [WorkflowController::class, 'create'], ['admin', 'permission:create_workflows']);
$router->post('/admin/workflows/create', [WorkflowController::class, 'store'], ['admin', 'permission:create_workflows', 'csrf']);
$router->get('/admin/workflows/edit', [WorkflowController::class, 'edit'], ['admin', 'permission:edit_workflows']);
$router->get('/admin/workflows/templates', [WorkflowController::class, 'templates'], ['admin', 'permission:workflow_templates']);
$router->get('/admin/workflows/history', [WorkflowController::class, 'history'], ['admin', 'permission:workflow_reports']);
$router->get('/admin/workflows/logs', [WorkflowController::class, 'logs'], ['admin', 'permission:workflow_logs']);



// API Preparation Routes (Mobile API)
$router->get('/api/categories/tree', [CategoryController::class, 'apiTree']);
$router->get('/api/brands', [BrandController::class, 'apiIndex']);
$router->get('/api/brands/.*', [BrandController::class, 'apiShow']);
$router->get('/api/products', [ProductController::class, 'apiIndex']);
$router->post('/api/products', [ProductController::class, 'apiStore']);
$router->get('/api/products/.*', [ProductController::class, 'apiShow']);
$router->put('/api/products/.*', [ProductController::class, 'apiUpdate']);
$router->delete('/api/products/.*', [ProductController::class, 'apiDelete']);

$router->get('/api/attributes', [AttributeController::class, 'apiIndex']);
$router->post('/api/attributes', [AttributeController::class, 'apiStore']);
$router->get('/api/attributes/.*', [AttributeController::class, 'apiShow']);
$router->put('/api/attributes/.*', [AttributeController::class, 'apiUpdate']);
$router->delete('/api/attributes/.*', [AttributeController::class, 'apiDelete']);

$router->get('/api/variants', [VariantController::class, 'apiIndex']);
$router->post('/api/variants', [VariantController::class, 'apiStore']);
$router->get('/api/variants/.*', [VariantController::class, 'apiShow']);
$router->put('/api/variants/.*', [VariantController::class, 'apiUpdate']);
$router->delete('/api/variants/.*', [VariantController::class, 'apiDelete']);
$router->get('/api/products/.*/variants', [VariantController::class, 'apiProductVariants']);

// Sprint 32 – Enterprise WMS Routes
$router->get('/admin/wms/dashboard',    [WarehouseController::class, 'dashboard'],    ['admin', 'permission:view_wms']);
$router->get('/admin/wms/warehouses',   [WarehouseController::class, 'warehouses'],   ['admin', 'permission:view_wms']);
$router->get('/admin/wms/locations',    [WarehouseController::class, 'locations'],    ['admin', 'permission:manage_locations']);
$router->get('/admin/wms/movements',    [WarehouseController::class, 'movements'],    ['admin', 'permission:view_wms']);
$router->get('/admin/wms/picking',      [WarehouseController::class, 'picking'],      ['admin', 'permission:view_wms']);
$router->get('/admin/wms/packing',      [WarehouseController::class, 'packing'],      ['admin', 'permission:view_wms']);
$router->get('/admin/wms/transfers',    [WarehouseController::class, 'transfers'],    ['admin', 'permission:manage_transfers']);
$router->post('/admin/wms/transfers/update', [WarehouseController::class, 'updateTransfer'], ['admin', 'permission:manage_transfers', 'csrf']);
$router->get('/admin/wms/counts',       [WarehouseController::class, 'counts'],       ['admin', 'permission:manage_counts']);
$router->get('/admin/wms/analytics',    [WarehouseController::class, 'analytics'],    ['admin', 'permission:wms_analytics']);
$router->get('/admin/wms/ai-assistant', [WarehouseController::class, 'aiAssistant'],  ['admin', 'permission:view_wms']);

// WMS API Routes
$router->get('/api/wms/warehouses',        [WarehouseController::class, 'apiWarehouses']);
$router->get('/api/wms/inventory',         [WarehouseController::class, 'apiInventory']);
$router->post('/api/wms/picking/validate', [WarehouseController::class, 'apiPickingValidate']);
$router->post('/api/wms/transfers/create', [WarehouseController::class, 'apiTransfersCreate']);
$router->post('/api/wms/counts/reconcile', [WarehouseController::class, 'apiCountsReconcile']);

// Sprint 33 – Enterprise Procurement Routes
$router->get('/admin/purchasing/dashboard',    [ProcurementController::class, 'dashboard'],    ['admin', 'permission:view_procurement']);
$router->get('/admin/purchasing/suppliers',    [ProcurementController::class, 'suppliers'],    ['admin', 'permission:manage_suppliers']);
$router->get('/admin/purchasing/suppliers/show', [ProcurementController::class, 'supplierShow'], ['admin', 'permission:manage_suppliers']);
$router->get('/admin/purchasing/orders',       [ProcurementController::class, 'orders'],       ['admin', 'permission:view_procurement']);
$router->get('/admin/purchasing/wizard',       [ProcurementController::class, 'wizard'],       ['admin', 'permission:manage_procurement']);
$router->get('/admin/purchasing/rfq',          [ProcurementController::class, 'rfq'],          ['admin', 'permission:manage_rfq']);
$router->get('/admin/purchasing/receipts',     [ProcurementController::class, 'receipts'],     ['admin', 'permission:receive_goods']);
$router->get('/admin/purchasing/payments',     [ProcurementController::class, 'payments'],     ['admin', 'permission:view_procurement']);
$router->get('/admin/purchasing/contracts',    [ProcurementController::class, 'contracts'],    ['admin', 'permission:manage_supplier_contracts']);
$router->get('/admin/purchasing/ai-assistant', [ProcurementController::class, 'aiAssistant'],  ['admin', 'permission:view_procurement']);

// POST Forms
$router->post('/admin/purchasing/suppliers/create', [ProcurementController::class, 'createSupplier'], ['admin', 'permission:manage_suppliers', 'csrf']);
$router->post('/admin/purchasing/suppliers/update', [ProcurementController::class, 'updateSupplier'], ['admin', 'permission:manage_suppliers', 'csrf']);
$router->post('/admin/purchasing/suppliers/delete', [ProcurementController::class, 'deleteSupplier'], ['admin', 'permission:manage_suppliers', 'csrf']);
$router->post('/admin/purchasing/suppliers/recalculate-score', [ProcurementController::class, 'recalculateSupplierScore'], ['admin', 'permission:manage_suppliers', 'csrf']);
$router->post('/admin/purchasing/suppliers/note', [ProcurementController::class, 'createSupplierNote'], ['admin', 'permission:manage_suppliers', 'csrf']);
$router->post('/admin/purchasing/orders/create',    [ProcurementController::class, 'createPurchaseOrder'], ['admin', 'permission:manage_procurement', 'csrf']);
$router->post('/admin/purchasing/orders/approve',   [ProcurementController::class, 'approvePurchaseOrder'], ['admin', 'permission:approve_purchase_orders', 'csrf']);
$router->post('/admin/purchasing/receipts/receive', [ProcurementController::class, 'receiveGoods'], ['admin', 'permission:receive_goods', 'csrf']);
$router->post('/admin/purchasing/rfq/create',       [ProcurementController::class, 'createRFQ'], ['admin', 'permission:manage_rfq', 'csrf']);
$router->post('/admin/purchasing/rfq/response',     [ProcurementController::class, 'submitRFQResponse'], ['admin', 'permission:manage_rfq', 'csrf']);
$router->post('/admin/purchasing/contracts/create', [ProcurementController::class, 'createContract'], ['admin', 'permission:manage_supplier_contracts', 'csrf']);
$router->post('/admin/purchasing/payments/update-status', [ProcurementController::class, 'updatePaymentStatus'], ['admin', 'permission:view_procurement', 'csrf']);

// REST API Endpoints
$router->get('/api/purchasing/suppliers',        [ProcurementController::class, 'apiSuppliers']);
$router->post('/api/purchasing/orders/create',    [ProcurementController::class, 'apiCreateOrder']);
$router->post('/api/purchasing/orders/approve',   [ProcurementController::class, 'apiApproveOrder']);
$router->post('/api/purchasing/rfq/compare',      [ProcurementController::class, 'apiCompareRfq']);
$router->post('/api/purchasing/receipt/receive',  [ProcurementController::class, 'apiReceiveGoods']);
