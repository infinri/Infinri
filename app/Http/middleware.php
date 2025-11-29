<?php

declare(strict_types=1);

/**
 * Application Middleware Configuration
 * 
 * Defines middleware for the HTTP pipeline.
 * Priority: Higher numbers run first.
 */
return [
    /**
     * Global middleware (runs on every request)
     */
    'global' => [
        \App\Core\Http\Middleware\MetricsMiddleware::class => ['priority' => 200], // First in, last out
        \App\Http\Middleware\TrimStrings::class => ['priority' => 100],
    ],

    /**
     * Web middleware group
     */
    'web' => [
        \App\Core\Http\Middleware\EncryptCookies::class => ['priority' => 100],  // Decrypt first, encrypt last
        \App\Core\Http\Middleware\SecurityHeadersMiddleware::class => ['priority' => 90],
        \App\Core\Http\Middleware\VerifyCsrfToken::class => ['priority' => 50],
        // \App\Http\Middleware\ShareErrorsFromSession::class => ['priority' => 40],
    ],

    /**
     * API middleware group
     */
    'api' => [
        \App\Core\Http\Middleware\RateLimitMiddleware::class => ['priority' => 100, 'args' => [60, 1]],
        \App\Core\Http\Middleware\SecurityHeadersMiddleware::class => ['priority' => 90],
    ],

    /**
     * Middleware aliases for route-level use
     */
    'aliases' => [
        'csrf' => \App\Core\Http\Middleware\VerifyCsrfToken::class,
        'throttle' => \App\Core\Http\Middleware\RateLimitMiddleware::class,
        'security' => \App\Core\Http\Middleware\SecurityHeadersMiddleware::class,
        // 'auth' => \App\Http\Middleware\Authenticate::class,
    ],
];
