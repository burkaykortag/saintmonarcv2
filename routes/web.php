<?php

declare(strict_types=1);

// $router is loaded in public/index.php

use App\Controllers\AuthController;
use App\Controllers\PasswordController;

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login']);

$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register']);

$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/verify-email', [AuthController::class, 'verifyEmail']);

$router->get('/password/forgot', [PasswordController::class, 'showForgot'], ['guest']);
$router->post('/password/forgot', [PasswordController::class, 'forgot']);

$router->get('/password/reset', [PasswordController::class, 'showReset'], ['guest']);
$router->post('/password/reset', [PasswordController::class, 'reset']);

$router->get('/sessions', [AuthController::class, 'showSessions'], ['auth']);
$router->post('/sessions/revoke', [AuthController::class, 'revokeSession'], ['auth']);

$router->get('/', function($request, $response) {
    $view = \Core\Application::getInstance()->getContainer()->get(\Core\View\View::class);
    return $view->render('home');
});

// Public Brand Page
$router->get('/brands/.*', [\App\Controllers\BrandController::class, 'showPublicBrandPage']);
