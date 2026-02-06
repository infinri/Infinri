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
namespace Tests\Unit\Metrics;

use App\Core\Metrics\MetricsCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetricsCollectorTest extends TestCase
{
    private string $tempFile;
    private MetricsCollector $collector;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/metrics_test_' . uniqid() . '.php';
        $this->collector = new MetricsCollector($this->tempFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    #[Test]
    public function record_request_increments_count(): void
    {
        $this->collector->recordRequest('GET', '/', 200, 0.05);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(1, $summary['total_requests']);
    }

    #[Test]
    public function record_request_tracks_errors(): void
    {
        $this->collector->recordRequest('GET', '/error', 500, 0.1);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(1, $summary['current_hour']['errors']);
    }

    #[Test]
    public function record_request_tracks_status_codes(): void
    {
        $this->collector->recordRequest('GET', '/', 200, 0.05);
        $this->collector->recordRequest('GET', '/not-found', 404, 0.03);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(1, $summary['status_codes']['200']);
        $this->assertSame(1, $summary['status_codes']['404']);
    }

    #[Test]
    public function record_request_tracks_duration(): void
    {
        $this->collector->recordRequest('GET', '/', 200, 0.100);
        $this->collector->recordRequest('GET', '/', 200, 0.200);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(150.0, $summary['current_hour']['avg_duration_ms']);
        $this->assertSame(200.0, $summary['current_hour']['max_duration_ms']);
    }

    #[Test]
    public function record_query_increments_count(): void
    {
        $this->collector->recordQuery(0.01);
        $this->collector->recordQuery(0.02);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(2, $summary['queries']['count']);
    }

    #[Test]
    public function record_query_tracks_duration(): void
    {
        $this->collector->recordQuery(0.010);
        $this->collector->recordQuery(0.030);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(20.0, $summary['queries']['avg_duration_ms']);
    }

    #[Test]
    public function record_cache_hit_increments_hits(): void
    {
        $this->collector->recordCache(true);
        $this->collector->recordCache(true);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(2, $summary['cache']['hits']);
    }

    #[Test]
    public function record_cache_miss_increments_misses(): void
    {
        $this->collector->recordCache(false);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(1, $summary['cache']['misses']);
    }

    #[Test]
    public function cache_hit_ratio_calculated_correctly(): void
    {
        $this->collector->recordCache(true);
        $this->collector->recordCache(true);
        $this->collector->recordCache(true);
        $this->collector->recordCache(false);
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(75.0, $summary['cache']['hit_ratio']);
    }

    #[Test]
    public function get_summary_returns_expected_structure(): void
    {
        $summary = $this->collector->getSummary();
        
        $this->assertArrayHasKey('total_requests', $summary);
        $this->assertArrayHasKey('last_request', $summary);
        $this->assertArrayHasKey('current_hour', $summary);
        $this->assertArrayHasKey('queries', $summary);
        $this->assertArrayHasKey('cache', $summary);
        $this->assertArrayHasKey('status_codes', $summary);
    }

    #[Test]
    public function get_hourly_metrics_returns_expected_hours(): void
    {
        $metrics = $this->collector->getHourlyMetrics(3);
        
        $this->assertCount(3, $metrics);
    }

    #[Test]
    public function reset_clears_all_metrics(): void
    {
        $this->collector->recordRequest('GET', '/', 200, 0.05);
        $this->collector->reset();
        
        $summary = $this->collector->getSummary();
        
        $this->assertSame(0, $summary['total_requests']);
    }

    #[Test]
    public function cleanup_removes_old_data(): void
    {
        // This test just ensures cleanup runs without error
        $removed = $this->collector->cleanup(7);
        
        $this->assertIsInt($removed);
    }

    #[Test]
    public function metrics_persist_to_file(): void
    {
        $this->collector->recordRequest('GET', '/', 200, 0.05);
        
        // Create new instance to verify persistence
        $collector2 = new MetricsCollector($this->tempFile);
        $summary = $collector2->getSummary();
        
        $this->assertSame(1, $summary['total_requests']);
    }

    #[Test]
    public function cleanup_removes_old_hourly_metrics(): void
    {
        // Manually create metrics file with old hourly data as PHP array
        $oldDate = (new \DateTime())->modify('-40 days')->format('Y-m-d-H');
        $recentDate = (new \DateTime())->format('Y-m-d-H');
        
        $phpContent = "<?php\nreturn " . var_export([
            'hours' => [
                $oldDate => ['requests' => 10, 'errors' => 1],
                $recentDate => ['requests' => 5, 'errors' => 0],
            ],
            'requests' => 15,
        ], true) . ";\n";
        
        file_put_contents($this->tempFile, $phpContent);
        
        $collector = new MetricsCollector($this->tempFile);
        $removed = $collector->cleanup(30);
        
        $this->assertSame(1, $removed);
    }
}
