<?php

declare(strict_types=1);

namespace App\Core\Session;

/**
 * Session Manager
 * 
 * Handles session lifecycle and data management.
 * CSRF is handled separately by Core\Security\Csrf.
 */
class SessionManager
{
    /**
     * Whether session has been started
     */
    protected static bool $started = false;

    /**
     * Start the session
     */
    public function start(): bool
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return true;
        }

        if (session_status() === PHP_SESSION_DISABLED) {
            return false;
        }

        $started = @session_start();
        self::$started = $started;

        return $started;
    }

    /**
     * Get a session value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     */
    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session key exists
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        $this->start();
        return $_SESSION ?? [];
    }

    /**
     * Clear all session data (but keep session alive)
     */
    public function flush(): void
    {
        $this->start();
        $_SESSION = [];
    }

    /**
     * Regenerate session ID (security: after login/privilege change)
     */
    public function regenerate(bool $deleteOld = true): bool
    {
        $this->start();
        return session_regenerate_id($deleteOld);
    }

    /**
     * Destroy the session completely
     */
    public function destroy(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        $destroyed = session_destroy();
        self::$started = false;

        return $destroyed;
    }

    /**
     * Get session ID
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Set session ID (must be called before start)
     */
    public function setId(string $id): void
    {
        session_id($id);
    }

    /**
     * Check if session is started
     */
    public function isStarted(): bool
    {
        return self::$started || session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Flash data (available only for next request)
     */
    public function flash(string $key, mixed $value): void
    {
        $this->set('_flash.' . $key, $value);
        
        $flashKeys = $this->get('_flash_keys', []);
        $flashKeys[] = $key;
        $this->set('_flash_keys', array_unique($flashKeys));
    }

    /**
     * Get flashed data
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $this->get('_flash.' . $key, $default);
    }

    /**
     * Clear old flash data (call at request start)
     */
    public function ageFlashData(): void
    {
        $flashKeys = $this->get('_flash_keys', []);
        
        foreach ($flashKeys as $key) {
            $this->forget('_flash.' . $key);
        }
        
        $this->forget('_flash_keys');
    }
}
