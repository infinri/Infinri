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
namespace App\Core\Database\Schema;

/**
 * Foreign Key Definition
 * 
 * Represents a foreign key constraint.
 */
class ForeignKeyDefinition
{
    protected string $column;
    protected string $referencedTable = '';
    protected string $referencedColumn = 'id';
    protected string $onDelete = 'CASCADE';
    protected string $onUpdate = 'CASCADE';
    protected ?string $name = null;

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    /**
     * Set the referenced table and column
     */
    public function references(string $column): static
    {
        $this->referencedColumn = $column;
        return $this;
    }

    /**
     * Set the referenced table
     */
    public function on(string $table): static
    {
        $this->referencedTable = $table;
        return $this;
    }

    /**
     * Set the ON DELETE action
     */
    public function onDelete(string $action): static
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    /**
     * Set the ON UPDATE action
     */
    public function onUpdate(string $action): static
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    /**
     * Set CASCADE on delete
     */
    public function cascadeOnDelete(): static
    {
        return $this->onDelete('CASCADE');
    }

    /**
     * Set NULL on delete
     */
    public function nullOnDelete(): static
    {
        return $this->onDelete('SET NULL');
    }

    /**
     * Set RESTRICT on delete
     */
    public function restrictOnDelete(): static
    {
        return $this->onDelete('RESTRICT');
    }

    /**
     * Set the constraint name
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    // ==================== Getters ====================

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }

    public function getReferencedColumn(): string
    {
        return $this->referencedColumn;
    }

    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    public function getName(): string
    {
        return $this->name ?? "fk_{$this->column}";
    }

    /**
     * Compile to SQL
     */
    public function toSql(string $table): string
    {
        return sprintf(
            'ALTER TABLE "%s" ADD CONSTRAINT "%s" FOREIGN KEY ("%s") REFERENCES "%s" ("%s") ON DELETE %s ON UPDATE %s',
            $table,
            $this->getName(),
            $this->column,
            $this->referencedTable,
            $this->referencedColumn,
            $this->onDelete,
            $this->onUpdate
        );
    }
}
