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

use App\Core\Support\Str;

/**
 * CSRF Protection
 *
 * Handles CSRF token generation and verification.
 */
class Csrf
{
    /**
     * Session key for CSRF token
     */
    protected const SESSION_KEY = '_csrf_token';

    /**
     * Token lifetime in seconds (default: 2 hours)
     */
    protected int $lifetime;

    public function __construct(int $lifetime = 7200)
    {
        $this->lifetime = $lifetime;
    }

    /**
     * Generate or retrieve CSRF token
     */
    public function token(): string
    {
        $this->ensureSessionStarted();

        if (! isset($_SESSION[self::SESSION_KEY]) || $this->isExpired()) {
            $this->regenerate();
        }

        return $_SESSION[self::SESSION_KEY]['token'];
    }

    /**
     * Verify a CSRF token
     */
    public function verify(string $token): bool
    {
        $this->ensureSessionStarted();

        if (! isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY]['token'], $token);
    }

    /**
     * Regenerate the CSRF token
     */
    public function regenerate(): string
    {
        $this->ensureSessionStarted();

        $token = Str::randomHex(32);

        $_SESSION[self::SESSION_KEY] = [
            'token' => $token,
            'expires' => time() + $this->lifetime,
        ];

        return $token;
    }

    /**
     * Get the token field HTML
     */
    public function field(): string
    {
        return sprintf(
            '<input type="hidden" name="csrf_token" value="%s">',
            htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Get the token meta tag HTML
     */
    public function meta(): string
    {
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Check if the token is expired
     */
    protected function isExpired(): bool
    {
        if (! isset($_SESSION[self::SESSION_KEY]['expires'])) {
            return true;
        }

        return $_SESSION[self::SESSION_KEY]['expires'] < time();
    }

    /**
     * Ensure session is started
     */
    protected function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
