<?php declare(strict_types=1);

namespace App\Modules\Shared;

use App\Modules\BaseModule;
use App\Modules\Shared\Middleware\RateLimitMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Redis;

/**
 * Shared module for cross-cutting concerns
 * 
 * This module provides shared functionality used across multiple modules,
 * such as middleware, utilities, and common services.
 */
class SharedModule extends BaseModule
{
    /**
     * Register shared services and middleware
     * 
     * Registers rate limiting middleware and other shared services.
     */
    public function register(): void
    {
        $this->registerMiddleware();
        $this->registerServices();
    }

    /**
     * Boot the shared module
     * 
     * This method is called after all modules have been registered.
     */
    public function boot(): void
    {
        // Shared module bootstrapping logic can be added here
    }

    /**
     * Register shared middleware
     */
    private function registerMiddleware(): void
    {
        $this->container->set(RateLimitMiddleware::class, function(ContainerInterface $c) {
            return new RateLimitMiddleware(
                $c->get(Redis::class),
                $c->get('settings')['rate_limiting'] ?? [],
                $c->get(LoggerInterface::class)
            );
        });
    }

    /**
     * Register shared services
     */
    private function registerServices(): void
    {
        // Register additional shared services here
    }

    /**
     * Check environment compatibility (stub).
     */
    public function isCompatible(): bool
    {
        return true;
    }
}
