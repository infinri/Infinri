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
namespace App\Core\Redis;

use Redis;
use RedisException;

/**
 * Redis Connection Manager
 *
 * Manages Redis connections with support for persistent connections,
 * connection pooling, and automatic reconnection.
 */
class RedisManager
{
    /**
     * Active Redis connections
     *
     * @var array<string, Redis>
     */
    protected array $connections = [];

    /**
     * Connection configurations
     */
    protected array $config;

    /**
     * Default connection name
     */
    protected string $default = 'default';

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->default = $config['default'] ?? 'default';
    }

    /**
     * Get a Redis connection
     */
    public function connection(?string $name = null): Redis
    {
        $name ??= $this->default;

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->connect($name);
        }

        // Verify connection is still alive
        if (! $this->isConnected($this->connections[$name])) {
            $this->connections[$name] = $this->connect($name);
        }

        return $this->connections[$name];
    }

    /**
     * Create a new Redis connection
     */
    protected function connect(string $name): Redis
    {
        $config = $this->getConnectionConfig($name);

        $redis = new Redis();

        try {
            // Use persistent connections for better performance
            if ($config['persistent'] ?? true) {
                $connected = $redis->pconnect(
                    $config['host'],
                    $config['port'],
                    $config['timeout'] ?? 2.0,
                    $config['persistent_id'] ?? $name
                );
            } else {
                $connected = $redis->connect(
                    $config['host'],
                    $config['port'],
                    $config['timeout'] ?? 2.0
                );
            }

            if (! $connected) {
                throw new RedisConnectionException("Failed to connect to Redis [{$name}]");
            }

            // Authenticate if password is set
            if (! empty($config['password'])) {
                if (! $redis->auth($config['password'])) {
                    throw new RedisConnectionException("Redis authentication failed [{$name}]");
                }
            }

            // Select database
            if (($config['database'] ?? 0) !== 0) {
                $redis->select((int) $config['database']);
            }

            // Set prefix for keys
            if (! empty($config['prefix'])) {
                $redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
            }

            // Set serializer for complex data types
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        } catch (RedisException $e) {
            throw new RedisConnectionException(
                "Redis connection error [{$name}]: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return $redis;
    }

    /**
     * Get connection configuration
     */
    protected function getConnectionConfig(string $name): array
    {
        $connections = $this->config['connections'] ?? [];

        if (isset($connections[$name])) {
            return $connections[$name];
        }

        // Fall back to default config from environment
        return [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('REDIS_DB', 0),
            'timeout' => 2.0,
            'persistent' => true,
            'persistent_id' => $name,
            'prefix' => env('REDIS_PREFIX', 'infinri:'),
        ];
    }

    /**
     * Check if a connection is still alive
     */
    protected function isConnected(Redis $redis): bool
    {
        try {
            $redis->ping();

            return true;
        } catch (RedisException $e) {
            logger()->debug('Redis connection check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Disconnect a specific connection
     */
    public function disconnect(?string $name = null): void
    {
        $name ??= $this->default;

        if (isset($this->connections[$name])) {
            try {
                $this->connections[$name]->close();
            } catch (RedisException $e) {
                logger()->debug('Redis disconnect failed', ['connection' => $name, 'error' => $e->getMessage()]);
            }
            unset($this->connections[$name]);
        }
    }

    /**
     * Disconnect all connections
     */
    public function disconnectAll(): void
    {
        foreach (array_keys($this->connections) as $name) {
            $this->disconnect($name);
        }
    }

    /**
     * Get all active connection names
     */
    public function getActiveConnections(): array
    {
        return array_keys($this->connections);
    }

    /**
     * Destructor - clean up connections
     */
    public function __destruct()
    {
        // Persistent connections don't need to be closed
        // but we clear the array
        $this->connections = [];
    }
}
