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
namespace App\Core\Http;

use App\Core\Metrics\MetricsCollector;

/**
 * Metrics Endpoint
 *
 * Provides metrics in Prometheus text format for scraping.
 * Typically exposed at /metrics or /_metrics
 */
class MetricsEndpoint
{
    protected MetricsCollector $collector;

    public function __construct(?MetricsCollector $collector = null)
    {
        $this->collector = $collector ?? new MetricsCollector();
    }

    /**
     * Handle metrics request
     */
    public function handle(): void
    {
        $format = $_GET['format'] ?? 'prometheus';

        if ($format === 'json') {
            $this->outputJson();
        } else {
            $this->outputPrometheus();
        }
    }

    /**
     * Output in Prometheus text format
     */
    protected function outputPrometheus(): void
    {
        header('Content-Type: text/plain; version=0.0.4; charset=utf-8');

        $summary = $this->collector->getSummary();
        $lines = [];

        // Help and type declarations
        $lines[] = '# HELP app_requests_total Total number of HTTP requests';
        $lines[] = '# TYPE app_requests_total counter';
        $lines[] = 'app_requests_total ' . $summary['total_requests'];

        $lines[] = '# HELP app_requests_current_hour Requests in current hour';
        $lines[] = '# TYPE app_requests_current_hour gauge';
        $lines[] = 'app_requests_current_hour ' . $summary['current_hour']['requests'];

        $lines[] = '# HELP app_errors_current_hour Errors in current hour';
        $lines[] = '# TYPE app_errors_current_hour gauge';
        $lines[] = 'app_errors_current_hour ' . $summary['current_hour']['errors'];

        $lines[] = '# HELP app_response_time_avg_ms Average response time in milliseconds';
        $lines[] = '# TYPE app_response_time_avg_ms gauge';
        $lines[] = 'app_response_time_avg_ms ' . $summary['current_hour']['avg_duration_ms'];

        $lines[] = '# HELP app_response_time_max_ms Maximum response time in milliseconds';
        $lines[] = '# TYPE app_response_time_max_ms gauge';
        $lines[] = 'app_response_time_max_ms ' . $summary['current_hour']['max_duration_ms'];

        $lines[] = '# HELP app_db_queries_total Total database queries';
        $lines[] = '# TYPE app_db_queries_total counter';
        $lines[] = 'app_db_queries_total ' . $summary['queries']['count'];

        $lines[] = '# HELP app_db_query_time_avg_ms Average query time in milliseconds';
        $lines[] = '# TYPE app_db_query_time_avg_ms gauge';
        $lines[] = 'app_db_query_time_avg_ms ' . $summary['queries']['avg_duration_ms'];

        $lines[] = '# HELP app_cache_hits_total Total cache hits';
        $lines[] = '# TYPE app_cache_hits_total counter';
        $lines[] = 'app_cache_hits_total ' . $summary['cache']['hits'];

        $lines[] = '# HELP app_cache_misses_total Total cache misses';
        $lines[] = '# TYPE app_cache_misses_total counter';
        $lines[] = 'app_cache_misses_total ' . $summary['cache']['misses'];

        $lines[] = '# HELP app_cache_hit_ratio Cache hit ratio percentage';
        $lines[] = '# TYPE app_cache_hit_ratio gauge';
        $lines[] = 'app_cache_hit_ratio ' . $summary['cache']['hit_ratio'];

        // Status codes
        if (! empty($summary['status_codes'])) {
            $lines[] = '# HELP app_http_responses_total HTTP responses by status code';
            $lines[] = '# TYPE app_http_responses_total counter';
            foreach ($summary['status_codes'] as $code => $count) {
                $lines[] = "app_http_responses_total{status=\"{$code}\"} {$count}";
            }
        }

        echo implode("\n", $lines) . "\n";
    }

    /**
     * Output in JSON format
     */
    protected function outputJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->collector->getSummary(), JSON_PRETTY_PRINT);
    }

    /**
     * Check if request should be allowed (basic auth or IP check)
     */
    public function authorize(): bool
    {
        // Allow from localhost
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return true;
        }

        // Check for API key if configured
        $apiKey = $_ENV['METRICS_API_KEY'] ?? '';
        if (! empty($apiKey)) {
            $providedKey = $_SERVER['HTTP_X_METRICS_KEY'] ?? $_GET['key'] ?? '';

            return hash_equals($apiKey, $providedKey);
        }

        // Default: deny remote access
        return false;
    }
}
