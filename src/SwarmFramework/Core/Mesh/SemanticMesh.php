<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Mesh;

use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Exceptions\MeshAccessException;
use Infinri\SwarmFramework\Exceptions\MeshCorruptionException;
use Infinri\SwarmFramework\Exceptions\MeshCapacityException;
use Infinri\SwarmFramework\Exceptions\MeshOperationException;
use Infinri\SwarmFramework\Exceptions\MeshSnapshotException;
use Infinri\SwarmFramework\Exceptions\MeshSubscriptionException;
use Infinri\SwarmFramework\Exceptions\MeshPublishException;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\RedisOperationWrapper;
use Infinri\SwarmFramework\Core\Common\CacheManager;
use Infinri\SwarmFramework\Core\Common\HealthCheckManager;
use Infinri\SwarmFramework\Core\Common\ThresholdValidator;
use Infinri\SwarmFramework\Core\AccessControl\MeshAccessController;
use Infinri\SwarmFramework\Core\Validation\MeshDataValidator;
use Infinri\SwarmFramework\Core\Monitoring\MeshPerformanceMonitor;
use Infinri\SwarmFramework\Core\PubSub\MeshSubscriptionManager;
use Psr\Log\LoggerInterface;
use Redis;
use RedisCluster;

/**
 * Semantic Mesh - Refactored with Centralized Utilities
 * 
 * The Living Memory of Digital Consciousness using centralized utilities
 * for Redis operations, performance timing, caching, and error handling.
 * 
 * BEFORE: 386 lines with massive redundancy
 * AFTER: ~200 lines leveraging centralized utilities
 * 
 * @performance O(1) mesh operations via Redis Cluster
 * @architecture Implements Semantic Mesh Pattern™
 * @reference infinri_blueprint.md → FR-CORE-002
 * @tactic TAC-PERF-001 (Four-tier caching)
 */
#[Injectable(dependencies: ['Redis', 'LoggerInterface', 'RedisOperationWrapper', 'CacheManager'])]
final class SemanticMesh implements SemanticMeshInterface
{
    use LoggerTrait;

    private Redis|RedisCluster $redis;
    private RedisOperationWrapper $redisWrapper;
    private CacheManager $cacheManager;
    private MeshAccessController $accessController;
    private MeshDataValidator $dataValidator;
    private MeshPerformanceMonitor $performanceMonitor;
    private MeshSubscriptionManager $subscriptionManager;
    
    private array $config;

    public function __construct(
        Redis|RedisCluster $redis,
        LoggerInterface $logger,
        RedisOperationWrapper $redisWrapper,
        CacheManager $cacheManager,
        array $config = []
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->redisWrapper = $redisWrapper;
        $this->cacheManager = $cacheManager;
        $this->config = ConfigManager::getConfig('SemanticMesh', $config);
        
        $this->initializeComponents();
    }

    /**
     * Initialize specialized components using centralized configuration
     */
    private function initializeComponents(): void
    {
        $this->accessController = new MeshAccessController($this->logger, $this->config);
        $this->dataValidator = new MeshDataValidator(
            $this->logger,
            new ThresholdValidator($this->logger),
            $this->config
        );
        $this->performanceMonitor = new MeshPerformanceMonitor(
            $this->redisWrapper,
            $this->logger,
            new HealthCheckManager($this->logger),
            $this->cacheManager,
            new ThresholdValidator($this->logger),
            $this->config
        );
        $this->subscriptionManager = new MeshSubscriptionManager(
            $this->redisWrapper,
            $this->logger,
            new ThresholdValidator($this->logger),
            $this->config
        );
    }

    /**
     * Retrieve a value from the mesh using centralized Redis operations and caching
     */
    public function get(string $key, ?string $namespace = null): mixed
    {
        $timer = PerformanceTimer::start('mesh_get');
        
        try {
            $this->logOperationStart('mesh_get', ['key' => $key, 'namespace' => $namespace]);
            
            // Check access permissions
            $this->accessController->checkAccess($key, 'read', $namespace);
            
            // Check cache first using centralized cache manager
            $fullKey = $this->buildKey($key, $namespace);
            $cachedValue = $this->cacheManager->get($fullKey);
            if ($cachedValue !== null) {
                $duration = PerformanceTimer::stop('mesh_get');
                $this->logOperationComplete('mesh_get', [
                    'key' => $key,
                    'cache_hit' => true,
                    'duration_ms' => round($duration * 1000, 2)
                ]);
                return $cachedValue;
            }
            
            // Get from Redis using centralized wrapper
            $rawValue = $this->redisWrapper->executeWithRetry(
                'get',
                fn() => $this->redis->get($fullKey),
                3,
                100,
                false
            );
            
            if ($rawValue === false) {
                $duration = PerformanceTimer::stop('mesh_get');
                $this->logOperationComplete('mesh_get', [
                    'key' => $key,
                    'found' => false,
                    'duration_ms' => round($duration * 1000, 2)
                ]);
                return null;
            }
            
            // Validate and deserialize data
            $value = $this->dataValidator->deserialize($rawValue);
            
            // Cache the result using centralized cache manager
            $this->cacheManager->set($fullKey, $value, $this->config['cache_ttl'] ?? 300);
            
            $duration = PerformanceTimer::stop('mesh_get');
            $this->logOperationComplete('mesh_get', [
                'key' => $key,
                'found' => true,
                'cached' => true,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $value;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('mesh_get');
            $this->logOperationFailure('mesh_get', [
                'key' => $key,
                'namespace' => $namespace,
                'error' => $e->getMessage()
            ]);
            throw ExceptionFactory::meshOperation('get', $key, $e);
        }
    }

    /**
     * Store a value in the mesh using centralized Redis operations and caching
     */
    public function set(string $key, mixed $value, ?string $namespace = null): bool
    {
        $timer = PerformanceTimer::start('mesh_set');
        
        try {
            $this->logOperationStart('mesh_set', ['key' => $key, 'namespace' => $namespace]);
            
            // Check access permissions
            $this->accessController->checkAccess($key, 'write', $namespace);
            
            // Validate data
            if (is_array($value)) {
                $this->dataValidator->validateArrayData($value);
            }
            $this->dataValidator->validateKey($key);
            
            // Serialize data
            $serializedValue = $this->dataValidator->serialize($value);
            
            // Store in Redis using centralized wrapper
            $fullKey = $this->buildKey($key, $namespace);
            $result = $this->redisWrapper->executeWithRetry(
                'set',
                fn() => $this->redis->setex($fullKey, $this->config['default_ttl'], $serializedValue),
                3,
                100,
                false
            );
            
            if ($result) {
                // Update cache using centralized cache manager
                $this->cacheManager->set($fullKey, $value, $this->config['cache_ttl'] ?? 300);
                
                // Publish change event
                $this->publish("mesh.changes.{$namespace}", [
                    'operation' => 'set',
                    'key' => $key,
                    'namespace' => $namespace,
                    'timestamp' => PerformanceTimer::now()
                ]);
            }
            
            $duration = PerformanceTimer::stop('mesh_set');
            $this->logOperationComplete('mesh_set', [
                'key' => $key,
                'success' => $result,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $result;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('mesh_set');
            $this->logOperationFailure('mesh_set', [
                'key' => $key,
                'namespace' => $namespace,
                'error' => $e->getMessage()
            ]);
            throw ExceptionFactory::meshOperation('set', $key, $e);
        }
    }

    /**
     * Delete a value from the mesh using centralized Redis operations
     */
    public function delete(string $key, ?string $namespace = null): bool
    {
        $timer = PerformanceTimer::start('mesh_delete');
        
        try {
            $this->logOperationStart('mesh_delete', ['key' => $key, 'namespace' => $namespace]);
            
            // Check access permissions
            $this->accessController->checkAccess($key, 'delete', $namespace);
            
            $fullKey = $this->buildKey($key, $namespace);
            
            // Delete from Redis using centralized wrapper
            $result = $this->redisWrapper->executeWithRetry(
                'delete',
                fn() => $this->redis->del($fullKey) > 0,
                3,
                100,
                false
            );
            
            if ($result) {
                // Remove from cache using centralized cache manager
                $this->cacheManager->delete($fullKey);
                
                // Publish change event
                $this->publish("mesh.changes.{$namespace}", [
                    'operation' => 'delete',
                    'key' => $key,
                    'namespace' => $namespace,
                    'timestamp' => PerformanceTimer::now()
                ]);
            }
            
            $duration = PerformanceTimer::stop('mesh_delete');
            $this->logOperationComplete('mesh_delete', [
                'key' => $key,
                'success' => $result,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $result;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('mesh_delete');
            $this->logOperationFailure('mesh_delete', [
                'key' => $key,
                'namespace' => $namespace,
                'error' => $e->getMessage()
            ]);
            throw ExceptionFactory::meshOperation('delete', $key, $e);
        }
    }

    /**
     * Check if a key exists in the mesh using centralized Redis operations
     */
    public function exists(string $key, ?string $namespace = null): bool
    {
        $timer = PerformanceTimer::start('mesh_exists');
        
        try {
            // Check access permissions
            $this->accessController->checkAccess($key, 'read', $namespace);
            
            $fullKey = $this->buildKey($key, $namespace);
            
            // Check cache first
            if ($this->cacheManager->has($fullKey)) {
                PerformanceTimer::stop('mesh_exists');
                return true;
            }
            
            // Check Redis using centralized wrapper
            $result = $this->redisWrapper->executeWithRetry(
                'exists',
                fn() => $this->redis->exists($fullKey) > 0,
                3,
                100,
                false
            );
            
            PerformanceTimer::stop('mesh_exists');
            return $result;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('mesh_exists');
            throw ExceptionFactory::meshOperation('exists', $key, $e);
        }
    }

    /**
     * Atomic compare-and-set operation using centralized Redis operations
     */
    public function compareAndSet(string $key, mixed $expected, mixed $value, ?string $namespace = null): bool
    {
        $timer = PerformanceTimer::start('mesh_compare_and_set');
        
        try {
            $this->logOperationStart('mesh_compare_and_set', ['key' => $key, 'namespace' => $namespace]);
            
            // Check access permissions
            $this->accessController->checkAccess($key, 'write', $namespace);
            
            $fullKey = $this->buildKey($key, $namespace);
            
            // Use Redis transaction for atomicity with centralized wrapper
            $result = $this->redisWrapper->executeWithRetry(
                'compareAndSet',
                function() use ($fullKey, $key, $expected, $value, $namespace) {
                    $this->redis->multi();
                    $this->redis->watch($fullKey);
                    
                    $current = $this->get($key, $namespace);
                    
                    if ($current !== $expected) {
                        $this->redis->discard();
                        return false;
                    }

                    $this->redis->multi();
                    $setResult = $this->set($key, $value, $namespace);
                    $this->redis->exec();

                    return $setResult;
                },
                3,
                100,
                false
            );
            
            $duration = PerformanceTimer::stop('mesh_compare_and_set');
            $this->logOperationComplete('mesh_compare_and_set', [
                'key' => $key,
                'success' => $result,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $result;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('mesh_compare_and_set');
            $this->redis->discard();
            throw ExceptionFactory::meshOperation('compareAndSet', $key, $e);
        }
    }

    /**
     * Create snapshot of mesh data using centralized operations
     */
    public function snapshot(array $keyPatterns = ['*']): array
    {
        $timer = PerformanceTimer::start('mesh_snapshot');
        
        try {
            $snapshot = [];
            
            foreach ($keyPatterns as $pattern) {
                $keys = $this->redisWrapper->executeWithRetry(
                    'keys',
                    fn() => $this->redis->keys($pattern),
                    3,
                    100,
                    []
                );
                
                foreach ($keys as $key) {
                    try {
                        $rawValue = $this->redisWrapper->executeWithRetry(
                            'get',
                            fn() => $this->redis->get($key),
                            3,
                            100,
                            false
                        );
                        
                        if ($rawValue !== false) {
                            $snapshot[$key] = $this->dataValidator->deserialize($rawValue);
                        }
                    } catch (\Throwable $e) {
                        // Skip corrupted entries
                        continue;
                    }
                }
            }

            $duration = PerformanceTimer::stop('mesh_snapshot');
            $this->logOperationComplete('mesh_snapshot', [
                'patterns' => $keyPatterns,
                'key_count' => count($snapshot),
                'duration_ms' => round($duration * 1000, 2)
            ]);

            return $snapshot;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('mesh_snapshot');
            throw ExceptionFactory::meshOperation('snapshot', 'multiple', $e);
        }
    }

    /**
     * Get version of a key using centralized Redis operations
     */
    public function getVersion(string $key): int
    {
        try {
            $fullKey = $this->buildKey($key);
            $data = $this->redisWrapper->executeWithRetry(
                'get',
                fn() => $this->redis->get($fullKey),
                3,
                100,
                false
            );
            
            if ($data === false) {
                return 0;
            }

            $decoded = json_decode($data, true);
            return $decoded['_version'] ?? 1;
            
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Subscribe to mesh changes using centralized subscription manager
     */
    public function subscribe(string $pattern, callable $callback): void
    {
        $this->subscriptionManager->subscribe($pattern, $callback);
    }

    /**
     * Publish mesh events using centralized subscription manager
     */
    public function publish(string $channel, array $data): void
    {
        $this->subscriptionManager->publish($channel, $data);
    }

    /**
     * Get keys matching a pattern
     */
    public function getKeysByPattern(string $pattern, ?string $namespace = null): array
    {
        try {
            $fullPattern = $this->buildKey($pattern, $namespace);
            
            $keys = $this->redisWrapper->executeWithRetry(
                'keys',
                fn() => $this->redis->keys($fullPattern),
                3,
                100,
                false
            );
            
            // Remove the mesh prefix from keys to return clean keys
            $separator = $this->config['namespace_separator'];
            $prefix = $namespace ? "mesh{$separator}{$namespace}{$separator}" : "mesh{$separator}";
            
            return array_map(function($key) use ($prefix) {
                return str_starts_with($key, $prefix) ? substr($key, strlen($prefix)) : $key;
            }, $keys ?: []);
            
        } catch (\Throwable $e) {
            $this->logOperationFailure('getKeysByPattern', [
                'pattern' => $pattern,
                'namespace' => $namespace,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get all mesh data using centralized snapshot
     */
    public function all(): array
    {
        return $this->snapshot(['*']);
    }

    /**
     * Get mesh statistics using centralized performance monitor
     */
    public function getStats(): array
    {
        return $this->performanceMonitor->getStats();
    }

    /**
     * Clear mesh data using centralized Redis operations
     */
    public function clear(?string $namespace = null): bool
    {
        $timer = PerformanceTimer::start('mesh_clear');
        
        try {
            $this->logOperationStart('mesh_clear', ['namespace' => $namespace]);
            
            $result = $this->redisWrapper->executeWithRetry(
                'clear',
                function() use ($namespace) {
                    if ($namespace === null) {
                        return $this->redis->flushDB();
                    }

                    $pattern = "mesh.{$namespace}.*";
                    $keys = $this->redis->keys($pattern);
                    
                    if (!empty($keys)) {
                        return $this->redis->del(...$keys) > 0;
                    }

                    return true;
                },
                3,
                100,
                false
            );
            
            if ($result && $namespace !== null) {
                // Clear related cache entries
                $this->cacheManager->clearByPattern("mesh.{$namespace}.*");
            }
            
            $duration = PerformanceTimer::stop('mesh_clear');
            $this->logOperationComplete('mesh_clear', [
                'namespace' => $namespace,
                'success' => $result,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $result;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('mesh_clear');
            $this->logOperationFailure('mesh_clear', [
                'namespace' => $namespace,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Build the full Redis key with namespace using centralized configuration
     */
    private function buildKey(string $key, ?string $namespace = null): string
    {
        $separator = $this->config['namespace_separator'];
        
        if ($namespace === null) {
            return "mesh{$separator}{$key}";
        }

        return "mesh{$separator}{$namespace}{$separator}{$key}";
    }
}
