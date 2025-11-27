<?php

declare(strict_types=1);

namespace App\Core\Database\Grammar;

/**
 * PostgreSQL Grammar
 * 
 * PostgreSQL-specific SQL compilation.
 * Extends base Grammar (Open/Closed Principle).
 */
class PostgresGrammar extends Grammar
{
    /**
     * Compile an INSERT statement with RETURNING
     */
    public function compileInsert(string $table, array $columns): string
    {
        $placeholders = array_fill(0, count($columns), '?');

        return sprintf(
            'INSERT INTO "%s" (%s) VALUES (%s) RETURNING id',
            $table,
            implode(', ', array_map(fn($c) => "\"{$c}\"", $columns)),
            implode(', ', $placeholders)
        );
    }

    /**
     * Wrap table name with quotes
     */
    public function wrapTable(string $table): string
    {
        return '"' . $table . '"';
    }

    /**
     * Wrap column name with quotes
     */
    public function wrapColumn(string $column): string
    {
        if ($column === '*') {
            return $column;
        }

        if (str_contains($column, '.')) {
            return implode('.', array_map(fn($p) => '"' . $p . '"', explode('.', $column)));
        }

        return '"' . $column . '"';
    }
}
