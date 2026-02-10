<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */

// ==================== Security Helpers ====================

if (! function_exists('e')) {
    /**
     * Escape HTML special characters (shorthand for sanitize)
     *
     * @param string $value
     *
     * @return string
     */
    function e(string $value): string
    {
        return \App\Core\Security\Sanitizer::html($value);
    }
}

if (! function_exists('csrf_token')) {
    /**
     * Get the CSRF token
     *
     * @return string
     */
    function csrf_token(): string
    {
        return app(\App\Core\Security\Csrf::class)->token();
    }
}

if (! function_exists('csrf_field')) {
    /**
     * Get the CSRF hidden input field
     *
     * @return string
     */
    function csrf_field(): string
    {
        return app(\App\Core\Security\Csrf::class)->field();
    }
}

if (! function_exists('csrf_verify')) {
    /**
     * Verify a CSRF token
     *
     * @param string $token
     *
     * @return bool
     */
    function csrf_verify(string $token): bool
    {
        return app(\App\Core\Security\Csrf::class)->verify($token);
    }
}

if (! function_exists('rate_limit')) {
    /**
     * Check rate limit for a key
     *
     * @param string $key Unique identifier (e.g., IP address)
     * @param int $maxAttempts Maximum attempts allowed
     *
     * @return bool True if allowed, false if rate limited
     */
    function rate_limit(string $key, int $maxAttempts = 5): bool
    {
        return ! app(\App\Core\Security\RateLimiter::class)->tooManyAttempts($key, $maxAttempts);
    }
}

if (! function_exists('rate_limit_hit')) {
    /**
     * Record a rate limit hit
     *
     * @param string $key
     * @param int $decaySeconds
     *
     * @return int Current attempt count
     */
    function rate_limit_hit(string $key, int $decaySeconds = 300): int
    {
        return app(\App\Core\Security\RateLimiter::class)->hit($key, $decaySeconds);
    }
}

if (! function_exists('csp_nonce')) {
    /**
     * Get the CSP nonce for inline scripts/styles
     *
     * @return string|null
     */
    function csp_nonce(): ?string
    {
        $app = app();

        if ($app->bound('csp.nonce')) {
            return $app->make('csp.nonce');
        }

        return null;
    }
}

if (! function_exists('csp_nonce_attr')) {
    /**
     * Get the CSP nonce as an HTML attribute string
     *
     * @return string Empty string if no nonce, or ' nonce="..."'
     */
    function csp_nonce_attr(): string
    {
        $nonce = csp_nonce();

        return $nonce !== null ? ' nonce="' . e($nonce) . '"' : '';
    }
}

// ==================== Safe Redirect Helpers ====================

if (! function_exists('safe_redirect')) {
    /**
     * Create a safe redirect response (prevents open redirect attacks)
     *
     * @param string $url The requested redirect URL
     * @param string $fallback Fallback URL if requested URL is invalid
     * @param int $status HTTP status code
     *
     * @return \App\Core\Http\RedirectResponse
     */
    function safe_redirect(string $url, string $fallback = '/', int $status = 302): \App\Core\Http\RedirectResponse
    {
        return \App\Core\Security\SafeRedirect::to($url, $fallback, $status);
    }
}

if (! function_exists('safe_redirect_intended')) {
    /**
     * Redirect to the intended URL from request, with validation
     *
     * @param string $param Request parameter name (default: 'redirect')
     * @param string $default Default URL if parameter is missing/invalid
     *
     * @return \App\Core\Http\RedirectResponse
     */
    function safe_redirect_intended(string $param = 'redirect', string $default = '/'): \App\Core\Http\RedirectResponse
    {
        return \App\Core\Security\SafeRedirect::fromRequest($param, $default);
    }
}

if (! function_exists('is_safe_redirect_url')) {
    /**
     * Check if a URL is safe for redirect
     *
     * @param string $url The URL to check
     *
     * @return bool True if URL is safe
     */
    function is_safe_redirect_url(string $url): bool
    {
        return \App\Core\Security\SafeRedirect::isSafe($url);
    }
}
