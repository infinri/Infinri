<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Middleware;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use Closure;

/**
 * Authenticate Middleware
 * 
 * Ensures the user is authenticated before accessing the route.
 * Redirects to login for web requests, returns 401 for API requests.
 */
class Authenticate extends AbstractAuthMiddleware
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        // Check if specified guards are authenticated
        $guards = $this->getGuards($request);

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                // Set the active guard for this request
                $this->auth->setDefaultGuard($guard);
                return $next($request);
            }
        }

        // Not authenticated - handle unauthenticated request
        return $this->unauthenticated($request, $guards);
    }

    /**
     * Get the guards to check from the request
     *
     * @return string[]
     */
    protected function getGuards(RequestInterface $request): array
    {
        // Middleware can be invoked with guards: auth:web,api
        // The guards are passed through request attributes
        $guards = $request->getAttribute('middleware.auth.guards', []);

        if (empty($guards)) {
            // Default guard
            return [$this->auth->getDefaultGuard()];
        }

        return $guards;
    }

    protected function unauthenticated(RequestInterface $request, array $guards): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->jsonError('Unauthenticated.', 401);
        }
        session()->set('url.intended', $request->getUri());
        return safe_redirect(config('auth.redirects.login', '/login'));
    }
}
