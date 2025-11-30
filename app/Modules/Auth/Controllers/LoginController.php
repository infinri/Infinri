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
use App\Core\Security\SafeRedirect;
use App\Modules\Auth\Controllers\Concerns\DetectsAuthContext;
use App\Modules\Auth\Controllers\Concerns\RespondsWithJson;
use App\Modules\Auth\Security\LoginRateLimiter;
use App\Modules\Auth\Services\AuthManager;

/**
 * Login Controller
 * 
 * Handles user login with rate limiting and 2FA support.
 * Context-aware: adapts behavior for customer vs admin authentication.
 */
class LoginController extends Controller
{
    use DetectsAuthContext;
    use RespondsWithJson;
    protected AuthManager $auth;
    protected LoginRateLimiter $rateLimiter;

    public function __construct(AuthManager $auth, LoginRateLimiter $rateLimiter)
    {
        parent::__construct();
        $this->auth = $auth;
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Show login form
     * Passes context data to template for conditional rendering
     */
    public function showLoginForm(RequestInterface $request): ResponseInterface
    {
        return $this->view('auth.login', $this->getContextViewData($request));
    }

    /**
     * Handle login attempt
     */
    public function login(RequestInterface $request): ResponseInterface
    {
        $validated = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);

        $ip = $request->ip() ?? 'unknown';
        $email = $validated['email'];

        // Check rate limiting
        $rateCheck = $this->rateLimiter->check($ip, $email);
        if (!$rateCheck['allowed']) {
            return $this->loginFailed($request, $rateCheck);
        }

        // Attempt authentication
        // Remember me only allowed if context permits
        $remember = $this->allowsRemember($request) && (bool) ($validated['remember'] ?? false);
        
        if (!$this->auth->attempt($validated, $remember)) {
            // Record failed attempt
            $this->rateLimiter->recordFailure($ip, $email);
            return $this->loginFailed($request, [
                'reason' => 'invalid_credentials',
                'message' => 'These credentials do not match our records.',
            ]);
        }

        // Success - clear rate limits
        $this->rateLimiter->clearOnSuccess($ip, $email);

        // Check if user has required role for this context (admin only)
        $user = $this->auth->user();
        $requiredRoles = $this->getRequiredRoles($request);
        
        if (!empty($requiredRoles) && $user) {
            $hasRequiredRole = false;
            foreach ($requiredRoles as $role) {
                if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                    $hasRequiredRole = true;
                    break;
                }
            }
            
            if (!$hasRequiredRole) {
                $this->auth->logout();
                return $this->loginFailed($request, [
                    'reason' => 'unauthorized_role',
                    'message' => 'You do not have permission to access this area.',
                ]);
            }
        }

        // Check if 2FA is required (context or user setting)
        $requires2FA = $this->requires2FA($request) || 
            ($user && method_exists($user, 'hasTwoFactorEnabled') && $user->hasTwoFactorEnabled());
            
        if ($requires2FA && $user) {
            // Ensure 2FA is set up if required by context
            if ($this->requires2FA($request) && !$user->hasTwoFactorEnabled()) {
                session()->set('2fa.setup_required', true);
                session()->flash('warning', 'Two-factor authentication is required. Please set it up.');
                // Still log in but redirect to 2FA setup
                session()->regenerate(true);
                return safe_redirect('/two-factor');
            }
            
            // Store user ID in session for 2FA challenge
            session()->set('2fa.user_id', $user->getAuthIdentifier());
            session()->set('2fa.remember', $remember);
            session()->set('2fa.context', $this->getContext($request));
            
            // Logout until 2FA is completed
            $this->auth->logout();
            
            return $this->redirect2FA($request);
        }

        // Regenerate session on successful login
        session()->regenerate(true);

        return $this->redirectIntended($request);
    }

    /**
     * Handle failed login
     */
    protected function loginFailed(RequestInterface $request, array $context): ResponseInterface
    {
        $message = $context['message'] ?? $this->getRateLimitMessage($context['reason'] ?? '');

        if ($this->expectsJson($request)) {
            return $this->error($message, 422, [
                'email' => [$message],
                'retry_after' => $context['retry_after'] ?? null,
            ]);
        }

        // Flash error and redirect back to context-specific login
        session()->flash('error', $message);
        session()->flash('old', ['email' => $request->input('email')]);

        return safe_redirect($this->contextRedirect($request, 'login'));
    }

    /**
     * Redirect to 2FA challenge (context-aware)
     */
    protected function redirect2FA(RequestInterface $request): ResponseInterface
    {
        $prefix = $this->isAdminContext($request) ? '/admin' : '';
        
        if ($this->expectsJson($request)) {
            return $this->success(['requires_2fa' => true], 'Two-factor authentication required.');
        }

        return safe_redirect($prefix . '/two-factor-challenge');
    }

    /**
     * Redirect to intended URL after login (context-aware)
     * Uses Core's SafeRedirect::intended() for URL validation
     */
    protected function redirectIntended(RequestInterface $request): ResponseInterface
    {
        // Use context-specific home URL as default
        $default = $this->contextRedirect($request, 'home');
        $storedUrl = session('url.intended');
        session()->forget('url.intended');

        // Validate intended URL using Core's SafeRedirect
        $intended = SafeRedirect::intended($storedUrl, $default);

        if ($this->expectsJson($request)) {
            return $this->success([
                'user' => $this->auth->user(),
                'redirect' => $intended,
            ], 'Login successful.');
        }

        return safe_redirect($intended);
    }

    /**
     * Get human-readable rate limit message
     */
    protected function getRateLimitMessage(string $reason): string
    {
        return match ($reason) {
            'account_locked' => 'This account has been locked due to too many failed attempts. Please try again later.',
            'too_many_attempts_email' => 'Too many login attempts for this email. Please try again later.',
            'too_many_attempts_ip' => 'Too many login attempts from this IP address. Please try again later.',
            'too_many_attempts_combined' => 'Too many login attempts. Please try again later.',
            'unauthorized_role' => 'You do not have permission to access this area.',
            default => 'These credentials do not match our records.',
        };
    }
}
