# Module Development Guide

This guide provides a comprehensive overview of developing modules for the application. It covers everything from creating a basic module to advanced topics like dependency management and event handling.

## Table of Contents

- [Creating a New Module](#creating-a-new-module)
  - [Basic Structure](#basic-structure)
  - [Module Class](#module-class)
  - [Service Providers](#service-providers)
- [Module Lifecycle](#module-lifecycle)
  - [Registration Phase](#registration-phase)
  - [Boot Phase](#boot-phase)
  - [Runtime](#runtime)
  - [Shutdown](#shutdown)
- [Dependency Management](#dependency-management)
  - [Required Dependencies](#required-dependencies)
  - [Optional Dependencies](#optional-dependencies)
  - [Version Constraints](#version-constraints)
- [Configuration](#configuration)
  - [Module Configuration](#module-configuration)
  - [Service Configuration](#service-configuration)
- [Services and Dependency Injection](#services-and-dependency-injection)
  - [Defining Services](#defining-services)
  - [Service Tags](#service-tags)
  - [Lazy Loading](#lazy-loading)
- [Event System](#event-system)
  - [Defining Events](#defining-events)
  - [Dispatching Events](#dispatching-events)
  - [Listening to Events](#listening-to-events)
- [Testing Modules](#testing-modules)
  - [Unit Testing](#unit-testing)
  - [Integration Testing](#integration-testing)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## Creating a New Module

### Basic Structure

A typical module has the following directory structure:

```
app/Modules/YourModule/
├── Config/                  # Configuration files
│   └── config.php
├── Resources/               # Templates, translations, etc.
│   ├── views/
│   └── translations/
├── Services/                # Service classes
│   └── YourService.php
├── Tests/                   # Test classes
│   └── YourServiceTest.php
├── YourModule.php           # Main module class
└── composer.json            # Module metadata and dependencies
```

### Module Class

Every module must implement `ModuleInterface`. Here's a minimal example:

```php
<?php declare(strict_types=1);

namespace App\Modules\YourModule;

use App\Modules\ModuleInterface;
use Psr\Container\ContainerInterface;

class YourModule implements ModuleInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getName(): string
    {
        return 'your-module';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getOptionalDependencies(): array
    {
        return [];
    }

    public function register(): void
    {
        // Register services, routes, etc.
    }


    public function boot(): void
    {
        // All modules are registered, safe to use their services
    }

    public function shutdown(): void
    {
        // Cleanup resources
    }
}
```

### Service Providers

For larger modules, consider using service providers to organize your service registration:

```php
// In your module's register() method
public function register(): void
{
    $this->container->addServiceProvider(new YourServiceProvider());
}

// Service provider example
class YourServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->set('your_service', function() {
            return new YourService(
                $this->container->get('config')['your_module']
            );
        });
    }
}
```

## Module Lifecycle

### Registration Phase

During registration, your module should:
- Register services with the container
- Register routes
- Register event listeners
- Register configuration

Example:

```php
public function register(): void
{
    // Register configuration
    $this->container->extend('config', function($config) {
        return array_merge_recursive($config, [
            'your_module' => require __DIR__ . '/Config/config.php',
        ]);
    });
    
    // Register services
    $this->container->set(YourService::class, function() {
        return new YourService(
            $this->container->get('config')['your_module']
        );
    });
    
    // Register routes
    $router = $this->container->get('router');
    $router->addRoute('GET', '/your-route', [YourController::class, 'index']);
}
```

### Boot Phase

During the boot phase, you can:
- Initialize services that depend on other modules
- Register event subscribers
- Perform runtime configuration

```php
public function boot(): void
{
    // Example: Initialize when another module is available
    if ($this->container->has(FeatureFlagService::class)) {
        $this->initializeFeatureFlags();
    }
    
    // Register event subscribers
    $dispatcher = $this->container->get('event_dispatcher');
    $dispatcher->addSubscriber(new YourEventSubscriber());
}
```

### Runtime

During runtime, your module can:
- Handle HTTP requests
- Process background jobs
- Respond to events
- Provide services to other modules

### Shutdown

Clean up resources when the application shuts down:

```php
public function shutdown(): void
{
    // Close database connections
    // Save cache
    // Release resources
}
```

## Dependency Management

### Required Dependencies

```php
public function getDependencies(): array
{
    return [
        'App\\Modules\\Auth\\AuthModule' => '^1.0',
        'App\\Modules\\Database\\DatabaseModule' => '>=2.0 <3.0',
    ];
}
```

### Optional Dependencies

```php
public function getOptionalDependencies(): array
{
    return [
        'App\\Modules\\Search\\SearchModule' => '^2.0',
        'App\\Modules\\Analytics\\AnalyticsModule' => '^1.0',
    ];
}
```

### Version Constraints

Use Composer-style version constraints:
- `^1.2.3`: >=1.2.3 <2.0.0
- `~1.2.3`: >=1.2.3 <1.3.0
- `>=1.0 <2.0`: Any version between 1.0 and 2.0
- `1.2.3`: Exactly version 1.2.3

## Configuration

### Module Configuration

Create a `config.php` file in your module's `Config` directory:

```php
// app/Modules/YourModule/Config/config.php
return [
    'enabled' => true,
    'options' => [
        'timeout' => 30,
        'retries' => 3,
    ],
];
```

### Service Configuration

Use the container to access configuration:

```php
$config = $this->container->get('config')['your_module'];
```

## Services and Dependency Injection

### Defining Services

```php
// In your module's register() method
$this->container->set('your_service', function() {
    return new YourService(
        $this->container->get('config')['your_module']
    );
});

// With autowiring
$this->container->set(YourService::class, function() {
    return new YourService(
        $this->container->get(LoggerInterface::class)
    );
});
```

### Service Tags

Tag services for easy retrieval:

```php
// Register with tag
$this->container->set('command.sync_users', function() {
    return new SyncUsersCommand();
})->addTag('console.command');

// Get all commands
$commands = $this->container->getByTag('console.command');
```

### Lazy Loading

For services that are expensive to create:

```php
$this->container->set(HeavyService::class, function() {
    return new class($this->container) {
        private $container;
        private $initialized = false;
        
        public function __construct($container) {
            $this->container = $container;
        }
        
        private function initialize() {
            if ($this->initialized) return;
            
            // Heavy initialization
            // ...
            
            $this->initialized = true;
        }
        
        public function doSomething() {
            $this->initialize();
            // ...
        }
    };
});
```

## Event System

### Defining Events

```php
namespace App\Modules\YourModule\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserRegisteredEvent extends Event
{
    public const NAME = 'user.registered';
    
    private $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
    
    public function getUser()
    {
        return $this->user;
    }
}
```

### Dispatching Events

```php
$event = new UserRegisteredEvent($user);
$dispatcher = $this->container->get('event_dispatcher');
$dispatcher->dispatch($event, UserRegisteredEvent::NAME);
```

### Listening to Events

```php
// In your module's boot() method
$dispatcher = $this->container->get('event_dispatcher');
$dispatcher->addListener(
    UserRegisteredEvent::NAME,
    function (UserRegisteredEvent $event) {
        // Handle the event
        $user = $event->getUser();
        // ...
    }
);
```

## Testing Modules

### Unit Testing

```php
use PHPUnit\Framework\TestCase;

class YourServiceTest extends TestCase
{
    private $service;
    
    protected function setUp(): void
    {
        $this->service = new YourService(
            // Dependencies
        );
    }
    
    public function testSomething()
    {
        $result = $this->service->doSomething();
        $this->assertTrue($result);
    }
}
```

### Integration Testing

```php
use PHPUnit\Framework\TestCase;
use App\Testing\ModuleTestCase;

class YourModuleTest extends ModuleTestCase
{
    protected function getModules(): array
    {
        return [
            new YourModule($this->container),
            // Other required modules
        ];
    }
    
    public function testModuleRegistration()
    {
        $this->assertTrue($this->container->has('your_service'));
    }
}
```

## Best Practices

1. **Single Responsibility**: Each module should have a single responsibility
2. **Loose Coupling**: Depend on interfaces, not implementations
3. **Feature Flags**: Use configuration to enable/disable features
4. **Documentation**: Document your module's API and configuration
5. **Testing**: Write tests for your module
6. **Performance**: Be mindful of performance, especially in the register() method
7. **Error Handling**: Provide meaningful error messages
8. **Logging**: Use PSR-3 compatible logging
9. **Configuration**: Make your module configurable
10. **Dependencies**: Keep dependencies to a minimum

## Troubleshooting

### Module Not Found

**Symptom**: `Class 'YourModule' not found`

**Solution**:
1. Check the namespace and file location
2. Run `composer dump-autoload`
3. Verify the module is in the correct directory

### Dependency Issues

**Symptom**: `Dependency not found` or `Version constraint failed`

**Solution**:
1. Check module dependencies in `getDependencies()`
2. Verify required modules are installed
3. Check version constraints

### Service Not Available

**Symptom**: `Service 'your_service' not found`

**Solution**:
1. Check if the service is registered in `register()`
2. Verify the service name matches exactly
3. Check for typos in the service name

### Boot Order Issues

**Symptom**: Service depends on another service that isn't initialized

**Solution**:
1. Move initialization code to `boot()`
2. Use lazy loading for services with dependencies
3. Check module dependencies

## Conclusion

This guide covers the essential aspects of module development. For more information, refer to:

- [Module Discovery](discovery.md)
- [Module Registry](registry.md)
- [Dependency Resolution](dependency-resolution.md)
- [Module Lifecycle](module-lifecycle.md)

Happy coding!
