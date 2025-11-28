<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Core\Database\Migration;
use App\Core\Database\Schema\SchemaBuilder;
use App\Core\Contracts\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    #[Test]
    public function set_connection_stores_connection(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $migration = new TestMigration();
        
        $migration->setConnection($connection);
        
        $this->assertSame($connection, $migration->getConnection());
    }

    #[Test]
    public function set_connection_creates_schema_builder(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $migration = new TestMigration();
        
        $migration->setConnection($connection);
        
        $this->assertInstanceOf(SchemaBuilder::class, $migration->getSchema());
    }

    #[Test]
    public function up_method_is_callable(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $migration = new TestMigration();
        $migration->setConnection($connection);
        
        $migration->up();
        
        $this->assertTrue($migration->upWasCalled);
    }

    #[Test]
    public function down_method_is_callable(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $migration = new TestMigration();
        $migration->setConnection($connection);
        
        $migration->down();
        
        $this->assertTrue($migration->downWasCalled);
    }
}

class TestMigration extends Migration
{
    public bool $upWasCalled = false;
    public bool $downWasCalled = false;

    public function up(): void
    {
        $this->upWasCalled = true;
    }

    public function down(): void
    {
        $this->downWasCalled = true;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection();
    }

    public function getSchema(): SchemaBuilder
    {
        return $this->schema();
    }
}
