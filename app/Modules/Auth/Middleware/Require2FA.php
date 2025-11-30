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
 * Require 2FA Middleware
 * 
 * Ensures the authenticated user has completed 2FA (if enabled).
 * Used on the 2FA challenge routes.
 */
class Require2FA implements MiddlewareInterface
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

        // If user has 2FA enabled but hasn't confirmed it this session
        if ($user instanceof AuthenticatableInterface
            && $user->hasTwoFactorEnabled()
            && !$this->hasPassed2FA()
        ) {
            return $this->redirect2FAChallenge($request);
        }

        return $next($request);
    }

    /**
     * Check if user has passed 2FA this session
     */
    protected function hasPassed2FA(): bool
    {
        return session()->get('auth.2fa_confirmed', false) === true;
    }

    /**
     * Redirect to 2FA challenge
     */
    protected function redirect2FAChallenge(RequestInterface $request): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Two-factor authentication required.',
                'code' => '2FA_REQUIRED',
            ], 403);
        }

        return safe_redirect('/two-factor-challenge');
    }

}
