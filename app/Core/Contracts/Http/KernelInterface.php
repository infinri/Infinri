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

/**
 * HTTP Kernel Interface
 *
 * Contract for HTTP request lifecycle management
 */
interface KernelInterface
{
    /**
     * Handle an incoming HTTP request
     */
    public function handle(RequestInterface $request): ResponseInterface;

    /**
     * Perform any final actions for the request lifecycle
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): void;

    /**
     * Get the application instance
     */
    public function getApplication(): mixed;
}
