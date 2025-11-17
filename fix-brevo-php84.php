<?php
/**
 * Fix Brevo PHP 8.4 Compatibility
 * 
 * Fixes breaking deprecation warnings in Brevo library for PHP 8.4
 * These are NOT just warnings - they can break execution in strict mode
 * 
 * Run this after: composer install, composer update
 * Usage: php fix-brevo-php84.php
 * 
 * Issue: https://github.com/getbrevo/brevo-php/issues/92
 */

$vendorPath = __DIR__ . '/vendor/getbrevo/brevo-php/lib/Model';

if (!is_dir($vendorPath)) {
    echo "⚠️  Brevo library not found at: $vendorPath\n";
    exit(1);
}

echo "🔧 Fixing Brevo PHP 8.4 compatibility issues...\n\n";

$files = glob($vendorPath . '/*.php');
$fixed = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Fix: public function __construct(array $data = null)
    // To:  public function __construct(?array $data = null)
    $content = preg_replace(
        '/public function __construct\(array \$data = null\)/',
        'public function __construct(?array $data = null)',
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        $fixed++;
        echo "✓ Fixed: " . basename($file) . "\n";
    }
}

echo "\n✅ Fixed $fixed files\n";
echo "🎉 Brevo library is now PHP 8.4 compatible!\n";
