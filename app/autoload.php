<?php declare(strict_types=1);
/**
 * PSR-4 Autoloader
 * 
 * Convention-based namespace-to-path mapping.
 * Automatically maps App\* namespaces to /app/* directories.
 * 
 * Convention:
 * - App\Core\*       → /app/base/core/*
 * - App\Modules\*    → /app/modules/*
 * - App\Helpers\*    → /app/base/helpers/* (Env, Path, Validate, Module, Str, Cache, Esc, Session, View, Logger)
 * 
 * @package App
 */

// Load environment variables first
require_once __DIR__ . '/bootstrap.php';

// Register PSR-4 autoloader
spl_autoload_register(function (string $class): void {
    // Security: Whitelist App namespace only
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    
    // Security: Reject null bytes
    if (str_contains($class, "\0")) {
        return;
    }
    
    // Remove App\ prefix
    $relativeClass = substr($class, 4);
    
    // Split into parts
    $parts = explode('\\', $relativeClass);
    
    // Get first segment (Core, Modules, Base, etc.)
    $segment = array_shift($parts);
    
    // Convention-based directory mapping
    $baseDir = match($segment) {
        'Core' => __DIR__ . '/base/core/',
        'Modules' => __DIR__ . '/modules/',
        'Helpers' => __DIR__ . '/base/helpers/',
        default => __DIR__ . '/' . strtolower($segment) . '/'
    };
    
    // Get class name (last part)
    $className = array_pop($parts);
    
    // Build subdirectory path (lowercase)
    $subPath = $parts ? strtolower(implode('/', $parts)) . '/' : '';
    
    // Construct final file path
    $file = $baseDir . $subPath . $className . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load procedural helper functions (if exists)
$functionsFile = __DIR__ . '/base/helpers/functions.php';
if (file_exists($functionsFile)) {
    require_once $functionsFile;
}
