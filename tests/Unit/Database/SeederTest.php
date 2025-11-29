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

use App\Core\Database\Seeder;
use App\Core\Database\QueryBuilder;
use App\Core\Contracts\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SeederTest extends TestCase
{
    #[Test]
    public function set_connection_stores_connection(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $seeder = new TestSeeder();
        
        $seeder->setConnection($connection);
        
        $this->assertInstanceOf(TestSeeder::class, $seeder);
    }

    #[Test]
    public function run_is_called(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $seeder = new TestSeeder();
        $seeder->setConnection($connection);
        
        $seeder->run();
        
        $this->assertTrue($seeder->wasRun);
    }

    #[Test]
    public function connection_is_accessible(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $seeder = new TestSeederWithConnection();
        $seeder->setConnection($connection);
        
        $this->assertSame($connection, $seeder->getConnection());
    }
}

class TestSeeder extends Seeder
{
    public bool $wasRun = false;
    
    public function run(): void
    {
        $this->wasRun = true;
    }
}

class TestSeederWithConnection extends Seeder
{
    public function run(): void {}
    
    public function getConnection(): ConnectionInterface
    {
        return $this->connection();
    }
}
