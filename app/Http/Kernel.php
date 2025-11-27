<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Http\Kernel as BaseKernel;

/**
 * Application HTTP Kernel
 * 
 * Configures middleware for the application
 */
class Kernel extends BaseKernel
{
    /**
     * Global middleware that runs on every request
     * 
     * @var array<int, string>
     */
    protected array $middleware = [
        \App\Http\Middleware\TrimStrings::class,
    ];

    /**
     * Route middleware (named middleware that can be assigned to routes)
     * 
     * @var array<string, string>
     */
    protected array $routeMiddleware = [
        // 'auth' => \App\Http\Middleware\Authenticate::class,
        // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        // 'throttle' => \App\Http\Middleware\ThrottleRequests::class,
    ];

    /**
     * Middleware groups
     * 
     * @var array<string, array<int, string>>
     */
    protected array $middlewareGroups = [
        'web' => [
            // \App\Http\Middleware\EncryptCookies::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
        ],
        
        'api' => [
            // 'throttle:60,1',
        ],
    ];
}
