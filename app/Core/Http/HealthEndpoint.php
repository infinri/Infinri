<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Application;
use App\Core\Support\HealthCheck;

/**
 * Health Check Endpoint Handler
 * 
 * Simple handler for /health endpoint (Phase 1)
 * Will be replaced by proper routing in Phase 2
 */
class HealthEndpoint
{
    /**
     * Handle the /health request
     *
     * @param Application $app
     * @return void
     */
    public static function handle(Application $app): void
    {
        $healthCheck = new HealthCheck($app);
        
        // Set JSON header
        header('Content-Type: application/json');
        
        // Set CORS headers for monitoring tools
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        
        // Handle OPTIONS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        
        // Get health status
        $health = $healthCheck->check();
        
        // Set appropriate HTTP status code
        $statusCode = match($health['status']) {
            'healthy' => 200,
            'degraded' => 200, // Still operational
            'critical' => 503, // Service unavailable
            default => 200,
        };
        
        http_response_code($statusCode);
        
        // Output JSON
        echo $healthCheck->toJson();
        exit;
    }

    /**
     * Check if current request is for /health endpoint
     *
     * @return bool
     */
    public static function isHealthCheckRequest(): bool
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        return $path === '/health' || $path === '/health/';
    }
}
