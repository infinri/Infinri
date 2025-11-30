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
use App\Modules\Auth\Services\AuthManager;
use Closure;

/**
 * Redirect If Authenticated (Guest) Middleware
 * 
 * Redirects authenticated users away from guest-only pages (login, register).
 * Alias: guest
 */
class RedirectIfAuthenticated implements MiddlewareInterface
{
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
        $guards = $this->getGuards($request);

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                // User is authenticated, redirect to home
                return $this->redirectToHome($request);
            }
        }

        // User is a guest, allow through
        return $next($request);
    }

    /**
     * Get the guards to check from the request
     *
     * @return string[]
     */
    protected function getGuards(RequestInterface $request): array
    {
        $guards = $request->getAttribute('middleware.guest.guards', []);

        if (empty($guards)) {
            return [null]; // Check default guard
        }

        return $guards;
    }

    /**
     * Redirect authenticated user to home
     * Uses Core's safe_redirect() helper
     */
    protected function redirectToHome(RequestInterface $request): ResponseInterface
    {
        return safe_redirect(config('auth.redirects.home', '/dashboard'));
    }
}
