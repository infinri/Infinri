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
namespace App\Core\Http;

use App\Core\Application;
use App\Core\Support\HealthCheck;

/**
 * Health Check Endpoint Handler
 * 
 * Handles /health endpoint requests for system health monitoring.
 * Can be used standalone or integrated with the router.
 */
class HealthEndpoint
{
    /**
     * Handle the /health request
     *
     * @param Application $app
     * @param bool $terminate Whether to call exit() after response
     * @return array{statusCode: int, headers: array<string, string>, body: string}
     */
    public static function handle(Application $app, bool $terminate = true): array
    {
        $response = self::buildResponse($app);
        
        // Send headers
        foreach ($response['headers'] as $name => $value) {
            header("{$name}: {$value}");
        }
        
        // Handle OPTIONS preflight
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            if ($terminate) {
                exit;
            }
            return ['statusCode' => 204, 'headers' => $response['headers'], 'body' => ''];
        }
        
        http_response_code($response['statusCode']);
        echo $response['body'];
        
        if ($terminate) {
            exit;
        }
        
        return $response;
    }

    /**
     * Build response without sending (for testing)
     *
     * @param Application $app
     * @return array{statusCode: int, headers: array<string, string>, body: string}
     */
    public static function buildResponse(Application $app): array
    {
        $healthCheck = new HealthCheck($app);
        $health = $healthCheck->check();
        
        $statusCode = self::getStatusCode($health['status']);
        
        return [
            'statusCode' => $statusCode,
            'headers' => self::getHeaders(),
            'body' => $healthCheck->toJson(),
        ];
    }

    /**
     * Get HTTP status code for health status
     *
     * @param string $status
     * @return int
     */
    public static function getStatusCode(string $status): int
    {
        return match($status) {
            'healthy' => 200,
            'degraded' => 200, // Still operational
            'critical' => 503, // Service unavailable
            default => 200,
        };
    }

    /**
     * Get response headers
     *
     * @return array<string, string>
     */
    public static function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        ];
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
