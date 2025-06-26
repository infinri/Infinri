# Module System Documentation

## Table of Contents
1. [Overview](#overview)
2. [Module Structure](#module-structure)
3. [Core Concepts](#core-concepts)
   - [Module Lifecycle](#module-lifecycle)
   - [Service Registration](#service-registration)
   - [Dependency Injection](#dependency-injection)
   - [Module Dependencies](#module-dependencies)
4. [Creating a New Module](#creating-a-new-module)
5. [Best Practices](#best-practices)
6. [Advanced Topics](#advanced-topics)
   - [Module Discovery](#module-discovery)
   - [View Registration](#view-registration)
   - [Controller Registration](#controller-registration)
7. [Testing Modules](#testing-modules)
8. [Troubleshooting](#troubleshooting)

## Overview

The module system is designed to provide a clean, organized way to structure your application's features. Each module is a self-contained component that can be developed and tested independently, then integrated into the main application.

Key benefits:
- **Separation of Concerns**: Each module focuses on a specific feature or domain
- **Reusability**: Modules can be reused across different projects
- **Maintainability**: Smaller, focused modules are easier to maintain
- **Testability**: Modules can be tested in isolation

## Module Structure

A typical module has the following structure:

```
app/Modules/
  ├── ModuleName/
  │   ├── Controllers/     # Module controllers
  │   ├── Models/          # Module models
  │   ├── Services/        # Business logic services
  │   ├── Middleware/      # HTTP middleware
  │   ├── Views/           # View templates
  │   ├── ModuleName.php   # Main module class
  │   └── routes.php       # Module routes (optional)
```

## Core Concepts

### Module Lifecycle

1. **Registration Phase**
   - The module's `register()` method is called
   - Register services, controllers, and other resources
   - Should not depend on other modules' services
   - Use `ModuleManager` for automatic dependency resolution

2. **Boot Phase**
   - The `boot()` method is called after all modules are registered
   - Safe to use services from other modules
   - Register event listeners, middleware, etc.
   - All modules' `boot()` methods are called in dependency order

### Module Dependencies

Modules can declare dependencies on other modules using the `getDependencies()` method:

```php
public function getDependencies(): array
{
    return [
        \App\Modules\Core\CoreModule::class,
        \App\Modules\Auth\AuthModule::class
    ];
}
```

The module system ensures that dependencies are loaded in the correct order, and will detect circular dependencies.

### Service Registration

Modules should register their services in the `register()` method. The container provides several ways to register services:

#### Basic Service Registration

```php
public function register(): void
{
    $this->container->set(MyService::class, function(ContainerInterface $c) {
        return new MyService($c->get(Dependency::class));
    });
}
```

#### Extending Existing Services

Use the `extend()` method to modify existing services:

```php
public function register(): void
{
    $this->container->extend('view.paths', function(array $paths) {
        $paths[] = $this->getViewsPath();
        return $paths;
    });
}
```

#### Using Traits for Common Functionality

Common registration patterns are available as traits:

- `RegistersViewPaths`: For adding view paths
- `RegistersControllers`: For controller registration

Example:

```php
use App\Modules\Concerns\RegistersViewPaths;
use App\Modules\Concerns\RegistersControllers;

class MyModule extends Module
{
    use RegistersViewPaths, RegistersControllers;
    
    public function register(): void
    {
        $this->registerViewPaths();
        $this->registerControllers([
            MyController::class => [MyService::class]
        ]);
    }
}
```

### Dependency Injection

The application uses constructor injection for dependencies. Always type-hint your dependencies:

```php
class MyController
{
    public function __construct(
        private MyService $service,
        private LoggerInterface $logger
    ) {}
}
```

## Creating a New Module

1. Create a new directory in `app/Modules/`
2. Create your module class (e.g., `MyModule.php`)
3. Extend the base `Module` class
4. Implement the required methods
5. The module will be automatically discovered and registered

Example module class:

```php
<?php declare(strict_types=1);

namespace App\Modules\MyModule;

use App\Modules\Module;
use App\Modules\Concerns\RegistersViewPaths;
use App\Modules\Concerns\RegistersControllers;

class MyModule extends Module
{
    use RegistersViewPaths, RegistersControllers;
    
    /**
     * Get module dependencies
     */
    public function getDependencies(): array
    {
        return [
            \App\Modules\Core\CoreModule::class
        ];
    }
    
    /**
     * Register module services
     */
    public function register(): void
    {
        // Register view paths
        $this->registerViewPaths();
        
        // Register controllers
        $this->registerControllers([
            Controllers\MyController::class => [
                'myService' => Services\MyService::class
            ]
        ]);
        
        // Register services
        $this->container->set(Services\MyService::class, function($c) {
            return new Services\MyService(
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
    }
    
    /**
     * Boot the module
     */
    public function boot(): void
    {
        // Module is fully booted, all services are available
        if ($this->container->has(\Psr\Log\LoggerInterface::class)) {
            $this->container->get(\Psr\Log\LoggerInterface::class)
                ->debug('MyModule has been booted');
        }
    }
        // Register services here
    }
    
    public function boot(): void
    {
        // Boot logic here
    }
    
    public function getBasePath(): string
    {
        return __DIR__;
    }
    
    public function getViewsPath(): string
    {
        return $this->getBasePath() . '/Views';
    }
    
    public function getNamespace(): string
    {
        return __NAMESPACE__;
    }
}

## Best Practices

- **Single Responsibility**: Each module should have a single responsibility
- **Dependency Injection**: Use constructor injection for all dependencies
- **Thin Controllers**: Keep controllers thin, move business logic to services
- **Testing**: Write unit and integration tests for your modules
- **Documentation**: Document your module's purpose and usage
- **Error Handling**: Implement proper error handling and logging
- **Configuration**: Use environment variables for configuration
- **Performance**: Be mindful of performance in the register() method

## Module Discovery

The module system supports automatic module discovery. By default, it looks for modules in the `app/Modules` directory. Each module should be in its own subdirectory with a `{ModuleName}Module.php` file.

## View Registration

Modules can register their view paths using the `RegistersViewPaths` trait:

```php
use App\Modules\Concerns\RegistersViewPaths;

class MyModule extends Module
{
    use RegistersViewPaths;
    
    public function register(): void
    {
        $this->registerViewPaths();
    }
}
```

## Controller Registration

Use the `RegistersControllers` trait to register controllers with their dependencies:

```php
use App\Modules\Concerns\RegistersControllers;

class MyModule extends Module
{
    use RegistersControllers;
    
    public function register(): void
    {
        $this->registerControllers([
            Controllers\MyController::class => [
                'myService' => Services\MyService::class,
                'logger' => \Psr\Log\LoggerInterface::class
            ]
        ]);
    }
}
```

## Testing Modules

Modules should include their own tests. The test suite provides a `ModuleTestCase` base class:

```php
use Tests\ModuleTestCase;

class MyModuleTest extends ModuleTestCase
{
    protected function getModuleClass(): string
    {
        return \App\Modules\MyModule\MyModule::class;
    }
    
    public function testModuleRegistration()
    {
        $this->assertTrue($this->container->has(\App\Modules\MyModule\Services\MyService::class));
    }
}
```

## Troubleshooting

### Circular Dependencies

If you encounter a circular dependency error, check your module's `getDependencies()` method to ensure there are no circular references between modules.

### Service Not Found

If a service is not found:
1. Verify the service is registered in the module's `register()` method
2. Check that the module's dependencies are properly declared
3. Ensure the service name matches exactly (case-sensitive)

### View Paths Not Registered

If views aren't being found:
1. Make sure your module uses the `RegistersViewPaths` trait
2. Call `$this->registerViewPaths()` in the `register()` method
3. Verify the views directory exists and has the correct permissions

## Advanced Topics

### Module Configuration

Modules can define their own configuration. The recommended approach is to use environment variables with sensible defaults:

```php
// In your module's service registration
$this->container->set('my_module.config', function($c) {
    return [
        'enabled' => (bool)($_ENV['MY_MODULE_ENABLED'] ?? true),
        'api_key' => $_ENV['MY_MODULE_API_KEY'] ?? null,
    ];
});
```

### Events and Hooks

The module system supports an event-driven architecture. Modules can subscribe to and dispatch events:

```php
// In register() method
$this->container->get('event_dispatcher')->addListener(
    'some.event',
    [$this, 'onSomeEvent']
);

// Event handler method
public function onSomeEvent(Event $event): void
{
    // Handle the event
}
```

### Database Migrations

Modules can include database migrations. Place migration files in a `Migrations` directory within your module and register them in your module class:

```php
public function register(): void
{
    $this->registerMigrations(__DIR__ . '/Migrations');
}
```

### Console Commands

Register console commands in your module's `register()` method:

```php
if ($this->container->has('console')) {
    $this->container->get('console')->add(new MyCommand());
}
```

### Testing

Use the `ModuleTestCase` as a base for your module tests:

```php
class MyModuleTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new MyModule($this->container);
    }
    
    // Test methods...
}
```
