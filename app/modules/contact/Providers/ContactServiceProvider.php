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
namespace App\Modules\Contact\Providers;

use App\Core\Container\ServiceProvider;

/**
 * Contact Service Provider
 * 
 * Registers contact module services.
 */
class ContactServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     */
    public function register(): void
    {
        // Register contact-specific services here
        // Example: $this->app->bind(ContactFormInterface::class, ContactForm::class);
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        // Bootstrap contact module
        // Example: load views, routes, configs
    }
}
