<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Controllers\Concerns;

use App\Core\Contracts\Http\RequestInterface;

/**
 * Detects Auth Context Trait
 * 
 * Provides context detection (customer vs admin) based on:
 * 1. Domain/subdomain (preferred for security - hides admin from bots)
 * 2. Route prefix fallback (for development/testing)
 * 
 * Enables single controller to handle both contexts with config-driven behavior.
 */
trait DetectsAuthContext
{
    /**
     * Cached context for current request
     */
    protected ?string $currentContext = null;

    /**
     * Get the authentication context from request
     * 
     * Detection priority:
     * 1. Domain match (ADMIN_DOMAIN env var) - most secure
     * 2. Route prefix match (/admin/) - fallback for dev/testing
     * 
     * @return string 'customer' or 'admin'
     */
    protected function getContext(RequestInterface $request): string
    {
        if ($this->currentContext !== null) {
            return $this->currentContext;
        }

        // Priority 1: Check domain-based detection (security best practice)
        $adminDomain = env('ADMIN_DOMAIN', '');
        if ($adminDomain !== '') {
            $currentHost = $this->getRequestHost($request);
            
            // Match exact domain or subdomain pattern
            if ($this->isAdminDomain($currentHost, $adminDomain)) {
                $this->currentContext = 'admin';
                return 'admin';
            }
        }

        // Priority 2: Fallback to path prefix detection (dev/testing)
        $path = $request->path() ?? $request->getUri();
        $contexts = config('auth.contexts', []);

        foreach ($contexts as $name => $config) {
            $prefix = $config['prefix'] ?? '';
            
            if ($prefix !== '' && str_starts_with(ltrim($path, '/'), $prefix . '/')) {
                $this->currentContext = $name;
                return $name;
            }
        }

        // Default to customer
        $this->currentContext = 'customer';
        return 'customer';
    }

    /**
     * Get the current request host
     */
    protected function getRequestHost(RequestInterface $request): string
    {
        // Try request method first, then fallback to server vars
        if (method_exists($request, 'getHost')) {
            return $request->getHost();
        }
        
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    }

    /**
     * Check if current host matches admin domain configuration
     * 
     * Supports:
     * - Exact match: "secretadmin.example.com"
     * - Subdomain only: "secretadmin" (matched against any base domain)
     */
    protected function isAdminDomain(string $currentHost, string $adminDomain): bool
    {
        $currentHost = strtolower($currentHost);
        $adminDomain = strtolower($adminDomain);

        // Remove port if present
        $currentHost = explode(':', $currentHost)[0];

        // Exact match (full domain specified)
        if ($currentHost === $adminDomain) {
            return true;
        }

        // Subdomain match (just subdomain name specified)
        // e.g., ADMIN_DOMAIN=secretadmin matches secretadmin.example.com
        if (!str_contains($adminDomain, '.')) {
            // Admin domain is just a subdomain name
            if (str_starts_with($currentHost, $adminDomain . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get context-specific configuration value
     */
    protected function contextConfig(RequestInterface $request, string $key, mixed $default = null): mixed
    {
        $context = $this->getContext($request);
        return config("auth.contexts.{$context}.{$key}", $default);
    }

    /**
     * Check if current context allows registration
     */
    protected function allowsRegistration(RequestInterface $request): bool
    {
        return (bool) $this->contextConfig($request, 'allow_registration', true);
    }

    /**
     * Check if current context allows remember me
     */
    protected function allowsRemember(RequestInterface $request): bool
    {
        return (bool) $this->contextConfig($request, 'allow_remember', true);
    }

    /**
     * Check if current context allows password reset
     */
    protected function allowsPasswordReset(RequestInterface $request): bool
    {
        return (bool) $this->contextConfig($request, 'allow_password_reset', true);
    }

    /**
     * Check if current context requires 2FA
     */
    protected function requires2FA(RequestInterface $request): bool
    {
        return (bool) $this->contextConfig($request, 'require_2fa', false);
    }

    /**
     * Get context-specific redirect URL
     */
    protected function contextRedirect(RequestInterface $request, string $type): string
    {
        $contextRedirects = $this->contextConfig($request, 'redirects', []);
        
        return $contextRedirects[$type] 
            ?? config("auth.redirects.{$type}", '/');
    }

    /**
     * Get rate limit attempts for current context
     */
    protected function getRateLimitAttempts(RequestInterface $request): int
    {
        return (int) $this->contextConfig($request, 'rate_limit_attempts', 5);
    }

    /**
     * Get required roles for current context (admin only)
     */
    protected function getRequiredRoles(RequestInterface $request): array
    {
        return $this->contextConfig($request, 'roles', []);
    }

    /**
     * Check if this is an admin context
     */
    protected function isAdminContext(RequestInterface $request): bool
    {
        return $this->getContext($request) === 'admin';
    }

    /**
     * Check if this is a customer context
     */
    protected function isCustomerContext(RequestInterface $request): bool
    {
        return $this->getContext($request) === 'customer';
    }

    /**
     * Get view data with context information for templates
     */
    protected function getContextViewData(RequestInterface $request): array
    {
        $context = $this->getContext($request);
        
        return [
            'context' => $context,
            'isAdmin' => $context === 'admin',
            'isCustomer' => $context === 'customer',
            'allowRegistration' => $this->allowsRegistration($request),
            'allowRemember' => $this->allowsRemember($request),
            'allowPasswordReset' => $this->allowsPasswordReset($request),
            'loginUrl' => $this->contextRedirect($request, 'login'),
            'homeUrl' => $this->contextRedirect($request, 'home'),
        ];
    }
}
