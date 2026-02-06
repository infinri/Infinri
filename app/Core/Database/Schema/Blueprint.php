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
namespace App\Core\Database\Schema;

/**
 * Schema Blueprint
 *
 * Fluent interface for defining table structure.
 */
class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreignKeys = [];
    protected array $commands = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Get the table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    // ==================== Column Types ====================

    /**
     * Add an auto-incrementing big integer (primary key)
     */
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($column);
    }

    /**
     * Add an auto-incrementing big integer
     */
    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->addColumn('bigserial', $column)->primary();
    }

    /**
     * Add an auto-incrementing integer
     */
    public function increments(string $column): ColumnDefinition
    {
        return $this->addColumn('serial', $column)->primary();
    }

    /**
     * Add a big integer column
     */
    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('bigint', $column);
    }

    /**
     * Add an integer column
     */
    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn('integer', $column);
    }

    /**
     * Add a small integer column
     */
    public function smallInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('smallint', $column);
    }

    /**
     * Add a string column
     */
    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('varchar', $column, ['length' => $length]);
    }

    /**
     * Add a text column
     */
    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Add a boolean column
     */
    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Add a date column
     */
    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn('date', $column);
    }

    /**
     * Add a datetime column
     */
    public function dateTime(string $column): ColumnDefinition
    {
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Add a timestamp column
     */
    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Add timestamptz column (with timezone)
     */
    public function timestampTz(string $column): ColumnDefinition
    {
        return $this->addColumn('timestamptz', $column);
    }

    /**
     * Add created_at and updated_at columns
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /**
     * Add a soft delete column
     */
    public function softDeletes(string $column = 'deleted_at'): ColumnDefinition
    {
        return $this->timestamp($column)->nullable();
    }

    /**
     * Add a decimal column
     */
    public function decimal(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('decimal', $column, [
            'precision' => $precision,
            'scale' => $scale,
        ]);
    }

    /**
     * Add a float column
     */
    public function float(string $column): ColumnDefinition
    {
        return $this->addColumn('real', $column);
    }

    /**
     * Add a double column
     */
    public function double(string $column): ColumnDefinition
    {
        return $this->addColumn('double precision', $column);
    }

    /**
     * Add a JSON column
     */
    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn('json', $column);
    }

    /**
     * Add a JSONB column
     */
    public function jsonb(string $column): ColumnDefinition
    {
        return $this->addColumn('jsonb', $column);
    }

    /**
     * Add a UUID column
     */
    public function uuid(string $column): ColumnDefinition
    {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Add a binary column
     */
    public function binary(string $column): ColumnDefinition
    {
        return $this->addColumn('bytea', $column);
    }

    /**
     * Add an enum column
     */
    public function enum(string $column, array $values): ColumnDefinition
    {
        return $this->addColumn('varchar', $column, ['allowed' => $values]);
    }

    // ==================== Indexes ====================

    /**
     * Add a primary key
     */
    public function primary(string|array $columns): static
    {
        $this->indexes[] = [
            'type' => 'primary',
            'columns' => (array) $columns,
        ];

        return $this;
    }

    /**
     * Add a unique index
     */
    public function unique(string|array $columns, ?string $name = null): static
    {
        $columns = (array) $columns;
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => $columns,
            'name' => $name ?? $this->table . '_' . implode('_', $columns) . '_unique',
        ];

        return $this;
    }

    /**
     * Add an index
     */
    public function index(string|array $columns, ?string $name = null): static
    {
        $columns = (array) $columns;
        $this->indexes[] = [
            'type' => 'index',
            'columns' => $columns,
            'name' => $name ?? $this->table . '_' . implode('_', $columns) . '_index',
        ];

        return $this;
    }

    // ==================== Foreign Keys ====================

    /**
     * Add a foreign key constraint
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $definition = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $definition;

        return $definition;
    }

    /**
     * Add a foreign ID column with constraint
     */
    public function foreignId(string $column): ColumnDefinition
    {
        return $this->bigInteger($column)->unsigned();
    }

    // ==================== Commands ====================

    /**
     * Drop a column
     */
    public function dropColumn(string|array $columns): static
    {
        $this->commands[] = [
            'type' => 'dropColumn',
            'columns' => (array) $columns,
        ];

        return $this;
    }

    /**
     * Rename a column
     */
    public function renameColumn(string $from, string $to): static
    {
        $this->commands[] = [
            'type' => 'renameColumn',
            'from' => $from,
            'to' => $to,
        ];

        return $this;
    }

    /**
     * Drop an index
     */
    public function dropIndex(string $name): static
    {
        $this->commands[] = [
            'type' => 'dropIndex',
            'name' => $name,
        ];

        return $this;
    }

    /**
     * Drop a foreign key
     */
    public function dropForeign(string $name): static
    {
        $this->commands[] = [
            'type' => 'dropForeign',
            'name' => $name,
        ];

        return $this;
    }

    // ==================== Helpers ====================

    /**
     * Add a column definition
     */
    protected function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        $definition = new ColumnDefinition($name, $type, $parameters);
        $this->columns[] = $definition;

        return $definition;
    }

    /**
     * Get all column definitions
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get all index definitions
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Get all foreign key definitions
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Get all commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
