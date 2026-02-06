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
 * Require 2FA Middleware
 * 
 * Ensures the authenticated user has completed 2FA (if enabled).
 */
class Require2FA extends AbstractAuthMiddleware
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $user = $this->auth->user();
        if ($user instanceof AuthenticatableInterface
            && $user->hasTwoFactorEnabled()
            && !session()->get('auth.2fa_confirmed', false)
        ) {
            if ($this->expectsJson($request)) {
                return $this->jsonError('Two-factor authentication required.', 403, '2FA_REQUIRED');
            }
            return safe_redirect('/two-factor-challenge');
        }
        return $next($request);
    }
}
