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
namespace Tests\Unit\Database\Schema;

use App\Core\Database\Schema\SchemaBuilder;
use App\Core\Database\Schema\Blueprint;
use App\Core\Contracts\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testable SchemaBuilder that bypasses logging
 */
class TestableSchemaBuilder extends SchemaBuilder
{
    public array $executedStatements = [];
    
    protected function logSchemaChange(string $action, string $details): void
    {
        // Don't log in tests
    }
    
    // Expose protected methods
    public function testCompileCreate(Blueprint $blueprint): string
    {
        return $this->compileCreate($blueprint);
    }
    
    public function testCompileIndex(string $table, array $index): string
    {
        return $this->compileIndex($table, $index);
    }
}

class SchemaBuilderTest extends TestCase
{
    private $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(ConnectionInterface::class);
    }

    #[Test]
    public function constructor_accepts_connection(): void
    {
        $schema = new SchemaBuilder($this->connection);
        
        $this->assertInstanceOf(SchemaBuilder::class, $schema);
    }

    #[Test]
    public function create_executes_statement(): void
    {
        $this->connection->expects($this->atLeastOnce())
            ->method('statement');
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    #[Test]
    public function drop_executes_drop_statement(): void
    {
        $this->connection->expects($this->once())
            ->method('statement')
            ->with($this->stringContains('DROP TABLE'));
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $schema->drop('users');
    }

    #[Test]
    public function drop_if_exists_calls_drop(): void
    {
        $this->connection->expects($this->once())
            ->method('statement')
            ->with($this->stringContains('DROP TABLE IF EXISTS'));
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $schema->dropIfExists('users');
    }

    #[Test]
    public function rename_executes_rename_statement(): void
    {
        $this->connection->expects($this->once())
            ->method('statement')
            ->with($this->stringContains('RENAME TO'));
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $schema->rename('old_table', 'new_table');
    }

    #[Test]
    public function has_table_queries_information_schema(): void
    {
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->willReturn(['exists' => true]);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $result = $schema->hasTable('users');
        
        $this->assertTrue($result);
    }

    #[Test]
    public function has_table_returns_false_when_not_exists(): void
    {
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->willReturn(['exists' => false]);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $result = $schema->hasTable('nonexistent');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function has_column_queries_information_schema(): void
    {
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->willReturn(['exists' => true]);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $result = $schema->hasColumn('users', 'email');
        
        $this->assertTrue($result);
    }

    #[Test]
    public function has_column_returns_false_when_not_exists(): void
    {
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->willReturn(['exists' => false]);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $result = $schema->hasColumn('users', 'nonexistent');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function get_tables_returns_array(): void
    {
        $this->connection->expects($this->once())
            ->method('select')
            ->willReturn([
                ['table_name' => 'users'],
                ['table_name' => 'posts'],
            ]);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $tables = $schema->getTables();
        
        $this->assertCount(2, $tables);
        $this->assertContains('users', $tables);
        $this->assertContains('posts', $tables);
    }

    #[Test]
    public function get_columns_returns_column_info(): void
    {
        $this->connection->expects($this->once())
            ->method('select')
            ->willReturn([
                ['column_name' => 'id', 'data_type' => 'integer', 'is_nullable' => 'NO', 'column_default' => null],
                ['column_name' => 'name', 'data_type' => 'character varying', 'is_nullable' => 'YES', 'column_default' => null],
            ]);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $columns = $schema->getColumns('users');
        
        $this->assertCount(2, $columns);
    }

    #[Test]
    public function compile_create_generates_valid_sql(): void
    {
        $schema = new TestableSchemaBuilder($this->connection);
        
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $blueprint->string('name');
        
        $sql = $schema->testCompileCreate($blueprint);
        
        $this->assertStringContainsString('CREATE TABLE "users"', $sql);
    }

    #[Test]
    public function compile_index_generates_regular_index(): void
    {
        $schema = new TestableSchemaBuilder($this->connection);
        
        $index = [
            'name' => 'idx_users_email',
            'columns' => ['email'],
            'type' => 'index',
        ];
        
        $sql = $schema->testCompileIndex('users', $index);
        
        $this->assertStringContainsString('CREATE INDEX', $sql);
        $this->assertStringContainsString('idx_users_email', $sql);
    }

    #[Test]
    public function compile_index_generates_unique_index(): void
    {
        $schema = new TestableSchemaBuilder($this->connection);
        
        $index = [
            'name' => 'idx_users_email_unique',
            'columns' => ['email'],
            'type' => 'unique',
        ];
        
        $sql = $schema->testCompileIndex('users', $index);
        
        $this->assertStringContainsString('CREATE UNIQUE INDEX', $sql);
    }

    #[Test]
    public function table_adds_columns(): void
    {
        $this->connection->expects($this->atLeastOnce())
            ->method('statement')
            ->with($this->stringContains('ALTER TABLE'));
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $schema->table('users', function (Blueprint $table) {
            $table->string('email');
        });
    }

    #[Test]
    public function create_handles_indexes(): void
    {
        $statementCalls = [];
        $this->connection->expects($this->atLeastOnce())
            ->method('statement')
            ->willReturnCallback(function ($sql) use (&$statementCalls) {
                $statementCalls[] = $sql;
                return 0;
            });
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->index('email');
        });
        
        // Should have create table and index
        $this->assertGreaterThanOrEqual(1, count($statementCalls));
    }

    #[Test]
    public function create_handles_foreign_keys(): void
    {
        $statementCalls = [];
        $this->connection->expects($this->atLeastOnce())
            ->method('statement')
            ->willReturnCallback(function ($sql) use (&$statementCalls) {
                $statementCalls[] = $sql;
                return 0;
            });
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $schema->create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->foreign('user_id')->references('id')->on('users');
        });
        
        $this->assertGreaterThanOrEqual(1, count($statementCalls));
    }

    #[Test]
    public function has_table_handles_null_result(): void
    {
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->willReturn(null);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $result = $schema->hasTable('users');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function has_column_handles_null_result(): void
    {
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->willReturn(null);
        
        $schema = new TestableSchemaBuilder($this->connection);
        
        $result = $schema->hasColumn('users', 'email');
        
        $this->assertFalse($result);
    }
}
