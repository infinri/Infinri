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
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use Closure;

/**
 * Ensure Email Is Verified Middleware
 * 
 * Ensures the authenticated user has verified their email address.
 */
class EnsureEmailIsVerified extends AbstractAuthMiddleware
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $user = $this->auth->user();
        if ($user instanceof AuthenticatableInterface && !$user->hasVerifiedEmail()) {
            if ($this->expectsJson($request)) {
                return $this->jsonError('Your email address is not verified.', 403, 'EMAIL_NOT_VERIFIED');
            }
            return safe_redirect('/email/verify');
        }
        return $next($request);
    }
}
