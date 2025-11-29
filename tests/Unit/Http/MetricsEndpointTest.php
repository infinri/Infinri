<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Unit\Http;

use App\Core\Http\MetricsEndpoint;
use App\Core\Metrics\MetricsCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetricsEndpointTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/metrics_endpoint_test_' . uniqid() . '.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_GET = [];
        $_ENV = [];
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        unset($_SERVER['REMOTE_ADDR'], $_GET['format'], $_GET['key'], $_ENV['METRICS_API_KEY']);
    }

    #[Test]
    public function constructor_creates_default_collector(): void
    {
        $endpoint = new MetricsEndpoint();
        
        $this->assertInstanceOf(MetricsEndpoint::class, $endpoint);
    }

    #[Test]
    public function constructor_accepts_custom_collector(): void
    {
        $collector = new MetricsCollector($this->tempFile);
        $endpoint = new MetricsEndpoint($collector);
        
        $this->assertInstanceOf(MetricsEndpoint::class, $endpoint);
    }

    #[Test]
    public function authorize_allows_localhost_ipv4(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $endpoint = new MetricsEndpoint();
        
        $this->assertTrue($endpoint->authorize());
    }

    #[Test]
    public function authorize_allows_localhost_ipv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = '::1';
        $endpoint = new MetricsEndpoint();
        
        $this->assertTrue($endpoint->authorize());
    }

    #[Test]
    public function authorize_denies_remote_without_key(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $endpoint = new MetricsEndpoint();
        
        $this->assertFalse($endpoint->authorize());
    }

    #[Test]
    public function authorize_allows_remote_with_valid_api_key(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_ENV['METRICS_API_KEY'] = 'secret123';
        $_SERVER['HTTP_X_METRICS_KEY'] = 'secret123';
        
        $endpoint = new MetricsEndpoint();
        
        $this->assertTrue($endpoint->authorize());
    }

    #[Test]
    public function authorize_allows_remote_with_query_key(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_ENV['METRICS_API_KEY'] = 'secret123';
        $_GET['key'] = 'secret123';
        
        $endpoint = new MetricsEndpoint();
        
        $this->assertTrue($endpoint->authorize());
    }

    #[Test]
    public function authorize_denies_remote_with_invalid_key(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_ENV['METRICS_API_KEY'] = 'secret123';
        $_SERVER['HTTP_X_METRICS_KEY'] = 'wrongkey';
        
        $endpoint = new MetricsEndpoint();
        
        $this->assertFalse($endpoint->authorize());
    }

    #[Test]
    public function handle_outputs_prometheus_format_by_default(): void
    {
        $collector = new MetricsCollector($this->tempFile);
        $endpoint = new MetricsEndpoint($collector);
        
        $_GET['format'] = 'prometheus';
        
        ob_start();
        @$endpoint->handle(); // Suppress header warning in CLI
        $output = ob_get_clean();
        
        $this->assertStringContainsString('app_requests_total', $output);
        $this->assertStringContainsString('# HELP', $output);
        $this->assertStringContainsString('# TYPE', $output);
    }

    #[Test]
    public function handle_outputs_json_format_when_requested(): void
    {
        $collector = new MetricsCollector($this->tempFile);
        $endpoint = new MetricsEndpoint($collector);
        
        $_GET['format'] = 'json';
        
        ob_start();
        @$endpoint->handle(); // Suppress header warning in CLI
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('total_requests', $decoded);
    }

    #[Test]
    public function handle_defaults_to_prometheus_without_format(): void
    {
        $collector = new MetricsCollector($this->tempFile);
        $endpoint = new MetricsEndpoint($collector);
        
        unset($_GET['format']);
        
        ob_start();
        @$endpoint->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('app_requests_total', $output);
        $this->assertStringContainsString('# TYPE', $output);
    }

    #[Test]
    public function prometheus_output_includes_all_metrics(): void
    {
        $collector = new MetricsCollector($this->tempFile);
        $endpoint = new MetricsEndpoint($collector);
        
        $_GET['format'] = 'prometheus';
        
        ob_start();
        @$endpoint->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('app_requests_total', $output);
        $this->assertStringContainsString('app_requests_current_hour', $output);
        $this->assertStringContainsString('app_errors_current_hour', $output);
        $this->assertStringContainsString('app_response_time_avg_ms', $output);
        $this->assertStringContainsString('app_response_time_max_ms', $output);
        $this->assertStringContainsString('app_db_queries_total', $output);
        $this->assertStringContainsString('app_db_query_time_avg_ms', $output);
        $this->assertStringContainsString('app_cache_hits_total', $output);
        $this->assertStringContainsString('app_cache_misses_total', $output);
        $this->assertStringContainsString('app_cache_hit_ratio', $output);
    }

    #[Test]
    public function prometheus_output_includes_status_codes_when_present(): void
    {
        $collector = new MetricsCollector($this->tempFile);
        // Record some requests to generate status codes
        $collector->recordRequest('GET', '/test', 200, 0.1);
        $collector->recordRequest('GET', '/notfound', 404, 0.1);
        
        $endpoint = new MetricsEndpoint($collector);
        $_GET['format'] = 'prometheus';
        
        ob_start();
        @$endpoint->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('app_http_responses_total', $output);
        $this->assertStringContainsString('status="200"', $output);
        $this->assertStringContainsString('status="404"', $output);
    }

    #[Test]
    public function json_output_is_valid_json(): void
    {
        $collector = new MetricsCollector($this->tempFile);
        $endpoint = new MetricsEndpoint($collector);
        
        $_GET['format'] = 'json';
        
        ob_start();
        @$endpoint->handle();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertNotNull($decoded);
        $this->assertArrayHasKey('current_hour', $decoded);
        $this->assertArrayHasKey('queries', $decoded);
        $this->assertArrayHasKey('cache', $decoded);
    }
}
