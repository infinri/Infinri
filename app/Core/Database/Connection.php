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
namespace App\Core\Database;

use App\Core\Contracts\Database\ConnectionInterface;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Database Connection
 * 
 * Wraps PDO and provides a fluent interface for database operations.
 */
class Connection implements ConnectionInterface
{
    protected ?PDO $pdo = null;
    protected array $config;
    protected string $name;
    protected int $transactionLevel = 0;
    protected array $queryLog = [];
    protected bool $loggingQueries = false;

    public function __construct(array $config, string $name = 'default')
    {
        $this->config = $config;
        $this->name = $name;
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    protected function connect(): void
    {
        $dsn = $this->buildDsn();
        
        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                $this->getOptions()
            );
            
            $this->configureConnection();
            
            $this->logQuery('Connected to database', []);
            
        } catch (PDOException $e) {
            $this->logConnectionError($e);
            throw new DatabaseException(
                "Failed to connect to database [{$this->name}]: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    protected function buildDsn(): string
    {
        $driver = $this->config['driver'] ?? 'pgsql';
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 5432;
        $database = $this->config['database'] ?? '';

        return "{$driver}:host={$host};port={$port};dbname={$database}";
    }

    protected function getOptions(): array
    {
        return array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], $this->config['options'] ?? []);
    }

    protected function configureConnection(): void
    {
        // Set timezone if specified
        if (isset($this->config['timezone'])) {
            $this->pdo->exec("SET TIME ZONE '{$this->config['timezone']}'");
        }

        // Set charset if specified
        if (isset($this->config['charset'])) {
            $this->pdo->exec("SET NAMES '{$this->config['charset']}'");
        }

        // Set search path for PostgreSQL
        if (isset($this->config['schema'])) {
            $this->pdo->exec("SET search_path TO {$this->config['schema']}");
        }
    }

    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $start = microtime(true);
        $bindings = $this->prepareBindings($bindings);
        
        try {
            $statement = $this->getPdo()->prepare($sql);
            $statement->execute($bindings);
            
            $this->logQuery($sql, $bindings, microtime(true) - $start);
            
            return $statement;
        } catch (PDOException $e) {
            $this->logQueryError($sql, $bindings, $e);
            throw new QueryException($sql, $bindings, $e);
        }
    }

    /**
     * Prepare bindings for PDO (handle booleans for PostgreSQL)
     */
    protected function prepareBindings(array $bindings): array
    {
        foreach ($bindings as $key => $value) {
            if (is_bool($value)) {
                // PostgreSQL needs 't'/'f' or true/false strings
                $bindings[$key] = $value ? 't' : 'f';
            }
        }
        return $bindings;
    }

    public function select(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $result = $this->query($sql, $bindings)->fetch();
        return $result === false ? null : $result;
    }

    public function insert(string $sql, array $bindings = []): int|string
    {
        $this->query($sql, $bindings);
        return $this->getPdo()->lastInsertId();
    }

    public function update(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    public function delete(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    public function statement(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    public function beginTransaction(): bool
    {
        if ($this->transactionLevel === 0) {
            $this->getPdo()->beginTransaction();
        } else {
            $this->getPdo()->exec("SAVEPOINT trans_{$this->transactionLevel}");
        }

        $this->transactionLevel++;
        
        return true;
    }

    public function commit(): bool
    {
        if ($this->transactionLevel === 1) {
            $this->getPdo()->commit();
        }

        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        
        return true;
    }

    public function rollBack(): bool
    {
        if ($this->transactionLevel === 1) {
            $this->getPdo()->rollBack();
        } else {
            $level = $this->transactionLevel - 1;
            $this->getPdo()->exec("ROLLBACK TO SAVEPOINT trans_{$level}");
        }

        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        
        return true;
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function transactionLevel(): int
    {
        return $this->transactionLevel;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDriverName(): string
    {
        return $this->config['driver'] ?? 'pgsql';
    }

    public function getDatabaseName(): string
    {
        return $this->config['database'] ?? '';
    }

    public function getTablePrefix(): string
    {
        return $this->config['prefix'] ?? '';
    }

    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Get a new query builder for this connection
     */
    public function newQuery(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Get a query builder for a table
     */
    public function table(string $table): QueryBuilder
    {
        return $this->newQuery()->table($table);
    }

    protected function logQuery(string $sql, array $bindings, float $time = 0): void
    {
        if ($this->loggingQueries) {
            $this->queryLog[] = [
                'query' => $sql,
                'bindings' => $bindings,
                'time' => $time,
            ];
        }

        // Record metrics (skip connection messages)
        if ($time > 0 && class_exists(\App\Core\Metrics\MetricsCollector::class)) {
            try {
                (new \App\Core\Metrics\MetricsCollector())->recordQuery($time);
            } catch (\Throwable) {
                // Don't let metrics recording break queries
            }
        }

        // Log slow queries (> 100ms)
        if ($time > 0.1 && function_exists('logger')) {
            logger()->warning('Slow query detected', [
                'query' => $sql,
                'bindings' => $bindings,
                'time_ms' => round($time * 1000, 2),
            ]);
        }

        // Log to query channel if available
        if ($time > 0 && function_exists('logger')) {
            $logger = logger();
            if (method_exists($logger, 'query')) {
                $logger->query($sql, $bindings, $time);
            }
        }
    }

    protected function logConnectionError(PDOException $e): void
    {
        if (function_exists('logger')) {
            logger()->error('Database connection failed', [
                'connection' => $this->name,
                'driver' => $this->getDriverName(),
                'database' => $this->getDatabaseName(),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }

    protected function logQueryError(string $sql, array $bindings, PDOException $e): void
    {
        if (function_exists('logger')) {
            logger()->error('Database query failed', [
                'query' => $sql,
                'bindings' => $bindings,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
