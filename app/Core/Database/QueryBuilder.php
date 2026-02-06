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
namespace App\Core\Database;

use App\Core\Contracts\Database\ConnectionInterface;
use App\Core\Contracts\Database\QueryBuilderInterface;
use App\Core\Database\Concerns\BuildsJoins;
use App\Core\Database\Concerns\BuildsWheres;
use App\Core\Database\Grammar\Grammar;

/**
 * Query Builder
 *
 * Provides a fluent interface for building SQL queries.
 * Uses traits for WHERE and JOIN building (Single Responsibility).
 * Uses Grammar for SQL compilation (Open/Closed).
 */
class QueryBuilder implements QueryBuilderInterface
{
    use BuildsWheres;
    use BuildsJoins;

    protected ConnectionInterface $connection;
    protected Grammar $grammar;
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $joins = [];
    protected array $orders = [];
    protected array $groups = [];
    protected array $havings = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;
    protected array $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
    ];

    public function __construct(ConnectionInterface $connection, ?Grammar $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?? new Grammar();
    }

    public function table(string $table): static
    {
        $this->table = $this->connection->getTablePrefix() . $table;

        return $this;
    }

    public function select(string|array $columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    public function selectRaw(string $expression, array $bindings = []): static
    {
        $this->columns[] = $expression;
        $this->bindings['select'] = array_merge($this->bindings['select'], $bindings);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
        ];

        return $this;
    }

    public function groupBy(string|array $columns): static
    {
        $this->groups = array_merge($this->groups, is_array($columns) ? $columns : [$columns]);

        return $this;
    }

    public function having(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = [
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
        ];

        $this->bindings['having'][] = $value;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limitValue = $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;

        return $this;
    }

    // ==================== Execution Methods ====================

    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings());
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    public function count(): int
    {
        $original = $this->columns;
        $this->columns = ['COUNT(*) as aggregate'];
        $result = $this->first();
        $this->columns = $original;

        return (int) ($result['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function insert(array $values): int|string
    {
        $columns = array_keys($values);
        $sql = $this->grammar->compileInsert($this->table, $columns);

        return $this->connection->insert($sql, array_values($values));
    }

    public function insertMany(array $values): int
    {
        if (empty($values)) {
            return 0;
        }

        $columns = array_keys($values[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($values), $placeholders));

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->table,
            implode(', ', $columns),
            $allPlaceholders
        );

        $bindings = [];
        foreach ($values as $row) {
            $bindings = array_merge($bindings, array_values($row));
        }

        return $this->connection->statement($sql, $bindings);
    }

    public function update(array $values): int
    {
        $columns = array_keys($values);
        $wheres = $this->grammar->compileWheres($this->wheres);
        $sql = $this->grammar->compileUpdate($this->table, $columns, $wheres);
        $bindings = array_merge(array_values($values), $this->bindings['where']);

        return $this->connection->update($sql, $bindings);
    }

    public function delete(): int
    {
        $wheres = $this->grammar->compileWheres($this->wheres);
        $sql = $this->grammar->compileDelete($this->table, $wheres);

        return $this->connection->delete($sql, $this->bindings['where']);
    }

    public function increment(string $column, int $amount = 1): int
    {
        return $this->update([$column => new Expression("{$column} + {$amount}")]);
    }

    public function decrement(string $column, int $amount = 1): int
    {
        return $this->update([$column => new Expression("{$column} - {$amount}")]);
    }

    // ==================== SQL Generation ====================

    public function toSql(): string
    {
        return $this->grammar->compileSelect([
            'columns' => $this->columns,
            'table' => $this->table,
            'joins' => $this->joins,
            'wheres' => $this->wheres,
            'groups' => $this->groups,
            'havings' => $this->havings,
            'orders' => $this->orders,
            'limit' => $this->limitValue,
            'offset' => $this->offsetValue,
        ]);
    }

    public function getBindings(): array
    {
        return array_merge(
            $this->bindings['select'],
            $this->bindings['join'],
            $this->bindings['where'],
            $this->bindings['having']
        );
    }

    // ==================== Utility ====================

    public function clone(): static
    {
        return clone $this;
    }

    public function reset(): static
    {
        $this->columns = ['*'];
        $this->wheres = [];
        $this->joins = [];
        $this->orders = [];
        $this->groups = [];
        $this->havings = [];
        $this->limitValue = null;
        $this->offsetValue = null;
        $this->bindings = [
            'select' => [],
            'join' => [],
            'where' => [],
            'having' => [],
        ];

        return $this;
    }
}
