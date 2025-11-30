<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Models\Concerns;

/**
 * Has Roles Trait
 * 
 * Provides role and permission functionality for user models.
 * Implements AuthorizableInterface methods from Core.
 */
trait HasRoles
{
    /**
     * Cached roles for this user
     * @var string[]|null
     */
    protected ?array $cachedRoles = null;

    /**
     * Cached permissions for this user
     * @var string[]|null
     */
    protected ?array $cachedPermissions = null;

    /**
     * Get the user's roles
     * @return string[]
     */
    public function getRoles(): array
    {
        if ($this->cachedRoles !== null) {
            return $this->cachedRoles;
        }

        $this->cachedRoles = $this->loadRoles();
        return $this->cachedRoles;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Get the user's permissions
     * @return string[]
     */
    public function getPermissions(): array
    {
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }

        $this->cachedPermissions = $this->loadPermissions();
        return $this->cachedPermissions;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions(), true);
    }

    /**
     * Check if user has any of the given roles
     * @param string[] $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given roles
     * @param string[] $roles
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    /**
     * Clear cached roles and permissions
     */
    public function clearPermissionCache(): void
    {
        $this->cachedRoles = null;
        $this->cachedPermissions = null;
    }

    /**
     * Load user roles from database
     * @return string[]
     */
    protected function loadRoles(): array
    {
        // TODO: Implement when Role model and pivot table exist
        return [];
    }

    /**
     * Load user permissions through roles from database
     * @return string[]
     */
    protected function loadPermissions(): array
    {
        // TODO: Implement when Permission model exists
        return [];
    }
}
