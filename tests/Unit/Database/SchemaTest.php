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

use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\ColumnDefinition;
use App\Core\Database\Schema\ForeignKeyDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    #[Test]
    public function it_creates_blueprint_for_table(): void
    {
        $blueprint = new Blueprint('users');
        
        $this->assertEquals('users', $blueprint->getTable());
    }

    #[Test]
    public function it_adds_id_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        
        $columns = $blueprint->getColumns();
        
        $this->assertCount(1, $columns);
        $this->assertEquals('id', $columns[0]->getName());
        $this->assertTrue($columns[0]->isPrimary());
    }

    #[Test]
    public function it_adds_string_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('name', 100);
        
        $columns = $blueprint->getColumns();
        $sql = $columns[0]->toSql();
        
        $this->assertStringContainsString('"name"', $sql);
        $this->assertStringContainsString('varchar(100)', $sql);
    }

    #[Test]
    public function it_adds_integer_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('age');
        
        $columns = $blueprint->getColumns();
        
        $this->assertEquals('integer', $columns[0]->getType());
    }

    #[Test]
    public function it_adds_boolean_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('is_active')->default(true);
        
        $columns = $blueprint->getColumns();
        $sql = $columns[0]->toSql();
        
        $this->assertStringContainsString('boolean', $sql);
        $this->assertStringContainsString('DEFAULT TRUE', $sql);
    }

    #[Test]
    public function it_adds_text_column(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->text('content');
        
        $columns = $blueprint->getColumns();
        
        $this->assertEquals('text', $columns[0]->getType());
    }

    #[Test]
    public function it_adds_timestamps(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        
        $columns = $blueprint->getColumns();
        
        $this->assertCount(2, $columns);
        $this->assertEquals('created_at', $columns[0]->getName());
        $this->assertEquals('updated_at', $columns[1]->getName());
    }

    #[Test]
    public function it_adds_nullable_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('bio')->nullable();
        
        $columns = $blueprint->getColumns();
        $sql = $columns[0]->toSql();
        
        $this->assertTrue($columns[0]->isNullable());
        $this->assertStringNotContainsString('NOT NULL', $sql);
    }

    #[Test]
    public function it_adds_default_value(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('status')->default('active');
        
        $columns = $blueprint->getColumns();
        $sql = $columns[0]->toSql();
        
        $this->assertStringContainsString("DEFAULT 'active'", $sql);
    }

    #[Test]
    public function it_adds_unique_constraint(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('email')->unique();
        
        $columns = $blueprint->getColumns();
        $sql = $columns[0]->toSql();
        
        $this->assertStringContainsString('UNIQUE', $sql);
    }

    #[Test]
    public function it_adds_index(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['name', 'email']);
        
        $indexes = $blueprint->getIndexes();
        
        $this->assertCount(1, $indexes);
        $this->assertEquals('index', $indexes[0]['type']);
        $this->assertEquals(['name', 'email'], $indexes[0]['columns']);
    }

    #[Test]
    public function it_adds_unique_index(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique('email');
        
        $indexes = $blueprint->getIndexes();
        
        $this->assertEquals('unique', $indexes[0]['type']);
    }

    #[Test]
    public function it_adds_json_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('preferences');
        
        $columns = $blueprint->getColumns();
        
        $this->assertEquals('json', $columns[0]->getType());
    }

    #[Test]
    public function it_adds_jsonb_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('data');
        
        $columns = $blueprint->getColumns();
        
        $this->assertEquals('jsonb', $columns[0]->getType());
    }

    #[Test]
    public function it_adds_decimal_column(): void
    {
        $blueprint = new Blueprint('products');
        $blueprint->decimal('price', 10, 2);
        
        $columns = $blueprint->getColumns();
        $sql = $columns[0]->toSql();
        
        $this->assertStringContainsString('decimal(10, 2)', $sql);
    }

    #[Test]
    public function it_adds_foreign_key(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('CASCADE');
        
        $foreignKeys = $blueprint->getForeignKeys();
        
        $this->assertCount(1, $foreignKeys);
        $this->assertEquals('user_id', $foreignKeys[0]->getColumn());
        $this->assertEquals('users', $foreignKeys[0]->getReferencedTable());
        $this->assertEquals('id', $foreignKeys[0]->getReferencedColumn());
        $this->assertEquals('CASCADE', $foreignKeys[0]->getOnDelete());
    }

    #[Test]
    public function it_adds_soft_deletes(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->softDeletes();
        
        $columns = $blueprint->getColumns();
        
        $this->assertEquals('deleted_at', $columns[0]->getName());
        $this->assertTrue($columns[0]->isNullable());
    }

    #[Test]
    public function it_adds_drop_column_command(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('old_column');
        
        $commands = $blueprint->getCommands();
        
        $this->assertCount(1, $commands);
        $this->assertEquals('dropColumn', $commands[0]['type']);
    }

    #[Test]
    public function it_adds_rename_column_command(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->renameColumn('old_name', 'new_name');
        
        $commands = $blueprint->getCommands();
        
        $this->assertEquals('renameColumn', $commands[0]['type']);
        $this->assertEquals('old_name', $commands[0]['from']);
        $this->assertEquals('new_name', $commands[0]['to']);
    }

    #[Test]
    public function it_adds_uuid_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('external_id');
        
        $columns = $blueprint->getColumns();
        
        $this->assertEquals('uuid', $columns[0]->getType());
    }

    #[Test]
    public function it_compiles_foreign_key_to_sql(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $fk->references('id')->on('users')->onDelete('CASCADE');
        
        $sql = $fk->toSql('posts');
        
        $this->assertStringContainsString('ALTER TABLE "posts"', $sql);
        $this->assertStringContainsString('FOREIGN KEY ("user_id")', $sql);
        $this->assertStringContainsString('REFERENCES "users" ("id")', $sql);
        $this->assertStringContainsString('ON DELETE CASCADE', $sql);
    }
}
