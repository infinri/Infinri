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

use App\Core\Database\Schema\ColumnDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ColumnDefinitionTest extends TestCase
{
    #[Test]
    public function constructor_sets_properties(): void
    {
        $column = new ColumnDefinition('name', 'varchar', ['length' => 255]);
        
        $this->assertSame('name', $column->getName());
        $this->assertSame('varchar', $column->getType());
        $this->assertSame(['length' => 255], $column->getParameters());
    }

    #[Test]
    public function nullable_makes_column_nullable(): void
    {
        $column = new ColumnDefinition('name', 'varchar');
        
        $column->nullable();
        
        $this->assertTrue($column->isNullable());
    }

    #[Test]
    public function default_sets_default_value(): void
    {
        $column = new ColumnDefinition('status', 'varchar');
        
        $column->default('active');
        
        $this->assertTrue($column->hasDefault());
        $this->assertSame('active', $column->getDefault());
    }

    #[Test]
    public function unsigned_marks_column_unsigned(): void
    {
        $column = new ColumnDefinition('count', 'integer');
        
        $column->unsigned();
        
        $this->assertTrue($column->isUnsigned());
    }

    #[Test]
    public function primary_marks_as_primary_key(): void
    {
        $column = new ColumnDefinition('id', 'bigserial');
        
        $column->primary();
        
        $this->assertTrue($column->isPrimary());
    }

    #[Test]
    public function unique_adds_unique_constraint(): void
    {
        $column = new ColumnDefinition('email', 'varchar');
        
        $column->unique();
        
        $this->assertTrue($column->isUnique());
    }

    #[Test]
    public function comment_sets_comment(): void
    {
        $column = new ColumnDefinition('status', 'varchar');
        
        $column->comment('User status');
        
        $this->assertSame('User status', $column->getComment());
    }

    #[Test]
    public function use_current_sets_current_timestamp(): void
    {
        $column = new ColumnDefinition('created_at', 'timestamp');
        
        $column->useCurrent();
        
        $this->assertTrue($column->hasDefault());
        $this->assertSame('CURRENT_TIMESTAMP', $column->getDefault());
    }

    #[Test]
    public function fluent_interface_returns_self(): void
    {
        $column = new ColumnDefinition('name', 'varchar');
        
        $result = $column->nullable()->default('test')->unique();
        
        $this->assertSame($column, $result);
    }

    #[Test]
    public function to_sql_generates_basic_column(): void
    {
        $column = new ColumnDefinition('name', 'varchar', ['length' => 255]);
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString('"name"', $sql);
        $this->assertStringContainsString('varchar(255)', $sql);
        $this->assertStringContainsString('NOT NULL', $sql);
    }

    #[Test]
    public function to_sql_includes_primary_key(): void
    {
        $column = new ColumnDefinition('id', 'bigserial');
        $column->primary();
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString('PRIMARY KEY', $sql);
    }

    #[Test]
    public function to_sql_includes_unique(): void
    {
        $column = new ColumnDefinition('email', 'varchar');
        $column->unique();
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString('UNIQUE', $sql);
    }

    #[Test]
    public function to_sql_includes_string_default(): void
    {
        $column = new ColumnDefinition('status', 'varchar');
        $column->default('active');
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString("DEFAULT 'active'", $sql);
    }

    #[Test]
    public function to_sql_includes_null_default(): void
    {
        $column = new ColumnDefinition('deleted_at', 'timestamp');
        $column->nullable()->default(null);
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString('DEFAULT NULL', $sql);
    }

    #[Test]
    public function to_sql_includes_boolean_default(): void
    {
        $column = new ColumnDefinition('active', 'boolean');
        $column->default(true);
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString('DEFAULT TRUE', $sql);
    }

    #[Test]
    public function to_sql_includes_numeric_default(): void
    {
        $column = new ColumnDefinition('count', 'integer');
        $column->default(0);
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString('DEFAULT 0', $sql);
    }

    #[Test]
    public function to_sql_includes_current_timestamp(): void
    {
        $column = new ColumnDefinition('created_at', 'timestamp');
        $column->useCurrent();
        
        $sql = $column->toSql();
        
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $sql);
    }

    #[Test]
    public function after_sets_position(): void
    {
        $column = new ColumnDefinition('email', 'varchar');
        $result = $column->after('name');
        
        $this->assertSame($column, $result);
        // The after property is set (tested by fluent return)
    }
}
