#!/usr/bin/env php
<?php

/**
 * Add Copyright Headers to Source Files
 * 
 * Usage: php bin/add-copyright-headers.php [--dry-run]
 * 
 * This script adds proprietary copyright headers to all PHP source files
 * in the app/ and tests/ directories.
 */

$dryRun = in_array('--dry-run', $argv);
$basePath = dirname(__DIR__);

$header = <<<'HEADER'
/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */

HEADER;

$directories = [
    $basePath . '/app',
    $basePath . '/tests',
];

$stats = [
    'scanned' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

echo "===========================================\n";
echo "  Copyright Header Insertion Tool\n";
echo "===========================================\n\n";

if ($dryRun) {
    echo "MODE: Dry run (no files will be modified)\n\n";
} else {
    echo "MODE: Live (files will be modified)\n\n";
}

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        echo "Skipping non-existent directory: {$directory}\n";
        continue;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        
        $stats['scanned']++;
        $filePath = $file->getPathname();
        $relativePath = str_replace($basePath . '/', '', $filePath);
        
        try {
            $content = file_get_contents($filePath);
            
            // Skip if already has copyright header
            if (strpos($content, '@copyright') !== false) {
                $stats['skipped']++;
                continue;
            }
            
            // Skip if it's a config/data file (not a class)
            if (strpos($content, 'class ') === false && 
                strpos($content, 'interface ') === false && 
                strpos($content, 'trait ') === false &&
                strpos($content, 'enum ') === false) {
                $stats['skipped']++;
                continue;
            }
            
            // Find the position after <?php and declare statement
            $insertPosition = 0;
            
            // Match <?php followed by optional declare statement
            if (preg_match('/^<\?php\s*(?:declare\s*\([^)]+\)\s*;\s*)?/s', $content, $matches)) {
                $insertPosition = strlen($matches[0]);
            }
            
            // Check if there's already a docblock right after
            $afterPhp = substr($content, $insertPosition);
            if (preg_match('/^\s*\/\*\*/', $afterPhp)) {
                // There's already a docblock, skip or we'd create duplicates
                $stats['skipped']++;
                continue;
            }
            
            // Insert the header
            $newContent = substr($content, 0, $insertPosition) . 
                          "\n" . $header . 
                          ltrim(substr($content, $insertPosition));
            
            if (!$dryRun) {
                file_put_contents($filePath, $newContent);
            }
            
            echo "  ✓ {$relativePath}\n";
            $stats['updated']++;
            
        } catch (Exception $e) {
            echo "  ✗ {$relativePath}: {$e->getMessage()}\n";
            $stats['errors']++;
        }
    }
}

echo "\n===========================================\n";
echo "  Summary\n";
echo "===========================================\n";
echo "  Scanned: {$stats['scanned']}\n";
echo "  Updated: {$stats['updated']}\n";
echo "  Skipped: {$stats['skipped']} (already have headers or non-class files)\n";
echo "  Errors:  {$stats['errors']}\n";
echo "===========================================\n";

if ($dryRun && $stats['updated'] > 0) {
    echo "\nRun without --dry-run to apply changes.\n";
}
