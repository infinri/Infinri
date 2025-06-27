<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Modules\ModuleInterface;

/**
 * Find all PHP files in the app/Modules directory
 */
function findPhpFiles(string $directory): array {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $files = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

/**
 * Check if a class implements ModuleInterface and has isCompatible()
 */
function checkModuleClass(string $file): ?array {
    $content = file_get_contents($file);
    
    // Skip if not a class or doesn't implement ModuleInterface
    if (!preg_match('/class\s+(\w+)/', $content, $matches) || 
        strpos($content, 'implements ModuleInterface') === false) {
        return null;
    }
    
    $className = $matches[1];
    $hasCompatibleMethod = strpos($content, 'function isCompatible()') !== false;
    
    return [
        'file' => $file,
        'class' => $className,
        'has_compatible_method' => $hasCompatibleMethod,
    ];
}

// Main execution
$modulesDir = __DIR__ . '/app/Modules';
$phpFiles = findPhpFiles($modulesDir);

$modules = [];
foreach ($phpFiles as $file) {
    if ($result = checkModuleClass($file)) {
        $modules[] = $result;
    }
}

// Output results
echo "Found " . count($modules) . " modules implementing ModuleInterface:\n\n";

foreach ($modules as $module) {
    $status = $module['has_compatible_method'] ? '✓' : '✗';
    echo sprintf(
        "%s %-40s %s\n",
        $status,
        $module['class'],
        str_replace(realpath(__DIR__) . '/', '', $module['file'])
    );
}

echo "\n";
$missing = array_filter($modules, fn($m) => !$m['has_compatible_method']);
if (count($missing) > 0) {
    echo "The following modules are missing the isCompatible() method:\n";
    foreach ($missing as $module) {
        echo "- " . $module['class'] . " in " . str_replace(realpath(__DIR__) . '/', '', $module['file']) . "\n";
    }
} else {
    echo "All modules have the isCompatible() method.\n";
}

echo "\n";
$with = array_filter($modules, fn($m) => $m['has_compatible_method']);
if (count($with) > 0) {
    echo "The following modules already have the isCompatible() method:\n";
    foreach ($with as $module) {
        echo "- " . $module['class'] . " in " . str_replace(realpath(__DIR__) . '/', '', $module['file']) . "\n";
    }
}
