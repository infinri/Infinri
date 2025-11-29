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
namespace App\Core\Database\Concerns;

/**
 * Builds Where Clauses
 * 
 * Provides WHERE clause building methods for the query builder.
 * Follows Single Responsibility Principle - only handles WHERE logic.
 */
trait BuildsWheres
{
    /**
     * Add a WHERE clause
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        // Handle 2-argument form: where('column', 'value')
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => 'AND',
        ];

        $this->bindings['where'][] = $value;

        return $this;
    }

    /**
     * Add an OR WHERE clause
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => 'OR',
        ];

        $this->bindings['where'][] = $value;

        return $this;
    }

    /**
     * Add a WHERE IN clause
     */
    public function whereIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => false,
        ];

        $this->bindings['where'] = array_merge($this->bindings['where'], $values);

        return $this;
    }

    /**
     * Add a WHERE NOT IN clause
     */
    public function whereNotIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => true,
        ];

        $this->bindings['where'] = array_merge($this->bindings['where'], $values);

        return $this;
    }

    /**
     * Add a WHERE NULL clause
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
            'not' => false,
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
            'not' => true,
        ];

        return $this;
    }

    /**
     * Add a WHERE BETWEEN clause
     */
    public function whereBetween(string $column, array $values): static
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
        ];

        $this->bindings['where'] = array_merge($this->bindings['where'], $values);

        return $this;
    }

    /**
     * Add a raw WHERE clause
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'AND',
        ];

        $this->bindings['where'] = array_merge($this->bindings['where'], $bindings);

        return $this;
    }
}
