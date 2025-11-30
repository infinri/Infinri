<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Setup\Data;

use App\Core\Setup\Patch\AbstractPatch;
use App\Core\Setup\Patch\DataPatchInterface;
use App\Core\Contracts\Database\ConnectionInterface;

/**
 * Seed Default Roles
 * 
 * Creates the default roles and permissions for the authentication system.
 * This patch runs once during initial setup.
 */
class SeedDefaultRoles extends AbstractPatch implements DataPatchInterface
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Alias for connection for cleaner code
     */
    protected function db(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Apply the data patch
     */
    public function apply(): void
    {
        $this->seedRoles();
        $this->seedPermissions();
        $this->assignPermissionsToRoles();
    }

    /**
     * Seed default roles
     */
    protected function seedRoles(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with ability to manage all aspects including other admins.',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access to manage content and users.',
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Standard registered user with access to their own account.',
            ],
        ];

        foreach ($roles as $role) {
            // Check if role already exists
            $exists = $this->db()->table('roles')
                ->where('name', $role['name'])
                ->first();

            if (!$exists) {
                $this->db()->table('roles')->insert([
                    ...$role,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Seed default permissions
     */
    protected function seedPermissions(): void
    {
        $permissions = [
            // User management
            ['name' => 'users.view', 'display_name' => 'View Users', 'description' => 'Can view user list and profiles'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'description' => 'Can create new users'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'description' => 'Can edit user information'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'description' => 'Can delete users'],
            
            // Role management
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'description' => 'Can view roles'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'description' => 'Can create new roles'],
            ['name' => 'roles.edit', 'display_name' => 'Edit Roles', 'description' => 'Can edit roles'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'description' => 'Can delete roles'],
            ['name' => 'roles.assign', 'display_name' => 'Assign Roles', 'description' => 'Can assign roles to users'],
            
            // Permission management
            ['name' => 'permissions.view', 'display_name' => 'View Permissions', 'description' => 'Can view permissions'],
            ['name' => 'permissions.assign', 'display_name' => 'Assign Permissions', 'description' => 'Can assign permissions to roles'],
            
            // Admin access
            ['name' => 'admin.access', 'display_name' => 'Admin Access', 'description' => 'Can access admin panel'],
            ['name' => 'admin.dashboard', 'display_name' => 'Admin Dashboard', 'description' => 'Can view admin dashboard'],
            
            // Session management
            ['name' => 'sessions.view', 'display_name' => 'View Sessions', 'description' => 'Can view active sessions'],
            ['name' => 'sessions.revoke', 'display_name' => 'Revoke Sessions', 'description' => 'Can revoke user sessions'],
            
            // Security audit
            ['name' => 'audit.view', 'display_name' => 'View Audit Logs', 'description' => 'Can view security audit logs'],
        ];

        foreach ($permissions as $permission) {
            $exists = $this->db()->table('permissions')
                ->where('name', $permission['name'])
                ->first();

            if (!$exists) {
                $this->db()->table('permissions')->insert([
                    ...$permission,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Assign permissions to roles
     */
    protected function assignPermissionsToRoles(): void
    {
        // Get role IDs
        $superAdminRole = $this->db()->table('roles')->where('name', 'super_admin')->first();
        $adminRole = $this->db()->table('roles')->where('name', 'admin')->first();

        if (!$superAdminRole || !$adminRole) {
            return;
        }

        // Super admin gets ALL permissions
        $allPermissions = $this->db()->table('permissions')->get();
        foreach ($allPermissions as $permission) {
            $this->assignPermissionToRole($permission->id, $superAdminRole->id);
        }

        // Admin gets most permissions except role/permission management
        $adminPermissions = [
            'users.view', 'users.create', 'users.edit',
            'admin.access', 'admin.dashboard',
            'sessions.view', 'sessions.revoke',
            'audit.view',
        ];

        foreach ($adminPermissions as $permissionName) {
            $permission = $this->db()->table('permissions')
                ->where('name', $permissionName)
                ->first();
            
            if ($permission) {
                $this->assignPermissionToRole($permission->id, $adminRole->id);
            }
        }
    }

    /**
     * Assign a permission to a role (idempotent)
     */
    protected function assignPermissionToRole(int $permissionId, int $roleId): void
    {
        $exists = $this->db()->table('permission_role')
            ->where('permission_id', $permissionId)
            ->where('role_id', $roleId)
            ->first();

        if (!$exists) {
            $this->db()->table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    /**
     * Get dependencies (patches that must run before this one)
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (alternative names for this patch)
     */
    public function getAliases(): array
    {
        return [];
    }
}
