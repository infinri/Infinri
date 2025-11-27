<?php

declare(strict_types=1);

namespace App\Core\Contracts\Http;

use Closure;

/**
 * Middleware Interface
 * 
 * Contract for HTTP middleware
 */
interface MiddlewareInterface
{
    /**
     * Handle an incoming request
     *
     * @param RequestInterface $request The incoming request
     * @param Closure $next The next middleware in the pipeline
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, Closure $next): ResponseInterface;
}
