<?php

declare(strict_types=1);

namespace Core;

use Core\Container\Container;
use Core\Contracts\ContainerInterface;
use Core\Contracts\ConfigInterface;
use Core\Contracts\EventDispatcherInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Core\Contracts\StorageInterface;
use Core\Contracts\MailInterface;
use Core\Contracts\QueueInterface;
use Core\Config\ConfigLoader;
use Core\Config\EnvParser;
use Core\Events\EventDispatcher;
use Core\Logging\Logger;
use Core\Database\Database;
use Core\Cache\FileCache;
use Core\Storage\FileStorage;
use Core\Mail\NullMail;
use Core\Queue\SyncQueue;
use Core\Exceptions\ExceptionHandler;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;
use Core\View\View;
use Core\Localization\Translator;
use Throwable;

class Application {
    public static Application $app;
    private static Application $instance;
    private Container $container;
    private string $basePath;

    public function __construct(string $basePath) {
        self::$app = $this;
        self::$instance = $this;
        $this->basePath = $basePath;
        $this->container = new Container();

        $this->bootstrap();
    }

    public static function getInstance(): Application {
        return self::$instance;
    }

    public function getContainer(): Container {
        return $this->container;
    }

    public function __get(string $name): mixed {
        $map = [
            'session' => \Core\Session::class,
            'request' => \Core\Http\Request::class,
            'response' => \Core\Http\Response::class,
            'router' => \Core\Http\Router::class,
            'db' => \Core\Database\Database::class,
        ];
        if (isset($map[$name])) {
            return $this->container->get($map[$name]);
        }
        return $this->container->get($name);
    }

    private function bootstrap(): void {
        EnvParser::parse($this->basePath . '/.env');

        $this->container->singleton(ContainerInterface::class, $this->container);
        $this->container->singleton(Container::class, $this->container);
        $this->container->singleton(\Core\Session::class, \Core\Session::class);

        // Core Services Bindings
        $this->container->singleton(ConfigInterface::class, function () {
            return new ConfigLoader($this->basePath . '/config');
        });

        // Abstract Interfaces -> Concrete Implementations
        $this->container->singleton(LoggerInterface::class, Logger::class);
        $this->container->singleton(EventDispatcherInterface::class, EventDispatcher::class);
        $this->container->singleton(CacheInterface::class, FileCache::class);
        $this->container->singleton(StorageInterface::class, FileStorage::class);
        $this->container->singleton(MailInterface::class, NullMail::class);
        $this->container->singleton(QueueInterface::class, SyncQueue::class);
        $this->container->singleton(DatabaseInterface::class, Database::class);
        
        // Concrete Class Bindings
        $this->container->singleton(Request::class, Request::class);
        $this->container->singleton(Response::class, Response::class);
        $this->container->singleton(View::class, View::class);
        $this->container->singleton(Translator::class, Translator::class);
        $this->container->singleton(Router::class, Router::class);
        
        $this->container->singleton(\App\Repositories\AttributeRepository::class, \App\Repositories\AttributeRepository::class);
        $this->container->singleton(\App\Services\AttributeService::class, \App\Services\AttributeService::class);
        $this->container->singleton(\App\Repositories\VariantRepository::class, \App\Repositories\VariantRepository::class);
        $this->container->singleton(\App\Services\VariantService::class, \App\Services\VariantService::class);
        $this->container->singleton(\App\Repositories\OrderRepository::class, \App\Repositories\OrderRepository::class);
        $this->container->singleton(\App\Services\OrderService::class, \App\Services\OrderService::class);
        $this->container->singleton(\App\Repositories\CustomerRepository::class, \App\Repositories\CustomerRepository::class);
        $this->container->singleton(\App\Services\CustomerService::class, \App\Services\CustomerService::class);
        $this->container->singleton(\App\Repositories\PromotionRepository::class, \App\Repositories\PromotionRepository::class);
        $this->container->singleton(\App\Services\PromotionService::class, \App\Services\PromotionService::class);
        $this->container->singleton(\App\Repositories\AiRecommendationRepository::class, \App\Repositories\AiRecommendationRepository::class);
        $this->container->singleton(\App\Services\AiRecommendationServiceInterface::class, \App\Services\OpenAiRecommendationService::class);
        $this->container->singleton(\App\Repositories\SearchRepository::class, \App\Repositories\SearchRepository::class);
        $this->container->singleton(\App\Services\SearchService::class, \App\Services\SearchService::class);
        $this->container->singleton(\App\Repositories\FinanceRepository::class, \App\Repositories\FinanceRepository::class);
        $this->container->singleton(\App\Services\FinanceService::class, \App\Services\FinanceService::class);
        $this->container->singleton(\App\Repositories\ShippingRepository::class, \App\Repositories\ShippingRepository::class);
        $this->container->singleton(\App\Services\ShippingService::class, \App\Services\ShippingService::class);
        $this->container->singleton(\App\Repositories\VendorRepository::class, \App\Repositories\VendorRepository::class);
        $this->container->singleton(\App\Services\VendorService::class, \App\Services\VendorService::class);
        $this->container->singleton(\App\Repositories\WorkflowRepository::class, \App\Repositories\WorkflowRepository::class);
        $this->container->singleton(\App\Services\WorkflowService::class, \App\Services\WorkflowService::class);
        $this->container->singleton(\App\Repositories\ProcurementRepository::class, \App\Repositories\ProcurementRepository::class);
        $this->container->singleton(\App\Services\ProcurementService::class, \App\Services\ProcurementService::class);
        $this->container->singleton(\App\Controllers\StoreController::class, \App\Controllers\StoreController::class);

        
        // Register Event Listeners
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        $dispatcher->addListener(
            \App\Events\UserLoggedInEvent::class,
            [$this->container->get(\App\Listeners\AuditLogListener::class), 'handleUserLoggedIn']
        );
        
        // Register Exception Handler
        $exceptionHandler = $this->container->get(ExceptionHandler::class);
        $exceptionHandler->register();
    }

    public function run(): void {
        $router = $this->container->get(Router::class);
        $request = $this->container->get(Request::class);
        $response = $this->container->get(Response::class);

        try {
            $result = $router->resolve($request, $response);
            if (is_string($result)) {
                $response->setContent($result)->send();
            }
        } catch (Throwable $e) {
            $exceptionHandler = $this->container->get(ExceptionHandler::class);
            $exceptionHandler->handleException($e);
        }
    }
}
