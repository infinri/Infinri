<?php

declare(strict_types=1);

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
