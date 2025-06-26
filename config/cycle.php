<?php declare(strict_types=1);

use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Transaction;

return [
    'schema' => [
        'cache' => false, // Enable in production: env('CYCLE_SCHEMA_CACHE', true)
        'generators' => [
            // Add generators here if needed
        ],
    ],
    'connections' => [
        'default' => [
            'driver' => 'pgsql',
            'connection' => 'pgsql:host=' . env('DB_HOST', '127.0.0.1') .
                         ';port=' . env('DB_PORT', '5432') .
                         ';dbname=' . env('DB_DATABASE', 'infinri'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
    'defaults' => [
        'schema' => 'public',
        'connection' => 'default',
    ],
    'schemaPaths' => [
        // Paths to your entity directories
        app_path('Entities'),
    ],
];
