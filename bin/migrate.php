<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use Cycle\Migrations\State;

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
        'pgsql' => new \Cycle\Database\Config\PostgresDriverConfig(
            connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                database: $_ENV['DB_DATABASE'] ?? 'infinri',
                host: $_ENV['DB_HOST'] ?? '127.0.0.1',
                port: (int)($_ENV['DB_PORT'] ?? 5432),
                user: $_ENV['DB_USERNAME'] ?? 'postgres',
                password: $_ENV['DB_PASSWORD'] ?? ''
            ),
            queryCache: true,
            timezone: 'UTC',
            schema: 'public',
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        )
    ]
]));

// Configure migrations
$config = new MigrationConfig([
    'directory' => __DIR__ . '/../migrations',
    'table' => 'migrations',
    'safe' => true, // Auto-confirm migrations
]);

// Create migrator
$migrator = new Migrator(
    $config,
    $dbal,
    new FileRepository($config)
);

// Initialize migrations table if it doesn't exist
if (!$migrator->isConfigured()) {
    $migrator->configure();
}

// Get all available migrations
$migrations = $migrator->getMigrations();

if (empty($migrations)) {
    echo "No migrations found.\n";
    exit(0);
}

// Get the status of all migrations
$status = $migrator->getRepository()->getMigrations();

$count = 0;
foreach ($migrations as $migration) {
    $state = $migration->getState();
    
    // Check if migration is not executed
    if ($state->getStatus() !== State::STATUS_EXECUTED) {
        echo "Running migration: " . $state->getName() . "\n";
        $migrator->run();
        $count++;
    } else {
        echo "Migration already executed: " . $state->getName() . "\n";
    }
}

if ($count > 0) {
    echo "Successfully ran $count migration(s).\n";
} else {
    echo "No new migrations to run.\n";
}
