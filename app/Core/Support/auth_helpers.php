<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */

// ==================== Authorization Helpers ====================

if (!function_exists('gate')) {
    /**
     * Get the Gate instance
     * 
     * @return \App\Core\Authorization\Gate
     */
    function gate(): \App\Core\Authorization\Gate
    {
        return app(\App\Core\Authorization\Gate::class);
    }
}

if (!function_exists('can')) {
    /**
     * Check if the current user can perform an ability
     * 
     * @param string $ability The ability to check
     * @param mixed ...$arguments Arguments (typically the model)
     * @return bool
     */
    function can(string $ability, mixed ...$arguments): bool
    {
        return gate()->allows($ability, ...$arguments);
    }
}

if (!function_exists('cannot')) {
    /**
     * Check if the current user cannot perform an ability
     * 
     * @param string $ability The ability to check
     * @param mixed ...$arguments Arguments (typically the model)
     * @return bool
     */
    function cannot(string $ability, mixed ...$arguments): bool
    {
        return gate()->denies($ability, ...$arguments);
    }
}

if (!function_exists('authorize')) {
    /**
     * Authorize an ability (throws AuthorizationException if denied)
     * 
     * @param string $ability The ability to check
     * @param mixed ...$arguments Arguments (typically the model)
     * @return \App\Core\Authorization\Response
     * @throws \App\Core\Authorization\AuthorizationException
     */
    function authorize(string $ability, mixed ...$arguments): \App\Core\Authorization\Response
    {
        return gate()->authorize($ability, ...$arguments);
    }
}
