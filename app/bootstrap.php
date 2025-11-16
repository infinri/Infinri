<?php declare(strict_types=1);
/**
 * Bootstrap - Environment Loader
 * 
 * Securely loads environment variables from .env file
 * 
 * Note: This file runs BEFORE helper classes are loaded (by design).
 * It must be self-contained with no external dependencies.
 * 
 * @package App
 */

$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    // Security: Prevent DOS attacks via huge .env files
    $maxSize = 100 * 1024; // 100KB limit
    if (filesize($envFile) > $maxSize) {
        error_log('Security: .env file exceeds size limit');
        http_response_code(500);
        exit('Configuration error');
    }
    
    // Use fopen for better memory efficiency
    $handle = fopen($envFile, 'r');
    if (!$handle) {
        error_log('Failed to open .env file');
        http_response_code(500);
        exit('Configuration error');
    }
    
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        
        // Skip empty lines, comments, and invalid format
        if (empty($line) || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        
        // Parse and validate key in one flow
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        
        // Skip invalid keys (alphanumeric + underscore only)
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
            continue;
        }
        
        // Handle quoted values properly (supports escaped quotes)
        if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
            // Unescape quotes
            $value = str_replace(['\"', "\'"], ['"', "'"], $value);
        } else {
            // Remove inline comments (not in quotes)
            if (($hashPos = strpos($value, '#')) !== false) {
                $value = trim(substr($value, 0, $hashPos));
            }
        }
        
        // Only set if not already defined (CLI args take precedence)
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
    
    fclose($handle);
}
