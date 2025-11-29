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
namespace Tests\Unit\Container;

use App\Core\Container\Container;
use App\Core\Container\ServiceProvider;
use App\Core\Contracts\Container\ContainerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    #[Test]
    public function it_can_be_instantiated_with_container(): void
    {
        $provider = new ConcreteServiceProvider($this->container);
        
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    #[Test]
    public function it_has_access_to_container_via_app_property(): void
    {
        $provider = new ConcreteServiceProvider($this->container);
        
        $this->assertSame($this->container, $provider->getApp());
    }

    #[Test]
    public function it_calls_register_method(): void
    {
        $provider = new ConcreteServiceProvider($this->container);
        
        $provider->register();
        
        $this->assertTrue($provider->wasRegistered());
    }

    #[Test]
    public function it_has_optional_boot_method(): void
    {
        $provider = new ConcreteServiceProvider($this->container);
        
        // Should not throw - boot is optional
        $provider->boot();
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_override_boot_method(): void
    {
        $provider = new BootableServiceProvider($this->container);
        
        $provider->boot();
        
        $this->assertTrue($provider->wasBooted());
    }

    #[Test]
    public function it_returns_empty_provides_array_by_default(): void
    {
        $provider = new ConcreteServiceProvider($this->container);
        
        $this->assertSame([], $provider->provides());
    }

    #[Test]
    public function it_can_override_provides_method(): void
    {
        $provider = new ProvidingServiceProvider($this->container);
        
        $this->assertSame(['service.one', 'service.two'], $provider->provides());
    }

    #[Test]
    public function it_is_not_deferred_by_default(): void
    {
        $provider = new ConcreteServiceProvider($this->container);
        
        $this->assertFalse($provider->isDeferred());
    }

    #[Test]
    public function it_can_be_deferred(): void
    {
        $provider = new DeferredServiceProvider($this->container);
        
        $this->assertTrue($provider->isDeferred());
    }

    #[Test]
    public function it_can_bind_services_in_register(): void
    {
        $provider = new BindingServiceProvider($this->container);
        
        $provider->register();
        
        $this->assertTrue($this->container->bound('test.service'));
    }
}

// Test fixtures

class ConcreteServiceProvider extends ServiceProvider
{
    private bool $registered = false;

    public function register(): void
    {
        $this->registered = true;
    }

    public function wasRegistered(): bool
    {
        return $this->registered;
    }

    public function getApp(): ContainerInterface
    {
        return $this->app;
    }
}

class BootableServiceProvider extends ServiceProvider
{
    private bool $booted = false;

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->booted = true;
    }

    public function wasBooted(): bool
    {
        return $this->booted;
    }
}

class ProvidingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function provides(): array
    {
        return ['service.one', 'service.two'];
    }
}

class DeferredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function isDeferred(): bool
    {
        return true;
    }
}

class BindingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('test.service', function () {
            return new \stdClass();
        });
    }
}
