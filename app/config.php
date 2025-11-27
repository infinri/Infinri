<?php declare(strict_types=1);
/**
 * Application Configuration
 * 
 * Uses helper classes for clean, centralized configuration
 * 
 * @package App
 */

use App\Core\Module\ModuleRegistry;

return [
    'environment' => env('APP_ENV', 'production'),
    'site' => [
        'name' => env('SITE_NAME', 'Infinri'),
        'url' => filter_var(env('SITE_URL', 'http://localhost'), FILTER_VALIDATE_URL) ?: 'http://localhost',
    ],
    'allowed_modules' => array_keys((new ModuleRegistry())->all()),
    'default_module' => 'home',
    'error_module' => 'error',
    'security' => [
        'csrf_enabled' => (bool) env('CSRF_ENABLED', true),
        'https_only' => (bool) env('HTTPS_ONLY', false),
    ],
];
