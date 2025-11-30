<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Controllers;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\Controller;
use App\Modules\Auth\Controllers\Concerns\DetectsAuthContext;
use App\Modules\Auth\Controllers\Concerns\RespondsWithJson;
use App\Modules\Auth\Services\AuthManager;

/**
 * Logout Controller
 * 
 * Handles user logout with session cleanup.
 * Context-aware: redirects to appropriate login page.
 */
class LogoutController extends Controller
{
    use DetectsAuthContext;
    use RespondsWithJson;
    protected AuthManager $auth;

    public function __construct(AuthManager $auth)
    {
        parent::__construct();
        $this->auth = $auth;
    }

    /**
     * Log the user out
     */
    public function logout(RequestInterface $request): ResponseInterface
    {
        $this->auth->logout();

        // Clear all session data
        session()->flush();
        session()->regenerate(true);

        if ($this->expectsJson($request)) {
            return $this->success(null, 'Logged out successfully.');
        }

        return safe_redirect($this->contextRedirect($request, 'logout'));
    }
}
