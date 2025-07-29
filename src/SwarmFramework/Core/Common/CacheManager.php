<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;

/**
 * Cache Manager - Centralized caching utilities
 * 
 * Eliminates redundant caching patterns and provides
 * consistent cache operations with TTL management.
 */
#[Injectable(dependencies: ['RedisOperationWrapper', 'LoggerInterface'])]
final class CacheManager
{
    use LoggerTrait;
    
    private RedisOperationWrapper $redisWrapper;
    private array $config;

    public function __construct(
        RedisOperationWrapper $redis,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->redisWrapper = $redis;
        $this->config = ConfigManager::getConfig('CacheManager', $config);
    }

    /**
     * Get cached value with automatic JSON decoding
     */
    public function get(string $key, bool $jsonDecode = true): mixed
    {
        $timer = PerformanceTimer::start("cache_get_{$key}");
        
        try {
            $value = $this->redisWrapper->get($key, $jsonDecode);
            $duration = PerformanceTimer::stop("cache_get_{$key}");
            
            $hit = $value !== false;
            
            $this->logger->debug('Cache get operation', [
                'key' => $key,
                'hit' => $hit,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $value;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop("cache_get_{$key}");
            
            $this->logger->warning('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Set cached value with TTL
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl ??= $this->config['default_ttl'] ?? 3600;
        $timer = PerformanceTimer::start("cache_set_{$key}");
        
        try {
            $success = $this->redisWrapper->setWithTtl($key, $value, $ttl);
            $duration = PerformanceTimer::stop("cache_set_{$key}");
            
            $this->logger->debug('Cache set operation', [
                'key' => $key,
                'ttl' => $ttl,
                'success' => $success,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $success;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop("cache_set_{$key}");
            
            $this->logger->warning('Cache set failed', [
                'key' => $key,
                'ttl' => $ttl,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get or compute cached value
     */
    public function getOrCompute(string $key, callable $computeFunction, ?int $ttl = null): mixed
    {
        // Try to get from cache first
        $cached = $this->get($key);
        if ($cached !== false) {
            return $cached;
        }
        
        // Compute the value
        $timer = PerformanceTimer::start("cache_compute_{$key}");
        
        try {
            $value = $computeFunction();
            $computeDuration = PerformanceTimer::stop("cache_compute_{$key}");
            
            // Cache the computed value
            $this->set($key, $value, $ttl);
            
            $this->logger->debug('Cache compute and store', [
                'key' => $key,
                'compute_duration_ms' => round($computeDuration * 1000, 2),
                'ttl' => $ttl ?? $this->config['default_ttl'] ?? 3600
            ]);
            
            return $value;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop("cache_compute_{$key}");
            
            $this->logger->error('Cache compute failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        try {
            $deleted = $this->redisWrapper->delete($key) > 0;
            
            $this->logger->debug('Cache delete operation', [
                'key' => $key,
                'deleted' => $deleted
            ]);
            
            return $deleted;
            
        } catch (\Throwable $e) {
            $this->logger->warning('Cache delete failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if key exists in cache
     */
    public function exists(string $key): bool
    {
        try {
            return $this->redisWrapper->execute(
                'exists',
                fn($redis) => $redis->exists($key) > 0,
                false
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Cache exists check failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if a key exists in cache (alias for exists)
     */
    public function has(string $key): bool
    {
        return $this->exists($key);
    }

    /**
     * Clear cache entries matching a pattern
     */
    public function clearByPattern(string $pattern): int
    {
        $timer = PerformanceTimer::start('cache_clear_pattern');
        $cleared = 0;
        
        try {
            $keys = $this->redisWrapper->execute(
                'keys',
                fn($redis) => $redis->keys($pattern),
                []
            );
            
            foreach ($keys as $key) {
                if ($this->delete($key)) {
                    $cleared++;
                }
            }
            
            $duration = PerformanceTimer::stop('cache_clear_pattern');
            
            $this->logger->info('Cache pattern clear completed', [
                'pattern' => $pattern,
                'keys_cleared' => $cleared,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $cleared;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('cache_clear_pattern');
            
            $this->logger->error('Cache pattern clear failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Cache multiple values at once
     */
    public function setMultiple(array $values, ?int $ttl = null): array
    {
        $results = [];
        
        foreach ($values as $key => $value) {
            $results[$key] = $this->set($key, $value, $ttl);
        }
        
        return $results;
    }

    /**
     * Get multiple cached values
     */
    public function getMultiple(array $keys, bool $jsonDecode = true): array
    {
        $results = [];
        
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $jsonDecode);
        }
        
        return $results;
    }

    /**
     * Invalidate cache by pattern
     */
    public function invalidatePattern(string $pattern): int
    {
        try {
            // Note: This is a simplified implementation
            // In production, you might want to use Redis SCAN for large datasets
            $keys = $this->redisWrapper->execute(
                'keys',
                fn($redis) => $redis->keys($pattern),
                []
            );
            
            $deletedCount = 0;
            foreach ($keys as $key) {
                if ($this->delete($key)) {
                    $deletedCount++;
                }
            }
            
            $this->logger->info('Cache pattern invalidation', [
                'pattern' => $pattern,
                'keys_found' => count($keys),
                'keys_deleted' => $deletedCount
            ]);
            
            return $deletedCount;
            
        } catch (\Throwable $e) {
            $this->logger->error('Cache pattern invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Warm up cache with precomputed values
     */
    public function warmUp(array $warmUpData, ?int $ttl = null): array
    {
        $results = [];
        $timer = PerformanceTimer::start('cache_warmup');
        
        foreach ($warmUpData as $key => $computeFunction) {
            try {
                if (is_callable($computeFunction)) {
                    $value = $computeFunction();
                } else {
                    $value = $computeFunction;
                }
                
                $results[$key] = $this->set($key, $value, $ttl);
                
            } catch (\Throwable $e) {
                $this->logger->warning('Cache warmup failed for key', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
                
                $results[$key] = false;
            }
        }
        
        $duration = PerformanceTimer::stop('cache_warmup');
        
        $this->logger->info('Cache warmup completed', [
            'keys_processed' => count($warmUpData),
            'successful_sets' => count(array_filter($results)),
            'duration_ms' => round($duration * 1000, 2)
        ]);
        
        return $results;
    }

    /**
     * Create cache key with namespace
     */
    public function createKey(string $namespace, string $identifier, array $params = []): string
    {
        $key = "{$namespace}:{$identifier}";
        
        if (!empty($params)) {
            ksort($params); // Ensure consistent ordering
            $paramString = http_build_query($params);
            $key .= ':' . md5($paramString);
        }
        
        return $key;
    }

    /**
     * Cache with automatic expiration refresh
     */
    public function getWithRefresh(string $key, callable $refreshFunction, int $refreshThreshold = 300): mixed
    {
        // Get current value and TTL
        $value = $this->get($key);
        
        if ($value !== false) {
            // Check remaining TTL
            try {
                $ttl = $this->redisWrapper->execute(
                    'ttl',
                    fn($redis) => $redis->ttl($key),
                    -1
                );
                
                // If TTL is below threshold, refresh in background
                if ($ttl > 0 && $ttl < $refreshThreshold) {
                    // Return current value immediately
                    // Refresh asynchronously (in a real implementation, this might use a queue)
                    $this->logger->debug('Cache refresh triggered', [
                        'key' => $key,
                        'remaining_ttl' => $ttl,
                        'threshold' => $refreshThreshold
                    ]);
                    
                    // For now, refresh synchronously
                    try {
                        $newValue = $refreshFunction();
                        $this->set($key, $newValue);
                    } catch (\Throwable $e) {
                        $this->logger->warning('Cache refresh failed', [
                            'key' => $key,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return $value;
                
            } catch (\Throwable $e) {
                // If TTL check fails, return cached value anyway
                return $value;
            }
        }
        
        // No cached value, compute fresh
        return $this->getOrCompute($key, $refreshFunction);
    }
}
