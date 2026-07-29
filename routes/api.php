<?php

declare(strict_types=1);

use App\Controllers\ProductController;

$router->get('/api/status', function(\Core\Http\Request $request, \Core\Http\Response $response) {
    $response->json(['status' => 'OK', 'system' => 'SaintMonarc API (Enterprise)']);
});

// Mobile API Product Endpoints
$router->get('/api/products', [ProductController::class, 'apiIndex']);
$router->post('/api/products', [ProductController::class, 'apiStore']);
$router->get('/api/products/.*', [ProductController::class, 'apiShow']);
$router->put('/api/products/.*', [ProductController::class, 'apiUpdate']);
$router->delete('/api/products/.*', [ProductController::class, 'apiDelete']);
