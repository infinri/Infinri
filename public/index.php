<?php declare(strict_types=1);

use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Monolog\Level;
use Slim\Factory\AppFactory;
use Slim\Views\PlatesRenderer;

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Check if running in built-in PHP server and serve static files
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Compile opcache for better performance in production
if (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true) && extension_loaded('opcache')) {
    opcache_compile_file(__DIR__ . '/../vendor/autoload.php');
}

// Create Container using PHP-DI
$container = new Container();

// Create Slim App early so router is available for modules
$app = AppFactory::create(container: $container);
// Expose router via container for modules expecting it
$container->set('router', $app);
$container->set(\Slim\Interfaces\RouteParserInterface::class, function (ContainerInterface $c) {
    /** @var \Slim\App $router */
    $router = $c->get('router');
    return $router->getRouteCollector()->getRouteParser();
});

// Register app settings
$container->set('settings', function () {
    return [
        'app' => [
            'name' => $_ENV['APP_NAME'] ?? 'Infinri',
            'env' => $_ENV['APP_ENV'] ?? 'production',
            'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        ],
        'view' => [
            'cache' => $_ENV['VIEW_CACHE'] ?? false,
        ],
    ];
});

// Register logger
$container->set(LoggerInterface::class, function (ContainerInterface $c) {
    $settings = $c->get('settings');
    $logger = new Logger($settings['app']['name']);
    
    $processor = new UidProcessor();
    $logger->pushProcessor($processor);
    
    $handler = new StreamHandler(
        dirname(__DIR__) . '/storage/logs/app.log',
        $settings['app']['debug'] ? Logger::DEBUG : Logger::INFO
    );
    $logger->pushHandler($handler);
    
    return $logger;
});

// Register simple navigation service
$container->set('navigation', function () {
    return new class {
        private array $items = [];
        public function addItem(string $key, array $config): void { $this->items[$key] = $config; }
        public function getItems(): array { return $this->items; }
    };
});

// Register Plates view engine
$container->set('view', function (ContainerInterface $c) {
    $settings = $c->get('settings');
    
    // Set the default templates directory (relative to the project root)
    $templatesPath = __DIR__ . '/../resources/views';
    
    // Create view engine with the templates directory
    $engine = new \League\Plates\Engine($templatesPath, 'php');
    
    // Add app data to all views
    $engine->addData([
        'app' => [
            'name' => $settings['app']['name'],
            'env' => $settings['app']['env'],
            'url' => rtrim($settings['app']['url'], '/'),
        ]
    ]);
    
    // Enable debug mode if in development
    if ($settings['app']['debug']) {
        $engine->addData(['debug' => true]);
        // Set file extension to empty string to match our template files without extension
        $engine->setFileExtension('');
    }
    
    return $engine;
});

// Initialize and register modules
$moduleProvider = new \App\Providers\ModuleServiceProvider(
    $container,
    dirname(__DIR__) . '/app/Modules'
);

// Register all modules (will auto-discover if no modules provided)
try {
    $modules = $moduleProvider->register();
    $container->get(LoggerInterface::class)->info('Registered modules: ' . implode(', ', $modules));
} catch (\Exception $e) {
    $container->get(LoggerInterface::class)->error('Module registration failed: ' . $e->getMessage());
    throw $e;
}

// Slim app already created above

// Add Plates middleware to inject common view data
$app->add(function ($request, $handler) use ($container) {
    $view = $container->get('view');
    $view->addData([
        'currentPath' => $request->getUri()->getPath(),
        'isHome' => $request->getUri()->getPath() === '/',
    ]);
    
    return $handler->handle($request);
});

// Add error middleware with detailed error reporting
$errorMiddleware = $app->addErrorMiddleware(
    true, // Always enable error details for now
    true,
    true,
    $container->get(LoggerInterface::class)
);

// Custom error handler
$errorMiddleware->setDefaultErrorHandler(function (
    Psr\Http\Message\ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        '<h1>Application Error</h1>' .
        '<h2>' . htmlspecialchars($exception->getMessage()) . '</h2>' .
        '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>'
    );
    
    return $response->withStatus(500);
});

// Add routing middleware
$app->addRoutingMiddleware();

// Register module routes
$routeFiles = [
    __DIR__ . '/../app/Modules/Core/routes.php',
    __DIR__ . '/../app/Modules/Pages/routes.php',
    __DIR__ . '/../app/Modules/Contact/routes.php',
];

foreach ($routeFiles as $routeFile) {
    if (file_exists($routeFile)) {
        $routeCallback = require $routeFile;
        if (is_callable($routeCallback)) {
            $routeCallback($app);
        }
    }
}

// Boot all modules
try {
    $moduleProvider->boot();
} catch (Exception $e) {
    $container->get(LoggerInterface::class)->error('Failed to boot modules: ' . $e->getMessage());
    throw $e;
}

// Run the application
$app->run();
