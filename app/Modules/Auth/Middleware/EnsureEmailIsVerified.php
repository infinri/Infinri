<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Middleware;

use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\JsonResponse;
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Middleware\Concerns\ChecksRequestType;
use App\Modules\Auth\Services\AuthManager;
use Closure;

/**
 * Ensure Email Is Verified Middleware
 * 
 * Ensures the authenticated user has verified their email address.
 * Alias: verified
 */
class EnsureEmailIsVerified implements MiddlewareInterface
{
    use ChecksRequestType;
    /**
     * Auth manager
     */
    protected AuthManager $auth;

    /**
     * Create a new middleware instance
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $user = $this->auth->user();

        // Check if user exists and has verified email
        if ($user instanceof AuthenticatableInterface && !$user->hasVerifiedEmail()) {
            return $this->unverified($request);
        }

        return $next($request);
    }

    /**
     * Handle unverified user
     */
    protected function unverified(RequestInterface $request): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Your email address is not verified.',
                'code' => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        // Redirect to verification notice page using Core's safe_redirect
        return safe_redirect('/email/verify');
    }

}
