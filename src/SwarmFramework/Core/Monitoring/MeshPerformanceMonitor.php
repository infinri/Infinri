<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Monitoring;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\RedisOperationWrapper;
use Infinri\SwarmFramework\Core\Common\HealthCheckManager;
use Infinri\SwarmFramework\Core\Common\StatisticsCalculator;
use Infinri\SwarmFramework\Core\Common\CacheManager;
use Infinri\SwarmFramework\Core\Common\ThresholdValidator;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Mesh Performance Monitor - Refactored with Centralized Utilities
 * 
 * Monitors semantic mesh performance using centralized utilities
 * for health checks, statistics, caching, and threshold validation.
 * 
 * BEFORE: 524 lines with massive redundancy
 * AFTER: ~150 lines leveraging 8 centralized utilities
 */
#[Injectable(dependencies: ['RedisOperationWrapper', 'LoggerInterface', 'HealthCheckManager', 'CacheManager', 'ThresholdValidator'])]
final class MeshPerformanceMonitor
{
    use LoggerTrait;

    private RedisOperationWrapper $redisWrapper;
    private HealthCheckManager $healthChecker;
    private CacheManager $cache;
    private ThresholdValidator $thresholdValidator;
    private array $config;

    public function __construct(
        RedisOperationWrapper $redis,
        LoggerInterface $logger,
        HealthCheckManager $healthChecker,
        CacheManager $cache,
        ThresholdValidator $thresholdValidator,
        array $config = []
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->healthChecker = $healthChecker;
        $this->cache = $cache;
        $this->thresholdValidator = $thresholdValidator;
        $this->config = ConfigManager::getConfig('MeshPerformanceMonitor', $config);
    }

    /**
     * Get comprehensive mesh statistics with caching
     */
    public function getStats(): array
    {
        return $this->cache->getOrCompute(
            'mesh_stats',
            fn() => $this->computeStats(),
            $this->config['stats_cache_ttl']
        );
    }

    /**
     * Check if mesh capacity is exceeded using centralized threshold validation
     */
    public function isCapacityExceeded(): bool
    {
        $currentKeys = $this->redis->getDbSize();
        
        $result = $this->thresholdValidator->validateCapacity(
            'MeshPerformanceMonitor',
            'keys',
            $currentKeys,
            $this->config['max_keys']
        );
        
        return $result['status'] === 'critical';
    }

    /**
     * Check memory usage with centralized threshold validation
     */
    public function isMemoryExceeded(): bool
    {
        $memoryStats = $this->redis->getMemoryUsage();
        
        $result = $this->thresholdValidator->validateCapacity(
            'MeshPerformanceMonitor',
            'memory',
            $memoryStats['used_memory'],
            $this->config['max_memory']
        );
        
        return $result['status'] === 'critical';
    }

    /**
     * Get comprehensive health status using centralized health checking
     */
    public function getHealthStatus(): array
    {
        $checks = [
            'connectivity' => $this->healthChecker->checkConnectivity(
                'Redis',
                fn() => $this->redis->testConnectivity()
            ),
            'capacity' => $this->checkCapacityHealth(),
            'memory' => $this->checkMemoryHealth(),
            'performance' => $this->checkPerformanceHealth()
        ];

        return $this->healthChecker->aggregateHealthChecks($checks);
    }

    /**
     * Get performance metrics using centralized statistics calculation
     */
    public function getPerformanceMetrics(): array
    {
        $performanceStats = $this->redis->getPerformanceStats();
        $memoryStats = $this->redis->getMemoryUsage();
        
        return StatisticsCalculator::buildStatsArray([
            'hits' => $performanceStats['keyspace_hits'],
            'misses' => $performanceStats['keyspace_misses'],
            'total_ops' => $performanceStats['total_commands_processed'],
            'uptime' => $this->getRedisUptime(),
            'used_memory' => $memoryStats['used_memory'],
            'allocated_memory' => $memoryStats['used_memory_peak'],
            'current' => $this->redis->getDbSize(),
            'maximum' => $this->config['max_keys']
        ]);
    }

    /**
     * Record operation metric with centralized performance timing
     */
    public function recordOperation(string $operation, float $duration, bool $success = true): void
    {
        $this->logOperationComplete($operation, [
            'duration_ms' => round($duration * 1000, 2),
            'success' => $success
        ]);

        // Store in Redis for aggregation
        $this->redis->hSet(
            'mesh_operations',
            $operation,
            json_encode([
                'last_duration' => $duration,
                'last_success' => $success,
                'timestamp' => PerformanceTimer::now()
            ])
        );
    }

    /**
     * Compute fresh statistics (called by cache when needed)
     */
    private function computeStats(): array
    {
        $this->logOperationStart('compute_stats');
        
        $redisInfo = $this->redis->getInfo();
        $performanceStats = $this->redis->getPerformanceStats();
        $memoryStats = $this->redis->getMemoryUsage();
        
        $stats = StatisticsCalculator::buildStatsArray($redisInfo + [
            'connections_active' => $redisInfo['connected_clients'] ?? 0,
            'memory_used_mb' => round(($redisInfo['used_memory'] ?? 0) / 1024 / 1024, 2)
        ]);
        
        $stats = array_merge($stats, [
            'total_ops' => $performanceStats['total_commands_processed'],
            'uptime' => $redisInfo['uptime_in_seconds'] ?? 0,
            'used_memory' => $memoryStats['used_memory'],
            'allocated_memory' => $memoryStats['used_memory_peak'],
            'current' => $this->redis->getDbSize(),
            'maximum' => $this->config['max_keys']
        ]);
        
        $this->logOperationComplete('compute_stats');
        
        return $stats;
    }

    /**
     * Check capacity health using centralized health manager
     */
    private function checkCapacityHealth(): array
    {
        $currentKeys = $this->redis->getDbSize();
        
        return $this->healthChecker->checkThresholdHealth(
            'MeshPerformanceMonitor',
            'capacity',
            (float)$currentKeys,
            (float)$this->config['max_keys']
        );
    }

    /**
     * Check memory health using centralized health manager
     */
    private function checkMemoryHealth(): array
    {
        $memoryStats = $this->redis->getMemoryUsage();
        
        return $this->healthChecker->checkThresholdHealth(
            'MeshPerformanceMonitor',
            'memory',
            (float)$memoryStats['used_memory'],
            (float)$this->config['max_memory']
        );
    }

    /**
     * Check performance health using centralized threshold validation
     */
    private function checkPerformanceHealth(): array
    {
        $performanceStats = $this->redis->getPerformanceStats();
        
        $result = $this->thresholdValidator->validatePerformance(
            'MeshPerformanceMonitor',
            [
                'hit_rate' => $performanceStats['hit_rate'],
                'ops_per_second' => $performanceStats['instantaneous_ops_per_sec']
            ]
        );
        
        return $this->healthChecker->createHealthResult(
            $result['overall_status'] ?? 'healthy',
            'Performance metrics within acceptable ranges'
        );
    }

    /**
     * Get Redis uptime from info
     */
    private function getRedisUptime(): int
    {
        $info = $this->redis->getInfo('server');
        return $info['uptime_in_seconds'] ?? 0;
    }
}
