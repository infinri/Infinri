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
namespace App\Core\Database\Grammar;

/**
 * SQL Grammar
 *
 * Compiles query builder components into SQL.
 * Follows Open/Closed Principle - extend for different database dialects.
 */
class Grammar
{
    /**
     * Compile a SELECT query
     */
    public function compileSelect(array $query): string
    {
        $sql = 'SELECT ' . $this->compileColumns($query['columns']);
        $sql .= ' FROM ' . $query['table'];
        $sql .= $this->compileJoins($query['joins']);
        $sql .= $this->compileWheres($query['wheres']);
        $sql .= $this->compileGroups($query['groups']);
        $sql .= $this->compileHavings($query['havings']);
        $sql .= $this->compileOrders($query['orders']);
        $sql .= $this->compileLimit($query['limit']);
        $sql .= $this->compileOffset($query['offset']);

        return $sql;
    }

    /**
     * Compile columns
     */
    protected function compileColumns(array $columns): string
    {
        return implode(', ', $columns);
    }

    /**
     * Compile joins
     */
    protected function compileJoins(array $joins): string
    {
        if ($joins === []) {
            return '';
        }

        $sql = '';
        foreach ($joins as $join) {
            $sql .= sprintf(
                ' %s JOIN %s ON %s %s %s',
                $join['type'],
                $join['table'],
                $join['first'],
                $join['operator'],
                $join['second']
            );
        }

        return $sql;
    }

    /**
     * Compile where clauses
     */
    public function compileWheres(array $wheres): string
    {
        if ($wheres === []) {
            return '';
        }

        $sql = ' WHERE ';
        $clauses = [];

        foreach ($wheres as $i => $where) {
            $clause = $i > 0 ? $where['boolean'] . ' ' : '';

            switch ($where['type']) {
                case 'basic':
                    $clause .= "{$where['column']} {$where['operator']} ?";
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $not = $where['not'] ? 'NOT ' : '';
                    $clause .= "{$where['column']} {$not}IN ({$placeholders})";
                    break;
                case 'null':
                    $not = $where['not'] ? 'NOT ' : '';
                    $clause .= "{$where['column']} IS {$not}NULL";
                    break;
                case 'between':
                    $clause .= "{$where['column']} BETWEEN ? AND ?";
                    break;
                case 'raw':
                    $clause .= $where['sql'];
                    break;
            }

            $clauses[] = $clause;
        }

        return $sql . implode(' ', $clauses);
    }

    /**
     * Compile groups
     */
    protected function compileGroups(array $groups): string
    {
        if ($groups === []) {
            return '';
        }

        return ' GROUP BY ' . implode(', ', $groups);
    }

    /**
     * Compile havings
     */
    protected function compileHavings(array $havings): string
    {
        if ($havings === []) {
            return '';
        }

        $clauses = [];
        foreach ($havings as $having) {
            $clauses[] = "{$having['column']} {$having['operator']} ?";
        }

        return ' HAVING ' . implode(' AND ', $clauses);
    }

    /**
     * Compile orders
     */
    protected function compileOrders(array $orders): string
    {
        if ($orders === []) {
            return '';
        }

        $clauses = [];
        foreach ($orders as $order) {
            $clauses[] = "{$order['column']} {$order['direction']}";
        }

        return ' ORDER BY ' . implode(', ', $clauses);
    }

    /**
     * Compile limit
     */
    protected function compileLimit(?int $limit): string
    {
        if ($limit === null) {
            return '';
        }

        return ' LIMIT ' . $limit;
    }

    /**
     * Compile offset
     */
    protected function compileOffset(?int $offset): string
    {
        if ($offset === null) {
            return '';
        }

        return ' OFFSET ' . $offset;
    }

    /**
     * Compile an INSERT statement
     */
    public function compileInsert(string $table, array $columns): string
    {
        $placeholders = array_fill(0, count($columns), '?');

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
    }

    /**
     * Compile an UPDATE statement
     */
    public function compileUpdate(string $table, array $columns, string $wheres): string
    {
        $sets = array_map(fn ($col) => "{$col} = ?", $columns);

        return sprintf(
            'UPDATE %s SET %s%s',
            $table,
            implode(', ', $sets),
            $wheres
        );
    }

    /**
     * Compile a DELETE statement
     */
    public function compileDelete(string $table, string $wheres): string
    {
        return sprintf('DELETE FROM %s%s', $table, $wheres);
    }
}
