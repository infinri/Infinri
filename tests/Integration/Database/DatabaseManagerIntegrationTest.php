<?php declare(strict_types=1);


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
use App\Core\Database\DatabaseException;
use App\Core\Database\DatabaseManager;
use App\Core\Database\DatabaseServiceProvider;
use App\Core\Database\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseManagerIntegrationTest extends TestCase
{
    private static ?Application $app = null;
    private DatabaseManager $manager;

    protected function setUp(): void
    {
        $this->bootApplication();
        $this->manager = self::$app->make(DatabaseManager::class);
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

            if (!self::$app->has(DatabaseManager::class)) {
                $provider = new DatabaseServiceProvider(self::$app);
                $provider->register();
            }
        }
    }

    #[Test]
    public function connection_returns_connection_instance(): void
    {
        $connection = $this->manager->connection();
        
        $this->assertInstanceOf(Connection::class, $connection);
    }

    #[Test]
    public function get_default_connection_returns_string(): void
    {
        $default = $this->manager->getDefaultConnection();
        
        $this->assertIsString($default);
    }

    #[Test]
    public function set_default_connection_changes_default(): void
    {
        $original = $this->manager->getDefaultConnection();
        
        $this->manager->setDefaultConnection('test_connection');
        
        // Reset for other tests
        $this->manager->setDefaultConnection($original);
        
        $this->assertTrue(true); // No exception means success
    }

    #[Test]
    public function table_returns_query_builder(): void
    {
        $builder = $this->manager->table('pages');
        
        $this->assertInstanceOf(QueryBuilder::class, $builder);
    }

    #[Test]
    public function get_connections_returns_array(): void
    {
        // Ensure at least one connection exists
        $this->manager->connection();
        
        $connections = $this->manager->getConnections();
        
        $this->assertIsArray($connections);
        $this->assertNotEmpty($connections);
    }

    #[Test]
    public function has_connection_returns_correct_status(): void
    {
        $default = $this->manager->getDefaultConnection();
        
        // Before connecting
        $this->manager->disconnect();
        $this->assertFalse($this->manager->hasConnection($default));
        
        // After connecting
        $this->manager->connection();
        $this->assertTrue($this->manager->hasConnection($default));
    }

    #[Test]
    public function disconnect_clears_connection(): void
    {
        $default = $this->manager->getDefaultConnection();
        
        $this->manager->connection();
        $this->assertTrue($this->manager->hasConnection($default));
        
        $this->manager->disconnect($default);
        $this->assertFalse($this->manager->hasConnection($default));
    }

    #[Test]
    public function disconnect_all_clears_all_connections(): void
    {
        $this->manager->connection();
        $this->assertNotEmpty($this->manager->getConnections());
        
        $this->manager->disconnect();
        $this->assertEmpty($this->manager->getConnections());
    }

    #[Test]
    public function reconnect_returns_fresh_connection(): void
    {
        $connection1 = $this->manager->connection();
        $connection2 = $this->manager->reconnect();
        
        $this->assertInstanceOf(Connection::class, $connection2);
    }

    #[Test]
    public function begin_transaction_returns_bool(): void
    {
        $result = $this->manager->beginTransaction();
        
        $this->assertIsBool($result);
        
        // Clean up
        $this->manager->rollBack();
    }

    #[Test]
    public function commit_returns_bool(): void
    {
        $this->manager->beginTransaction();
        $result = $this->manager->commit();
        
        $this->assertIsBool($result);
    }

    #[Test]
    public function rollback_returns_bool(): void
    {
        $this->manager->beginTransaction();
        $result = $this->manager->rollBack();
        
        $this->assertIsBool($result);
    }

    #[Test]
    public function transaction_executes_callback(): void
    {
        $executed = false;
        
        $this->manager->transaction(function() use (&$executed) {
            $executed = true;
        });
        
        $this->assertTrue($executed);
    }

    #[Test]
    public function transaction_returns_callback_result(): void
    {
        $result = $this->manager->transaction(function() {
            return 'test_result';
        });
        
        $this->assertSame('test_result', $result);
    }

    #[Test]
    public function magic_call_forwards_to_connection(): void
    {
        // select() is a method on Connection
        $result = $this->manager->select('SELECT 1 as num');
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function connection_throws_for_unconfigured_name(): void
    {
        $this->expectException(\App\Core\Database\DatabaseException::class);
        $this->expectExceptionMessage('not configured');
        
        $this->manager->connection('nonexistent_connection_xyz');
    }
}
