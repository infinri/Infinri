<?php

declare(strict_types=1);

namespace App\Core\Routing\Exceptions;

use RuntimeException;

/**
 * Method Not Allowed Exception
 * 
 * Thrown when the HTTP method is not allowed for the matched route
 */
class MethodNotAllowedException extends RuntimeException
{
    /**
     * The requested path
     */
    protected string $path;

    /**
     * The HTTP method that was used
     */
    protected string $method;

    /**
     * The allowed HTTP methods
     */
    protected array $allowedMethods;

    /**
     * Create a new exception instance
     */
    public function __construct(string $path, string $method, array $allowedMethods = [])
    {
        $this->path = $path;
        $this->method = $method;
        $this->allowedMethods = $allowedMethods;
        
        $allowed = implode(', ', $allowedMethods);
        
        parent::__construct(
            sprintf('Method %s not allowed for %s. Allowed: %s', $method, $path, $allowed),
            405
        );
        
        $this->logException();
    }

    /**
     * Log the method not allowed event
     */
    protected function logException(): void
    {
        if (function_exists('logger')) {
            try {
                logger()->warning('Method not allowed', [
                    'path' => $this->path,
                    'method' => $this->method,
                    'allowed_methods' => $this->allowedMethods,
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
     * Get the HTTP method that was used
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the allowed HTTP methods
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
