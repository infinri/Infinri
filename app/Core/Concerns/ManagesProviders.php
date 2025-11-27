<?php

declare(strict_types=1);

namespace App\Core\Concerns;

use App\Core\Container\ServiceProvider;

/**
 * Manages Providers
 * 
 * Provides service provider registration and boot logic.
 * Follows Single Responsibility Principle - only handles provider lifecycle.
 * 
 * Requires: protected bool $hasBeenBootstrapped to be defined in the using class.
 */
trait ManagesProviders
{
    /**
     * The array of service providers
     */
    protected array $serviceProviders = [];

    /**
     * The array of registered provider classes
     */
    protected array $loadedProviders = [];

    /**
     * Register a service provider
     */
    public function register(ServiceProvider|string $provider, bool $force = false): ServiceProvider
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        $this->markAsRegistered($provider);

        if ($this->hasBeenBootstrapped) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance
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
     * Get all registered providers
     */
    public function getProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * Mark the given provider as registered
     */
    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Boot the given service provider
     */
    protected function bootProvider(ServiceProvider $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $provider->boot();
        }
    }

    /**
     * Boot all registered service providers
     */
    public function boot(): void
    {
        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }
    }

    /**
     * Check if a provider has been loaded
     */
    public function providerIsLoaded(string $provider): bool
    {
        return isset($this->loadedProviders[$provider]);
    }
}
