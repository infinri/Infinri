<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Application;

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
        return [
            'status' => $this->getOverallStatus(),
            'timestamp' => date('Y-m-d\TH:i:s.uP'),
            'app' => $this->getAppInfo(),
            'system' => $this->getSystemInfo(),
            'http' => $this->getHttpInfo(),
            'database' => $this->getDatabaseInfo(),
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
        if (!$this->app->has(\App\Core\Database\DatabaseManager::class)) {
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
            } catch (\Throwable $e) {
                $info['last_migration'] = 'migrations_table_not_found';
            }

        } catch (\Throwable $e) {
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
            } catch (\Throwable $e) {
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
