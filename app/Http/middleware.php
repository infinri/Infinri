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
        \App\Http\Middleware\TrimStrings::class => ['priority' => 100],
    ],

    /**
     * Web middleware group
     */
    'web' => [
        // \App\Http\Middleware\VerifyCsrfToken::class => ['priority' => 50],
        // \App\Http\Middleware\ShareErrorsFromSession::class => ['priority' => 40],
    ],

    /**
     * API middleware group
     */
    'api' => [
        // \App\Http\Middleware\ThrottleRequests::class => ['priority' => 100],
    ],

    /**
     * Middleware aliases for route-level use
     */
    'aliases' => [
        'auth' => \App\Http\Middleware\TrimStrings::class, // placeholder
        // 'throttle' => \App\Http\Middleware\ThrottleRequests::class,
        // 'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
    ],
];
