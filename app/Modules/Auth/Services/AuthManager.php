<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Services;

use App\Core\Contracts\Auth\AuthorizableInterface;
use App\Core\Contracts\Container\ContainerInterface;
use App\Modules\Auth\Contracts\GuardInterface;
use App\Modules\Auth\Guards\SessionGuard;
use App\Modules\Auth\Guards\TokenGuard;
use InvalidArgumentException;

/**
 * Auth Manager
 * 
 * Manages multiple authentication guards and provides a unified interface.
 * 
 * Usage:
 *   $auth = app('auth');
 *   $auth->guard('web')->check();
 *   $auth->user();  // Uses default guard
 */
class AuthManager
{
    /**
     * The container instance
     */
    protected ContainerInterface $app;

    /**
     * The array of created guards
     * 
     * @var array<string, GuardInterface>
     */
    protected array $guards = [];

    /**
     * The user resolver callback
     */
    protected ?\Closure $userResolver = null;

    /**
     * Create a new Auth Manager instance
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Get a guard instance by name
     *
     * @param string|null $name Guard name (null for default)
     */
    public function guard(?string $name = null): GuardInterface
    {
        $name = $name ?? $this->getDefaultGuard();

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->createGuard($name);
        }

        return $this->guards[$name];
    }

    /**
     * Create a guard instance by name
     */
    protected function createGuard(string $name): GuardInterface
    {
        $config = $this->getGuardConfig($name);
        $driver = $config['driver'] ?? 'session';

        return match ($driver) {
            'session' => $this->createSessionGuard($name, $config),
            'token' => $this->createTokenGuard($name, $config),
            default => throw new InvalidArgumentException(
                "Auth guard driver [{$driver}] is not supported."
            ),
        };
    }

    /**
     * Create a session-based guard
     * SessionGuard uses session() helper internally
     */
    protected function createSessionGuard(string $name, array $config): SessionGuard
    {
        $guard = new SessionGuard(
            $name,
            $this->createUserProvider($config['provider'] ?? 'users'),
            $this->app->get(\App\Modules\Auth\Security\PasswordHasher::class)
        );

        // Set remember token lifetime from config
        $rememberConfig = $this->getConfig('remember', []);
        if (!empty($rememberConfig['lifetime'])) {
            $guard->setRememberDuration($rememberConfig['lifetime']);
        }

        return $guard;
    }

    /**
     * Create a token-based guard
     */
    protected function createTokenGuard(string $name, array $config): TokenGuard
    {
        return new TokenGuard(
            $name,
            $this->createUserProvider($config['provider'] ?? 'users'),
            $this->app->has('request') ? $this->app->get('request') : null,
            $config['input_key'] ?? 'api_token',
            $config['storage_key'] ?? 'api_token',
            $config['hash'] ?? false
        );
    }

    /**
     * Create a user provider by name
     */
    protected function createUserProvider(string $name): \App\Modules\Auth\Contracts\UserRepositoryInterface
    {
        // Use the registered UserRepositoryInterface
        return $this->app->get(\App\Modules\Auth\Contracts\UserRepositoryInterface::class);
    }

    /**
     * Get the guard configuration
     */
    protected function getGuardConfig(string $name): array
    {
        $guards = $this->getConfig('guards', []);

        if (!isset($guards[$name])) {
            throw new InvalidArgumentException(
                "Auth guard [{$name}] is not defined."
            );
        }

        return $guards[$name];
    }

    /**
     * Get configuration value
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        if ($this->app->has('config')) {
            return $this->app->get('config')->get("auth.{$key}", $default);
        }

        return $default;
    }

    /**
     * Get the default guard name
     */
    public function getDefaultGuard(): string
    {
        return $this->getConfig('defaults.guard', 'web');
    }

    /**
     * Set the default guard name
     */
    public function setDefaultGuard(string $name): static
    {
        if ($this->app->has('config')) {
            $this->app->get('config')->set('auth.defaults.guard', $name);
        }

        return $this;
    }

    // =========================================================================
    // Convenience methods (only non-trivial ones that need special handling)
    // All other GuardInterface methods are proxied via __call
    // =========================================================================

    /**
     * Get the currently authenticated user (common accessor)
     */
    public function user(): ?AuthorizableInterface
    {
        return $this->guard()->user();
    }

    /**
     * Log out from all guards (special multi-guard operation)
     */
    public function logoutAll(): void
    {
        foreach ($this->guards as $guard) {
            $guard->logout();
        }
    }

    /**
     * Forget all the created guards
     */
    public function forgetGuards(): static
    {
        $this->guards = [];

        return $this;
    }

    /**
     * Set a custom user resolver
     */
    public function resolveUsersUsing(\Closure $resolver): static
    {
        $this->userResolver = $resolver;

        return $this;
    }

    /**
     * Get the user resolver callback
     */
    public function userResolver(): ?\Closure
    {
        return $this->userResolver;
    }

    /**
     * Dynamically call the default guard instance
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->guard()->$method(...$parameters);
    }
}
