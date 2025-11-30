<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth;

use App\Core\Authorization\Gate;
use App\Core\Container\ServiceProvider;
use App\Modules\Auth\Contracts\GuardInterface;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Guards\SessionGuard;
use App\Modules\Auth\Guards\TokenGuard;
use App\Modules\Auth\Repositories\DatabaseUserRepository;
use App\Modules\Auth\Security\LoginRateLimiter;
use App\Modules\Auth\Security\PasswordHasher;
use App\Modules\Auth\Services\AuthManager;
use App\Modules\Auth\Services\EmailVerificationService;
use App\Modules\Auth\Services\PasswordResetService;
use App\Modules\Auth\Services\PasswordService;
use App\Modules\Auth\Services\TwoFactorService;

/**
 * Auth Service Provider
 * 
 * Registers authentication services, guards, and middleware.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register authentication services
     */
    public function register(): void
    {
        $this->mergeConfig();
        $this->registerPasswordHasher();
        $this->registerPasswordService();
        $this->registerRateLimiter();
        $this->registerUserRepository();
        $this->registerAuthManager();
        $this->registerGuards();
        $this->registerPasswordResetService();
        $this->registerTwoFactorService();
        $this->registerEmailVerificationService();
    }

    /**
     * Bootstrap authentication services
     */
    public function boot(): void
    {
        $this->configureGate();
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerViews();
        // Note: Schema and patches are processed by `php bin/console s:up`
        // See Setup/schema.php and Setup/Data/ for definitions
    }

    /**
     * Register view paths for Auth templates
     */
    protected function registerViews(): void
    {
        // Register view namespace if view manager is available
        if ($this->app->has('view')) {
            $view = $this->app->get('view');
            
            // Register base templates (shared between customer/admin)
            if (method_exists($view, 'addNamespace')) {
                $view->addNamespace('Auth', __DIR__ . '/view/base/templates');
            }
        }
    }

    /**
     * Merge auth configuration
     */
    protected function mergeConfig(): void
    {
        $configPath = __DIR__ . '/Config/auth.php';

        if (file_exists($configPath)) {
            $authConfig = require $configPath;

            if ($this->app->has('config')) {
                $config = $this->app->get('config');
                $config->set('auth', $authConfig);
            }
        }
    }

    /**
     * Register the password hasher
     */
    protected function registerPasswordHasher(): void
    {
        $this->app->singleton(PasswordHasher::class, function ($app) {
            $config = $app->has('config') ? $app->get('config') : null;
            $hashConfig = $config?->get('auth.hashing', []) ?? [];

            return new PasswordHasher([
                'driver' => $hashConfig['driver'] ?? 'argon2id',
                'argon' => $hashConfig['argon'] ?? [],
                'bcrypt' => $hashConfig['bcrypt'] ?? [],
            ]);
        });
    }

    /**
     * Register the login rate limiter
     */
    protected function registerRateLimiter(): void
    {
        $this->app->singleton(LoginRateLimiter::class, function ($app) {
            $config = $app->has('config') ? $app->get('config') : null;
            $throttleConfig = $config?->get('auth.throttle', []) ?? [];
            $cache = $app->has('cache') ? $app->get('cache') : null;

            return new LoginRateLimiter($cache, [
                'max_attempts_email' => $throttleConfig['max_attempts_email'] ?? 5,
                'max_attempts_ip' => $throttleConfig['max_attempts_ip'] ?? 50,
                'max_attempts_combined' => $throttleConfig['max_attempts_combined'] ?? 10,
                'decay_minutes' => $throttleConfig['decay_minutes'] ?? 15,
                'lockout_minutes' => $throttleConfig['lockout_minutes'] ?? 60,
            ]);
        });
    }

    /**
     * Register the password service
     */
    protected function registerPasswordService(): void
    {
        $this->app->singleton(PasswordService::class, function ($app) {
            return new PasswordService(
                $app->get('db'),
                $app->get(PasswordHasher::class)
            );
        });
    }

    /**
     * Register the user repository
     */
    protected function registerUserRepository(): void
    {
        $this->app->singleton(UserRepositoryInterface::class, function ($app) {
            $config = $app->has('config') ? $app->get('config') : null;
            $providerConfig = $config?->get('auth.providers.users', []) ?? [];
            $modelClass = $providerConfig['model'] ?? \App\Modules\Auth\Models\User::class;

            // Inject PasswordService if available
            $passwordService = $app->has(PasswordService::class) 
                ? $app->get(PasswordService::class) 
                : null;

            return new DatabaseUserRepository($modelClass, $passwordService);
        });
    }

    /**
     * Register the auth manager
     */
    protected function registerAuthManager(): void
    {
        $this->app->singleton('auth', function ($app) {
            return new AuthManager($app);
        });

        $this->app->singleton(AuthManager::class, function ($app) {
            return $app->get('auth');
        });
    }

    /**
     * Register authentication guards
     */
    protected function registerGuards(): void
    {
        // Register SessionGuard factory (uses session() helper internally)
        $this->app->bind(SessionGuard::class, function ($app) {
            return new SessionGuard(
                'web',
                $app->get(UserRepositoryInterface::class),
                $app->get(PasswordHasher::class)
            );
        });

        // Register TokenGuard factory
        $this->app->bind(TokenGuard::class, function ($app) {
            return new TokenGuard(
                'api',
                $app->get(UserRepositoryInterface::class),
                $app->has('request') ? $app->get('request') : null
            );
        });

        // Alias the default guard interface to the session guard
        $this->app->singleton(GuardInterface::class, function ($app) {
            return $app->get('auth')->guard();
        });
    }

    /**
     * Register the password reset service
     */
    protected function registerPasswordResetService(): void
    {
        $this->app->singleton(PasswordResetService::class, function ($app) {
            $config = $app->has('config') ? $app->get('config') : null;
            $resetConfig = $config?->get('auth.passwords', []) ?? [];

            return new PasswordResetService(
                $app->get(UserRepositoryInterface::class),
                $app->get(PasswordHasher::class),
                [
                    'expiration' => ($resetConfig['expire'] ?? 60) * 60, // minutes to seconds
                    'throttle' => $resetConfig['throttle'] ?? 60,
                ]
            );
        });
    }

    /**
     * Configure the authorization gate with user resolver
     */
    protected function configureGate(): void
    {
        if (!$this->app->has(Gate::class)) {
            return;
        }

        $gate = $this->app->get(Gate::class);

        // Set the user resolver - this connects Auth to Core's authorization system
        $gate->setUserResolver(function () {
            return $this->app->get('auth')->user();
        });

        // Register any global abilities here
        // $gate->define('admin-access', fn($user) => $user?->hasRole('admin') ?? false);
    }

    /**
     * Register authentication middleware
     */
    protected function registerMiddleware(): void
    {
        // Middleware will be registered via routes or kernel
        // The middleware classes themselves are in Middleware/
    }

    /**
     * Register authentication routes
     */
    protected function registerRoutes(): void
    {
        // Routes are loaded via module.json -> routes configuration
        // Or can be loaded here directly:
        // 
        // if ($this->app->has('router')) {
        //     $router = $this->app->get('router');
        //     require __DIR__ . '/Config/routes.php';
        // }
    }

    /**
     * Register the two-factor authentication service
     */
    protected function registerTwoFactorService(): void
    {
        $this->app->singleton(TwoFactorService::class, function ($app) {
            return new TwoFactorService(
                $app->get(UserRepositoryInterface::class)
            );
        });
    }

    /**
     * Register the email verification service
     */
    protected function registerEmailVerificationService(): void
    {
        $this->app->singleton(EmailVerificationService::class, function ($app) {
            $config = $app->has('config') ? $app->get('config') : null;
            $verificationConfig = $config?->get('auth.verification', []) ?? [];

            return new EmailVerificationService(
                $app->get(UserRepositoryInterface::class),
                [
                    'expiration' => ($verificationConfig['expire'] ?? 60) * 60,
                    'throttle' => $verificationConfig['throttle'] ?? 60,
                ]
            );
        });
    }

    /**
     * Get helper function for accessing auth
     */
    public static function auth(): AuthManager
    {
        return app('auth');
    }

    /**
     * Services provided by this provider
     */
    public function provides(): array
    {
        return [
            'auth',
            AuthManager::class,
            GuardInterface::class,
            UserRepositoryInterface::class,
            PasswordHasher::class,
            PasswordService::class,
            LoginRateLimiter::class,
            PasswordResetService::class,
            TwoFactorService::class,
            EmailVerificationService::class,
        ];
    }
}
