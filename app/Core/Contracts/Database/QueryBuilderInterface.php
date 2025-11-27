<?php

declare(strict_types=1);

namespace App\Core\Contracts\Database;

/**
 * Query Builder Interface
 * 
 * Defines the contract for fluent query building.
 */
interface QueryBuilderInterface
{
    /**
     * Set the table for the query
     */
    public function table(string $table): static;

    /**
     * Add a column to select
     */
    public function select(string|array $columns = ['*']): static;

    /**
     * Add a raw SELECT expression
     */
    public function selectRaw(string $expression, array $bindings = []): static;

    /**
     * Add a WHERE clause
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static;

    /**
     * Add an OR WHERE clause
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static;

    /**
     * Add a WHERE IN clause
     */
    public function whereIn(string $column, array $values): static;

    /**
     * Add a WHERE NOT IN clause
     */
    public function whereNotIn(string $column, array $values): static;

    /**
     * Add a WHERE NULL clause
     */
    public function whereNull(string $column): static;

    /**
     * Add a WHERE NOT NULL clause
     */
    public function whereNotNull(string $column): static;

    /**
     * Add a WHERE BETWEEN clause
     */
    public function whereBetween(string $column, array $values): static;

    /**
     * Add a raw WHERE clause
     */
    public function whereRaw(string $sql, array $bindings = []): static;

    /**
     * Add a JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second): static;

    /**
     * Add a LEFT JOIN clause
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static;

    /**
     * Add a RIGHT JOIN clause
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static;

    /**
     * Add an ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'asc'): static;

    /**
     * Add a GROUP BY clause
     */
    public function groupBy(string|array $columns): static;

    /**
     * Add a HAVING clause
     */
    public function having(string $column, mixed $operator = null, mixed $value = null): static;

    /**
     * Add a LIMIT clause
     */
    public function limit(int $limit): static;

    /**
     * Add an OFFSET clause
     */
    public function offset(int $offset): static;

    /**
     * Execute the query and get all results
     */
    public function get(): array;

    /**
     * Execute the query and get the first result
     */
    public function first(): ?array;

    /**
     * Get the count of results
     */
    public function count(): int;

    /**
     * Check if any records exist
     */
    public function exists(): bool;

    /**
     * Insert a new record
     */
    public function insert(array $values): int|string;

    /**
     * Insert multiple records
     */
    public function insertMany(array $values): int;

    /**
     * Update records
     */
    public function update(array $values): int;

    /**
     * Delete records
     */
    public function delete(): int;

    /**
     * Increment a column value
     */
    public function increment(string $column, int $amount = 1): int;

    /**
     * Decrement a column value
     */
    public function decrement(string $column, int $amount = 1): int;

    /**
     * Get the raw SQL query
     */
    public function toSql(): string;

    /**
     * Get the query bindings
     */
    public function getBindings(): array;
}
