<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module Routes (Web)
 *
 * Context-aware routing: Same controllers handle both customer and admin contexts.
 * Routes are registered for both / (customer) and /admin/ (admin) prefixes.
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

use App\Core\Routing\Router;

/**
 * @var Router $router
 */

/**
 * Register auth routes for a given context (customer or admin)
 * Controllers detect context from route prefix and adjust behavior accordingly
 */
$registerAuthRoutes = function (Router $router, string $prefix = '', string $context = 'customer') {
    
    $namePrefix = $prefix ? "{$prefix}." : '';
    $pathPrefix = $prefix ? "/{$prefix}" : '';
    
    // =========================================================================
    // Guest Routes (unauthenticated users only)
    // =========================================================================
    
    $router->group(['middleware' => ['guest'], 'prefix' => $prefix], function (Router $router) use ($namePrefix, $pathPrefix) {
        
        // Login
        $router->get('/login', [
            'uses' => 'App\Modules\Auth\Controllers\LoginController@showLoginForm',
            'as' => $namePrefix . 'login',
        ]);
        
        $router->post('/login', [
            'uses' => 'App\Modules\Auth\Controllers\LoginController@login',
            'as' => $namePrefix . 'login.attempt',
            'middleware' => ['csrf', 'throttle:login'],
        ]);

        // Registration (controller will block for admin context)
        $router->get('/register', [
            'uses' => 'App\Modules\Auth\Controllers\RegisterController@showRegistrationForm',
            'as' => $namePrefix . 'register',
        ]);
        
        $router->post('/register', [
            'uses' => 'App\Modules\Auth\Controllers\RegisterController@register',
            'as' => $namePrefix . 'register.store',
            'middleware' => ['csrf'],
        ]);

        // Password Reset Request
        $router->get('/forgot-password', [
            'uses' => 'App\Modules\Auth\Controllers\ForgotPasswordController@showLinkRequestForm',
            'as' => $namePrefix . 'password.request',
        ]);
        
        $router->post('/forgot-password', [
            'uses' => 'App\Modules\Auth\Controllers\ForgotPasswordController@sendResetLinkEmail',
            'as' => $namePrefix . 'password.email',
            'middleware' => ['csrf', 'throttle:password'],
        ]);

        // Password Reset
        $router->get('/reset-password/{token}', [
            'uses' => 'App\Modules\Auth\Controllers\ResetPasswordController@showResetForm',
            'as' => $namePrefix . 'password.reset',
        ]);
        
        $router->post('/reset-password', [
            'uses' => 'App\Modules\Auth\Controllers\ResetPasswordController@reset',
            'as' => $namePrefix . 'password.update',
            'middleware' => ['csrf'],
        ]);
    });

    // =========================================================================
    // 2FA Challenge (user identified but not fully authenticated)
    // =========================================================================
    
    $router->group(['middleware' => ['2fa.challenge'], 'prefix' => $prefix], function (Router $router) use ($namePrefix) {
        
        $router->get('/two-factor-challenge', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorChallengeController@showChallengeForm',
            'as' => $namePrefix . '2fa.challenge',
        ]);
        
        $router->post('/two-factor-challenge', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorChallengeController@verify',
            'as' => $namePrefix . '2fa.challenge.verify',
            'middleware' => ['csrf', 'throttle:2fa'],
        ]);
        
        $router->post('/two-factor-challenge/recovery', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorChallengeController@verifyRecoveryCode',
            'as' => $namePrefix . '2fa.challenge.recovery',
            'middleware' => ['csrf', 'throttle:2fa'],
        ]);
    });
};

// =============================================================================
// Register Customer Routes (no prefix)
// =============================================================================

$registerAuthRoutes($router, '', 'customer');

// =============================================================================
// Register Admin Routes (/admin prefix)
// =============================================================================

$registerAuthRoutes($router, 'admin', 'admin');

/**
 * Register authenticated routes for a given context
 */
$registerAuthenticatedRoutes = function (Router $router, string $prefix = '', string $context = 'customer') {
    
    $namePrefix = $prefix ? "{$prefix}." : '';
    
    $router->group(['middleware' => ['auth'], 'prefix' => $prefix], function (Router $router) use ($namePrefix) {
        
        // Logout
        $router->post('/logout', [
            'uses' => 'App\Modules\Auth\Controllers\LogoutController@logout',
            'as' => $namePrefix . 'logout',
            'middleware' => ['csrf'],
        ]);

        // Email Verification
        $router->get('/email/verify', [
            'uses' => 'App\Modules\Auth\Controllers\EmailVerificationController@notice',
            'as' => $namePrefix . 'verification.notice',
        ]);
        
        $router->get('/email/verify/{id}/{hash}', [
            'uses' => 'App\Modules\Auth\Controllers\EmailVerificationController@verify',
            'as' => $namePrefix . 'verification.verify',
            'middleware' => ['signed'],
        ]);
        
        $router->post('/email/verification-notification', [
            'uses' => 'App\Modules\Auth\Controllers\EmailVerificationController@resend',
            'as' => $namePrefix . 'verification.send',
            'middleware' => ['csrf', 'throttle:verification'],
        ]);

        // Password Change
        $router->get('/password/change', [
            'uses' => 'App\Modules\Auth\Controllers\PasswordController@showChangeForm',
            'as' => $namePrefix . 'password.change',
        ]);
        
        $router->post('/password/change', [
            'uses' => 'App\Modules\Auth\Controllers\PasswordController@change',
            'as' => $namePrefix . 'password.change.update',
            'middleware' => ['csrf'],
        ]);

        // Two-Factor Authentication Management
        $router->get('/two-factor', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorController@show',
            'as' => $namePrefix . '2fa.show',
        ]);
        
        $router->post('/two-factor/setup', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorController@setup',
            'as' => $namePrefix . '2fa.setup',
            'middleware' => ['csrf'],
        ]);
        
        $router->post('/two-factor/enable', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorController@enable',
            'as' => $namePrefix . '2fa.enable',
            'middleware' => ['csrf'],
        ]);
        
        $router->post('/two-factor/disable', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorController@disable',
            'as' => $namePrefix . '2fa.disable',
            'middleware' => ['csrf'],
        ]);
        
        $router->post('/two-factor/recovery-codes', [
            'uses' => 'App\Modules\Auth\Controllers\TwoFactorController@regenerateRecoveryCodes',
            'as' => $namePrefix . '2fa.recovery-codes',
            'middleware' => ['csrf'],
        ]);
    });
};

// Register authenticated routes for both contexts
$registerAuthenticatedRoutes($router, '', 'customer');
$registerAuthenticatedRoutes($router, 'admin', 'admin');
