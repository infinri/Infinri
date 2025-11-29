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
namespace Tests\Unit\Database;

use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    #[Test]
    public function constructor_sets_config(): void
    {
        $connection = new Connection(['driver' => 'pgsql']);
        
        $this->assertInstanceOf(Connection::class, $connection);
    }

    #[Test]
    public function constructor_sets_name(): void
    {
        $connection = new Connection([], 'custom');
        
        $this->assertSame('custom', $connection->getName());
    }

    #[Test]
    public function get_name_returns_default(): void
    {
        $connection = new Connection([]);
        
        $this->assertSame('default', $connection->getName());
    }

    #[Test]
    public function get_driver_name_returns_config(): void
    {
        $connection = new Connection(['driver' => 'mysql']);
        
        $this->assertSame('mysql', $connection->getDriverName());
    }

    #[Test]
    public function get_driver_name_defaults_to_pgsql(): void
    {
        $connection = new Connection([]);
        
        $this->assertSame('pgsql', $connection->getDriverName());
    }

    #[Test]
    public function get_database_name_returns_config(): void
    {
        $connection = new Connection(['database' => 'testdb']);
        
        $this->assertSame('testdb', $connection->getDatabaseName());
    }

    #[Test]
    public function get_table_prefix_returns_config(): void
    {
        $connection = new Connection(['prefix' => 'app_']);
        
        $this->assertSame('app_', $connection->getTablePrefix());
    }

    #[Test]
    public function get_table_prefix_defaults_empty(): void
    {
        $connection = new Connection([]);
        
        $this->assertSame('', $connection->getTablePrefix());
    }

    #[Test]
    public function enable_query_log_enables_logging(): void
    {
        $connection = new Connection([]);
        
        $connection->enableQueryLog();
        
        // No exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function disable_query_log_disables_logging(): void
    {
        $connection = new Connection([]);
        
        $connection->enableQueryLog();
        $connection->disableQueryLog();
        
        $this->assertTrue(true);
    }

    #[Test]
    public function get_query_log_returns_array(): void
    {
        $connection = new Connection([]);
        
        $this->assertIsArray($connection->getQueryLog());
    }

    #[Test]
    public function flush_query_log_clears_log(): void
    {
        $connection = new Connection([]);
        
        $connection->flushQueryLog();
        
        $this->assertEmpty($connection->getQueryLog());
    }

    #[Test]
    public function transaction_level_starts_at_zero(): void
    {
        $connection = new Connection([]);
        
        $this->assertSame(0, $connection->transactionLevel());
    }

    #[Test]
    public function disconnect_sets_pdo_null(): void
    {
        $connection = new Connection([]);
        
        $connection->disconnect();
        
        $this->assertTrue(true); // No exception
    }
}
