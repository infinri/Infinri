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
