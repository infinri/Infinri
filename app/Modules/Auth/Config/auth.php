<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module Configuration
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "session", "token"
    |
    */
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "database", "model"
    |
    */
    'providers' => [
        'users' => [
            'driver' => 'model',
            'model' => \App\Modules\Auth\Models\User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Configuration
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'table' => 'password_resets',
            'expire' => 60,        // Token expires in 60 minutes
            'throttle' => 60,      // Seconds between reset requests
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Validation Rules
    |--------------------------------------------------------------------------
    */
    'password_rules' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'check_compromised' => true,    // Check HaveIBeenPwned
        'history_count' => 5,           // Prevent reuse of last N passwords
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Hashing (Argon2id with bcrypt fallback)
    |--------------------------------------------------------------------------
    */
    'hashing' => [
        'driver' => 'argon2id',         // argon2id, bcrypt
        'argon' => [
            'memory' => 65536,          // 64MB
            'time' => 4,
            'threads' => 1,
        ],
        'bcrypt' => [
            'rounds' => 12,
        ],
        'pepper' => env('AUTH_PEPPER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Rate Limiting
    |--------------------------------------------------------------------------
    */
    'throttle' => [
        'max_attempts_email' => 5,
        'max_attempts_ip' => 50,
        'max_attempts_combined' => 10,
        'decay_minutes' => 15,
        'lockout_minutes' => 60,
        'progressive_delay' => true,    // Exponential backoff
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */
    'session' => [
        'regenerate_on_login' => true,
        'invalidate_on_password_change' => true,
        'concurrent_sessions' => 5,     // Max sessions per user (0 = unlimited)
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    */
    'remember' => [
        'enabled' => true,
        'lifetime' => 43200,            // 30 days in minutes
        'rotate_token' => true,         // Rotate on each use
        'cookie_name' => 'remember',    // Will become __Host-remember
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    '2fa' => [
        'enabled' => true,
        'enforce' => false,             // Require 2FA for all users
        'issuer' => 'Infinri',
        'recovery_codes' => 8,          // Number of recovery codes
        'challenge_ttl' => 300,         // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | All auth cookies use __Host- or __Secure- prefixes for maximum security.
    | See CORE_REFERENCE.md for Cookie class usage.
    |
    */
    'cookies' => [
        'auth_session' => [
            'name' => 'auth_session',   // Becomes __Host-auth_session
            'prefix' => 'host',         // Use Cookie::host()
            'lifetime' => 0,            // Session cookie
            'same_site' => 'Strict',
        ],
        'remember' => [
            'name' => 'remember',       // Becomes __Host-remember
            'prefix' => 'host',
            'lifetime' => 43200,        // 30 days
            'same_site' => 'Strict',
        ],
        '2fa_challenge' => [
            'name' => '2fa_challenge',
            'prefix' => 'none',
            'lifetime' => 5,            // 5 minutes
            'same_site' => 'Strict',
        ],
        'device_token' => [
            'name' => 'device_token',   // Becomes __Secure-device_token
            'prefix' => 'secure',
            'lifetime' => 525600,       // 1 year
            'same_site' => 'Strict',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Contexts
    |--------------------------------------------------------------------------
    |
    | Define different behavior for customer vs admin authentication.
    | 
    | Context detection priority:
    | 1. ADMIN_DOMAIN env var (recommended for production security)
    |    e.g., ADMIN_DOMAIN=secretpanel → secretpanel.example.com is admin
    | 2. Route prefix fallback (for development/testing)
    |    e.g., /admin/login → admin context
    |
    */
    'contexts' => [
        'customer' => [
            'prefix' => '',                     // No prefix for customer routes
            'allow_registration' => true,
            'allow_remember' => true,
            'allow_password_reset' => true,
            'require_2fa' => false,
            'require_email_verification' => true,
            'rate_limit_attempts' => 5,
            'redirects' => [
                'login' => '/login',
                'home' => '/dashboard',
                'logout' => '/',
            ],
        ],
        'admin' => [
            // In production: detected via ADMIN_DOMAIN env var (subdomain)
            // In development: fallback to /admin/ prefix
            'prefix' => 'admin',
            'allow_registration' => false,      // Admins created by other admins
            'allow_remember' => false,          // Security: no persistent sessions
            'allow_password_reset' => true,
            'require_2fa' => true,              // Mandatory 2FA
            'require_email_verification' => true,
            'rate_limit_attempts' => 3,         // Stricter rate limiting
            'redirects' => [
                // These work for both subdomain and prefix modes
                'login' => '/login',            // On admin domain, this is the login
                'home' => '/dashboard',         // On admin domain, this is admin dashboard
                'logout' => '/login',
            ],
            'roles' => ['admin', 'super_admin'], // Required roles
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects (Default/Fallback)
    |--------------------------------------------------------------------------
    */
    'redirects' => [
        'login' => '/login',
        'logout' => '/',
        'home' => '/dashboard',
        'verified' => '/dashboard',
        'password_reset' => '/login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    */
    'verification' => [
        'enabled' => true,
        'expire' => 1440,               // 24 hours in minutes
        'throttle' => 60,               // Seconds between resend
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => true,
        'events' => [
            'login_success',
            'login_failure',
            'logout',
            'password_change',
            'password_reset',
            '2fa_enabled',
            '2fa_disabled',
            'session_invalidated',
            'account_locked',
            'account_unlocked',
        ],
    ],
];
