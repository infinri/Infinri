<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Contracts\Auth;

/**
 * Authorizable Interface
 *
 * Implement this on your User model to enable authorization checks.
 */
interface AuthorizableInterface
{
    /**
     * Get the unique identifier for the user
     */
    public function getAuthIdentifier(): int|string;

    /**
     * Get the user's roles (if using role-based access)
     *
     * @return string[]
     */
    public function getRoles(): array;

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool;

    /**
     * Get the user's direct permissions (if using permission-based access)
     *
     * @return string[]
     */
    public function getPermissions(): array;

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool;
}
