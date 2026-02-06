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

use App\Core\Authorization\Gate;
use App\Core\Cache\CacheManager;
use App\Core\Cache\FileStore;
use App\Core\Container\ServiceProvider;
use App\Core\Contracts\Cache\CacheInterface;
use App\Core\Security\Csrf;
use App\Core\Security\RateLimiter;
use App\Core\Session\SessionManager;

/**
 * Core Service Provider
 *
 * Registers core framework services in the container.
 * Centralizes service registration to enforce DI principles.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCacheServices();
        $this->registerSecurityServices();
        $this->registerSessionServices();
        $this->registerAuthorizationServices();
    }

    protected function registerCacheServices(): void
    {
        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager([
                'default' => env('CACHE_DRIVER', 'file'),
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => $app->storagePath('cache'),
                    ],
                    'array' => [
                        'driver' => 'array',
                    ],
                ],
            ], $app->basePath());
        });

        $this->app->alias(CacheManager::class, CacheInterface::class);
        $this->app->alias(CacheManager::class, 'cache');
    }

    protected function registerSecurityServices(): void
    {
        $this->app->singleton(Csrf::class, fn () => new Csrf());
        $this->app->alias(Csrf::class, 'csrf');

        $this->app->singleton(RateLimiter::class, function ($app) {
            return new RateLimiter(
                new FileStore($app->storagePath('cache/rate_limits'))
            );
        });
        $this->app->alias(RateLimiter::class, 'rate_limiter');
    }

    protected function registerSessionServices(): void
    {
        $this->app->singleton(SessionManager::class, fn () => new SessionManager());
        $this->app->alias(SessionManager::class, 'session');
    }

    protected function registerAuthorizationServices(): void
    {
        $this->app->singleton(Gate::class, fn () => new Gate());
        $this->app->alias(Gate::class, 'gate');
    }

    public function boot(): void
    {
        // Services are now available via container
    }
}
