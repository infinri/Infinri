<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Http;

/**
 * HTTP Cookie
 * 
 * Represents a cookie to be sent with the response.
 * Provides a fluent interface for building cookies with secure defaults.
 */
class Cookie
{
    public readonly string $name;
    public readonly string $value;
    public readonly int $expires;
    public readonly string $path;
    public readonly string $domain;
    public readonly bool $secure;
    public readonly bool $httpOnly;
    public readonly string $sameSite;

    /**
     * Create a new cookie instance
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $minutes Minutes until expiration (0 = session cookie)
     * @param string $path Cookie path
     * @param string $domain Cookie domain
     * @param bool $secure HTTPS only
     * @param bool $httpOnly HTTP only (no JavaScript access)
     * @param string $sameSite SameSite attribute (Strict, Lax, None)
     */
    public function __construct(
        string $name,
        string $value = '',
        int $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = true,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->expires = $minutes !== 0 ? time() + ($minutes * 60) : 0;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
    }

    /**
     * Create a cookie that lasts forever (5 years)
     */
    public static function forever(string $name, string $value, string $path = '/'): static
    {
        return new static($name, $value, 60 * 24 * 365 * 5, $path);
    }

    /**
     * Create a cookie that expires with the browser session
     */
    public static function session(string $name, string $value, string $path = '/'): static
    {
        return new static($name, $value, 0, $path);
    }

    /**
     * Create a cookie that deletes an existing cookie
     */
    public static function forget(string $name, string $path = '/'): static
    {
        return new static($name, '', -2628000, $path); // Expired 5 years ago
    }

    /**
     * Create from array (e.g., from config)
     */
    public static function make(string $name, string $value, array $options = []): static
    {
        return new static(
            $name,
            $value,
            $options['minutes'] ?? 0,
            $options['path'] ?? '/',
            $options['domain'] ?? '',
            $options['secure'] ?? true,
            $options['httpOnly'] ?? true,
            $options['sameSite'] ?? 'Lax'
        );
    }

    /**
     * Send the cookie (using setcookie)
     */
    public function send(): bool
    {
        return setcookie($this->name, $this->value, [
            'expires' => $this->expires,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ]);
    }

    /**
     * Get the cookie as a Set-Cookie header value
     */
    public function toHeaderValue(): string
    {
        $parts = [
            urlencode($this->name) . '=' . urlencode($this->value),
        ];

        if ($this->expires !== 0) {
            $parts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $this->expires);
            $parts[] = 'Max-Age=' . max(0, $this->expires - time());
        }

        if ($this->path !== '') {
            $parts[] = 'Path=' . $this->path;
        }

        if ($this->domain !== '') {
            $parts[] = 'Domain=' . $this->domain;
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($this->sameSite !== '') {
            $parts[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $parts);
    }

    /**
     * Check if the cookie is expired
     */
    public function isExpired(): bool
    {
        return $this->expires !== 0 && $this->expires < time();
    }

    /**
     * Check if this is a session cookie (expires when browser closes)
     */
    public function isSessionCookie(): bool
    {
        return $this->expires === 0;
    }

    /**
     * Get cookie value for use in requests (name=value)
     */
    public function __toString(): string
    {
        return $this->name . '=' . $this->value;
    }

    // ==================== Cookie Prefixes (Advanced Security) ====================

    /**
     * Create a __Host- prefixed cookie (most secure)
     * 
     * Requirements enforced by browsers:
     * - Must have Secure flag
     * - Must NOT have Domain attribute
     * - Path must be /
     * 
     * Use for: session cookies, auth tokens
     */
    public static function host(string $name, string $value, int $minutes = 0): static
    {
        return new static(
            '__Host-' . $name,
            $value,
            $minutes,
            '/',      // Must be /
            '',       // Must be empty (no domain)
            true,     // Must be secure
            true,
            'Lax'
        );
    }

    /**
     * Create a __Secure- prefixed cookie
     * 
     * Requirements enforced by browsers:
     * - Must have Secure flag
     * 
     * Use for: sensitive cookies that need domain/path flexibility
     */
    public static function secure(string $name, string $value, int $minutes = 0, string $path = '/'): static
    {
        return new static(
            '__Secure-' . $name,
            $value,
            $minutes,
            $path,
            '',
            true,     // Must be secure
            true,
            'Strict'  // Stricter for secure cookies
        );
    }

    /**
     * Check if cookie uses __Host- prefix
     */
    public function isHostPrefixed(): bool
    {
        return str_starts_with($this->name, '__Host-');
    }

    /**
     * Check if cookie uses __Secure- prefix
     */
    public function isSecurePrefixed(): bool
    {
        return str_starts_with($this->name, '__Secure-');
    }

    /**
     * Validate that prefixed cookies meet browser requirements
     * 
     * @throws \InvalidArgumentException if validation fails
     */
    public function validate(): bool
    {
        if ($this->isHostPrefixed()) {
            if (!$this->secure) {
                throw new \InvalidArgumentException(
                    "__Host- cookies must have Secure flag"
                );
            }
            if ($this->domain !== '') {
                throw new \InvalidArgumentException(
                    "__Host- cookies must not have Domain attribute"
                );
            }
            if ($this->path !== '/') {
                throw new \InvalidArgumentException(
                    "__Host- cookies must have Path=/"
                );
            }
        }

        if ($this->isSecurePrefixed() && !$this->secure) {
            throw new \InvalidArgumentException(
                "__Secure- cookies must have Secure flag"
            );
        }

        return true;
    }

    /**
     * Get the cookie name without prefix
     */
    public function getUnprefixedName(): string
    {
        if ($this->isHostPrefixed()) {
            return substr($this->name, 7); // Remove __Host-
        }
        if ($this->isSecurePrefixed()) {
            return substr($this->name, 9); // Remove __Secure-
        }
        return $this->name;
    }
}
