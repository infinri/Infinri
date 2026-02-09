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
use App\Core\Database\Schema\SchemaBuilder;

/**
 * Migrator
 *
 * Handles running and rolling back database migrations.
 */
class Migrator
{
    protected ConnectionInterface $connection;
    protected SchemaBuilder $schema;
    protected string $migrationsPath;
    protected string $migrationsTable = 'migrations';

    public function __construct(ConnectionInterface $connection, string $migrationsPath)
    {
        $this->connection = $connection;
        $this->schema = new SchemaBuilder($connection);
        $this->migrationsPath = rtrim($migrationsPath, '/');
    }

    /**
     * Run all pending migrations
     */
    public function migrate(): array
    {
        $this->ensureMigrationsTableExists();

        $pending = $this->getPendingMigrations();
        $ran = [];

        foreach ($pending as $migration) {
            $this->runMigration($migration);
            $ran[] = $migration;
        }

        return $ran;
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback(int $steps = 1): array
    {
        $this->ensureMigrationsTableExists();

        $migrations = $this->getMigrationsToRollback($steps);
        $rolledBack = [];

        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
            $rolledBack[] = $migration;
        }

        return $rolledBack;
    }

    /**
     * Reset all migrations
     */
    public function reset(): array
    {
        $this->ensureMigrationsTableExists();

        $migrations = $this->getRanMigrations();
        $rolledBack = [];

        foreach (array_reverse($migrations) as $migration) {
            $this->rollbackMigration($migration);
            $rolledBack[] = $migration;
        }

        return $rolledBack;
    }

    /**
     * Reset and re-run all migrations
     */
    public function refresh(): array
    {
        $this->reset();

        return $this->migrate();
    }

    /**
     * Get the status of all migrations
     */
    public function status(): array
    {
        $this->ensureMigrationsTableExists();

        $ran = $this->getRanMigrations();
        $all = $this->getAllMigrations();
        $status = [];

        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration,
                'ran' => in_array($migration, $ran, true),
            ];
        }

        return $status;
    }

    /**
     * Run a single migration
     */
    public function runMigration(string $migration): void
    {
        $instance = $this->resolve($migration);
        $instance->setConnection($this->connection);

        $this->connection->transaction(function () use ($instance, $migration): void {
            $instance->up();
            $this->log($migration);
        });

        $this->logMigrationRun('up', $migration);
    }

    /**
     * Rollback a single migration
     */
    protected function rollbackMigration(string $migration): void
    {
        $instance = $this->resolve($migration);
        $instance->setConnection($this->connection);

        $this->connection->transaction(function () use ($instance, $migration): void {
            $instance->down();
            $this->unlog($migration);
        });

        $this->logMigrationRun('down', $migration);
    }

    /**
     * Resolve a migration instance
     */
    protected function resolve(string $migration): Migration
    {
        $file = $this->migrationsPath . '/' . $migration . '.php';

        if (! file_exists($file)) {
            throw new DatabaseException("Migration file not found: {$file}");
        }

        require_once $file;

        // Convert filename to class name
        $className = $this->migrationToClassName($migration);

        if (! class_exists($className)) {
            throw new DatabaseException("Migration class not found: {$className}");
        }

        return new $className();
    }

    /**
     * Convert migration filename to class name
     */
    protected function migrationToClassName(string $migration): string
    {
        // Remove date prefix (e.g., 2025_11_27_000000_)
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration);

        // Convert to StudlyCase
        $words = explode('_', $name);

        return implode('', array_map('ucfirst', $words));
    }

    /**
     * Get all migration files
     */
    protected function getAllMigrations(): array
    {
        if (! is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        $migrations = [];

        foreach ($files as $file) {
            $migrations[] = pathinfo($file, PATHINFO_FILENAME);
        }

        sort($migrations);

        return $migrations;
    }

    /**
     * Get all ran migrations
     */
    protected function getRanMigrations(): array
    {
        $results = $this->connection->select(
            "SELECT migration FROM \"{$this->migrationsTable}\" ORDER BY batch, migration"
        );

        return array_column($results, 'migration');
    }

    /**
     * Get pending migrations
     */
    protected function getPendingMigrations(): array
    {
        $all = $this->getAllMigrations();
        $ran = $this->getRanMigrations();

        return array_diff($all, $ran);
    }

    /**
     * Get migrations to rollback
     */
    protected function getMigrationsToRollback(int $steps): array
    {
        $lastBatch = $this->getLastBatchNumber();
        $targetBatch = max(0, $lastBatch - $steps + 1);

        $results = $this->connection->select(
            "SELECT migration FROM \"{$this->migrationsTable}\" 
             WHERE batch >= ? 
             ORDER BY batch DESC, migration DESC",
            [$targetBatch]
        );

        return array_column($results, 'migration');
    }

    /**
     * Log a migration as ran
     */
    protected function log(string $migration): void
    {
        $batch = $this->getNextBatchNumber();

        $this->connection->insert(
            "INSERT INTO \"{$this->migrationsTable}\" (migration, batch) VALUES (?, ?)",
            [$migration, $batch]
        );
    }

    /**
     * Remove a migration from the log
     */
    protected function unlog(string $migration): void
    {
        $this->connection->delete(
            "DELETE FROM \"{$this->migrationsTable}\" WHERE migration = ?",
            [$migration]
        );
    }

    /**
     * Get the last batch number
     */
    protected function getLastBatchNumber(): int
    {
        $result = $this->connection->selectOne(
            "SELECT MAX(batch) as batch FROM \"{$this->migrationsTable}\""
        );

        return (int) ($result['batch'] ?? 0);
    }

    /**
     * Get the next batch number
     */
    protected function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Ensure the migrations table exists
     */
    protected function ensureMigrationsTableExists(): void
    {
        if ($this->schema->hasTable($this->migrationsTable)) {
            return;
        }

        $this->schema->create($this->migrationsTable, function ($table): void {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Log migration execution
     */
    protected function logMigrationRun(string $direction, string $migration): void
    {
        if (function_exists('log_system')) {
            log_system("Migration {$direction}: {$migration}");
        }
    }
}
