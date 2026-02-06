<?php declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Bootstrap
|--------------------------------------------------------------------------
|
| Bootstrap file for PHPUnit tests. Loads composer autoloader and
| sets up test environment.
|
*/

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Load application helpers
require_once BASE_PATH . '/app/Core/Support/helpers.php';

// Set up test environment
date_default_timezone_set('UTC');

// Create test directories if they don't exist
$testDirs = [
    BASE_PATH . '/var/log',
    BASE_PATH . '/var/tmp',
    BASE_PATH . '/var/cache',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Clean up test artifacts before running tests
$cleanupFiles = [
    BASE_PATH . '/var/log/test.log',
];

foreach ($cleanupFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}
