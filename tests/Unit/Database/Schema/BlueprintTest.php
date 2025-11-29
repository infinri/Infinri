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
namespace Tests\Unit\Database\Schema;

use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\ColumnDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BlueprintTest extends TestCase
{
    #[Test]
    public function constructor_sets_table_name(): void
    {
        $blueprint = new Blueprint('users');
        
        $this->assertSame('users', $blueprint->getTable());
    }

    #[Test]
    public function id_creates_auto_incrementing_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->id();
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function id_uses_custom_name(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->id('user_id');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function big_increments_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->bigIncrements('id');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function increments_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->increments('id');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function string_creates_varchar_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->string('name');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function string_accepts_length(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->string('name', 100);
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function text_creates_text_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->text('content');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function integer_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->integer('age');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function big_integer_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->bigInteger('count');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function small_integer_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->smallInteger('status');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function get_columns_returns_columns(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $blueprint->string('name');
        
        $columns = $blueprint->getColumns();
        
        $this->assertCount(2, $columns);
    }

    #[Test]
    public function boolean_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->boolean('active');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function date_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->date('birth_date');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function datetime_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->dateTime('published_at');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function timestamp_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->timestamp('created_at');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function timestamp_tz_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->timestampTz('event_at');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function timestamps_creates_created_and_updated(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->timestamps();
        
        $columns = $blueprint->getColumns();
        $this->assertCount(2, $columns);
    }

    #[Test]
    public function soft_deletes_creates_nullable_timestamp(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->softDeletes();
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function soft_deletes_accepts_custom_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->softDeletes('removed_at');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function decimal_creates_column(): void
    {
        $blueprint = new Blueprint('products');
        
        $column = $blueprint->decimal('price', 10, 2);
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function float_creates_column(): void
    {
        $blueprint = new Blueprint('products');
        
        $column = $blueprint->float('rating');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function double_creates_column(): void
    {
        $blueprint = new Blueprint('products');
        
        $column = $blueprint->double('precision_value');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function json_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->json('meta');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function jsonb_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->jsonb('settings');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function uuid_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->uuid('guid');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function binary_creates_column(): void
    {
        $blueprint = new Blueprint('files');
        
        $column = $blueprint->binary('data');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function enum_creates_column(): void
    {
        $blueprint = new Blueprint('users');
        
        $column = $blueprint->enum('status', ['active', 'inactive', 'pending']);
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function primary_creates_index(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->primary('id');
        
        $indexes = $blueprint->getIndexes();
        $this->assertCount(1, $indexes);
        $this->assertSame('primary', $indexes[0]['type']);
    }

    #[Test]
    public function primary_accepts_array(): void
    {
        $blueprint = new Blueprint('user_roles');
        
        $blueprint->primary(['user_id', 'role_id']);
        
        $indexes = $blueprint->getIndexes();
        $this->assertCount(2, $indexes[0]['columns']);
    }

    #[Test]
    public function unique_creates_index(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->unique('email');
        
        $indexes = $blueprint->getIndexes();
        $this->assertCount(1, $indexes);
        $this->assertSame('unique', $indexes[0]['type']);
    }

    #[Test]
    public function unique_accepts_custom_name(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->unique('email', 'custom_unique_index');
        
        $indexes = $blueprint->getIndexes();
        $this->assertSame('custom_unique_index', $indexes[0]['name']);
    }

    #[Test]
    public function index_creates_index(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->index('email');
        
        $indexes = $blueprint->getIndexes();
        $this->assertCount(1, $indexes);
        $this->assertSame('index', $indexes[0]['type']);
    }

    #[Test]
    public function index_accepts_custom_name(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->index('email', 'custom_index');
        
        $indexes = $blueprint->getIndexes();
        $this->assertSame('custom_index', $indexes[0]['name']);
    }

    #[Test]
    public function foreign_creates_foreign_key(): void
    {
        $blueprint = new Blueprint('posts');
        
        $foreign = $blueprint->foreign('user_id')->references('id')->on('users');
        
        $foreignKeys = $blueprint->getForeignKeys();
        $this->assertCount(1, $foreignKeys);
    }

    #[Test]
    public function foreign_id_creates_big_integer(): void
    {
        $blueprint = new Blueprint('posts');
        
        $column = $blueprint->foreignId('user_id');
        
        $this->assertInstanceOf(ColumnDefinition::class, $column);
    }

    #[Test]
    public function drop_column_adds_command(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->dropColumn('email');
        
        $commands = $blueprint->getCommands();
        $this->assertCount(1, $commands);
        $this->assertSame('dropColumn', $commands[0]['type']);
    }

    #[Test]
    public function drop_column_accepts_array(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->dropColumn(['email', 'phone']);
        
        $commands = $blueprint->getCommands();
        $this->assertCount(2, $commands[0]['columns']);
    }

    #[Test]
    public function rename_column_adds_command(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->renameColumn('email', 'email_address');
        
        $commands = $blueprint->getCommands();
        $this->assertCount(1, $commands);
        $this->assertSame('renameColumn', $commands[0]['type']);
        $this->assertSame('email', $commands[0]['from']);
        $this->assertSame('email_address', $commands[0]['to']);
    }

    #[Test]
    public function drop_index_adds_command(): void
    {
        $blueprint = new Blueprint('users');
        
        $blueprint->dropIndex('users_email_index');
        
        $commands = $blueprint->getCommands();
        $this->assertCount(1, $commands);
        $this->assertSame('dropIndex', $commands[0]['type']);
    }

    #[Test]
    public function drop_foreign_adds_command(): void
    {
        $blueprint = new Blueprint('posts');
        
        $blueprint->dropForeign('posts_user_id_foreign');
        
        $commands = $blueprint->getCommands();
        $this->assertCount(1, $commands);
        $this->assertSame('dropForeign', $commands[0]['type']);
    }

    #[Test]
    public function get_indexes_returns_indexes(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->index('email');
        $blueprint->unique('username');
        
        $indexes = $blueprint->getIndexes();
        
        $this->assertCount(2, $indexes);
    }

    #[Test]
    public function get_foreign_keys_returns_foreign_keys(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->foreign('user_id')->references('id')->on('users');
        $blueprint->foreign('category_id')->references('id')->on('categories');
        
        $foreignKeys = $blueprint->getForeignKeys();
        
        $this->assertCount(2, $foreignKeys);
    }

    #[Test]
    public function get_commands_returns_commands(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('temp');
        $blueprint->renameColumn('old', 'new');
        
        $commands = $blueprint->getCommands();
        
        $this->assertCount(2, $commands);
    }
}
