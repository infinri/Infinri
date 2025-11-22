<?php declare(strict_types=1);
/**
 * Application Configuration
 * 
 * Uses helper classes for clean, centralized configuration
 * 
 * @package App
 */

use App\Helpers\Env;
use App\Helpers\Module;

return [
    'environment' => Env::get('APP_ENV', 'production'),
    'site' => [
        'name' => Env::get('SITE_NAME', 'Infinri'),
        'url' => filter_var(Env::get('SITE_URL', 'http://localhost'), FILTER_VALIDATE_URL) ?: 'http://localhost',
    ],
    'allowed_modules' => Module::discover(),
    'default_module' => 'home',
    'error_module' => 'error',
    'security' => [
        'csrf_enabled' => Env::get('CSRF_ENABLED', true, 'bool'),
        'https_only' => Env::get('HTTPS_ONLY', false, 'bool'),
    ],
];
