<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Postgres\PostgresDriver;

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

// Check if admin user already exists
$adminEmail = 'admin@example.com';
$adminExists = $dbal->database('default')
    ->table('users')
    ->select()
    ->where('email', $adminEmail)
    ->count() > 0;

if ($adminExists) {
    echo "Admin user already exists.\n";
    exit(0);
}

// Create admin user
$password = password_hash('admin123', PASSWORD_DEFAULT);
$now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

$dbal->database('default')
    ->insert('users')
    ->values([
        'email' => $adminEmail,
        'password' => $password,
        'name' => 'Admin User',
        'role' => 'admin',
        'created_at' => $now,
        'updated_at' => $now,
    ])
    ->run();

echo "Admin user created successfully.\n";
echo "Email: admin@example.com\n";
echo "Password: admin123\n";
echo "\nPlease change the password after first login.\n";
