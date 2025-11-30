<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Authorization;

use App\Core\Contracts\Auth\AuthorizableInterface;

/**
 * Authorizes Requests Trait
 * 
 * Use this trait in controllers to easily authorize actions.
 * 
 * Usage:
 *   class PostController
 *   {
 *       use AuthorizesRequests;
 *       
 *       public function update(int $id)
 *       {
 *           $post = Post::findOrFail($id);
 *           $this->authorize('update', $post);
 *           // ... proceed with update
 *       }
 *   }
 */
trait AuthorizesRequests
{
    /**
     * Authorize an action
     * 
     * @throws AuthorizationException
     */
    protected function authorize(string $ability, mixed ...$arguments): Response
    {
        return $this->gate()->authorize($ability, ...$arguments);
    }

    /**
     * Authorize an action for a specific user
     * 
     * @throws AuthorizationException
     */
    protected function authorizeForUser(AuthorizableInterface $user, string $ability, mixed ...$arguments): Response
    {
        return $this->gate()->forUser($user)->authorize($ability, ...$arguments);
    }

    /**
     * Check if current user can perform an action (no exception)
     */
    protected function can(string $ability, mixed ...$arguments): bool
    {
        return $this->gate()->allows($ability, ...$arguments);
    }

    /**
     * Check if current user cannot perform an action
     */
    protected function cannot(string $ability, mixed ...$arguments): bool
    {
        return $this->gate()->denies($ability, ...$arguments);
    }

    /**
     * Authorize a resource action based on the model class
     * 
     * Automatically determines the policy based on the model.
     * 
     * @param string $ability The ability (create, update, delete, etc.)
     * @param class-string|object $model The model class or instance
     * @throws AuthorizationException
     */
    protected function authorizeResource(string $ability, string|object $model): Response
    {
        return $this->authorize($ability, $model);
    }

    /**
     * Get the Gate instance
     */
    protected function gate(): Gate
    {
        // Try to resolve from container, fall back to global
        if (function_exists('app')) {
            try {
                return app(Gate::class);
            } catch (\Throwable) {
                // Fall through to global
            }
        }

        return gate();
    }
}
