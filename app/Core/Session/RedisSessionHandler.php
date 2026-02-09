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
namespace App\Core\Session;

use App\Core\Redis\Concerns\UsesRedis;
use App\Core\Redis\RedisManager;
use App\Core\Support\Str;
use Redis;
use RedisException;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Redis Session Handler
 *
 * Custom session handler using Redis for distributed session storage.
 * Supports session locking, automatic expiration, and clustering.
 */
class RedisSessionHandler implements
    SessionHandlerInterface,
    SessionIdInterface,
    SessionUpdateTimestampHandlerInterface
{
    use UsesRedis;

    protected RedisManager $redis;

    protected string $connection;

    protected string $prefix;

    /**
     * Session TTL in seconds
     */
    protected int $ttl;

    /**
     * Lock timeout in seconds
     */
    protected int $lockTimeout;

    /**
     * Current session lock key
     */
    protected ?string $lockKey = null;

    public function __construct(
        RedisManager $redis,
        string $connection = 'session',
        string $prefix = 'session:',
        int $ttl = 7200,
        int $lockTimeout = 30
    ) {
        $this->redis = $redis;
        $this->connection = $connection;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
        $this->lockTimeout = $lockTimeout;
    }

    /**
     * Get the lock key for a session
     */
    protected function lockKeyFor(string $sessionId): string
    {
        return $this->prefix . 'lock:' . $sessionId;
    }

    /**
     * Open the session
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Close the session
     */
    public function close(): bool
    {
        // Release session lock
        $this->releaseLock();

        return true;
    }

    /**
     * Read session data
     */
    public function read(string $id): string|false
    {
        // Acquire lock for session
        $this->acquireLock($id);

        try {
            $data = $this->redis()->get($this->key($id));

            return $data === false ? '' : $data;
        } catch (RedisException $e) {
            $this->logRedisError('Session read', $e, ['session_id' => $id]);

            return '';
        }
    }

    /**
     * Write session data
     */
    public function write(string $id, string $data): bool
    {
        try {
            return $this->redis()->setex($this->key($id), $this->ttl, $data);
        } catch (RedisException $e) {
            $this->logRedisError('Session write', $e, ['session_id' => $id], 'error');

            return false;
        }
    }

    /**
     * Destroy a session
     */
    public function destroy(string $id): bool
    {
        // Release lock first
        $this->releaseLock();

        try {
            $this->redis()->del($this->key($id));

            return true;
        } catch (RedisException $e) {
            $this->logRedisError('Session destroy', $e, ['session_id' => $id], 'error');

            return false;
        }
    }

    /**
     * Garbage collection - Redis handles this via TTL
     */
    public function gc(int $max_lifetime): int|false
    {
        // Redis automatically expires keys, no action needed
        return 0;
    }

    /**
     * Create a new session ID
     */
    public function create_sid(): string
    {
        return Str::randomHex(32);
    }

    /**
     * Validate session ID format
     */
    public function validateId(string $id): bool
    {
        // Accept standard session ID format (hex string, 64 chars)
        return (bool) preg_match('/^[a-f0-9]{64}$/', $id);
    }

    /**
     * Update the timestamp of a session
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        try {
            // Just update the TTL
            return $this->redis()->expire($this->key($id), $this->ttl);
        } catch (RedisException $e) {
            $this->logRedisError('Session timestamp update', $e, ['session_id' => $id]);

            return false;
        }
    }

    /**
     * Acquire a lock for the session
     */
    protected function acquireLock(string $sessionId): bool
    {
        $this->lockKey = $this->lockKeyFor($sessionId);
        $attempts = 0;
        $maxAttempts = $this->lockTimeout * 10; // 100ms intervals

        while ($attempts < $maxAttempts) {
            try {
                // Try to acquire lock with NX (only if not exists) and EX (expiration)
                $acquired = $this->redis()->set(
                    $this->lockKey,
                    getmypid() . ':' . time(),
                    ['NX', 'EX' => $this->lockTimeout]
                );

                if ($acquired) {
                    return true;
                }
            } catch (RedisException $e) {
                $this->logRedisError('Session lock attempt', $e, [], 'debug');
            }

            // Wait 100ms before retrying
            usleep(100000);
            $attempts++;
        }

        // Failed to acquire lock - proceed anyway but log warning
        // In production, you might want to throw an exception instead
        return false;
    }

    /**
     * Release the session lock
     */
    protected function releaseLock(): void
    {
        if ($this->lockKey === null) {
            return;
        }

        try {
            // Only delete if we still own the lock
            $lockValue = $this->redis()->get($this->lockKey);
            if ($lockValue && str_starts_with($lockValue, getmypid() . ':')) {
                $this->redis()->del($this->lockKey);
            }
        } catch (RedisException $e) {
            $this->logRedisError('Session lock release', $e, [], 'debug');
        }

        $this->lockKey = null;
    }

    /**
     * Register this handler as the session handler
     */
    public function register(): void
    {
        session_set_save_handler($this, true);
    }

    /**
     * Get session TTL
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Set session TTL
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Check if a session exists
     */
    public function exists(string $id): bool
    {
        try {
            return $this->redis()->exists($this->key($id)) > 0;
        } catch (RedisException $e) {
            $this->logRedisError('Session exists check', $e, ['session_id' => $id]);

            return false;
        }
    }

    /**
     * Get all active session IDs (for admin purposes)
     */
    public function getAllSessionIds(): array
    {
        try {
            $lockPrefix = $this->prefix . 'lock:';
            $ids = [];
            $iterator = null;

            while (($keys = $this->redis()->scan($iterator, $this->prefix . '*', 100)) !== false) {
                foreach ($keys as $key) {
                    if (! str_starts_with($key, $lockPrefix)) {
                        $ids[] = substr($key, strlen($this->prefix));
                    }
                }
            }

            return $ids;
        } catch (RedisException $e) {
            $this->logRedisError('Get session IDs', $e);

            return [];
        }
    }

    /**
     * Get active session count
     */
    public function getActiveSessionCount(): int
    {
        return count($this->getAllSessionIds());
    }

    /**
     * Destroy all sessions (admin/emergency function)
     */
    public function destroyAll(): int
    {
        try {
            $redis = $this->redis();
            $deleted = 0;
            $iterator = null;

            while (($keys = $redis->scan($iterator, $this->prefix . '*', 100)) !== false) {
                if (! empty($keys)) {
                    $deleted += $redis->del(...$keys);
                }
            }

            return $deleted;
        } catch (RedisException $e) {
            $this->logRedisError('Destroy all sessions', $e, [], 'error');

            return 0;
        }
    }
}
