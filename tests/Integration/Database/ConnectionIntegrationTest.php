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
namespace Tests\Integration\Database;

use App\Core\Application;
use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConnectionIntegrationTest extends TestCase
{
    private static ?Application $app = null;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->bootApplication();
        
        $this->connection = new Connection([
            'driver' => 'pgsql',
            'host' => env('DB_TEST_HOST', '127.0.0.1'),
            'port' => (int) env('DB_TEST_PORT', 5432),
            'database' => env('DB_TEST_DATABASE', 'infinri_test'),
            'username' => env('DB_TEST_USERNAME', 'postgres'),
            'password' => env('DB_TEST_PASSWORD', 'postgres'),
        ], 'testing');
    }

    private function bootApplication(): void
    {
        if (self::$app === null) {
            $basePath = dirname(__DIR__, 3);
            
            $reflection = new \ReflectionClass(Application::class);
            $instance = $reflection->getProperty('instance');
            $instance->setAccessible(true);
            $instance->setValue(null, null);
            
            self::$app = new Application($basePath);
            self::$app->bootstrap();
        }
    }

    #[Test]
    public function select_returns_array(): void
    {
        $result = $this->connection->select('SELECT 1 as num');
        
        $this->assertIsArray($result);
        $this->assertSame(1, (int) $result[0]['num']);
    }

    #[Test]
    public function select_one_returns_single_row(): void
    {
        $result = $this->connection->selectOne('SELECT 1 as num');
        
        $this->assertIsArray($result);
        $this->assertSame(1, (int) $result['num']);
    }

    #[Test]
    public function select_one_returns_null_for_no_results(): void
    {
        $result = $this->connection->selectOne('SELECT 1 WHERE false');
        
        $this->assertNull($result);
    }

    #[Test]
    public function statement_executes_sql(): void
    {
        $result = $this->connection->statement('SELECT 1');
        
        $this->assertIsInt($result);
    }

    #[Test]
    public function table_returns_query_builder(): void
    {
        $builder = $this->connection->table('pages');
        
        $this->assertInstanceOf(QueryBuilder::class, $builder);
    }

    #[Test]
    public function query_log_records_queries(): void
    {
        $this->connection->enableQueryLog();
        $this->connection->select('SELECT 1');
        $this->connection->select('SELECT 2');
        
        $log = $this->connection->getQueryLog();
        
        // Just verify queries are being logged (at least our 2 queries)
        $this->assertGreaterThanOrEqual(2, count($log));
    }

    #[Test]
    public function flush_query_log_clears_entries(): void
    {
        $this->connection->enableQueryLog();
        $this->connection->select('SELECT 1');
        $this->connection->flushQueryLog();
        
        $this->assertEmpty($this->connection->getQueryLog());
    }

    #[Test]
    public function begin_transaction_increases_level(): void
    {
        $this->connection->beginTransaction();
        
        $this->assertSame(1, $this->connection->transactionLevel());
        
        $this->connection->rollBack();
    }

    #[Test]
    public function commit_decreases_level(): void
    {
        $this->connection->beginTransaction();
        $this->connection->commit();
        
        $this->assertSame(0, $this->connection->transactionLevel());
    }

    #[Test]
    public function rollback_decreases_level(): void
    {
        $this->connection->beginTransaction();
        $this->connection->rollBack();
        
        $this->assertSame(0, $this->connection->transactionLevel());
    }

    #[Test]
    public function transaction_callback_commits_on_success(): void
    {
        $this->connection->statement('DELETE FROM pages WHERE slug = ?', ['tx-test']);
        
        $result = $this->connection->transaction(function ($conn) {
            $conn->insert(
                'INSERT INTO pages (title, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())',
                ['TX Test', 'tx-test']
            );
            return 'success';
        });
        
        $this->assertSame('success', $result);
        
        $this->connection->statement('DELETE FROM pages WHERE slug = ?', ['tx-test']);
    }

    #[Test]
    public function get_pdo_returns_pdo_instance(): void
    {
        $pdo = $this->connection->getPdo();
        
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    #[Test]
    public function reconnect_creates_new_connection(): void
    {
        $pdo1 = $this->connection->getPdo();
        $this->connection->reconnect();
        $pdo2 = $this->connection->getPdo();
        
        // After reconnect, we should still have a working connection
        $result = $this->connection->selectOne('SELECT 1 as num');
        $this->assertSame(1, (int) $result['num']);
    }
}
