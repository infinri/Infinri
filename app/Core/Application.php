<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core;

use App\Core\Concerns\ManagesPaths;
use App\Core\Concerns\ManagesProviders;
use App\Core\Config\Config;
use App\Core\Container\Container;
use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Contracts\Container\ContainerInterface;
use App\Core\Contracts\Log\LoggerInterface;
use App\Core\Indexer\IndexerRegistry;
use App\Core\Log\LogManager;
use App\Core\Module\ModuleLoader;
use App\Core\Module\ModuleRegistry;
use App\Core\Support\Environment;
use App\Core\Support\Str;
use RuntimeException;

/**
 * Application
 *
 * The core application class that bootstraps the framework.
 * Uses traits for path and provider management (Single Responsibility).
 */
class Application extends Container
{
    use ManagesPaths;
    use ManagesProviders;

    /**
     * The Infinri framework version
     */
    public const VERSION = '0.1.0';

    /**
     * The base path for the installation
     */
    protected string $basePath;

    /**
     * Indicates if the application has been bootstrapped
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * The module loader
     */
    protected ?ModuleLoader $moduleLoader = null;

    /**
     * The application instance
     */
    protected static ?Application $instance = null;

    /**
     * Create a new application instance
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        if (static::$instance !== null) {
            throw new RuntimeException('Application instance already exists');
        }

        $this->basePath = rtrim($basePath, '\\/');

        static::$instance = $this;

        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
    }

    /**
     * Get the application instance
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            throw new RuntimeException('Application not instantiated');
        }

        return static::$instance;
    }

    /**
     * Reset the application instance (for testing)
     *
     * @return void
     */
    public static function resetInstance(): void
    {
        static::$instance = null;
    }

    /**
     * Get the version number of the application
     *
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Register the basic bindings into the container
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        $this->instance('app', $this);
        $this->instance(Application::class, $this);
        $this->instance(Container::class, $this);
        $this->instance(ContainerInterface::class, $this);
    }

    /**
     * Register core container aliases
     *
     * @return void
     */
    protected function registerCoreContainerAliases(): void
    {
        $this->alias(ContainerInterface::class, 'app');
        $this->alias(Application::class, 'app');
        $this->alias(ConfigInterface::class, 'config');
        $this->alias(LoggerInterface::class, 'logger');
    }

    /**
     * Bootstrap the application
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if ($this->hasBeenBootstrapped) {
            return;
        }

        // Load environment variables
        $this->loadEnvironment();

        // Register configuration
        $this->registerConfiguration();

        // Register logger
        $this->registerLogger();

        // Register core services (cache, session, security, auth)
        $this->register(CoreServiceProvider::class);

        // Load modules and their service providers
        $this->loadModules();

        // Boot all service providers
        $this->boot();

        // Log application start to system channel
        $logger = logger();
        if ($logger instanceof LogManager) {
            $logger->system('Application bootstrapped', [
                'version' => $this->version(),
                'environment' => env('APP_ENV', 'production'),
                'php_version' => PHP_VERSION,
                'base_path' => $this->basePath,
                'modules_loaded' => count($this->getModuleLoader()->getModules()),
            ]);
        }

        $this->hasBeenBootstrapped = true;
    }

    /**
     * Load modules and their service providers
     *
     * @return void
     */
    protected function loadModules(): void
    {
        $this->moduleLoader = new ModuleLoader($this);

        // Bind to container
        $this->instance(ModuleLoader::class, $this->moduleLoader);
        $this->instance(ModuleRegistry::class, $this->moduleLoader->getRegistry());

        // Register indexer registry (modules can add their indexers)
        $this->singleton(IndexerRegistry::class, function () {
            return new IndexerRegistry();
        });

        // Load all modules
        $this->moduleLoader->load();
    }

    /**
     * Get the module loader
     *
     * @return ModuleLoader
     */
    public function getModuleLoader(): ModuleLoader
    {
        if ($this->moduleLoader === null) {
            $this->moduleLoader = new ModuleLoader($this);
        }

        return $this->moduleLoader;
    }

    /**
     * Load the environment file
     *
     * @return void
     */
    protected function loadEnvironment(): void
    {
        $env = new Environment($this->basePath);

        try {
            $env->load();
        } catch (RuntimeException $e) {
            // In production, we might want to handle this differently
            // For now, we'll just let it throw
            throw $e;
        }
    }

    /**
     * Register configuration
     *
     * @return void
     */
    protected function registerConfiguration(): void
    {
        $config = new Config();

        // Load app configuration
        $config->set('app', [
            'name' => env('APP_NAME', 'Infinri'),
            'env' => env('APP_ENV', 'production'),
            'debug' => env('APP_DEBUG', false),
            'url' => env('APP_URL', 'http://localhost'),
            'timezone' => env('APP_TIMEZONE', 'UTC'),
        ]);

        $this->instance(ConfigInterface::class, $config);
        $this->instance(Config::class, $config);
    }

    /**
     * Register logger
     *
     * @return void
     */
    protected function registerLogger(): void
    {
        $logDirectory = $this->storagePath('log');

        $logger = new LogManager($logDirectory);

        // Set correlation ID if we're in a web context
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $logger->setCorrelationId($this->generateRequestId());
        }

        // Add environment context
        $logger->addGlobalContext('environment', env('APP_ENV', 'production'));
        $logger->addGlobalContext('app_name', env('APP_NAME', 'Infinri'));

        $this->instance(LoggerInterface::class, $logger);
        $this->instance(LogManager::class, $logger);
    }

    /**
     * Generate a unique request ID
     *
     * @return string
     */
    protected function generateRequestId(): string
    {
        return 'req_' . Str::randomHex(6);
    }

    /**
     * Determine if the application is in debug mode
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return config('app.debug', false) === true;
    }

    /**
     * Get the environment the application is running in
     *
     * @return string
     */
    public function environment(): string
    {
        return config('app.env', 'production');
    }

    /**
     * Determine if the application is running in the console
     *
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }
}
