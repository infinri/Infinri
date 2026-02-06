<?php declare(strict_types=1);

/**
 * Phase 1 Core Bootstrap
 *
 * This file bootstraps the new Phase 1 core framework.
 * It can be included in pub/index.php to enable the container,
 * config, and logging systems.
 *
 * Usage in pub/index.php:
 *
 * // After composer autoload:
 * $app = require_once __DIR__ . '/../app/Core/bootstrap.php';
 *
 * // Check for /health endpoint:
 * if (\App\Core\Http\HealthEndpoint::isHealthCheckRequest()) {
 *     \App\Core\Http\HealthEndpoint::handle($app);
 * }
 *
 * // Continue with existing code...
 */

use App\Core\Application;
use App\Core\Http\HealthEndpoint;

// Define base path if not already defined
if (! defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Create and bootstrap application
$app = new Application(BASE_PATH);
$app->bootstrap();

// Handle /health endpoint if requested (Phase 1 temporary solution)
if (HealthEndpoint::isHealthCheckRequest()) {
    HealthEndpoint::handle($app);
}

// Return application instance for use in the rest of the application
return $app;
