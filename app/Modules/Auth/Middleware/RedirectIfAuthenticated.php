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
 * Redirect If Authenticated (Guest) Middleware
 * 
 * Redirects authenticated users away from guest-only pages.
 */
class RedirectIfAuthenticated extends AbstractAuthMiddleware
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $guards = $request->getAttribute('middleware.guest.guards', []) ?: [null];
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return safe_redirect(config('auth.redirects.home', '/dashboard'));
            }
        }
        return $next($request);
    }
}
