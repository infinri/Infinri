<?php

declare(strict_types=1);

namespace App\Core\Metrics;

/**
 * Metrics Collector
 * 
 * Collects basic application metrics: request counts, response times, errors.
 * Stores in file cache for simplicity (can be upgraded to Redis/Prometheus).
 */
class MetricsCollector
{
    protected string $storagePath;
    protected array $metrics = [];
    protected bool $loaded = false;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? base_path('var/state/metrics.php');
    }

    /**
     * Record a request
     */
    public function recordRequest(string $method, string $path, int $statusCode, float $duration): void
    {
        $this->load();

        $hour = date('Y-m-d-H');
        
        // Initialize hour bucket if needed
        if (!isset($this->metrics['hours'][$hour])) {
            $this->metrics['hours'][$hour] = [
                'requests' => 0,
                'errors' => 0,
                'duration_sum' => 0,
                'duration_max' => 0,
            ];
        }

        $bucket = &$this->metrics['hours'][$hour];
        $bucket['requests']++;
        $bucket['duration_sum'] += $duration;
        $bucket['duration_max'] = max($bucket['duration_max'], $duration);

        // Track errors (4xx and 5xx)
        if ($statusCode >= 400) {
            $bucket['errors']++;
        }

        // Track by status code
        $statusKey = (string) $statusCode;
        $this->metrics['status_codes'][$statusKey] = ($this->metrics['status_codes'][$statusKey] ?? 0) + 1;

        // Update totals
        $this->metrics['total_requests'] = ($this->metrics['total_requests'] ?? 0) + 1;
        $this->metrics['last_request'] = date('c');

        $this->save();
    }

    /**
     * Record a database query
     */
    public function recordQuery(float $duration): void
    {
        $this->load();

        $this->metrics['queries']['count'] = ($this->metrics['queries']['count'] ?? 0) + 1;
        $this->metrics['queries']['duration_sum'] = ($this->metrics['queries']['duration_sum'] ?? 0) + $duration;

        $this->save();
    }

    /**
     * Record cache hit/miss
     */
    public function recordCache(bool $hit): void
    {
        $this->load();

        $key = $hit ? 'hits' : 'misses';
        $this->metrics['cache'][$key] = ($this->metrics['cache'][$key] ?? 0) + 1;

        $this->save();
    }

    /**
     * Get current metrics summary
     */
    public function getSummary(): array
    {
        $this->load();

        $totalRequests = $this->metrics['total_requests'] ?? 0;
        $currentHour = date('Y-m-d-H');
        $hourData = $this->metrics['hours'][$currentHour] ?? [];

        $cacheHits = $this->metrics['cache']['hits'] ?? 0;
        $cacheMisses = $this->metrics['cache']['misses'] ?? 0;
        $cacheTotal = $cacheHits + $cacheMisses;

        return [
            'total_requests' => $totalRequests,
            'last_request' => $this->metrics['last_request'] ?? null,
            'current_hour' => [
                'requests' => $hourData['requests'] ?? 0,
                'errors' => $hourData['errors'] ?? 0,
                'avg_duration_ms' => ($hourData['requests'] ?? 0) > 0 
                    ? round((($hourData['duration_sum'] ?? 0) / $hourData['requests']) * 1000, 2) 
                    : 0,
                'max_duration_ms' => round(($hourData['duration_max'] ?? 0) * 1000, 2),
            ],
            'queries' => [
                'count' => $this->metrics['queries']['count'] ?? 0,
                'avg_duration_ms' => ($this->metrics['queries']['count'] ?? 0) > 0
                    ? round(($this->metrics['queries']['duration_sum'] / $this->metrics['queries']['count']) * 1000, 2)
                    : 0,
            ],
            'cache' => [
                'hits' => $cacheHits,
                'misses' => $cacheMisses,
                'hit_ratio' => $cacheTotal > 0 ? round($cacheHits / $cacheTotal * 100, 1) : 0,
            ],
            'status_codes' => $this->metrics['status_codes'] ?? [],
        ];
    }

    /**
     * Get metrics for last N hours
     */
    public function getHourlyMetrics(int $hours = 24): array
    {
        $this->load();

        $result = [];
        $now = new \DateTime();

        for ($i = 0; $i < $hours; $i++) {
            $hour = $now->format('Y-m-d-H');
            $result[$hour] = $this->metrics['hours'][$hour] ?? [
                'requests' => 0,
                'errors' => 0,
                'duration_sum' => 0,
                'duration_max' => 0,
            ];
            $now->modify('-1 hour');
        }

        return array_reverse($result, true);
    }

    /**
     * Clear old metrics (keep last N days)
     */
    public function cleanup(int $daysToKeep = 7): int
    {
        $this->load();

        $cutoff = (new \DateTime())->modify("-{$daysToKeep} days")->format('Y-m-d');
        $removed = 0;

        if (isset($this->metrics['hours'])) {
            foreach (array_keys($this->metrics['hours']) as $hour) {
                if (substr($hour, 0, 10) < $cutoff) {
                    unset($this->metrics['hours'][$hour]);
                    $removed++;
                }
            }
        }

        $this->save();
        return $removed;
    }

    /**
     * Reset all metrics
     */
    public function reset(): void
    {
        $this->metrics = [];
        $this->save();
    }

    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (file_exists($this->storagePath)) {
            $this->metrics = require $this->storagePath;
        } else {
            $this->metrics = [];
        }

        $this->loaded = true;
    }

    protected function save(): void
    {
        save_php_array($this->storagePath, $this->metrics, 'Application Metrics', LOCK_EX);
    }
}
