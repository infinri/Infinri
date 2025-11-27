<?php

declare(strict_types=1);

namespace App\Core\Database\Concerns;

/**
 * Builds Join Clauses
 * 
 * Provides JOIN clause building methods for the query builder.
 * Follows Single Responsibility Principle - only handles JOIN logic.
 */
trait BuildsJoins
{
    /**
     * Add a JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $this->connection->getTablePrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $this->connection->getTablePrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add a RIGHT JOIN clause
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $this->connection->getTablePrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }
}
