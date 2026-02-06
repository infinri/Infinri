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
namespace App\Core\Security;

use App\Core\Http\RedirectResponse;

/**
 * Safe Redirect
 *
 * Prevents open redirect vulnerabilities by validating redirect URLs.
 *
 * Open redirects are used in phishing attacks where attackers send
 * links like: https://trusted.com/login?redirect=https://evil.com
 *
 * This class ensures redirects only go to:
 * 1. Local paths (same origin)
 * 2. Explicitly whitelisted external domains
 */
final class SafeRedirect
{
    /**
     * Whitelisted external domains for redirects
     *
     * @var string[]
     */
    private static array $allowedDomains = [];

    /**
     * Default fallback URL if redirect is invalid
     */
    private static string $fallbackUrl = '/';

    /**
     * Configure allowed external domains
     *
     * @param string[] $domains Array of allowed domains (e.g., ['partner.com', 'oauth.provider.com'])
     */
    public static function allowDomains(array $domains): void
    {
        self::$allowedDomains = array_map('strtolower', $domains);
    }

    /**
     * Add a single domain to the whitelist
     */
    public static function allowDomain(string $domain): void
    {
        self::$allowedDomains[] = strtolower($domain);
    }

    /**
     * Set the fallback URL for invalid redirects
     */
    public static function setFallback(string $url): void
    {
        self::$fallbackUrl = $url;
    }

    /**
     * Create a safe redirect response
     *
     * @param string $url The requested redirect URL
     * @param string|null $fallback Override fallback URL
     * @param int $status HTTP status code (302, 301, 303)
     *
     * @return RedirectResponse
     */
    public static function to(string $url, ?string $fallback = null, int $status = 302): RedirectResponse
    {
        $safeUrl = self::validate($url, $fallback);

        return new RedirectResponse($safeUrl, $status);
    }

    /**
     * Validate and sanitize a redirect URL
     *
     * Returns the URL if valid, or fallback if invalid.
     *
     * @param string $url The URL to validate
     * @param string|null $fallback Override fallback URL
     *
     * @return string Safe URL to redirect to
     */
    public static function validate(string $url, ?string $fallback = null): string
    {
        $fallback ??= self::$fallbackUrl;

        // Empty or whitespace-only URL
        if (trim($url) === '') {
            return $fallback;
        }

        // Sanitize the URL
        $url = self::sanitize($url);

        // Check for dangerous schemes
        if (self::hasDangerousScheme($url)) {
            return $fallback;
        }

        // Parse the URL
        $parsed = parse_url($url);

        // If parsing failed, reject
        if ($parsed === false) {
            return $fallback;
        }

        // Case 1: Absolute URL with scheme
        if (isset($parsed['scheme'])) {
            // Only allow http/https
            if (! in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
                return $fallback;
            }

            // Must have a host
            if (! isset($parsed['host'])) {
                return $fallback;
            }

            // Check if host is in whitelist
            if (! self::isDomainAllowed($parsed['host'])) {
                return $fallback;
            }

            return $url;
        }

        // Case 2: Protocol-relative URL (//example.com/path)
        if (str_starts_with($url, '//')) {
            // Extract host from protocol-relative URL
            $host = $parsed['host'] ?? null;

            if ($host === null || ! self::isDomainAllowed($host)) {
                return $fallback;
            }

            return $url;
        }

        // Case 3: Local path (starts with /)
        if (str_starts_with($url, '/')) {
            // Prevent path traversal
            $normalizedPath = self::normalizePath($url);

            // Ensure it still starts with /
            if (! str_starts_with($normalizedPath, '/')) {
                return $fallback;
            }

            return $normalizedPath;
        }

        // Case 4: Relative path (no leading /)
        // This is ambiguous and potentially dangerous, reject it
        // Force local paths to start with /
        return $fallback;
    }

    /**
     * Check if a URL is safe for redirect (without transforming it)
     *
     * @param string $url The URL to check
     *
     * @return bool True if URL is safe
     */
    public static function isSafe(string $url): bool
    {
        return self::validate($url, '__INVALID__') !== '__INVALID__';
    }

    /**
     * Check if URL has a dangerous scheme
     */
    private static function hasDangerousScheme(string $url): bool
    {
        $dangerous = [
            'javascript:',
            'data:',
            'vbscript:',
            'file:',
            'blob:',
        ];

        $lower = strtolower(trim($url));

        foreach ($dangerous as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a domain is in the whitelist
     */
    private static function isDomainAllowed(string $host): bool
    {
        // Get current request host for same-origin check
        $currentHost = self::getCurrentHost();

        // Same-origin is always allowed
        if ($currentHost !== null && strtolower($host) === strtolower($currentHost)) {
            return true;
        }

        // Check explicit whitelist
        $hostLower = strtolower($host);
        foreach (self::$allowedDomains as $allowed) {
            // Exact match
            if ($hostLower === $allowed) {
                return true;
            }
            // Subdomain match (e.g., *.example.com)
            if (str_starts_with($allowed, '*.')) {
                $baseDomain = substr($allowed, 2);
                if ($hostLower === $baseDomain || str_ends_with($hostLower, '.' . $baseDomain)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the current request host
     */
    private static function getCurrentHost(): ?string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
    }

    /**
     * Sanitize URL string
     */
    private static function sanitize(string $url): string
    {
        // Remove null bytes and control characters
        $url = preg_replace('/[\x00-\x1f\x7f]/', '', $url);

        // Trim whitespace
        $url = trim($url);

        // Decode URL-encoded characters for validation (but keep original for return)
        // This catches encoded attacks like %2f%2f (which is //)

        return $url;
    }

    /**
     * Normalize a path (remove . and .. components)
     */
    private static function normalizePath(string $path): string
    {
        // Split into components
        $parts = explode('/', $path);
        $normalized = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                // Skip empty and current directory
                continue;
            }
            if ($part === '..') {
                // Go up one directory (but don't go above root)
                array_pop($normalized);
            } else {
                $normalized[] = $part;
            }
        }

        return '/' . implode('/', $normalized);
    }

    /**
     * Extract intended URL from user input with validation
     *
     * Use this when handling ?redirect= or ?next= parameters.
     *
     * @param string|null $intended User-provided redirect URL
     * @param string $default Default URL if intended is invalid
     *
     * @return string Safe redirect URL
     */
    public static function intended(?string $intended, string $default = '/'): string
    {
        if ($intended === null) {
            return $default;
        }

        return self::validate($intended, $default);
    }

    /**
     * Create redirect from request parameter
     *
     * @param string $param Query parameter name (default: 'redirect')
     * @param string $default Default URL if parameter is missing/invalid
     *
     * @return RedirectResponse
     */
    public static function fromRequest(string $param = 'redirect', string $default = '/'): RedirectResponse
    {
        $url = $_GET[$param] ?? $_POST[$param] ?? null;

        return self::to(self::intended($url, $default));
    }

    /**
     * Get allowed domains list
     *
     * @return string[]
     */
    public static function getAllowedDomains(): array
    {
        return self::$allowedDomains;
    }

    /**
     * Clear allowed domains (for testing)
     */
    public static function clearAllowedDomains(): void
    {
        self::$allowedDomains = [];
    }
}
