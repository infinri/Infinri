<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Guards;

use App\Core\Contracts\Auth\AuthorizableInterface;
use App\Core\Http\Cookie;
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Contracts\GuardInterface;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Security\PasswordHasher;

/**
 * Session Guard
 * 
 * Session-based authentication guard using PHP sessions.
 * Uses Core's SessionManager for session operations.
 */
class SessionGuard implements GuardInterface
{
    /**
     * Guard name
     */
    protected string $name;

    /**
     * User repository
     */
    protected UserRepositoryInterface $provider;


    /**
     * Password hasher
     */
    protected PasswordHasher $hasher;

    /**
     * The currently authenticated user
     */
    protected ?AuthorizableInterface $user = null;

    /**
     * Whether the user has been retrieved
     */
    protected bool $userRetrieved = false;

    /**
     * Whether the user was authenticated via "remember me"
     */
    protected bool $viaRemember = false;

    /**
     * Remember token duration in minutes
     */
    protected int $rememberDuration = 43200; // 30 days

    /**
     * Session key for user ID
     */
    protected const SESSION_USER_KEY = '_auth_user_id';

    /**
     * Session key for remember hash
     */
    protected const SESSION_REMEMBER_KEY = '_auth_remember';

    /**
     * Create a new session guard instance
     */
    public function __construct(
        string $name,
        UserRepositoryInterface $provider,
        PasswordHasher $hasher
    ) {
        $this->name = $name;
        $this->provider = $provider;
        $this->hasher = $hasher;
    }

    /**
     * {@inheritDoc}
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * {@inheritDoc}
     */
    public function user(): ?AuthorizableInterface
    {
        if ($this->userRetrieved) {
            return $this->user;
        }

        $this->userRetrieved = true;

        // Try to get user from session (using Core helper)
        $id = session(self::SESSION_USER_KEY);

        if ($id !== null) {
            $this->user = $this->provider->findById($id);
        }

        // If no user in session, try remember cookie
        if ($this->user === null) {
            $this->user = $this->getUserFromRememberCookie();
            if ($this->user !== null) {
                $this->viaRemember = true;
                $this->updateSession($this->user->getAuthIdentifier());
            }
        }

        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function id(): int|string|null
    {
        $user = $this->user();
        return $user?->getAuthIdentifier();
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->findByEmail($credentials['email'] ?? '');

        if ($user === null) {
            return false;
        }

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * {@inheritDoc}
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->provider->findByEmail($credentials['email'] ?? '');

        if ($user === null || !$this->hasValidCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user, $remember);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function once(array $credentials = []): bool
    {
        if ($this->validate($credentials)) {
            $user = $this->provider->findByEmail($credentials['email'] ?? '');
            $this->setUser($user);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function login(AuthorizableInterface $user, bool $remember = false): void
    {
        $this->updateSession($user->getAuthIdentifier());

        if ($remember && $user instanceof AuthenticatableInterface) {
            $this->createRememberTokenIfDoesntExist($user);
            $this->queueRememberCookie($user);
        }

        $this->setUser($user);
    }

    /**
     * {@inheritDoc}
     */
    public function loginUsingId(int|string $id, bool $remember = false): ?AuthorizableInterface
    {
        $user = $this->provider->findById($id);

        if ($user !== null) {
            $this->login($user, $remember);
            return $user;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function onceUsingId(int|string $id): ?AuthorizableInterface
    {
        $user = $this->provider->findById($id);

        if ($user !== null) {
            $this->setUser($user);
            return $user;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function viaRemember(): bool
    {
        return $this->viaRemember;
    }

    /**
     * {@inheritDoc}
     */
    public function logout(): void
    {
        $user = $this->user();

        // Clear the remember cookie
        $this->clearRememberCookie();

        // Clear remember token in database
        if ($user instanceof AuthenticatableInterface) {
            $this->cycleRememberToken($user);
        }

        // Clear session (using Core helper)
        session()->forget(self::SESSION_USER_KEY);
        session()->forget(self::SESSION_REMEMBER_KEY);
        session()->regenerate(true);

        $this->user = null;
        $this->userRetrieved = false;
        $this->viaRemember = false;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the currently authenticated user
     */
    public function setUser(AuthorizableInterface $user): static
    {
        $this->user = $user;
        $this->userRetrieved = true;

        return $this;
    }

    /**
     * Set the remember token duration
     */
    public function setRememberDuration(int $minutes): static
    {
        $this->rememberDuration = $minutes;

        return $this;
    }

    /**
     * Check if credentials are valid
     */
    protected function hasValidCredentials(?AuthorizableInterface $user, array $credentials): bool
    {
        if ($user === null || !$user instanceof AuthenticatableInterface) {
            return false;
        }

        return $this->hasher->verify(
            $credentials['password'] ?? '',
            $user->getAuthPassword()
        );
    }

    /**
     * Update the session with the given ID
     * Uses Core's session() helper
     */
    protected function updateSession(int|string $id): void
    {
        session()->set(self::SESSION_USER_KEY, $id);

        // Regenerate session ID on login (prevents session fixation)
        session()->regenerate(true);
    }

    /**
     * Get user from remember cookie
     */
    protected function getUserFromRememberCookie(): ?AuthorizableInterface
    {
        $rememberCookie = $this->getRememberCookie();

        if ($rememberCookie === null) {
            return null;
        }

        // Parse cookie: user_id|token
        $parts = explode('|', $rememberCookie, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$id, $token] = $parts;

        $user = $this->provider->findByRememberToken($id, $token);

        // Optionally rotate the remember token
        if ($user instanceof AuthenticatableInterface) {
            $this->cycleRememberToken($user);
            $this->queueRememberCookie($user);
        }

        return $user;
    }

    /**
     * Get the remember cookie value
     * Uses Core's cookie_get() helper
     */
    protected function getRememberCookie(): ?string
    {
        // Try __Host- prefix first (secure), then fallback
        return cookie_get('__Host-remember') ?? cookie_get('remember');
    }

    /**
     * Create a remember token if one doesn't exist
     */
    protected function createRememberTokenIfDoesntExist(AuthenticatableInterface $user): void
    {
        if (empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }
    }

    /**
     * Refresh the remember token for the user
     */
    protected function cycleRememberToken(AuthenticatableInterface $user): void
    {
        $token = bin2hex(random_bytes(32));
        $user->setRememberToken($token);
        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Queue the remember cookie to be set
     * Uses Core's Cookie::host() for __Host- prefix
     */
    protected function queueRememberCookie(AuthenticatableInterface $user): void
    {
        $value = $user->getAuthIdentifier() . '|' . $user->getRememberToken();
        $cookie = Cookie::host('remember', $value, $this->rememberDuration);
        $GLOBALS['_pending_cookies'][] = $cookie;
    }

    /**
     * Clear the remember cookie
     * Uses Core's cookie_forget() helper
     */
    protected function clearRememberCookie(): void
    {
        $GLOBALS['_pending_cookies'][] = cookie_forget('__Host-remember');
    }
}
