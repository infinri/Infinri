<?php

declare(strict_types=1);

namespace App\Core\Routing\Exceptions;

use RuntimeException;

/**
 * Route Not Found Exception
 * 
 * Thrown when no route matches the request
 */
class RouteNotFoundException extends RuntimeException
{
    /**
     * The requested path
     */
    protected string $path;

    /**
     * The HTTP method
     */
    protected string $method;

    /**
     * Create a new exception instance
     */
    public function __construct(string $path, string $method)
    {
        $this->path = $path;
        $this->method = $method;
        
        parent::__construct(
            sprintf('No route found for %s %s', $method, $path),
            404
        );
        
        $this->logException();
    }

    /**
     * Log the route not found event
     */
    protected function logException(): void
    {
        if (function_exists('logger')) {
            try {
                logger()->warning('Route not found', [
                    'path' => $this->path,
                    'method' => $this->method,
                ]);
            } catch (\Throwable $e) {
                // Silently fail if logging not available
            }
        }
    }

    /**
     * Get the requested path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
