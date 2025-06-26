<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize DBAL
$dbal = new DatabaseManager(new DatabaseConfig([
    'default' => 'default',
    'databases' => [
        'default' => ['connection' => 'pgsql']
    ],
    'connections' => [
        'pgsql' => [
            'driver' => PostgresDriver::class,
            'connection' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['DB_HOST'] ?? '127.0.0.1',
                $_ENV['DB_PORT'] ?? '5432',
                $_ENV['DB_DATABASE'] ?? 'infinri'
            ),
            'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]
    ]
]));

// Configure migrations
$migrationsDir = __DIR__ . '/../migrations';
if (!file_exists($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

$config = new MigrationConfig([
    'directory' => $migrationsDir,
    'table' => 'migrations',
    'safe' => false,
]);

// Create migrator
$migrator = new Migrator(
    $config,
    $dbal,
    new FileRepository($config)
);

// Create migration
$migration = $migrator->create('create_users_table');

// Add SQL to the migration
$migration->up()->add(
    "CREATE TABLE IF NOT EXISTS users (\n" .
    "    id SERIAL PRIMARY KEY,\n" .
    "    email VARCHAR(255) NOT NULL,\n" .
    "    password VARCHAR(255) NOT NULL,\n" .
    "    name VARCHAR(255) NULL,\n" .
    "    role VARCHAR(50) NOT NULL DEFAULT 'user',\n" .
    "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
    "    updated_at TIMESTAMP NULL,\n" .
    "    CONSTRAINT users_email_unique UNIQUE (email)"
    
);

$migration->down()->add("DROP TABLE IF EXISTS users");

// Save the migration
$migration->save();

echo "Migration created: " . basename($migration->getFile()->getFilename()) . "\n";
