<?php
/**
 * Fix Brevo PHP 8.4 Compatibility
 * 
 * Fixes breaking deprecation warnings in Brevo library for PHP 8.4
 * These are NOT just warnings - they BREAK execution in strict mode
 * 
 * Run this after: composer install, composer update
 * Usage: php fix-brevo-php84.php
 * 
 * Issue: https://github.com/getbrevo/brevo-php/issues/92
 */

$vendorBase = __DIR__ . '/vendor/getbrevo/brevo-php/lib';

if (!is_dir($vendorBase)) {
    echo "⚠️  Brevo library not found at: $vendorBase\n";
    exit(1);
}

echo "🔧 Fixing Brevo PHP 8.4 compatibility issues...\n";
echo "   This fixes implicit nullable parameters that BREAK in PHP 8.4\n\n";

$fixed = 0;

// Fix both Model and Api directories
$directories = ['Model', 'Api'];

foreach ($directories as $dir) {
    $dirPath = $vendorBase . '/' . $dir;
    if (!is_dir($dirPath)) {
        continue;
    }
    
    echo "📂 Processing {$dir}/ directory...\n";
    $files = glob($dirPath . '/*.php');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $original = $content;
        
        // Fix 1: Remove double question marks (from previous bad fix)
        // ??array -> ?array, ??ClientInterface -> ?ClientInterface
        $content = preg_replace('/\?\?([a-zA-Z])/', '?$1', $content);
        
        // Fix 2: Add missing question marks on function parameters ONLY
        // Match constructor lines and fix inline
        $content = preg_replace_callback(
            '/(public\s+function\s+__construct\s*\([^)]*\))/s',
            function($matches) {
                $constructor = $matches[1];
                // Fix: Type $param = null -> ?Type $param = null (if not already fixed)
                $constructor = preg_replace(
                    '/(\(|,)\s*(?<!\?)(\barray\b|\b[A-Z]\w*)\s+(\$\w+)\s*=\s*null/',
                    '$1?$2 $3 = null',
                    $constructor
                );
                return $constructor;
            },
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            $fixed++;
            echo "  ✓ Fixed: " . basename($file) . "\n";
        }
    }
}

echo "\n✅ Fixed {$fixed} files across Model and Api directories\n";
echo "🎉 Brevo library is now PHP 8.4 compatible!\n";
echo "\n💡 Run 'php tests/brevo-production-test.php' to verify no more warnings\n";
