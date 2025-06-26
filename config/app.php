<?php

return [
    'app' => [
        'name' => 'Infinri',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
        'timezone' => 'UTC',
    ],
    
    'views' => [
        'path' => __DIR__ . '/../app/Views',
        'cache' => $_ENV['VIEW_CACHE'] ?? false,
    ],
    
    'database' => [
        'driver' => $_ENV['DB_CONNECTION'] ?? 'pgsql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? 5432,
        'database' => $_ENV['DB_DATABASE'] ?? 'infinri',
        'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => '',
    ],
    
    'cache' => [
        'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../storage/cache',
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
            ],
        ],
    ],
    
    'session' => [
        'driver' => $_ENV['SESSION_DRIVER'] ?? 'file',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => __DIR__ . '/../storage/sessions',
        'connection' => 'default',
        'table' => 'sessions',
        'lottery' => [2, 100],
        'cookie' => 'infinri_session',
        'path' => '/',
        'domain' => $_ENV['SESSION_DOMAIN'] ?? null,
        'secure' => ($_ENV['SESSION_SECURE_COOKIE'] ?? 'false') === 'true',
        'http_only' => true,
        'same_site' => 'lax',
    ],
    
    'logging' => [
        'default' => $_ENV['LOG_CHANNEL'] ?? 'stack',
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single'],
                'ignore_exceptions' => false,
            ],
            'single' => [
                'driver' => 'single',
                'path' => __DIR__ . '/../storage/logs/app.log',
                'level' => 'debug',
            ],
        ],
    ],
];
