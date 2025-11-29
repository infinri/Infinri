<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Database;

use App\Core\Container\ServiceProvider;
use App\Core\Contracts\Database\ConnectionInterface;

/**
 * Database Service Provider
 * 
 * Registers database services in the container.
 */
class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register database configuration
        $this->registerDatabaseConfig();

        // Register the database manager as a singleton
        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager($app);
        });

        // Register the default connection
        $this->app->singleton(ConnectionInterface::class, function ($app) {
            return $app->make(DatabaseManager::class)->connection();
        });

        // Register aliases
        $this->app->alias(DatabaseManager::class, 'db');
        $this->app->alias(ConnectionInterface::class, 'db.connection');
    }

    public function boot(): void
    {
        // Log database service registration
        if (function_exists('log_system')) {
            log_system('Database service registered', [
                'default_connection' => config('database.default', 'pgsql'),
            ]);
        }
    }

    protected function registerDatabaseConfig(): void
    {
        // Only register if not already set
        if (config('database') !== null) {
            return;
        }

        config([
            'database' => [
                'default' => env('DB_CONNECTION', 'pgsql'),
                
                'connections' => [
                    'pgsql' => [
                        'driver' => 'pgsql',
                        'host' => env('DB_HOST', 'localhost'),
                        'port' => env('DB_PORT', 5432),
                        'database' => env('DB_DATABASE', 'infinri'),
                        'username' => env('DB_USERNAME', 'postgres'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => 'utf8',
                        'schema' => 'public',
                        'timezone' => env('APP_TIMEZONE', 'UTC'),
                        'prefix' => env('DB_PREFIX', ''),
                    ],
                    
                    'testing' => [
                        'driver' => 'pgsql',
                        'host' => env('DB_TEST_HOST', 'localhost'),
                        'port' => env('DB_TEST_PORT', 5432),
                        'database' => env('DB_TEST_DATABASE', 'infinri_test'),
                        'username' => env('DB_TEST_USERNAME', 'postgres'),
                        'password' => env('DB_TEST_PASSWORD', ''),
                        'charset' => 'utf8',
                        'schema' => 'public',
                        'prefix' => '',
                    ],
                ],
            ],
        ]);
    }
}
