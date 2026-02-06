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
     *
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, Closure $next): ResponseInterface;
}
