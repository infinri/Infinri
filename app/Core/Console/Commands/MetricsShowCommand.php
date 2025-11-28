<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Metrics\MetricsCollector;

/**
 * Metrics Show Command
 * 
 * Displays application metrics.
 */
class MetricsShowCommand extends Command
{
    protected string $name = 'metrics:show';
    protected string $description = 'Display application metrics';
    protected array $aliases = ['metrics'];

    public function handle(array $args = []): int
    {
        $collector = new MetricsCollector();
        $summary = $collector->getSummary();

        $this->line("Application Metrics");
        $this->line(str_repeat('═', 50));

        // Overview
        $this->line("\n📊 Overview");
        $this->line("  • Total requests: " . number_format($summary['total_requests']));
        if ($summary['last_request']) {
            $this->line("  • Last request: {$summary['last_request']}");
        }

        // Current hour
        $this->line("\n⏱️  Current Hour");
        $hour = $summary['current_hour'];
        $this->line("  • Requests: " . number_format($hour['requests']));
        $this->line("  • Errors: " . number_format($hour['errors']));
        $this->line("  • Avg response: {$hour['avg_duration_ms']}ms");
        $this->line("  • Max response: {$hour['max_duration_ms']}ms");

        // Database
        if ($summary['queries']['count'] > 0) {
            $this->line("\n🗄️  Database");
            $this->line("  • Total queries: " . number_format($summary['queries']['count']));
            $this->line("  • Avg query time: {$summary['queries']['avg_duration_ms']}ms");
        }

        // Cache
        if ($summary['cache']['hits'] > 0 || $summary['cache']['misses'] > 0) {
            $this->line("\n💾 Cache");
            $this->line("  • Hits: " . number_format($summary['cache']['hits']));
            $this->line("  • Misses: " . number_format($summary['cache']['misses']));
            $this->line("  • Hit ratio: {$summary['cache']['hit_ratio']}%");
        }

        // Status codes
        if (!empty($summary['status_codes'])) {
            $this->line("\n📈 Status Codes");
            ksort($summary['status_codes']);
            foreach ($summary['status_codes'] as $code => $count) {
                $this->line("  • {$code}: " . number_format($count));
            }
        }

        // Show hourly if --hourly flag
        if (in_array('--hourly', $args) || in_array('-h', $args)) {
            $this->line("\n📅 Last 24 Hours");
            $hourly = $collector->getHourlyMetrics(24);
            foreach ($hourly as $hour => $data) {
                if ($data['requests'] > 0) {
                    $avg = round($data['duration_sum'] / $data['requests'] * 1000);
                    $this->line("  {$hour}: {$data['requests']} req, {$data['errors']} err, {$avg}ms avg");
                }
            }
        }

        $this->line();
        return 0;
    }
}
