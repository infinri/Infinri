<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;
use Redis;
use RedisCluster;

/**
 * Redis Operation Wrapper - Centralized Redis error handling
 * 
 * Eliminates redundant Redis try/catch patterns and provides
 * consistent error handling, logging, and fallback behavior.
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class RedisOperationWrapper
{
    use LoggerTrait;
    private Redis|RedisCluster $redis;
    private array $config;

    public function __construct(
        Redis|RedisCluster $redis,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('RedisOperationWrapper', $config);
    }

    /**
     * Execute Redis operation with error handling and fallback
     */
    public function execute(
        string $operation,
        callable $redisOperation,
        mixed $fallbackValue = null,
        array $context = []
    ): mixed {
        $timer = PerformanceTimer::start("redis_{$operation}");
        
        try {
            $result = $redisOperation($this->redis);
            
            $duration = PerformanceTimer::stop("redis_{$operation}");
            
            $this->logger->debug("Redis {$operation} successful", $this->buildOperationContext($operation, [
                'duration_ms' => round($duration * 1000, 2),
                'result_type' => gettype($result)
            ] + $context));
            
            return $result;
            
        } catch (\RedisException $e) {
            $duration = PerformanceTimer::stop("redis_{$operation}");
            
            $this->logger->error("Redis {$operation} failed", $this->buildErrorContext($operation, $e, [
                'duration_ms' => round($duration * 1000, 2),
                'fallback_used' => $fallbackValue !== null
            ] + $context));
            
            if ($fallbackValue !== null) {
                return $fallbackValue;
            }
            
            throw ExceptionFactory::meshOperation($operation, $e->getMessage(), $e);
        }
    }

    /**
     * Execute Redis operation with retry logic
     */
    public function executeWithRetry(
        string $operation,
        callable $redisOperation,
        int $maxRetries = 3,
        int $retryDelayMs = 100,
        mixed $fallbackValue = null,
        array $context = []
    ): mixed {
        $attempt = 1;
        $lastException = null;
        
        while ($attempt <= $maxRetries) {
            try {
                return $this->execute($operation, $redisOperation, null, $this->buildOperationContext($operation, [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries
                ] + $context));
                
            } catch (\Throwable $e) {
                $lastException = $e;
                
                if ($attempt < $maxRetries) {
                    $this->logger->warning("Redis {$operation} retry {$attempt}/{$maxRetries}", $this->buildOperationContext($operation, [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'retry_delay_ms' => $retryDelayMs
                    ]));
                    
                    usleep($retryDelayMs * 1000); // Convert to microseconds
                    $retryDelayMs *= 2; // Exponential backoff
                }
                
                $attempt++;
            }
        }
        
        // All retries failed
        $this->logger->error("Redis {$operation} failed after {$maxRetries} retries", $this->buildErrorContext($operation, $lastException, [
            'max_retries' => $maxRetries
        ]));
        
        if ($fallbackValue !== null) {
            return $fallbackValue;
        }
        
        throw $lastException;
    }

    /**
     * Get Redis info with error handling
     */
    public function getInfo(string $section = ''): array
    {
        return $this->execute(
            'info' . ($section ? "_{$section}" : ''),
            fn($redis) => $redis->info($section),
            [],
            ['section' => $section]
        );
    }

    /**
     * Get database size with error handling
     */
    public function getDbSize(): int
    {
        return $this->execute(
            'dbsize',
            fn($redis) => $redis->dbSize(),
            0
        );
    }

    /**
     * Set value with TTL and error handling
     */
    public function setWithTtl(string $key, mixed $value, int $ttl): bool
    {
        return $this->execute(
            'setex',
            fn($redis) => $redis->setex($key, $ttl, is_array($value) ? json_encode($value) : $value),
            false,
            ['key' => $key, 'ttl' => $ttl]
        );
    }

    /**
     * Get value with error handling and JSON decode
     */
    public function get(string $key, bool $jsonDecode = false): mixed
    {
        $result = $this->execute(
            'get',
            fn($redis) => $redis->get($key),
            false,
            ['key' => $key, 'json_decode' => $jsonDecode]
        );
        
        if ($result !== false && $jsonDecode) {
            $decoded = json_decode($result, true);
            return $decoded !== null ? $decoded : $result;
        }
        
        return $result;
    }

    /**
     * Delete key with error handling
     */
    public function delete(string $key): int
    {
        return $this->execute(
            'del',
            fn($redis) => $redis->del($key),
            0,
            ['key' => $key]
        );
    }

    /**
     * Check if key exists
     */
    public function exists(string $key): bool
    {
        return $this->execute(
            'exists',
            fn($redis) => $redis->exists($key) > 0,
            false,
            ['key' => $key]
        );
    }

    /**
     * Set hash field with error handling
     */
    public function hSet(string $key, string $field, mixed $value): bool
    {
        return $this->execute(
            'hset',
            fn($redis) => $redis->hSet($key, $field, is_array($value) ? json_encode($value) : $value),
            false,
            ['key' => $key, 'field' => $field]
        );
    }

    /**
     * Get hash field with error handling
     */
    public function hGet(string $key, string $field, bool $jsonDecode = false): mixed
    {
        $result = $this->execute(
            'hget',
            fn($redis) => $redis->hGet($key, $field),
            false,
            ['key' => $key, 'field' => $field, 'json_decode' => $jsonDecode]
        );
        
        if ($result !== false && $jsonDecode) {
            $decoded = json_decode($result, true);
            return $decoded !== null ? $decoded : $result;
        }
        
        return $result;
    }

    /**
     * Get all hash fields
     */
    public function hGetAll(string $key, bool $jsonDecodeValues = false): array
    {
        $result = $this->execute(
            'hgetall',
            fn($redis) => $redis->hGetAll($key),
            [],
            ['key' => $key, 'json_decode_values' => $jsonDecodeValues]
        );
        
        if ($jsonDecodeValues && is_array($result)) {
            foreach ($result as $field => $value) {
                $decoded = json_decode($value, true);
                if ($decoded !== null) {
                    $result[$field] = $decoded;
                }
            }
        }
        
        return $result;
    }

    /**
     * Publish message to channel
     */
    public function publish(string $channel, mixed $message): int
    {
        return $this->execute(
            'publish',
            fn($redis) => $redis->publish($channel, is_array($message) ? json_encode($message) : $message),
            0,
            ['channel' => $channel]
        );
    }

    /**
     * Test Redis connectivity
     */
    public function testConnectivity(): bool
    {
        return $this->execute(
            'ping',
            fn($redis) => $redis->ping() === '+PONG',
            false
        );
    }

    /**
     * Get Redis memory usage
     */
    public function getMemoryUsage(): array
    {
        $info = $this->getInfo('memory');
        
        return [
            'used_memory' => $info['used_memory'] ?? 0,
            'used_memory_human' => $info['used_memory_human'] ?? '0B',
            'used_memory_peak' => $info['used_memory_peak'] ?? 0,
            'used_memory_peak_human' => $info['used_memory_peak_human'] ?? '0B',
            'mem_fragmentation_ratio' => $info['mem_fragmentation_ratio'] ?? 1.0
        ];
    }

    /**
     * Get Redis performance stats
     */
    public function getPerformanceStats(): array
    {
        $info = $this->getInfo('stats');
        
        return [
            'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? 0,
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => $this->calculateHitRate($info)
        ];
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Execute multiple Redis operations in pipeline
     */
    public function pipeline(array $operations): array
    {
        return $this->execute(
            'pipeline',
            function($redis) use ($operations) {
                $pipe = $redis->pipeline();
                
                foreach ($operations as $operation) {
                    $method = $operation['method'];
                    $args = $operation['args'] ?? [];
                    $pipe->$method(...$args);
                }
                
                return $pipe->exec();
            },
            [],
            ['operation_count' => count($operations)]
        );
    }
}
