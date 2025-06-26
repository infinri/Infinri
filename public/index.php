<?php declare(strict_types=1);

use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
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

// Register Plates view renderer
$container->set('view', function (ContainerInterface $c) {
    $settings = $c->get('settings');
    
    // Create view with empty path (paths will be added by modules)
    $view = new PlatesEngine(null, 'phtml');
    
    // Add app data to all views
    $view->addData([
        'app' => [
            'name' => $settings['app']['name'],
            'env' => $settings['app']['env'],
            'url' => rtrim($settings['app']['url'], '/'),
        ]
    ]);
    
    return $view;
});

// Initialize and register modules
$moduleProvider = new ModuleServiceProvider($container);
$modules = [
    'App\\Modules\\Core\\CoreModule',
    'App\\Modules\\Pages\\PagesModule',
    'App\\Modules\\Contact\\ContactModule',
];

// Register all modules
$moduleProvider->register($modules);

// Create App instance
$app = AppFactory::create(container: $container);

// Add Plates middleware to inject common view data
$app->add(function ($request, $handler) use ($container) {
    $view = $container->get('view');
    $view->addData([
        'currentPath' => $request->getUri()->getPath(),
        'isHome' => $request->getUri()->getPath() === '/',
    ]);
    
    return $handler->handle($request);
});

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(
    ($_ENV['APP_DEBUG'] ?? '') === 'true',
    true,
    true,
    $container->get(LoggerInterface::class)
);

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
