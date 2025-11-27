<?php

declare(strict_types=1);

namespace App\Core\Database\Schema;

use App\Core\Contracts\Database\ConnectionInterface;
use Closure;

/**
 * Schema Builder
 * 
 * Provides methods for creating and modifying database tables.
 */
class SchemaBuilder
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create a new table
     */
    public function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = $this->compileCreate($blueprint);
        $this->connection->statement($sql);

        // Create indexes
        foreach ($blueprint->getIndexes() as $index) {
            if ($index['type'] !== 'primary') {
                $this->connection->statement($this->compileIndex($table, $index));
            }
        }

        // Create foreign keys
        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            $this->connection->statement($foreignKey->toSql($table));
        }

        $this->logSchemaChange('Created table', $table);
    }

    /**
     * Modify an existing table
     */
    public function table(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        // Add new columns
        foreach ($blueprint->getColumns() as $column) {
            $sql = sprintf(
                'ALTER TABLE "%s" ADD COLUMN %s',
                $table,
                $column->toSql()
            );
            $this->connection->statement($sql);
        }

        // Execute commands
        foreach ($blueprint->getCommands() as $command) {
            $this->executeCommand($table, $command);
        }

        // Create indexes
        foreach ($blueprint->getIndexes() as $index) {
            $this->connection->statement($this->compileIndex($table, $index));
        }

        // Create foreign keys
        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            $this->connection->statement($foreignKey->toSql($table));
        }

        $this->logSchemaChange('Modified table', $table);
    }

    /**
     * Drop a table
     */
    public function drop(string $table): void
    {
        $sql = sprintf('DROP TABLE IF EXISTS "%s"', $table);
        $this->connection->statement($sql);
        
        $this->logSchemaChange('Dropped table', $table);
    }

    /**
     * Drop a table if it exists
     */
    public function dropIfExists(string $table): void
    {
        $this->drop($table);
    }

    /**
     * Rename a table
     */
    public function rename(string $from, string $to): void
    {
        $sql = sprintf('ALTER TABLE "%s" RENAME TO "%s"', $from, $to);
        $this->connection->statement($sql);
        
        $this->logSchemaChange('Renamed table', "{$from} -> {$to}");
    }

    /**
     * Check if a table exists
     */
    public function hasTable(string $table): bool
    {
        $result = $this->connection->selectOne(
            "SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            ) as exists",
            [$table]
        );

        return (bool) ($result['exists'] ?? false);
    }

    /**
     * Check if a column exists
     */
    public function hasColumn(string $table, string $column): bool
    {
        $result = $this->connection->selectOne(
            "SELECT EXISTS (
                SELECT FROM information_schema.columns 
                WHERE table_schema = 'public' 
                AND table_name = ? 
                AND column_name = ?
            ) as exists",
            [$table, $column]
        );

        return (bool) ($result['exists'] ?? false);
    }

    /**
     * Get all tables
     */
    public function getTables(): array
    {
        $results = $this->connection->select(
            "SELECT table_name FROM information_schema.tables 
             WHERE table_schema = 'public' 
             AND table_type = 'BASE TABLE'
             ORDER BY table_name"
        );

        return array_column($results, 'table_name');
    }

    /**
     * Get all columns for a table
     */
    public function getColumns(string $table): array
    {
        return $this->connection->select(
            "SELECT column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ?
             ORDER BY ordinal_position",
            [$table]
        );
    }

    /**
     * Compile a CREATE TABLE statement
     */
    protected function compileCreate(Blueprint $blueprint): string
    {
        $columns = [];
        
        foreach ($blueprint->getColumns() as $column) {
            $columns[] = $column->toSql();
        }

        return sprintf(
            'CREATE TABLE "%s" (%s)',
            $blueprint->getTable(),
            implode(', ', $columns)
        );
    }

    /**
     * Compile an index creation statement
     */
    protected function compileIndex(string $table, array $index): string
    {
        $columns = implode('", "', $index['columns']);

        if ($index['type'] === 'unique') {
            return sprintf(
                'CREATE UNIQUE INDEX "%s" ON "%s" ("%s")',
                $index['name'],
                $table,
                $columns
            );
        }

        return sprintf(
            'CREATE INDEX "%s" ON "%s" ("%s")',
            $index['name'],
            $table,
            $columns
        );
    }

    /**
     * Execute a schema command
     */
    protected function executeCommand(string $table, array $command): void
    {
        switch ($command['type']) {
            case 'dropColumn':
                foreach ($command['columns'] as $column) {
                    $this->connection->statement(
                        sprintf('ALTER TABLE "%s" DROP COLUMN "%s"', $table, $column)
                    );
                }
                break;

            case 'renameColumn':
                $this->connection->statement(
                    sprintf(
                        'ALTER TABLE "%s" RENAME COLUMN "%s" TO "%s"',
                        $table,
                        $command['from'],
                        $command['to']
                    )
                );
                break;

            case 'dropIndex':
                $this->connection->statement(
                    sprintf('DROP INDEX IF EXISTS "%s"', $command['name'])
                );
                break;

            case 'dropForeign':
                $this->connection->statement(
                    sprintf('ALTER TABLE "%s" DROP CONSTRAINT "%s"', $table, $command['name'])
                );
                break;
        }
    }

    /**
     * Log schema changes
     */
    protected function logSchemaChange(string $action, string $details): void
    {
        if (function_exists('log_system')) {
            log_system("Schema: {$action}", ['details' => $details]);
        }
    }
}
