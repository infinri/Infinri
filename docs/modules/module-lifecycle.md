# Module Lifecycle Management

The module lifecycle consists of several distinct phases that modules go through during application startup. This document explains each phase and how to work with the `ModuleLoader` to manage module initialization.

## Table of Contents

- [Overview](#overview)
- [Lifecycle Phases](#lifecycle-phases)
  - [1. Discovery](#1-discovery)
  - [2. Registration](#2-registration)
  - [3. Boot](#3-boot)
  - [4. Runtime](#4-runtime)
  - [5. Shutdown](#5-shutdown)
- [ModuleLoader Reference](#moduleloader-reference)
  - [Loading Modules](#loading-modules)
  - [Booting Modules](#booting-modules)
  - [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## Overview

The module lifecycle is managed by the `ModuleLoader` class, which coordinates the following phases:

1. **Discovery**: Finding and validating module classes
2. **Registration**: Instantiating modules and calling `register()`
3. **Boot**: Calling `boot()` on all registered modules
4. **Runtime**: Application is running, modules handle requests
5. **Shutdown**: Cleanup when the application terminates

## Lifecycle Phases

### 1. Discovery

Modules are discovered using the `ModuleDiscovery` class, which scans directories for module classes. This phase:

- Scans configured directories for module classes
- Validates that classes implement `ModuleInterface`
- Caches discovery results for performance

### 2. Registration

During registration, each module is instantiated and its `register()` method is called:

```php
// Example module registration
class MyModule implements ModuleInterface
{
    public function register(): void
    {
        // Register services, routes, event listeners, etc.
        $this->container->set(MyService::class, function() {
            return new MyService();
        });
    }
}
```

**Key Points**:
- Modules are registered in dependency order
- The container is available for service registration
- Avoid heavy initialization in `register()`

### 3. Boot

After all modules are registered, the `boot()` method is called on each module:

```php
public function boot(): void
{
    // All modules are registered, safe to use their services
    $someService = $this->container->get(SomeService::class);
    $someService->initialize();
}
```

**Key Points**:
- All modules are registered when `boot()` is called
- Safe to use services from other modules
- Keep boot logic lightweight

### 4. Runtime

After booting, the application enters the runtime phase where it handles requests:

- Modules can listen to events
- Controllers handle HTTP requests
- Background jobs process asynchronously

### 5. Shutdown

When the application shuts down, modules can clean up resources:

```php
public function shutdown(): void
{
    // Close connections, save state, etc.
    $this->cache->persist();
}
```

## ModuleLoader Reference

### Loading Modules

```php
use App\Modules\Loader\ModuleLoader;
use App\Modules\Registry\ModuleRegistry;

// Set up dependencies
$container = /* PSR-11 container */;
$registry = new ModuleRegistry();
$loader = new ModuleLoader($container, $registry);

// Load a single module
$module = $loader->load(MyModule::class);

// Or load multiple modules
$modules = [
    CoreModule::class,
    AuthModule::class,
    FeatureModule::class,
];

foreach ($modules as $moduleClass) {
    $loader->load($moduleClass);
}
```

### Booting Modules

```php
// Boot all registered modules
$loader->bootAll();

// Or boot a single module (rarely needed)
$module = $registry->get(MyModule::class);
$loader->boot($module);
```

### Error Handling

Handle module loading and booting errors:

```php
try {
    $module = $loader->load(ProblematicModule::class);
    $loader->bootAll();
} catch (\RuntimeException $e) {
    // Handle module loading/booting errors
    $logger->error('Module error: ' . $e->getMessage());
    
    // Optionally continue without the failed module
    if ($e->getPrevious()) {
        $logger->error('Caused by: ' . $e->getPrevious()->getMessage());
    }
}
```

## Best Practices

1. **Keep `register()` Lightweight**
   - Register services and configuration
   - Avoid heavy operations
   - Don't depend on other modules' services

2. **Use `boot()` for Initialization**
   - Initialize services that depend on other modules
   - Register event listeners
   - Perform runtime configuration

3. **Handle Dependencies**
   - Declare dependencies in `getDependencies()`
   - Use optional dependencies when appropriate
   - Document version requirements

4. **Error Handling**
   - Validate configuration in `register()`
   - Use try/catch for external service initialization
   - Provide meaningful error messages

5. **Testing**
   - Test modules in isolation
   - Mock dependencies in unit tests
   - Test module registration and booting

## Troubleshooting

### Module Not Found

**Symptom**: `Class 'MyModule' not found`

**Solution**:
1. Ensure the module class exists and is autoloadable
2. Check the module's namespace and file location
3. Run `composer dump-autoload` if using Composer

### Circular Dependencies

**Symptom**: `Circular dependency detected`

**Solution**:
1. Review your module dependencies
2. Extract common functionality to a shared module
3. Use events or service providers for cross-module communication

### Service Not Available

**Symptom**: `Service 'SomeService' not found in container`

**Solution**:
1. Ensure the service is registered in the container
2. Check that the providing module is loaded first
3. Verify service names match exactly

### Boot Order Issues

**Symptom**: Module tries to use another module's service during boot but it's not initialized

**Solution**:
1. Check that all dependencies are properly declared
2. Move initialization code from `__construct()` to `boot()`
3. Use lazy loading for optional dependencies

## Example Module

```php
<?php

declare(strict_types=1);

namespace App\Modules\MyFeature;

use App\Modules\ModuleInterface;
use Psr\Container\ContainerInterface;

class MyFeatureModule implements ModuleInterface
{
    private ContainerInterface $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getName(): string
    {
        return 'my-feature';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function getDependencies(): array
    {
        return [
            'App\\Modules\\Auth\\AuthModule' => '^1.0',
        ];
    }
    
    public function getOptionalDependencies(): array
    {
        return [
            'App\\Modules\\Search\\SearchModule' => '^2.0',
        ];
    }
    
    public function register(): void
    {
        // Register services
        $this->container->set('my_feature.config', [
            'enabled' => true,
            'options' => [],
        ]);
        
        $this->container->set(MyService::class, function() {
            return new MyService($this->container->get('my_feature.config'));
        });
    }
    
    public function boot(): void
    {
        // All modules are registered, safe to use their services
        if ($this->container->has(SearchModule::class)) {
            $this->enableSearchIntegration();
        }
    }
    
    public function shutdown(): void
    {
        // Cleanup resources
    }
    
    private function enableSearchIntegration(): void
    {
        // Initialize search integration
    }
}
```
