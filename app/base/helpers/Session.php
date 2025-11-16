<?php
declare(strict_types=1);
/**
 * Session Helper
 *
 * Secure session management with CSRF protection
 *
 * @package App\Helpers
 */

namespace App\Helpers;

final class Session
{
    private static bool $started = false;

    /**
     * Start secure session
     *
     * @return void
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        // If session already active, just mark as started
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
            return;
        }

        // Session params already set by index.php
        // Don't override them here to avoid conflicts
        
        // Start session (suppressing warnings in CLI/test environments)
        @session_start();
        self::$started = true;

        // Generate CSRF token if not exists
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    }

    /**
     * Get CSRF token
     *
     * @return string
     */
    public static function csrf(): string
    {
        self::start();

        // Ensure token exists (failsafe for test environments)
        if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     *
     * @param string $token Token to verify
     * @return bool
     */
    public static function verifyCsrf(string $token): bool
    {
        self::start();
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Get session value
     *
     * @param string $key Session key
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     *
     * @param string $key Session key
     * @param mixed $value Value to set
     * @return void
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Regenerate session ID (after login)
     *
     * @return void
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Destroy session (logout)
     *
     * @return void
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        self::$started = false;
    }
}
