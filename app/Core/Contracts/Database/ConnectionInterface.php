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
namespace App\Core\Contracts\Database;

use PDO;
use PDOStatement;

/**
 * Database Connection Interface
 * 
 * Defines the contract for database connections.
 */
interface ConnectionInterface
{
    /**
     * Get the underlying PDO connection
     */
    public function getPdo(): PDO;

    /**
     * Execute a raw SQL query
     */
    public function query(string $sql, array $bindings = []): PDOStatement;

    /**
     * Execute an INSERT statement and return the last insert ID
     */
    public function insert(string $sql, array $bindings = []): int|string;

    /**
     * Execute an UPDATE statement and return affected rows
     */
    public function update(string $sql, array $bindings = []): int;

    /**
     * Execute a DELETE statement and return affected rows
     */
    public function delete(string $sql, array $bindings = []): int;

    /**
     * Execute a statement and return affected rows
     */
    public function statement(string $sql, array $bindings = []): int;

    /**
     * Run a SELECT query and return all results
     */
    public function select(string $sql, array $bindings = []): array;

    /**
     * Run a SELECT query and return the first result
     */
    public function selectOne(string $sql, array $bindings = []): ?array;

    /**
     * Begin a database transaction
     */
    public function beginTransaction(): bool;

    /**
     * Commit the current transaction
     */
    public function commit(): bool;

    /**
     * Rollback the current transaction
     */
    public function rollBack(): bool;

    /**
     * Execute a callback within a transaction
     */
    public function transaction(callable $callback): mixed;

    /**
     * Get the current transaction level
     */
    public function transactionLevel(): int;

    /**
     * Disconnect from the database
     */
    public function disconnect(): void;

    /**
     * Reconnect to the database
     */
    public function reconnect(): void;

    /**
     * Get the connection name
     */
    public function getName(): string;

    /**
     * Get the database driver name
     */
    public function getDriverName(): string;

    /**
     * Get the database name
     */
    public function getDatabaseName(): string;

    /**
     * Get the table prefix
     */
    public function getTablePrefix(): string;
}
