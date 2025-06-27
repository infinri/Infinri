# Module Registry

The `ModuleRegistry` is a central component of the module system that manages module instances, their metadata, and provides querying capabilities. This document explains how to use the registry effectively.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
  - [Registering Modules](#registering-modules)
  - [Retrieving Modules](#retrieving-modules)
- [Tagging System](#tagging-system)
- [Querying Modules](#querying-modules)
  - [By Interface](#by-interface)
  - [By Tag](#by-tag)
- [Advanced Usage](#advanced-usage)
  - [Custom Module Collections](#custom-module-collections)
  - [Lazy Loading](#lazy-loading)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## Overview

The `ModuleRegistry` serves as a central repository for module instances in your application. It provides:

- **Instance Management**: Register and retrieve module instances
- **Categorization**: Tag modules for logical grouping
- **Querying**: Find modules by type, interface, or tag
- **Type Safety**: Ensures only valid module instances are managed

## Basic Usage

### Registering Modules

```php
use App\Modules\Registry\ModuleRegistry;
use App\Modules\MyModule;

$registry = new ModuleRegistry();
$module = new MyModule();

// Register a module
$registry->register($module);

// Check if a module is registered
if ($registry->has(MyModule::class)) {
    // Module is registered
}

// Get a registered module
$myModule = $registry->get(MyModule::class);

// Get all registered modules
$allModules = $registry->all();
```

### Retrieving Modules

```php
// Get a single module (throws if not found)
$module = $registry->get(MyModule::class);

// Safely get a module (returns null if not found)
$module = $registry->has(MyModule::class) 
    ? $registry->get(MyModule::class) 
    : null;

// Get all modules as [class => instance] array
$allModules = $registry->all();
```

## Tagging System

Tags provide a way to categorize and group modules:

```php
// Tag a module during or after registration
$registry->tagModule(MyModule::class, 'admin');
$registry->tagModule(MyModule::class, 'dashboard');

// Get all modules with a specific tag
$adminModules = $registry->getByTag('admin');

// A module can have multiple tags
$registry->tagModule(MyModule::class, 'backend');
$registry->tagModule(MyModule::class, 'api');
```

## Querying Modules

### By Interface

Find all modules that implement a specific interface:

```php
// Define an interface
interface AdminInterface {
    public function getAdminRoutes(): array;
}

// Implement the interface
class AdminModule implements AdminInterface {
    public function getAdminRoutes(): array {
        return ['/admin' => 'AdminController'];
    }
}

// Register the module
$registry->register(new AdminModule());

// Find all admin modules
$adminModules = $registry->getByInterface(AdminInterface::class);
```

### By Tag

```php
// Tag modules during registration
$registry->tagModule(ReportModule::class, 'reporting');
$registry->tagModule(AnalyticsModule::class, 'reporting');

// Later, get all reporting modules
$reportingModules = $registry->getByTag('reporting');
```

## Advanced Usage

### Custom Module Collections

Create custom collections of modules for specific purposes:

```php
class ModuleCollection implements \IteratorAggregate
{
    private array $modules = [];
    
    public function __construct(private ModuleRegistry $registry, private string $tag) {}
    
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->registry->getByTag($this->tag));
    }
    
    public function call(string $method, array $args = []): array
    {
        $results = [];
        foreach ($this as $module) {
            if (method_exists($module, $method)) {
                $results[] = $module->$method(...$args);
            }
        }
        return $results;
    }
}

// Usage
$adminModules = new ModuleCollection($registry, 'admin');
$adminModules->call('registerRoutes', [$router]);
```

### Lazy Loading

For better performance, consider lazy-loading modules:

```php
class LazyModule implements ModuleInterface
{
    private ?ModuleInterface $module = null;
    
    public function __construct(private string $className) {}
    
    private function getModule(): ModuleInterface
    {
        if ($this->module === null) {
            $this->module = new $this->className();
        }
        return $this->module;
    }
    
    public function getName(): string
    {
        return $this->getModule()->getName();
    }
    
    // Delegate all other method calls to the actual module
    public function __call(string $method, array $args)
    {
        return $this->getModule()->$method(...$args);
    }
}

// Register a lazy-loaded module
$registry->register(new LazyModule(HeavyModule::class));
```

## Best Practices

1. **Register Early**: Register all modules during application bootstrap
2. **Use Interfaces**: Depend on interfaces rather than concrete implementations
3. **Tag Consistently**: Use consistent tag names across your application
4. **Keep Modules Focused**: Each module should have a single responsibility
5. **Document Dependencies**: Document any module dependencies in their docblocks

## Troubleshooting

### Module Not Found

```php
try {
    $module = $registry->get(NonExistentModule::class);
} catch (OutOfBoundsException $e) {
    // Handle missing module
    $logger->error('Module not found: ' . $e->getMessage());
}
```

### Duplicate Registration

```php
try {
    $registry->register($module);
    $registry->register($module); // Will throw
} catch (RuntimeException $e) {
    // Module already registered
}
```

### Debugging

```php
// Check if a module is registered
$isRegistered = $registry->has(MyModule::class);

// Get all registered module classes
$moduleClasses = array_keys($registry->all());

// Get all tags and their modules
$tags = $registry->getTags(); // If implemented
```
