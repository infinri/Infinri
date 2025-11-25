<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Container\Container;
use App\Core\Container\ServiceProvider;
use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Contracts\Container\ContainerInterface;
use App\Core\Contracts\Log\LoggerInterface;
use App\Core\Config\Config;
use App\Core\Log\Logger;
use App\Core\Support\Environment;

/**
 * Application
 * 
 * The core application class that bootstraps the framework
 */
class Application extends Container
{
    /**
     * The Infinri framework version
     *
     * @var string
     */
    const VERSION = '0.1.0';

    /**
     * The base path for the Infinri installation
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Indicates if the application has been bootstrapped
     *
     * @var bool
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * The array of service providers
     *
     * @var array<ServiceProvider>
     */
    protected array $serviceProviders = [];

    /**
     * The names of the loaded service providers
     *
     * @var array<string, bool>
     */
    protected array $loadedProviders = [];

    /**
     * The application instance
     *
     * @var static|null
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
            throw new \RuntimeException('Application instance already exists');
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
            throw new \RuntimeException('Application not instantiated');
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

        // Log application start
        logger()->info('Application bootstrapped', [
            'version' => $this->version(),
            'environment' => env('APP_ENV', 'production'),
        ]);

        $this->hasBeenBootstrapped = true;
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
        } catch (\RuntimeException $e) {
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
        $logPath = $this->storagePath('log/app.log');
        
        $logger = new Logger($logPath);

        // Set correlation ID if we're in a web context
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $logger->setCorrelationId($this->generateRequestId());
        }

        $this->instance(LoggerInterface::class, $logger);
        $this->instance(Logger::class, $logger);
    }

    /**
     * Generate a unique request ID
     *
     * @return string
     */
    protected function generateRequestId(): string
    {
        return 'req_' . bin2hex(random_bytes(6));
    }

    /**
     * Register a service provider
     *
     * @param ServiceProvider|string $provider
     * @param bool $force
     * @return ServiceProvider
     */
    public function register(ServiceProvider|string $provider, bool $force = false): ServiceProvider
    {
        // If already registered, return the existing instance
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        // Create instance if string was passed
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        // Call register method
        $provider->register();

        // Mark as loaded
        $this->markAsRegistered($provider);

        // If already bootstrapped, call boot immediately
        if ($this->hasBeenBootstrapped) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get a registered service provider
     *
     * @param ServiceProvider|string $provider
     * @return ServiceProvider|null
     */
    public function getProvider(ServiceProvider|string $provider): ?ServiceProvider
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        foreach ($this->serviceProviders as $value) {
            if ($value instanceof $name) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Mark the given provider as registered
     *
     * @param ServiceProvider $provider
     * @return void
     */
    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Boot the given service provider
     *
     * @param ServiceProvider $provider
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $provider->boot();
        }
    }

    /**
     * Boot all registered providers
     *
     * @return void
     */
    public function boot(): void
    {
        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }
    }

    /**
     * Get the base path of the Infinri installation
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the application "app" directory
     *
     * @param string $path
     * @return string
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the storage directory
     *
     * @param string $path
     * @return string
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('var' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the public directory
     *
     * @param string $path
     * @return string
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath('pub' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the configuration directory
     *
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
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
