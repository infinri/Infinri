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
        ];
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
