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
namespace App\Core\Redis\Concerns;

use Redis;
use RedisException;

/**
 * Shared Redis access helpers for classes that use RedisManager
 *
 * Provides common redis(), key(), and error-logging methods
 * used by RedisStore, RedisSessionHandler, and similar classes.
 *
 * @property \App\Core\Redis\RedisManager $redis
 * @property string $connection
 * @property string $prefix
 */
trait UsesRedis
{
    /**
     * Get the Redis connection instance
     */
    protected function redis(): Redis
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Get the prefixed key
     */
    protected function key(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Log a Redis operation error consistently
     *
     * @param string $operation Description of the failed operation (e.g. 'Cache get', 'Session write')
     * @param RedisException $e The caught exception
     * @param array $context Additional context (key, session_id, etc.)
     * @param string $level Log level: 'warning', 'error', or 'debug'
     */
    protected function logRedisError(string $operation, RedisException $e, array $context = [], string $level = 'warning'): void
    {
        safe_log($level, "{$operation} failed", array_merge($context, [
            'error' => $e->getMessage(),
        ]));
    }
}
