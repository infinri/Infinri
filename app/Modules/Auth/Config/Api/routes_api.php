<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module Routes (API)
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

use App\Core\Routing\Router;

/**
 * @var Router $router
 */

$router->group(['prefix' => '/api/auth', 'middleware' => ['api']], function (Router $router) {
    
    // =============================================================================
    // Public API Routes (no authentication required)
    // =============================================================================
    
    $router->post('/login', [
        'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@login',
        'as' => 'api.auth.login',
        'middleware' => ['throttle:api.login'],
    ]);
    
    $router->post('/register', [
        'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@register',
        'as' => 'api.auth.register',
    ]);
    
    $router->post('/forgot-password', [
        'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@forgotPassword',
        'as' => 'api.auth.forgot-password',
        'middleware' => ['throttle:api.password'],
    ]);
    
    $router->post('/reset-password', [
        'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@resetPassword',
        'as' => 'api.auth.reset-password',
    ]);

    // =============================================================================
    // Authenticated API Routes (token required)
    // =============================================================================
    
    $router->group(['middleware' => ['auth:api']], function (Router $router) {
        
        // Current User
        $router->get('/user', [
            'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@user',
            'as' => 'api.auth.user',
        ]);
        
        // Logout (revoke token)
        $router->post('/logout', [
            'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@logout',
            'as' => 'api.auth.logout',
        ]);
        
        // Refresh Token
        $router->post('/refresh', [
            'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@refresh',
            'as' => 'api.auth.refresh',
        ]);
        
        // Change Password
        $router->post('/password', [
            'uses' => 'App\Modules\Auth\Controllers\Api\AuthController@changePassword',
            'as' => 'api.auth.password',
        ]);
        
        // Personal Access Tokens
        $router->get('/tokens', [
            'uses' => 'App\Modules\Auth\Controllers\Api\TokenController@index',
            'as' => 'api.tokens.index',
        ]);
        
        $router->post('/tokens', [
            'uses' => 'App\Modules\Auth\Controllers\Api\TokenController@store',
            'as' => 'api.tokens.store',
        ]);
        
        $router->delete('/tokens/{id}', [
            'uses' => 'App\Modules\Auth\Controllers\Api\TokenController@destroy',
            'as' => 'api.tokens.destroy',
        ]);
    });
});
