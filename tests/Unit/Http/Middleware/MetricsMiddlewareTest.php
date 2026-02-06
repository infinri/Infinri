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
namespace Tests\Unit\Http\Middleware;

use App\Core\Http\Middleware\MetricsMiddleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Metrics\MetricsCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetricsMiddlewareTest extends TestCase
{
    #[Test]
    public function constructor_creates_collector_if_not_provided(): void
    {
        $middleware = new MetricsMiddleware();
        
        $this->assertInstanceOf(MetricsMiddleware::class, $middleware);
    }

    #[Test]
    public function constructor_accepts_collector(): void
    {
        $collector = new MetricsCollector();
        $middleware = new MetricsMiddleware($collector);
        
        $this->assertInstanceOf(MetricsMiddleware::class, $middleware);
    }

    #[Test]
    public function handle_calls_next_and_returns_response(): void
    {
        $collector = new MetricsCollector();
        $middleware = new MetricsMiddleware($collector);
        
        $request = Request::create('/test', 'GET');
        $response = new Response('OK', 200);
        
        $next = fn($req) => $response;
        
        $result = $middleware->handle($request, $next);
        
        $this->assertSame($response, $result);
    }

    #[Test]
    public function handle_records_metrics(): void
    {
        $tempFile = sys_get_temp_dir() . '/metrics_test_' . uniqid() . '.json';
        $collector = new MetricsCollector($tempFile);
        $middleware = new MetricsMiddleware($collector);
        
        $request = Request::create('/api/users', 'POST');
        $response = new Response('Created', 201);
        
        $next = fn($req) => $response;
        
        $middleware->handle($request, $next);
        
        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['total_requests']);
        
        @unlink($tempFile);
    }
}
