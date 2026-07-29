<?php

declare(strict_types=1);

// $router is loaded in public/index.php

use App\Controllers\AdminAuthController;
use App\Controllers\RoleController;
use App\Controllers\AdminDashboardController;
use App\Controllers\MediaController;
use App\Controllers\CategoryController;
use App\Controllers\BrandController;
use App\Controllers\ProductController;
use App\Controllers\ProductReportController;
use App\Controllers\AttributeController;
use App\Controllers\VariantController;

// Guest Routes
$router->get('/admin/login', [AdminAuthController::class, 'showLogin'], ['guest']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/logout', [AdminAuthController::class, 'logout']);

// Admin Dashboard (Requires admin authentication)
$router->get('/admin', [AdminDashboardController::class, 'index'], ['admin']);
$router->get('/admin/dashboard', [AdminDashboardController::class, 'index'], ['admin']);

// RBAC Management Routes (Protected by admin auth and specific manage_users permission)
$router->get('/admin/roles', [RoleController::class, 'index'], ['admin', 'permission:manage_users']);
$router->get('/admin/roles/create', [RoleController::class, 'showCreate'], ['admin', 'permission:manage_users']);
$router->post('/admin/roles/create', [RoleController::class, 'store'], ['admin', 'permission:manage_users', 'csrf']);
$router->get('/admin/roles/edit', [RoleController::class, 'showEdit'], ['admin', 'permission:manage_users']);
$router->post('/admin/roles/edit', [RoleController::class, 'update'], ['admin', 'permission:manage_users', 'csrf']);
$router->post('/admin/roles/duplicate', [RoleController::class, 'duplicate'], ['admin', 'permission:manage_users', 'csrf']);
$router->post('/admin/roles/delete', [RoleController::class, 'delete'], ['admin', 'permission:manage_users', 'csrf']);
$router->post('/admin/roles/toggle', [RoleController::class, 'toggleStatus'], ['admin', 'permission:manage_users', 'csrf']);

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
