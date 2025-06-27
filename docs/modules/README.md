# Module System Documentation

## Table of Contents

- [Overview](#overview)
- [Getting Started](#getting-started)
  - [Creating a Module](#creating-a-module)
  - [Module Structure](#module-structure)
- [Core Components](#core-components)
  - [Module Lifecycle](module-lifecycle.md)
  - [Module Discovery](discovery.md)
  - [Module Registry](registry.md)
  - [Dependency Resolution](dependency-resolution.md)
- [Development Guide](development-guide.md)
- [Advanced Topics](#advanced-topics)
  - [Service Providers](#service-providers)
  - [Event System](#event-system)
  - [Testing](#testing)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [API Reference](#api-reference)

## Overview

The module system provides a powerful way to organize your application into reusable, independent components. Each module is a self-contained unit that can be developed, tested, and maintained separately.

### Key Features

- **Modular Architecture**: Build your application from independent, reusable modules
- **Dependency Management**: Declare and resolve module dependencies automatically
- **Service Discovery**: Automatic registration of services and components
- **Lifecycle Management**: Clear hooks for module initialization and cleanup
- **Performance Optimized**: Lazy loading and intelligent caching

## Getting Started

### Creating a Module

1. Create a new module class that implements `ModuleInterface`
2. Define your module's dependencies
3. Register services in the `register()` method
4. Initialize your module in the `boot()` method

Example:

```php
namespace App\Modules\MyModule;

use App\Modules\ModuleInterface;
use Psr\Container\ContainerInterface;

class MyModule implements ModuleInterface
{
    public function __construct(private ContainerInterface $container) {}
    
    public function register(): void
    {
        // Register services here
    }
    
    public function boot(): void
    {
        // Initialize your module
    }
}
```

For a complete guide, see the [Development Guide](development-guide.md).

## Module Structure

A well-structured module follows these conventions:

```
app/Modules/YourModule/
├── Config/                  # Configuration files
│   └── config.php
├── Controllers/             # HTTP controllers
│   └── YourController.php
├── Models/                  # Database models
│   └── YourModel.php
├── Services/                # Business logic
│   └── YourService.php
├── Events/                  # Event classes
│   └── YourEvent.php
├── Listeners/               # Event listeners
│   └── YourListener.php
├── Resources/               # Templates, translations, etc.
│   ├── views/
│   └── translations/
├── Tests/                   # Test classes
│   └── YourServiceTest.php
├── YourModule.php           # Main module class
└── composer.json            # Module metadata and dependencies
```

### Core Components

- **[Module Lifecycle](module-lifecycle.md)**: Understand how modules are loaded and initialized
- **[Module Discovery](discovery.md)**: How the system finds and loads modules
- **[Module Registry](registry.md)**: Manage module instances and metadata
- **[Dependency Resolution](dependency-resolution.md)**: Handle module dependencies and versioning

## Advanced Topics

### Service Providers

Service providers allow you to organize your service registration into separate classes:

```php
class YourServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->set('your_service', function() {
            return new YourService();
        });
    }
}
```

### Event System

Modules can publish and subscribe to events:

```php
// Dispatching an event
$event = new UserRegisteredEvent($user);
$dispatcher = $container->get('event_dispatcher');
$dispatcher->dispatch($event, UserRegisteredEvent::NAME);

// Listening to an event
$dispatcher->addListener(
    UserRegisteredEvent::NAME,
    function (UserRegisteredEvent $event) {
        // Handle the event
    }
);
```

### Testing

Test your modules in isolation:

```php
class YourModuleTest extends ModuleTestCase
{
    protected function getModules(): array
    {
        return [
            new YourModule($this->container),
        ];
    }
    
    public function testModuleRegistration()
    {
        $this->assertTrue($this->container->has('your_service'));
    }
}
```
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

1. **Single Responsibility**: Each module should have a single, well-defined purpose
2. **Loose Coupling**: Depend on interfaces rather than concrete implementations
3. **Explicit Dependencies**: Clearly declare all module dependencies
4. **Configuration**: Make your module configurable through the container
5. **Error Handling**: Provide clear, actionable error messages
6. **Documentation**: Document your module's purpose, configuration, and API
7. **Testing**: Include unit and integration tests
8. **Performance**: Be mindful of initialization time in the `register()` method
9. **Versioning**: Follow semantic versioning for your modules
10. **Backward Compatibility**: Maintain backward compatibility in public APIs

## Troubleshooting

### Common Issues

#### Module Not Found
- Verify the module class exists and is autoloadable
- Check the module's namespace and file location
- Run `composer dump-autoload` if using Composer

#### Dependency Issues
- Check module dependencies in `getDependencies()`
- Verify required modules are installed
- Check version constraints for compatibility

#### Service Not Available
- Ensure the service is registered in the container
- Verify the service name matches exactly
- Check for typos in the service name

## API Reference

For detailed API documentation, see:

- [ModuleInterface](path/to/ModuleInterface.md)
- [ModuleLoader](path/to/ModuleLoader.md)
- [ModuleRegistry](path/to/ModuleRegistry.md)
- [DependencyResolver](path/to/DependencyResolver.md)

## Contributing

We welcome contributions! Please see our [contributing guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

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
