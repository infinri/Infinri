<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Middleware\Concerns;

use App\Core\Contracts\Http\RequestInterface;

/**
 * Checks Request Type Trait
 * 
 * Common methods for determining request type (JSON/API vs web).
 * Used by authentication middleware to determine response format.
 */
trait ChecksRequestType
{
    /**
     * Determine if the request expects a JSON response
     */
    protected function expectsJson(RequestInterface $request): bool
    {
        $accept = $request->header('Accept', '');

        return str_contains($accept, 'application/json')
            || str_contains($accept, '*/*')
            || $this->isApiRequest($request);
    }

    /**
     * Determine if this is an API request
     */
    protected function isApiRequest(RequestInterface $request): bool
    {
        return str_starts_with($request->getUri(), '/api/');
    }
}
