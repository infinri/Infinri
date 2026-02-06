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

use App\Core\Application;
use App\Core\Contracts\Database\ConnectionInterface;

/**
 * Database Manager
 *
 * Manages multiple database connections and provides a facade for database operations.
 */
class DatabaseManager
{
    protected Application $app;

    /** @var array<string, ConnectionInterface> */
    protected array $connections = [];

    protected string $defaultConnection = 'default';

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a database connection instance
     */
    public function connection(?string $name = null): ConnectionInterface
    {
        $name ??= $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Create a new database connection
     */
    protected function makeConnection(string $name): ConnectionInterface
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new DatabaseException("Database connection [{$name}] not configured.");
        }

        return new Connection($config, $name);
    }

    /**
     * Get the configuration for a connection
     */
    protected function getConfig(string $name): ?array
    {
        return config("database.connections.{$name}");
    }

    /**
     * Get the default connection name
     */
    public function getDefaultConnection(): string
    {
        return config('database.default', $this->defaultConnection);
    }

    /**
     * Set the default connection name
     */
    public function setDefaultConnection(string $name): void
    {
        $this->defaultConnection = $name;
    }

    /**
     * Disconnect from all connections
     */
    public function disconnect(?string $name = null): void
    {
        if ($name === null) {
            foreach ($this->connections as $connection) {
                $connection->disconnect();
            }
            $this->connections = [];
        } elseif (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
            unset($this->connections[$name]);
        }
    }

    /**
     * Reconnect to a connection
     */
    public function reconnect(?string $name = null): ConnectionInterface
    {
        $name ??= $this->getDefaultConnection();

        $this->disconnect($name);

        return $this->connection($name);
    }

    /**
     * Get a query builder for a table
     */
    public function table(string $table, ?string $connection = null): QueryBuilder
    {
        return $this->connection($connection)->table($table);
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(?string $connection = null): bool
    {
        return $this->connection($connection)->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(?string $connection = null): bool
    {
        return $this->connection($connection)->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollBack(?string $connection = null): bool
    {
        return $this->connection($connection)->rollBack();
    }

    /**
     * Execute a callback within a transaction
     */
    public function transaction(callable $callback, ?string $connection = null): mixed
    {
        return $this->connection($connection)->transaction($callback);
    }

    /**
     * Get all registered connections
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Check if a connection exists
     */
    public function hasConnection(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Dynamically pass methods to the default connection
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->$method(...$parameters);
    }
}
