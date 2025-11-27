<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Contracts\Database\ConnectionInterface;

/**
 * Database Seeder
 * 
 * Base class for database seeders.
 */
abstract class Seeder
{
    protected ConnectionInterface $connection;

    /**
     * Run the seeder
     */
    abstract public function run(): void;

    /**
     * Set the database connection
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Get the database connection
     */
    protected function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Get a query builder for a table
     */
    protected function table(string $table): QueryBuilder
    {
        return $this->connection->table($table);
    }

    /**
     * Call another seeder
     */
    protected function call(string $seederClass): void
    {
        $seeder = new $seederClass();
        $seeder->setConnection($this->connection);
        $seeder->run();

        $this->logSeederRun($seederClass);
    }

    /**
     * Log seeder execution
     */
    protected function logSeederRun(string $seeder): void
    {
        if (function_exists('log_system')) {
            log_system("Seeder run: {$seeder}");
        }
    }
}
