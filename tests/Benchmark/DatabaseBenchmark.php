<?php

declare(strict_types=1);

namespace Tests\Benchmark;

use App\Core\Application;
use App\Core\Database\Connection;
use App\Core\Database\Model;

/**
 * Database & ORM Benchmark
 * 
 * Benchmarks:
 * - Simple SELECT
 * - Prepared statements
 * - Joins
 * - Bulk inserts
 * - Hydration time
 * 
 * Uses PostgreSQL via the framework's Connection class.
 * Requires a configured database connection.
 * 
 * Run with: php tests/Benchmark/DatabaseBenchmark.php
 */
final class DatabaseBenchmark
{
    private array $results = [];
    private const ITERATIONS = 100;
    private ?Connection $connection = null;
    private ?Application $app = null;
    private string $tempDir = '';
    private bool $createdTestTables = false;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Database & ORM Benchmark\n";
        echo "========================================\n\n";

        if (!$this->setupDatabase()) {
            echo "Skipping database benchmarks (no database connection)\n\n";
            $this->benchmarkModelHydration();
            $this->printResults();
            return;
        }

        try {
            $this->benchmarkSimpleSelect();
            $this->benchmarkPreparedStatements();
            $this->benchmarkJoins();
            $this->benchmarkBulkInserts();
            $this->benchmarkHydration();
            $this->benchmarkModelHydration();
            $this->benchmarkLargeDataset();
            $this->printResults();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Setup database connection using framework
     */
    private function setupDatabase(): bool
    {
        try {
            // Setup temporary app for database access
            $this->tempDir = sys_get_temp_dir() . '/db_bench_' . uniqid();
            mkdir($this->tempDir, 0755, true);
            mkdir($this->tempDir . '/var/log', 0755, true);
            
            // Copy .env if exists
            $envFile = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envFile)) {
                copy($envFile, $this->tempDir . '/.env');
            } else {
                echo "No .env file found, skipping database benchmarks\n";
                return false;
            }

            Application::resetInstance();
            $this->app = new Application($this->tempDir);
            $this->app->bootstrap();

            // Get database connection from env (use testing database for benchmarks)
            $dbConfig = [
                'driver' => 'pgsql',
                'host' => env('DB_TEST_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => (int) env('DB_TEST_PORT', env('DB_PORT', 5432)),
                'database' => env('DB_TEST_DATABASE', env('DB_DATABASE', '')),
                'username' => env('DB_TEST_USERNAME', env('DB_USERNAME', 'postgres')),
                'password' => env('DB_TEST_PASSWORD', env('DB_PASSWORD', '')),
            ];
            
            if (empty($dbConfig['database'])) {
                echo "No database configured in .env\n";
                return false;
            }
            
            echo "Connecting to {$dbConfig['database']}@{$dbConfig['host']}... ";
            $this->connection = new Connection($dbConfig);
            
            // Test connection
            $this->connection->select('SELECT 1');
            
            // Create test tables
            $this->createTestTables();
            $this->seedDatabase();
            
            return true;
        } catch (\Throwable $e) {
            echo "Database setup failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Create test tables
     */
    private function createTestTables(): void
    {
        echo "Creating test tables... ";

        // Use temporary table names to avoid conflicts
        $prefix = 'bench_' . substr(uniqid(), -6) . '_';
        
        $this->connection->statement("
            CREATE TABLE IF NOT EXISTS {$prefix}users (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->connection->statement("
            CREATE TABLE IF NOT EXISTS {$prefix}posts (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                body TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->connection->statement("
            CREATE TABLE IF NOT EXISTS {$prefix}comments (
                id SERIAL PRIMARY KEY,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->createdTestTables = true;
        $this->tablePrefix = $prefix;
        
        echo "Done\n";
    }

    private string $tablePrefix = '';

    /**
     * Seed test data
     */
    private function seedDatabase(): void
    {
        echo "Seeding database... ";

        $prefix = $this->tablePrefix;

        // Insert users
        for ($i = 1; $i <= 100; $i++) {
            $this->connection->insert(
                "INSERT INTO {$prefix}users (name, email, status) VALUES (?, ?, ?)",
                ["User {$i}", "user{$i}@bench.test", $i % 5 === 0 ? 'inactive' : 'active']
            );
        }

        // Insert posts
        for ($i = 1; $i <= 500; $i++) {
            $userId = rand(1, 100);
            $this->connection->insert(
                "INSERT INTO {$prefix}posts (user_id, title, body) VALUES (?, ?, ?)",
                [$userId, "Post {$i}", str_repeat("Content. ", 10)]
            );
        }

        // Insert comments
        for ($i = 1; $i <= 1000; $i++) {
            $postId = rand(1, 500);
            $userId = rand(1, 100);
            $this->connection->insert(
                "INSERT INTO {$prefix}comments (post_id, user_id, content) VALUES (?, ?, ?)",
                [$postId, $userId, "Comment {$i}"]
            );
        }

        echo "Done\n";
    }

    /**
     * Benchmark simple SELECT queries
     */
    private function benchmarkSimpleSelect(): void
    {
        echo "Benchmarking Simple SELECT... ";
        $prefix = $this->tablePrefix;

        // Single row
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->connection->selectOne("SELECT * FROM {$prefix}users WHERE id = ?", [rand(1, 100)]);
            $times[] = hrtime(true) - $start;
        }

        $this->results['SELECT (Single Row)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Multiple rows
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->connection->select("SELECT * FROM {$prefix}users WHERE status = ?", ['active']);
            $times[] = hrtime(true) - $start;
        }

        $this->results['SELECT (Multiple Rows)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // All rows
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->connection->select("SELECT * FROM {$prefix}users");
            $times[] = hrtime(true) - $start;
        }

        $this->results['SELECT (All 100 Rows)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark prepared statements
     */
    private function benchmarkPreparedStatements(): void
    {
        echo "Benchmarking Prepared Statements... ";
        $prefix = $this->tablePrefix;

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->connection->select(
                "SELECT * FROM {$prefix}users WHERE id = ? AND status = ?",
                [rand(1, 100), 'active']
            );
            $times[] = hrtime(true) - $start;
        }

        $this->results['Prepared Statement'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark JOIN queries
     */
    private function benchmarkJoins(): void
    {
        echo "Benchmarking JOINs... ";
        $prefix = $this->tablePrefix;

        // Simple JOIN
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->connection->select("
                SELECT u.name, p.title 
                FROM {$prefix}users u
                JOIN {$prefix}posts p ON u.id = p.user_id 
                WHERE u.id = ?
            ", [rand(1, 100)]);
            $times[] = hrtime(true) - $start;
        }

        $this->results['JOIN (Simple)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Multiple JOINs
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->connection->select("
                SELECT u.name, p.title, c.content 
                FROM {$prefix}users u
                JOIN {$prefix}posts p ON u.id = p.user_id 
                JOIN {$prefix}comments c ON p.id = c.post_id 
                WHERE u.id = ?
                LIMIT 10
            ", [rand(1, 100)]);
            $times[] = hrtime(true) - $start;
        }

        $this->results['JOIN (Triple)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark bulk inserts
     */
    private function benchmarkBulkInserts(): void
    {
        echo "Benchmarking Bulk Inserts... ";
        $prefix = $this->tablePrefix;

        // Single inserts
        $times = [];
        for ($i = 0; $i < 50; $i++) {
            $start = hrtime(true);
            $this->connection->insert(
                "INSERT INTO {$prefix}users (name, email) VALUES (?, ?)",
                ["BulkUser{$i}", "bulk{$i}@bench.test"]
            );
            $times[] = hrtime(true) - $start;
        }

        $this->results['INSERT (Single)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => 50 / (array_sum($times) / 1e9),
        ];

        // Transaction batch
        $times = [];
        for ($batch = 0; $batch < 5; $batch++) {
            $start = hrtime(true);
            $this->connection->beginTransaction();
            for ($i = 0; $i < 100; $i++) {
                $this->connection->insert(
                    "INSERT INTO {$prefix}users (name, email) VALUES (?, ?)",
                    ["TxUser{$batch}_{$i}", "tx{$batch}_{$i}@bench.test"]
                );
            }
            $this->connection->commit();
            $times[] = hrtime(true) - $start;
        }

        $this->results['INSERT (100 in Transaction)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'per_row_us' => ((array_sum($times) / count($times)) / 100) / 1e3,
            'rows_per_sec' => (5 * 100) / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark result hydration
     */
    private function benchmarkHydration(): void
    {
        echo "Benchmarking Hydration... ";
        $prefix = $this->tablePrefix;

        $times = [];
        $rows = 0;
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $result = $this->connection->select("SELECT * FROM {$prefix}users LIMIT 50");
            $rows = count($result);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Hydration (Array, 50 rows)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'rows' => $rows,
            'per_row_us' => $rows > 0 ? ((array_sum($times) / count($times)) / $rows) / 1e3 : 0,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark Model hydration
     */
    private function benchmarkModelHydration(): void
    {
        echo "Benchmarking Model Hydration... ";

        // Create synthetic model class
        $modelClass = new class extends Model {
            protected string $table = 'users';
            protected array $fillable = ['name', 'email', 'status'];
        };

        $data = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $times = [];
        $memoryUsages = [];
        for ($i = 0; $i < self::ITERATIONS * 10; $i++) {
            $startMemory = memory_get_usage();
            $start = hrtime(true);
            
            $model = new $modelClass();
            $model->fill($data);
            
            $times[] = hrtime(true) - $start;
            $memoryUsages[] = memory_get_usage() - $startMemory;
        }

        $this->results['Model Hydration (Single)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => count($times) / (array_sum($times) / 1e9),
            'memory_per_instance_bytes' => array_sum($memoryUsages) / count($memoryUsages),
        ];

        // Bulk hydration
        $bulkData = array_fill(0, 100, $data);
        
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $models = [];
            foreach ($bulkData as $row) {
                $model = new $modelClass();
                $model->fill($row);
                $models[] = $model;
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Model Hydration (100 instances)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'per_model_ns' => (array_sum($times) / count($times)) / 100,
            'models_per_sec' => (self::ITERATIONS * 100) / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark large dataset handling
     */
    private function benchmarkLargeDataset(): void
    {
        echo "Benchmarking Large Dataset... ";
        $prefix = $this->tablePrefix;

        $times = [];
        for ($i = 0; $i < 10; $i++) {
            $start = hrtime(true);
            $this->connection->select("
                SELECT p.*, COUNT(c.id) as comment_count
                FROM {$prefix}posts p
                LEFT JOIN {$prefix}comments c ON p.id = c.post_id
                GROUP BY p.id
            ");
            $times[] = hrtime(true) - $start;
        }

        $this->results['Large Query (500 posts + aggregation)'] = [
            'avg_ms' => (array_sum($times) / count($times)) / 1e6,
        ];

        echo "Done\n";
    }

    /**
     * Print results
     */
    private function printResults(): void
    {
        echo "\n========================================\n";
        echo "  Results\n";
        echo "========================================\n\n";

        foreach ($this->results as $name => $data) {
            echo "{$name}:\n";
            
            foreach ($data as $key => $value) {
                $label = str_replace('_', ' ', ucfirst($key));
                
                if (is_float($value)) {
                    if (str_contains($key, '_us')) {
                        printf("  %s: %.2f µs\n", $label, $value);
                    } elseif (str_contains($key, '_ns')) {
                        printf("  %s: %.0f ns\n", $label, $value);
                    } elseif (str_contains($key, '_ms')) {
                        printf("  %s: %.2f ms\n", $label, $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", $label, number_format($value, 0));
                    } elseif (str_contains($key, 'bytes')) {
                        printf("  %s: %d bytes\n", $label, (int)$value);
                    } else {
                        printf("  %s: %.2f\n", $label, $value);
                    }
                } else {
                    printf("  %s: %s\n", $label, $value);
                }
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }

    /**
     * Cleanup test tables and connection
     */
    private function cleanup(): void
    {
        if ($this->createdTestTables && $this->connection) {
            try {
                $prefix = $this->tablePrefix;
                $this->connection->statement("DROP TABLE IF EXISTS {$prefix}comments");
                $this->connection->statement("DROP TABLE IF EXISTS {$prefix}posts");
                $this->connection->statement("DROP TABLE IF EXISTS {$prefix}users");
            } catch (\Throwable $e) {
                // Ignore cleanup errors
            }
        }

        Application::resetInstance();
        
        if ($this->tempDir && is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__, 2));
    }
    
    (new DatabaseBenchmark())->run();
}
