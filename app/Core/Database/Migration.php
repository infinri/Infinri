<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Database\Schema\SchemaBuilder;
use App\Core\Contracts\Database\ConnectionInterface;

/**
 * Migration Base Class
 * 
 * Base class for database migrations.
 */
abstract class Migration
{
    protected ConnectionInterface $connection;
    protected SchemaBuilder $schema;

    /**
     * Run the migration
     */
    abstract public function up(): void;

    /**
     * Reverse the migration
     */
    abstract public function down(): void;

    /**
     * Set the database connection
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
        $this->schema = new SchemaBuilder($connection);
    }

    /**
     * Get the schema builder
     */
    protected function schema(): SchemaBuilder
    {
        return $this->schema;
    }

    /**
     * Get the connection
     */
    protected function connection(): ConnectionInterface
    {
        return $this->connection;
    }
}
