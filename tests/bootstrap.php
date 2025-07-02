<?php
declare(strict_types=1);

// Set the application path
define('APP_ROOT', dirname(__DIR__));

// Load Composer's autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

// Set test environment
$_ENV['APP_ENV'] = 'testing';

// Create test database configuration
$testDbConfig = [
    'driver' => 'pgsql',
    'host' => $_ENV['DB_TEST_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_TEST_DATABASE'] ?? 'infinri_test',
    'username' => $_ENV['DB_TEST_USERNAME'] ?? 'infinri_test',
    'password' => $_ENV['DB_PASSWORD'] ?? 'infinri_test',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
];

// Override database configuration for tests
putenv('DB_CONNECTION=testing');
putenv('DB_HOST=' . $testDbConfig['host']);
putenv('DB_DATABASE=' . $testDbConfig['database']);
putenv('DB_USERNAME=' . $testDbConfig['username']);
putenv('DB_PASSWORD=' . $testDbConfig['password']);

// Create a new DI container
$container = new DI\Container();

// Set up application settings
$container->set('settings', function () use ($testDbConfig) {
    return [
        'app' => [
            'name' => 'Infinri Test',
            'env' => 'testing',
            'debug' => true,
            'url' => 'http://localhost:8000',
        ],
        'db' => $testDbConfig,
        'view' => [
            'cache' => false,
        ],
    ];
});

// Set up logger
$container->set(Psr\Log\LoggerInterface::class, function (DI\Container $c) {
    $logger = new Monolog\Logger('test');
    $logger->pushHandler(new Monolog\Handler\TestHandler());
    return $logger;
});

// Set up view engine
$container->set('view', function (DI\Container $c) {
    $templatesPath = APP_ROOT . '/resources/views';
    return new \League\Plates\Engine($templatesPath, 'php');
});



    // Alias RouteParserInterface for controllers expecting it
    $container->set(\Slim\Interfaces\RouteParserInterface::class, function (DI\Container $c) {
        /** @var \Slim\Routing\RouteCollectorProxy $router */
        $router = $c->get('router');
        return $router->getRouteCollector()->getRouteParser();
    });

    // Set up navigation stub for tests
$container->set('navigation', function () {
    return new class {
        private array $items = [];
        public function addItem(string $key, array $config): void { $this->items[$key] = $config; }
        public function getItems(): array { return $this->items; }
    };
});

// Create Slim App instance early so that router is available for module registration
$app = Slim\Factory\AppFactory::create(container: $container);

// Use the app itself as the router service
$container->set('router', $app);

// Alias RouteParserInterface now that router exists
$container->set(\Slim\Interfaces\RouteParserInterface::class, function (DI\Container $c) {
    /** @var \Slim\App $router */
    $router = $c->get('router');
    return $router->getRouteCollector()->getRouteParser();
});

// Initialize and register modules (requires router)
$moduleProvider = new App\Providers\ModuleServiceProvider(
    $container,
    APP_ROOT . '/app/Modules'
);

try {
    $modules = $moduleProvider->register();
    $container->get(Psr\Log\LoggerInterface::class)->info('Registered modules: ' . implode(', ', $modules));
} catch (\Exception $e) {
    $container->get(Psr\Log\LoggerInterface::class)->error('Module registration failed: ' . $e->getMessage());
    throw $e;
}

// Add middleware AFTER modules are registered
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true, $container->get(Psr\Log\LoggerInterface::class));

// Boot all modules
try {
    $moduleProvider->boot();
} catch (Exception $e) {
    $container->get(Psr\Log\LoggerInterface::class)->error('Failed to boot modules: ' . $e->getMessage());
    throw $e;
}

// Return the application for testing
return $app;
