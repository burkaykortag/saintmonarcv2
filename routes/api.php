<?php

declare(strict_types=1);

use App\Controllers\ProductController;
use App\Controllers\OrderController;
use App\Controllers\CustomerController;
use App\Controllers\PromotionController;
use App\Controllers\SearchController;
use App\Controllers\FinanceController;
use App\Controllers\ShippingController;
use App\Controllers\VendorController;
use App\Controllers\WorkflowController;

$router->get('/api/status', function(\Core\Http\Request $request, \Core\Http\Response $response) {
    $response->json(['status' => 'OK', 'system' => 'SaintMonarc API (Enterprise)']);
});

// Mobile API Product Endpoints
$router->get('/api/products', [ProductController::class, 'apiIndex']);
$router->post('/api/products', [ProductController::class, 'apiStore']);
$router->get('/api/products/.*', [ProductController::class, 'apiShow']);
$router->put('/api/products/.*', [ProductController::class, 'apiUpdate']);
$router->delete('/api/products/.*', [ProductController::class, 'apiDelete']);

// Mobile API Order Endpoints
$router->get('/api/orders', [OrderController::class, 'apiIndex']);
$router->post('/api/orders', [OrderController::class, 'apiStore']);
$router->get('/api/orders/.*', [OrderController::class, 'apiShow']);
$router->put('/api/orders/.*', [OrderController::class, 'apiUpdate']);
$router->delete('/api/orders/.*', [OrderController::class, 'apiDelete']);

// Mobile API Customer Endpoints
$router->get('/api/customers', [CustomerController::class, 'apiIndex']);
$router->get('/api/customers/search', [CustomerController::class, 'apiSearch']);
$router->get('/api/customers/export', [CustomerController::class, 'apiExport']);
$router->get('/api/customers/segments', [CustomerController::class, 'apiSegments']);
$router->get('/api/customers/.*', [CustomerController::class, 'apiShow']);

// Mobile API Promotion & Coupon Endpoints (Sprint 16)
$router->get('/api/promotions', [PromotionController::class, 'apiIndex']);
$router->get('/api/promotions/preview', [PromotionController::class, 'apiPreview']);
$router->post('/api/promotions/calculate', [PromotionController::class, 'apiCalculate']);
$router->get('/api/promotions/.*', [PromotionController::class, 'apiShow']);
$router->get('/api/coupons', [PromotionController::class, 'apiCoupons']);
$router->post('/api/coupons/validate', [PromotionController::class, 'apiCouponsValidate']);
$router->get('/api/coupons/history', [PromotionController::class, 'apiCouponsHistory']);

// REST Search API Endpoints (Sprint 18)
$router->get('/api/search', [SearchController::class, 'apiSearch']);
$router->get('/api/search/suggest', [SearchController::class, 'apiSuggest']);
$router->get('/api/search/autocomplete', [SearchController::class, 'apiAutocomplete']);
$router->get('/api/search/popular', [SearchController::class, 'apiPopular']);
$router->get('/api/search/history', [SearchController::class, 'apiHistory']);
$router->get('/api/search/statistics', [SearchController::class, 'apiStatistics']);
$router->get('/api/search/rebuild', [SearchController::class, 'apiRebuild']);
$router->get('/api/search/ai', [SearchController::class, 'apiAi']);

// REST Finance API Endpoints (Sprint 20)
$router->get('/api/finance', [FinanceController::class, 'apiFinance']);
$router->get('/api/accounts', [FinanceController::class, 'apiAccounts']);
$router->get('/api/invoices', [FinanceController::class, 'apiInvoices']);
$router->get('/api/payments', [FinanceController::class, 'apiPayments']);
$router->get('/api/expenses', [FinanceController::class, 'apiExpenses']);
$router->get('/api/revenues', [FinanceController::class, 'apiRevenues']);
$router->get('/api/reports', [FinanceController::class, 'apiReports']);

// REST Shipping API Endpoints (Sprint 21)
$router->get('/api/shipping', [ShippingController::class, 'apiIndex']);
$router->get('/api/shipping/calculate', [ShippingController::class, 'apiCalculate']);
$router->get('/api/shipping/track', [ShippingController::class, 'apiTrack']);
$router->get('/api/shipping/returns', [ShippingController::class, 'apiReturns']);
$router->get('/api/shipping/companies', [ShippingController::class, 'apiCompanies']);
$router->get('/api/shipping/labels', [ShippingController::class, 'apiLabels']);
$router->get('/api/shipping/statistics', [ShippingController::class, 'apiStatistics']);

// REST Marketplace / Vendor API Endpoints
$router->get('/api/vendors', [VendorController::class, 'apiList']);
$router->get('/api/vendors/products', [VendorController::class, 'apiProducts']);
$router->get('/api/vendors/orders', [VendorController::class, 'apiOrders']);
$router->get('/api/vendors/statistics', [VendorController::class, 'apiStatistics']);
$router->get('/api/vendors/wallet', [VendorController::class, 'apiWallet']);
$router->get('/api/vendors/payments', [VendorController::class, 'apiPayments']);
$router->get('/api/vendors/.*', [VendorController::class, 'apiShow']);

// REST Workflow Automation API Endpoints
$router->get('/api/workflows', [WorkflowController::class, 'apiList']);
$router->post('/api/workflows/run', [WorkflowController::class, 'apiRun']);
$router->get('/api/workflows/history', [WorkflowController::class, 'apiHistory']);
$router->get('/api/workflows/logs', [WorkflowController::class, 'apiLogs']);
$router->get('/api/workflows/templates', [WorkflowController::class, 'apiTemplates']);
$router->get('/api/workflows/.*', [WorkflowController::class, 'apiShow']);

// Central Address System (Turkey Cities & Districts) API Endpoints
$router->get('/api/address/cities', function(\Core\Http\Request $request, \Core\Http\Response $response) {
    $response->json(['success' => true, 'cities' => \App\Helpers\AddressHelper::getCities()]);
});

$router->get('/api/address/districts', function(\Core\Http\Request $request, \Core\Http\Response $response) {
    $city = $request->get('city') ?? '';
    $response->json(['success' => true, 'districts' => \App\Helpers\AddressHelper::getDistricts($city)]);
});




