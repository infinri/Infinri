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
 * Base Policy Class
 * 
 * Extend this class to create resource-specific authorization policies.
 * 
 * Policy methods receive the user and model, returning bool or Response:
 * - true / Response::allow() = authorized
 * - false / Response::deny() = denied
 * 
 * Standard CRUD methods:
 * - viewAny(user) - list/index
 * - view(user, model) - show single
 * - create(user) - create new
 * - update(user, model) - update existing
 * - delete(user, model) - delete
 * - restore(user, model) - restore soft-deleted
 * - forceDelete(user, model) - permanently delete
 * 
 * Example:
 *   class PostPolicy extends Policy
 *   {
 *       public function update(?AuthorizableInterface $user, Post $post): bool
 *       {
 *           return $user?->getAuthIdentifier() === $post->user_id;
 *       }
 *   }
 */
abstract class Policy
{
    /**
     * Run before any other authorization checks
     * 
     * Return true to allow all, false to deny all, null to continue.
     * Use for admin bypass or global rules.
     */
    public function before(?AuthorizableInterface $user, string $ability): ?bool
    {
        // Override in subclass for admin bypass, e.g.:
        // return $user?->hasRole('admin') ? true : null;
        return null;
    }

    /**
     * Helper: Check if user owns the model
     */
    protected function owns(?AuthorizableInterface $user, object $model, string $ownerKey = 'user_id'): bool
    {
        if ($user === null) {
            return false;
        }

        $ownerId = $model->$ownerKey ?? ($model->getAttribute($ownerKey) ?? null);
        
        return $ownerId !== null && $user->getAuthIdentifier() == $ownerId;
    }

    /**
     * Helper: Check if user has any of the given roles
     */
    protected function hasAnyRole(?AuthorizableInterface $user, array $roles): bool
    {
        if ($user === null) {
            return false;
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Check if user has all of the given roles
     */
    protected function hasAllRoles(?AuthorizableInterface $user, array $roles): bool
    {
        if ($user === null) {
            return false;
        }

        foreach ($roles as $role) {
            if (!$user->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper: Check if user has any of the given permissions
     */
    protected function hasAnyPermission(?AuthorizableInterface $user, array $permissions): bool
    {
        if ($user === null) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Deny with a custom message
     */
    protected function deny(?string $message = null): Response
    {
        return Response::deny($message);
    }

    /**
     * Helper: Allow with a custom message
     */
    protected function allow(?string $message = null): Response
    {
        return Response::allow($message);
    }

    /**
     * Helper: Deny if condition is true
     */
    protected function denyIf(bool $condition, ?string $message = null): Response
    {
        return Response::denyIf($condition, $message);
    }

    /**
     * Helper: Allow if condition is true
     */
    protected function allowIf(bool $condition, ?string $message = null): Response
    {
        return Response::allowIf($condition, $message);
    }

    /**
     * Helper: Deny with a message
     */
    protected function denyWithStatus(string $message): Response
    {
        return Response::deny($message);
    }
}
