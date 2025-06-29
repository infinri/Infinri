<?php declare(strict_types=1);

namespace App\Modules\Core;

use App\Modules\Concerns\RegistersViewPaths;
use App\Modules\Concerns\RegistersControllers;
use App\Modules\Core\Controllers\Controller as CoreController;
use App\Modules\Core\Controllers\HomeController;
use App\Modules\Core\Services\NotificationService;
use App\Modules\BaseModule;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\RouteParserInterface;

/**
 * Core module for the application
 * 
 * This module provides essential services and configurations that are required
 * for the application to function properly. It's automatically loaded and booted
 * during the application bootstrap process.
 */
class CoreModule extends BaseModule
{
    use RegistersViewPaths, RegistersControllers;

    /**
     * Register core services and configurations
     * 
     * Registers view paths, router, notification service, and core controllers.
     * This method is called during the application bootstrap process.
     */
    public function register(): void
    {
        $this->registerViewPaths();
        $this->registerRouter();
        $this->registerNotificationService();
        $this->registerControllers();
    }

    /**
     * Boot the core module
     * 
     * This method is called after all modules have been registered.
     * Can be used to perform initialization that requires access to other services.
     */
    public function boot(): void
    {
        // Core module bootstrapping logic can be added here
    }

    /**
     * Register router and route parser
     */
    private function registerRouter(): void
    {
        // Register RouteParserInterface if not already registered
        if (!$this->container->has('router')) {
            $this->container->set('router', function(ContainerInterface $c) {
                return $c->get('app')->getRouteCollector()->getRouteParser();
            });
        }
        
        // Register RouteParserInterface alias for type-hinting
        if (!$this->container->has(RouteParserInterface::class)) {
            $this->container->set(
                RouteParserInterface::class,
                function(ContainerInterface $c) {
                    return $c->get('router');
                }
            );
        }
    }

    /**
     * Register the notification service
     */
    private function registerNotificationService(): void
    {
        $this->container->set(NotificationService::class, function(ContainerInterface $c) {
            $config = $c->get('settings')['brevo'] ?? [];
            
            return new NotificationService(
                $c->get(LoggerInterface::class),
                $config['api_key'] ?? null,
                $config['from_email'] ?? $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
                $config['from_name'] ?? $_ENV['MAIL_FROM_NAME'] ?? 'Notification System',
                (bool)($config['enabled'] ?? true)
            );
        });
    }

    /**
     * Register core controllers
     */
    private function registerControllers(): void
    {
        // Register base controller
        $this->registerController(CoreController::class, [
            'view',
            'container' => ContainerInterface::class,
            LoggerInterface::class
        ]);
        
        // Register home controller
        $this->registerController(HomeController::class, [
            'view',
            'router' => RouteParserInterface::class,
        ]);
    }
    
    /**
     * Check if the module is compatible with the current environment
     * 
     * @return bool True if the module can be loaded, false otherwise
     */
    public function isCompatible(): bool
    {
        // Check for required PHP extensions
        $requiredExtensions = [
            'pdo',
            'pdo_mysql',
            'json',
            'mbstring',
            'openssl',
        ];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                trigger_error(
                    sprintf('Missing required PHP extension: %s', $ext),
                    E_USER_WARNING
                );
                return false;
            }
        }
        
        // Check for required environment variables
        $requiredEnvVars = [
            'APP_ENV',
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
            'DB_PASS',
            'APP_SECRET',
        ];
        
        foreach ($requiredEnvVars as $var) {
            if (empty($_ENV[$var] ?? null)) {
                trigger_error(
                    sprintf('Missing required environment variable: %s', $var),
                    E_USER_WARNING
                );
                return false;
            }
        }
        
        return true;
    }
}
