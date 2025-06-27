# Module Discovery

The module discovery system is responsible for finding and loading modules from the filesystem. This document explains how the discovery process works, supported module formats, and configuration options.

## Table of Contents

- [Overview](#overview)
- [Supported Module Formats](#supported-module-formats)
  - [PSR-4 Structure (Recommended)](#psr-4-structure-recommended)
  - [Legacy Structure](#legacy-structure)
- [Discovery Process](#discovery-process)
- [Caching](#caching)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [Best Practices](#best-practices)

## Overview

The `ModuleDiscovery` class scans specified directories for modules and returns a list of valid module classes that implement the `ModuleInterface`. It supports both PSR-4 autoloading and legacy module structures.

## Supported Module Formats

### PSR-4 Structure (Recommended)

```
/modules
    /VendorName
        /src
            VendorNameModule.php  // Class: VendorName\VendorNameModule
        /tests
            // Test files
        composer.json             // Optional: for PSR-4 autoloading
```

**Example Module Class:**

```php
<?php declare(strict_types=1);

namespace VendorName;

use App\Modules\ModuleInterface;

class VendorNameModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'VendorName';
    }
    
    // Implement other required methods...
}
```

### Legacy Structure

```
/modules
    /VendorName
        VendorName.php            // Class: VendorName_VendorName
        /templates
            // Template files
```

**Example Legacy Module Class:**

```php
<?php

class VendorName_VendorName implements ModuleInterface
{
    public function getName()
    {
        return 'VendorName';
    }
    
    // Implement other required methods...
}
```

## Discovery Process

The discovery process works as follows:

1. **Cache Check** (if enabled):
   - Check if a valid cache exists
   - Return cached module list if available and not stale

2. **Directory Scanning**:
   - Scan the modules directory for subdirectories
   - Skip files and special directories (`.`, `..`)

3. **Module Validation**:
   - For each module directory, attempt to find a valid module class
   - Try PSR-4 class loading first, then fall back to legacy structure
   - Validate that the class exists and implements `ModuleInterface`

4. **Caching** (if enabled):
   - Save the list of valid modules to cache
   - Cache includes file modification times for invalidation

## Caching

Module discovery includes an optional caching layer to improve performance:

- **Cache Location**: System temp directory by default, configurable
- **Cache Invalidation**: Automatically invalidates when:
  - Module files are added or removed
  - Module files are modified
  - Cache file is deleted

**Configuration Example:**

```php
// With default cache location
$discovery = new ModuleDiscovery('/path/to/modules');

// With custom cache file
$discovery = new ModuleDiscovery(
    '/path/to/modules',
    true,                           // Enable cache
    '/custom/cache/dir/modules.cache' // Custom cache file
);

// Disable caching (not recommended for production)
$discovery = new ModuleDiscovery('/path/to/modules', false);
```

## Configuration

### Constructor Parameters

```php
public function __construct(
    string $modulesPath,  // Path to modules directory
    bool $useCache = true, // Whether to enable caching
    ?string $cacheFile = null // Optional custom cache file path
)
```

### Environment Variables

- `MODULE_CACHE_ENABLED`: Set to `0` to disable caching globally
- `MODULE_CACHE_FILE`: Custom cache file path

## Troubleshooting

### Common Issues

1. **Module Not Found**
   - Verify the module directory exists and is readable
   - Check that the module class implements `ModuleInterface`
   - Ensure the class name follows the expected naming convention

2. **Cache Issues**
   - Delete the cache file to force rediscovery
   - Verify cache directory is writable
   - Check file permissions on the cache file

3. **Debugging**
   - Enable debug logging in the `ModuleDiscovery` class
   - Check PHP error logs for any issues

## Best Practices

1. **Use PSR-4 Structure**
   - Better autoloading support
   - Clearer code organization
   - Easier to test

2. **Keep Modules Focused**
   - Each module should have a single responsibility
   - Keep module code in the module's namespace

3. **Cache in Production**
   - Always enable caching in production environments
   - Consider warming the cache during deployment

4. **Testing**
   - Test modules in isolation
   - Mock the filesystem for unit tests
   - Use the `Test\Modules` namespace for test modules

5. **Error Handling**
   - Handle missing module dependencies gracefully
   - Provide clear error messages for configuration issues
