<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module Database Schema
 *
 * Defines tables for authentication features (password resets, sessions, tokens).
 * 
 * NOTE: The `users` table is NOT created by Auth module. It is expected to be
 * provided by the module that owns user management (typically Admin module).
 * Auth module provides the authentication logic; other modules define user storage.
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

return [
    // Tables that Auth depends on but does NOT create
    'dependencies' => [
        'users' => [
            'required_columns' => ['id', 'email', 'password'],
            'optional_columns' => [
                'email_verified_at',
                'remember_token',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ],
        ],
    ],

    'tables' => [
        // =====================================================================
        // Password Resets - Token storage for password recovery
        // =====================================================================
        'password_resets' => [
            'columns' => [
                'email' => ['type' => 'string', 'length' => 255],
                'token' => ['type' => 'string', 'length' => 255],
                'created_at' => ['type' => 'timestamp', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['email']],
                ['columns' => ['token']],
            ],
            'timestamps' => false,
        ],

        // =====================================================================
        // Passwords - Secure password storage with history
        // Supports: customers, admins, or any future user type
        // =====================================================================
        'passwords' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'user_id' => [
                    'type' => 'foreignId',
                    'references' => 'users.id',
                    'onDelete' => 'cascade',
                ],
                'password_hash' => ['type' => 'string', 'length' => 255],
                'is_current' => ['type' => 'boolean', 'default' => false],
                'expires_at' => ['type' => 'timestamp', 'nullable' => true],
                'must_change' => ['type' => 'boolean', 'default' => false],
            ],
            'indexes' => [
                ['columns' => ['user_id', 'is_current']],
                ['columns' => ['user_id', 'password_hash']], // History lookup
            ],
            'timestamps' => true,
        ],

        // =====================================================================
        // Roles - Authorization roles
        // =====================================================================
        'roles' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'name' => ['type' => 'string', 'length' => 255],
                'display_name' => ['type' => 'string', 'length' => 255, 'nullable' => true],
                'description' => ['type' => 'text', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['name'], 'unique' => true],
            ],
            'timestamps' => true,
        ],

        // =====================================================================
        // Permissions - Granular permission definitions
        // =====================================================================
        'permissions' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'name' => ['type' => 'string', 'length' => 255],
                'display_name' => ['type' => 'string', 'length' => 255, 'nullable' => true],
                'description' => ['type' => 'text', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['name'], 'unique' => true],
            ],
            'timestamps' => true,
        ],

        // =====================================================================
        // Role-User Pivot - Many-to-many: users <-> roles
        // =====================================================================
        'role_user' => [
            'columns' => [
                'user_id' => [
                    'type' => 'foreignId',
                    'references' => 'users.id',
                    'onDelete' => 'cascade',
                ],
                'role_id' => [
                    'type' => 'foreignId',
                    'references' => 'roles.id',
                    'onDelete' => 'cascade',
                ],
            ],
            'indexes' => [
                ['columns' => ['user_id', 'role_id'], 'primary' => true],
                ['columns' => ['role_id']],
            ],
            'timestamps' => false,
        ],

        // =====================================================================
        // Permission-Role Pivot - Many-to-many: roles <-> permissions
        // =====================================================================
        'permission_role' => [
            'columns' => [
                'permission_id' => [
                    'type' => 'foreignId',
                    'references' => 'permissions.id',
                    'onDelete' => 'cascade',
                ],
                'role_id' => [
                    'type' => 'foreignId',
                    'references' => 'roles.id',
                    'onDelete' => 'cascade',
                ],
            ],
            'indexes' => [
                ['columns' => ['permission_id', 'role_id'], 'primary' => true],
                ['columns' => ['role_id']],
            ],
            'timestamps' => false,
        ],

        // =====================================================================
        // Sessions - Database session storage (optional)
        // =====================================================================
        'sessions' => [
            'columns' => [
                'id' => ['type' => 'string', 'length' => 255, 'primary' => true],
                'user_id' => ['type' => 'bigInteger', 'unsigned' => true, 'nullable' => true],
                'ip_address' => ['type' => 'string', 'length' => 45, 'nullable' => true],
                'user_agent' => ['type' => 'text', 'nullable' => true],
                'payload' => ['type' => 'text'],
                'last_activity' => ['type' => 'integer', 'unsigned' => true],
            ],
            'indexes' => [
                ['columns' => ['user_id']],
                ['columns' => ['last_activity']],
            ],
            'timestamps' => false,
        ],

        // =====================================================================
        // Personal Access Tokens - API authentication tokens
        // =====================================================================
        'personal_access_tokens' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'user_id' => [
                    'type' => 'foreignId',
                    'references' => 'users.id',
                    'onDelete' => 'cascade',
                ],
                'name' => ['type' => 'string', 'length' => 255],
                'token' => ['type' => 'string', 'length' => 64],
                'abilities' => ['type' => 'text', 'nullable' => true],
                'last_used_at' => ['type' => 'timestamp', 'nullable' => true],
                'expires_at' => ['type' => 'timestamp', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['token'], 'unique' => true],
                ['columns' => ['user_id']],
            ],
            'timestamps' => true,
        ],

        // =====================================================================
        // Remember Tokens - Persistent login tokens (separate from user table)
        // =====================================================================
        'remember_tokens' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'user_id' => [
                    'type' => 'foreignId',
                    'references' => 'users.id',
                    'onDelete' => 'cascade',
                ],
                'token_hash' => ['type' => 'string', 'length' => 255],
                'device_name' => ['type' => 'string', 'length' => 255, 'nullable' => true],
                'ip_address' => ['type' => 'string', 'length' => 45, 'nullable' => true],
                'user_agent' => ['type' => 'string', 'length' => 255, 'nullable' => true],
                'last_used_at' => ['type' => 'timestamp', 'nullable' => true],
                'expires_at' => ['type' => 'timestamp'],
            ],
            'indexes' => [
                ['columns' => ['token_hash'], 'unique' => true],
                ['columns' => ['user_id']],
                ['columns' => ['expires_at']],
            ],
            'timestamps' => true,
        ],

        // =====================================================================
        // Login Attempts - Audit log for login attempts (security)
        // =====================================================================
        'login_attempts' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'email' => ['type' => 'string', 'length' => 255],
                'ip_address' => ['type' => 'string', 'length' => 45],
                'user_agent' => ['type' => 'string', 'length' => 255, 'nullable' => true],
                'successful' => ['type' => 'boolean', 'default' => false],
                'failure_reason' => ['type' => 'string', 'length' => 100, 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['email']],
                ['columns' => ['ip_address']],
                ['columns' => ['created_at']],
            ],
            'timestamps' => true,
        ],
    ],
];
