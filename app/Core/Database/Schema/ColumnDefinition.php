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
 * Column Definition
 *
 * Represents a table column with its properties.
 */
class ColumnDefinition
{
    protected string $name;
    protected string $type;
    protected array $parameters;
    protected bool $nullable = false;
    protected mixed $default = null;
    protected bool $hasDefault = false;
    protected bool $unsigned = false;
    protected bool $primary = false;
    protected bool $unique = false;
    protected ?string $comment = null;
    protected ?string $after = null;

    public function __construct(string $name, string $type, array $parameters = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->parameters = $parameters;
    }

    /**
     * Allow NULL values
     */
    public function nullable(bool $value = true): static
    {
        $this->nullable = $value;

        return $this;
    }

    /**
     * Set a default value
     */
    public function default(mixed $value): static
    {
        $this->default = $value;
        $this->hasDefault = true;

        return $this;
    }

    /**
     * Set column as unsigned (for integers)
     */
    public function unsigned(bool $value = true): static
    {
        $this->unsigned = $value;

        return $this;
    }

    /**
     * Set as primary key
     */
    public function primary(bool $value = true): static
    {
        $this->primary = $value;

        return $this;
    }

    /**
     * Add unique constraint
     */
    public function unique(bool $value = true): static
    {
        $this->unique = $value;

        return $this;
    }

    /**
     * Add a comment
     */
    public function comment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Place column after another column
     */
    public function after(string $column): static
    {
        $this->after = $column;

        return $this;
    }

    /**
     * Set default to current timestamp
     */
    public function useCurrent(): static
    {
        $this->default = 'CURRENT_TIMESTAMP';
        $this->hasDefault = true;

        return $this;
    }

    // ==================== Getters ====================

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Compile the column to SQL
     */
    public function toSql(): string
    {
        $sql = "\"{$this->name}\" {$this->compileType()}";

        if (! $this->nullable && ! $this->primary) {
            $sql .= ' NOT NULL';
        }

        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . $this->compileDefault();
        }

        if ($this->primary) {
            $sql .= ' PRIMARY KEY';
        }

        if ($this->unique && ! $this->primary) {
            $sql .= ' UNIQUE';
        }

        return $sql;
    }

    /**
     * Compile the column type
     */
    protected function compileType(): string
    {
        $type = $this->type;

        if (isset($this->parameters['length'])) {
            $type .= "({$this->parameters['length']})";
        }

        if (isset($this->parameters['precision'])) {
            $scale = $this->parameters['scale'] ?? 0;
            $type .= "({$this->parameters['precision']}, {$scale})";
        }

        return $type;
    }

    /**
     * Compile the default value
     */
    protected function compileDefault(): string
    {
        if ($this->default === 'CURRENT_TIMESTAMP') {
            return 'CURRENT_TIMESTAMP';
        }

        if ($this->default === null) {
            return 'NULL';
        }

        if (is_bool($this->default)) {
            return $this->default ? 'TRUE' : 'FALSE';
        }

        if (is_numeric($this->default)) {
            return (string) $this->default;
        }

        return "'" . addslashes((string) $this->default) . "'";
    }
}
