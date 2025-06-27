# Module Dependency Resolution

The dependency resolution system ensures that modules are loaded in the correct order based on their dependencies. This document explains how to define and manage module dependencies in your application.

## Table of Contents

- [Overview](#overview)
- [Defining Dependencies](#defining-dependencies)
  - [Required Dependencies](#required-dependencies)
  - [Optional Dependencies](#optional-dependencies)
  - [Version Constraints](#version-constraints)
- [Dependency Resolution](#dependency-resolution)
  - [How It Works](#how-it-works)
  - [Example](#example)
- [Common Patterns](#common-patterns)
  - [Circular Dependencies](#circular-dependencies)
  - [Optional Features](#optional-features)
  - [Version Management](#version-management)
- [Troubleshooting](#troubleshooting)
  - [Common Issues](#common-issues)
  - [Debugging](#debugging)

## Overview

The `DependencyResolver` ensures that:

1. Modules are loaded after their dependencies
2. Version constraints are satisfied
3. Circular dependencies are detected
4. Optional dependencies are handled gracefully

## Defining Dependencies

### Required Dependencies

In your module class, implement `getDependencies()` to declare required dependencies:

```php
class MyModule implements ModuleInterface
{
    public function getDependencies(): array
    {
        return [
            // Format: 'Fully\\Qualified\\ClassName' => 'version-constraint'
            'App\\Modules\\Auth\\AuthModule' => '^1.0',
            'App\\Modules\\Database\\DatabaseModule' => '>=2.0 <3.0',
        ];
    }
}
```

### Optional Dependencies

For optional features that depend on other modules, use `getOptionalDependencies()`:

```php
public function getOptionalDependencies(): array
{
    return [
        'App\\Modules\\Search\\SearchModule' => '^1.0',
        'App\\Modules\\Analytics\\AnalyticsModule' => '^2.0',
    ];
}

public function boot()
{
    if ($this->hasSearch()) {
        // Enable search features
    }
}

private function hasSearch(): bool
{
    return $this->container->has('App\\Modules\\Search\\SearchModule');
}
```

### Version Constraints

Version constraints use Composer's version constraint syntax:

- `^1.2.3`: >=1.2.3 <2.0.0
- `~1.2.3`: >=1.2.3 <1.3.0
- `>=1.0 <2.0`: Any version between 1.0 and 2.0
- `1.2.3`: Exactly version 1.2.3

## Dependency Resolution

### How It Works

1. The resolver builds a dependency graph of all modules
2. It performs a topological sort to determine load order
3. For each module:
   - Required dependencies must exist and satisfy version constraints
   - Optional dependencies are loaded if available
   - Circular dependencies are detected and reported

### Example

```php
use App\Modules\Dependency\DependencyResolver;
use App\Modules\Registry\ModuleRegistry;

$registry = new ModuleRegistry();
$resolver = new DependencyResolver($registry);

// Register modules (order doesn't matter)
$registry->register(new CoreModule());
$registry->register(new AuthModule());
$registry->register(new AdminModule());

// Resolve dependencies
$modules = [
    new AdminModule(),  // Depends on AuthModule
    new AuthModule(),   // Depends on CoreModule
    new CoreModule()    // No dependencies
];

try {
    // Returns [CoreModule, AuthModule, AdminModule]
    $orderedModules = $resolver->resolveDependencies($modules);
} catch (\RuntimeException $e) {
    // Handle dependency errors
    echo "Dependency error: " . $e->getMessage();
}
```

## Common Patterns

### Circular Dependencies

Circular dependencies (A → B → A) are not supported and will throw an exception. Instead:

1. **Extract Common Functionality**: Move shared code to a third module
2. **Use Events**: Decouple modules using events
3. **Dependency Injection**: Pass dependencies at runtime

### Optional Features

Use optional dependencies for features that enhance but aren't required:

```php
class BlogModule implements ModuleInterface
{
    private ?SearchService $searchService = null;

    public function boot()
    {
        if ($this->container->has(SearchModule::class)) {
            $this->searchService = $this->container->get(SearchModule::class)->getSearchService();
            $this->enableSearch();
        }
    }
}
```

### Version Management

1. **Be Specific**: Use precise version constraints
2. **Follow SemVer**: Respect semantic versioning in your modules
3. **Test Upgrades**: Test with different dependency versions

## Troubleshooting

### Common Issues

#### Missing Dependencies

```
Dependency error: Required dependency App\Modules\Auth\AuthModule not found
```

**Solution**: Ensure the required module is registered before resolving dependencies.

#### Version Constraint Failed

```
Dependency error: Version constraint failed for App\Modules\Auth\AuthModule: 
Required: ^2.0, Installed: 1.5.0
```

**Solution**: Update the module or adjust the version constraint.

#### Circular Dependency

```
Dependency error: Circular dependency detected involving App\Modules\A\AModule
```

**Solution**: Restructure your modules to remove the circular dependency.

### Debugging

1. **Check Dependencies**:
   ```php
   foreach ($modules as $module) {
       echo get_class($module) . " depends on: " . 
            implode(', ', array_keys($module->getDependencies())) . "\n";
   }
   ```

2. **Enable Debug Logging**:
   ```php
   // In your bootstrap code
   $resolver->setLogger($logger);
   ```

3. **Visualize Dependencies**:
   ```bash
   # Generate a DOT file of module dependencies
   php bin/console module:graph > modules.dot
   dot -Tpng modules.dot -o modules.png
   ```

4. **Check Module Versions**:
   ```php
   foreach ($registry->all() as $module) {
       echo get_class($module) . ': ' . $module->getVersion() . "\n";
   }
   ```
