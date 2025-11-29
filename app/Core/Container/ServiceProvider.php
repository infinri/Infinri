<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Container;

use App\Core\Contracts\Container\ContainerInterface;

/**
 * Service Provider Base Class
 * 
 * All service providers must extend this class
 */
abstract class ServiceProvider
{
    /**
     * The container instance
     *
     * @var ContainerInterface
     */
    protected ContainerInterface $app;

    /**
     * Create a new service provider instance
     *
     * @param ContainerInterface $app
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Register any application services
     *
     * This method is called first, before boot() on any provider.
     * Use this to bind services into the container.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Bootstrap any application services
     *
     * This method is called after all providers have been registered.
     * Use this to configure services, load routes, views, etc.
     *
     * @return void
     */
    public function boot(): void
    {
        // Optional method - override if needed
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred
     *
     * @return bool
     */
    public function isDeferred(): bool
    {
        return false;
    }
}
