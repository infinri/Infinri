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
namespace App\Core\Support;

use App\Core\Application;
use App\Core\Contracts\Queue\QueueInterface;
use App\Core\Queue\RedisQueue;
use App\Core\Redis\RedisManager;
use Throwable;

/**
 * Health Check
 *
 * Provides system health information for monitoring
 */
class HealthCheck
{
    /**
     * The application instance
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Component health statuses
     *
     * @var array<string, string>
     */
    protected array $componentStatus = [];

    /**
     * Create a new health check instance
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the full health check response
     *
     * @return array
     */
    public function check(): array
    {
        $this->componentStatus = [];

        return [
            'status' => $this->getOverallStatus(),
            'timestamp' => date('Y-m-d\TH:i:s.uP'),
            'app' => $this->getAppInfo(),
            'system' => $this->getSystemInfo(),
            'http' => $this->getHttpInfo(),
            'database' => $this->getDatabaseInfo(),
            'redis' => $this->getRedisInfo(),
            'queue' => $this->getQueueInfo(),
            'cache' => $this->getCacheInfo(),
            'modules' => $this->getModulesInfo(),
        ];
    }

    /**
     * Get database information (Phase 3)
     *
     * @return array
     */
    protected function getDatabaseInfo(): array
    {
        $info = [
            'status' => 'not_configured',
            'connection' => null,
            'last_migration' => null,
        ];

        // Check if database manager is registered
        if (! $this->app->has(\App\Core\Database\DatabaseManager::class)) {
            return $info;
        }

        try {
            $db = $this->app->make(\App\Core\Database\DatabaseManager::class);
            $connection = $db->connection();

            // Test connection
            $connection->select('SELECT 1');
            $info['status'] = 'connected';
            $info['connection'] = $connection->getName();
            $info['driver'] = $connection->getDriverName();
            $info['database'] = $connection->getDatabaseName();

            // Get last migration
            try {
                $result = $connection->selectOne(
                    'SELECT migration FROM migrations ORDER BY id DESC LIMIT 1'
                );
                $info['last_migration'] = $result['migration'] ?? null;
            } catch (Throwable $e) {
                $info['last_migration'] = 'migrations_table_not_found';
            }

        } catch (Throwable $e) {
            $info['status'] = 'error';
            $info['error'] = $e->getMessage();
        }

        return $info;
    }

    /**
     * Get HTTP layer information (Phase 2)
     *
     * @return array
     */
    protected function getHttpInfo(): array
    {
        $info = [
            'router' => 'not_registered',
            'routes_count' => 0,
            'middleware' => [],
        ];

        // Check if router is registered
        if ($this->app->has(\App\Core\Contracts\Routing\RouterInterface::class)) {
            try {
                $router = $this->app->make(\App\Core\Contracts\Routing\RouterInterface::class);
                $info['router'] = 'active';
                $info['routes_count'] = count($router->getRoutes());
            } catch (Throwable $e) {
                $info['router'] = 'error';
            }
        }

        // Check if kernel is registered
        if ($this->app->has(\App\Core\Contracts\Http\KernelInterface::class)) {
            $info['kernel'] = 'active';
        }

        return $info;
    }

    /**
     * Get Redis information
     *
     * @return array
     */
    protected function getRedisInfo(): array
    {
        $info = [
            'status' => 'not_configured',
            'connection' => null,
        ];

        if (env('CACHE_DRIVER') !== 'redis' &&
            env('SESSION_DRIVER') !== 'redis' &&
            env('QUEUE_CONNECTION') !== 'redis') {
            return $info;
        }

        if (! $this->app->has(RedisManager::class)) {
            return $info;
        }

        try {
            $redis = $this->app->make(RedisManager::class);
            $connection = $redis->connection();
            $connection->ping();

            $info['status'] = 'connected';
            $info['connection'] = 'default';

            $redisInfo = $connection->info();
            $info['version'] = $redisInfo['redis_version'] ?? 'unknown';
            $info['memory_used'] = $redisInfo['used_memory_human'] ?? 'unknown';
            $info['connected_clients'] = $redisInfo['connected_clients'] ?? 0;
            $info['uptime_seconds'] = $redisInfo['uptime_in_seconds'] ?? 0;

            $this->componentStatus['redis'] = 'healthy';
        } catch (Throwable $e) {
            $info['status'] = 'error';
            $info['error'] = $e->getMessage();
            $this->componentStatus['redis'] = 'critical';
        }

        return $info;
    }

    /**
     * Get queue information
     *
     * @return array
     */
    protected function getQueueInfo(): array
    {
        $info = [
            'status' => 'not_configured',
            'driver' => env('QUEUE_CONNECTION', 'sync'),
        ];

        if (! $this->app->has(QueueInterface::class)) {
            return $info;
        }

        try {
            $queue = $this->app->make(QueueInterface::class);
            $info['status'] = 'active';

            if ($queue instanceof RedisQueue) {
                $stats = $queue->stats();
                $info['pending'] = $stats['pending'] ?? 0;
                $info['delayed'] = $stats['delayed'] ?? 0;
                $info['failed'] = $stats['failed'] ?? 0;
            }

            $this->componentStatus['queue'] = 'healthy';
        } catch (Throwable $e) {
            $info['status'] = 'error';
            $info['error'] = $e->getMessage();
            $this->componentStatus['queue'] = 'degraded';
        }

        return $info;
    }

    /**
     * Get cache information
     *
     * @return array
     */
    protected function getCacheInfo(): array
    {
        $info = [
            'status' => 'active',
            'driver' => env('CACHE_DRIVER', 'file'),
        ];

        try {
            $cache = cache();

            // Test cache operation
            $testKey = '_health_check_' . time();
            $cache->put($testKey, true, 10);
            $result = $cache->get($testKey);
            $cache->forget($testKey);

            if ($result === true) {
                $info['status'] = 'active';
                $this->componentStatus['cache'] = 'healthy';
            } else {
                $info['status'] = 'degraded';
                $this->componentStatus['cache'] = 'degraded';
            }
        } catch (Throwable $e) {
            $info['status'] = 'error';
            $info['error'] = $e->getMessage();
            $this->componentStatus['cache'] = 'critical';
        }

        return $info;
    }

    /**
     * Get modules information
     *
     * @return array
     */
    protected function getModulesInfo(): array
    {
        $info = [
            'total' => 0,
            'loaded' => 0,
            'deferred' => 0,
        ];

        if (! $this->app->has(\App\Core\Module\ModuleLoader::class)) {
            return $info;
        }

        try {
            $loader = $this->app->make(\App\Core\Module\ModuleLoader::class);
            $registry = $loader->getRegistry();

            $info['total'] = count($registry->getEnabled());
            $info['loaded'] = count($loader->getLoadedModuleNames());
            $info['deferred'] = count($loader->getDeferredModules());
            $info['modules'] = array_map(
                fn ($m) => [
                    'name' => $m->name,
                    'version' => $m->version,
                    'lazy' => $m->lazy,
                    'loaded' => $loader->isModuleLoaded($m->name),
                ],
                $registry->getEnabled()
            );
        } catch (Throwable $e) {
            $info['error'] = $e->getMessage();
        }

        return $info;
    }

    /**
     * Get overall system status
     *
     * @return string
     */
    protected function getOverallStatus(): string
    {
        // Check critical systems
        $memoryUsage = memory_get_usage(true) / $this->getMemoryLimit();

        if ($memoryUsage > 0.9) {
            return 'critical';
        }

        if ($memoryUsage > 0.75) {
            return 'degraded';
        }

        // Check component statuses
        if (in_array('critical', $this->componentStatus, true)) {
            return 'critical';
        }

        if (in_array('degraded', $this->componentStatus, true)) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Get application information
     *
     * @return array
     */
    protected function getAppInfo(): array
    {
        return [
            'name' => config('app.name', 'Infinri'),
            'version' => $this->app->version(),
            'environment' => $this->app->environment(),
            'debug' => $this->app->isDebug(),
        ];
    }

    /**
     * Get system information
     *
     * @return array
     */
    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_limit_mb' => round($this->getMemoryLimit() / 1024 / 1024, 2),
            'memory_usage_percent' => round((memory_get_usage(true) / $this->getMemoryLimit()) * 100, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }

    /**
     * Get the memory limit in bytes
     *
     * @return int
     */
    protected function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit == -1) {
            return PHP_INT_MAX;
        }

        return $this->convertToBytes($memoryLimit);
    }

    /**
     * Convert PHP ini memory value to bytes
     *
     * @param string $value
     *
     * @return int
     */
    protected function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Get JSON response
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->check(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
