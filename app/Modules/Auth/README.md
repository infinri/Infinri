# Auth Module Implementation Checklist

This document tracks all features required for enterprise-grade authentication.

## Status Legend
- ⬜ Not Started
- 🔄 In Progress  
- ✅ Complete (in Core)
- ☑️ Complete (in Auth Module)

---

## 1. Core Framework Features (Prerequisites)

These must be implemented in `app/Core` before Auth module work begins.

### Cookie Security
- ✅ Basic Cookie class with secure defaults
- ✅ HttpOnly flag (default: true)
- ✅ Secure flag (default: true)
- ✅ SameSite attribute (default: Lax)
- ✅ Path/Domain scoping
- ✅ TTL control (session, forever, minutes)
- ✅ Cookie queueing in Response
- ✅ Signed cookies (HMAC) - `CookieEncrypter`
- ✅ Encrypted cookies (AES-256-GCM) - `CookieEncrypter`
- ✅ Cookie prefix support (`__Host-`, `__Secure-`)
- ✅ EncryptCookies middleware
- ✅ Cookie helper functions (`cookie()`, `cookie_get()`, `cookie_string()`, etc.)
- ✅ Type-safe cookie reading with validation

### Session Security
- ✅ Session Manager
- ✅ Session regeneration (`regenerate()`)
- ✅ Redis session handler
- ✅ Session flash data
- ⬜ Session garbage collection config

### CSRF Protection
- ✅ CSRF token generation
- ✅ CSRF verification middleware
- ✅ Token rotation

### Rate Limiting
- ✅ Rate limiter middleware
- ⬜ Login-specific rate limiting rules

---

## 2. Auth Module Components

### 2.1 User Model & Repository
- ⬜ `User` model with:
  - ⬜ `id`, `email`, `password`, `name`
  - ⬜ `email_verified_at`, `created_at`, `updated_at`
  - ⬜ `remember_token` for persistent login
  - ⬜ `two_factor_secret`, `two_factor_recovery_codes`
- ⬜ `UserRepository` interface
- ⬜ `DatabaseUserRepository` implementation
- ⬜ Database migration for users table

### 2.2 Password Security
- ⬜ Password hashing (Argon2id preferred, bcrypt fallback)
- ⬜ Password strength validation rules
- ⬜ Password history (prevent reuse)
- ⬜ Rehashing on algorithm upgrade

### 2.3 Authentication Guards
- ⬜ `AuthManager` - manages guards
- ⬜ `SessionGuard` - web session auth
- ⬜ `TokenGuard` - API token auth
- ⬜ `GuardInterface` contract

### 2.4 Login Flow
- ⬜ `LoginController`
  - ⬜ Show login form
  - ⬜ Attempt authentication
  - ⬜ Handle 2FA if enabled
  - ⬜ Redirect on success
- ⬜ Login rate limiting (5 attempts per minute)
- ⬜ Session regeneration on login
- ⬜ "Remember me" functionality
  - ⬜ Secure token generation
  - ⬜ Token hashing in database
  - ⬜ Token rotation on use
  - ⬜ Device/IP binding (optional)

### 2.5 Logout Flow
- ⬜ `LogoutController`
  - ⬜ Invalidate session
  - ⬜ Clear remember token
  - ⬜ Regenerate CSRF token
  - ⬜ Clear auth cookies

### 2.6 Registration Flow
- ⬜ `RegisterController`
  - ⬜ Show registration form
  - ⬜ Validate input
  - ⬜ Create user
  - ⬜ Send verification email
  - ⬜ Auto-login after registration (configurable)

### 2.7 Email Verification
- ⬜ `VerificationController`
  - ⬜ Send verification email
  - ⬜ Verify token
  - ⬜ Resend verification
- ⬜ Signed URL generation
- ⬜ Token expiration (24 hours)

### 2.8 Password Reset
- ⬜ `ForgotPasswordController`
  - ⬜ Show forgot password form
  - ⬜ Send reset email
- ⬜ `ResetPasswordController`
  - ⬜ Show reset form
  - ⬜ Validate token
  - ⬜ Update password
  - ⬜ Invalidate all sessions
- ⬜ Reset token table/storage
- ⬜ Token expiration (1 hour)

### 2.9 Two-Factor Authentication (2FA)
- ⬜ TOTP implementation (Google Authenticator compatible)
- ⬜ QR code generation for setup
- ⬜ Recovery codes (8 single-use codes)
- ⬜ 2FA enforcement middleware
- ⬜ 2FA enable/disable flow
- ⬜ Backup codes regeneration

### 2.10 Authorization (Roles & Permissions)
- ⬜ `Role` model
- ⬜ `Permission` model
- ⬜ User-Role relationship (many-to-many)
- ⬜ Role-Permission relationship (many-to-many)
- ⬜ `Gate` class for authorization checks
- ⬜ `@can` directive for views
- ⬜ Policy classes for model authorization
- ⬜ Super admin bypass

---

## 3. Middleware

- ⬜ `Authenticate` - require authentication
- ⬜ `RedirectIfAuthenticated` - guest-only routes
- ⬜ `EnsureEmailIsVerified` - verified users only
- ⬜ `Require2FA` - enforce 2FA completion
- ⬜ `CheckRole` - role-based access
- ⬜ `CheckPermission` - permission-based access

---

## 4. Events

- ⬜ `UserRegistered`
- ⬜ `UserLoggedIn`
- ⬜ `UserLoggedOut`
- ⬜ `UserFailedLogin`
- ⬜ `UserLockedOut`
- ⬜ `PasswordReset`
- ⬜ `EmailVerified`
- ⬜ `TwoFactorEnabled`
- ⬜ `TwoFactorDisabled`

---

## 5. Views & Templates

- ⬜ Login form
- ⬜ Registration form
- ⬜ Forgot password form
- ⬜ Reset password form
- ⬜ Email verification notice
- ⬜ 2FA challenge form
- ⬜ 2FA setup wizard

---

## 6. API Authentication (Optional)

- ⬜ Personal access tokens
- ⬜ Token abilities/scopes
- ⬜ Token expiration
- ⬜ Token revocation
- ⬜ API rate limiting per token

---

## 7. Security Hardening

### Session Security
- ⬜ Session fixation protection (regenerate on login)
- ⬜ Concurrent session limiting (optional)
- ⬜ Session activity tracking
- ⬜ "Log out other devices" feature

### Cookie Security (Auth-specific)
- ⬜ Device-bound cookies (user-agent + IP hash)
- ⬜ Remember token rotation on each use
- ⬜ Separate auth cookie from session cookie

### Cookie Implementation Guidelines
```php
// Use core cookie helpers with secure defaults
use function cookie;
use function cookie_get;
use function cookie_string;
use function cookie_int;
use function cookie_forget;

// Remember me token (encrypted, long-lived)
// Use __Host- prefix for maximum security
$rememberToken = bin2hex(random_bytes(32));
$response->withCookie(\App\Core\Http\Cookie::host('remember', $rememberToken, 60 * 24 * 30)); // 30 days

// Auth session cookie (encrypted, session-only)
$response->withCookie(\App\Core\Http\Cookie::host('auth_session', $sessionId, 0)); // Session

// 2FA challenge cookie (short-lived)
$response->withCookie(cookie('2fa_challenge', $challengeId, 5)); // 5 minutes

// Clear auth cookies on logout
$response->withCookie(cookie_forget('__Host-remember'));
$response->withCookie(cookie_forget('__Host-auth_session'));
```

### Auth Cookie Security Requirements
| Cookie | HttpOnly | Secure | SameSite | Expiration | Encrypted | Prefix |
|--------|----------|--------|----------|------------|-----------|--------|
| `remember` | Yes | Yes | Strict | 30 days | Yes | `__Host-` |
| `auth_session` | Yes | Yes | Strict | Session | Yes | `__Host-` |
| `2fa_challenge` | Yes | Yes | Strict | 5 min | Yes | None |
| `device_token` | Yes | Yes | Strict | 1 year | Yes | `__Secure-` |

### Brute Force Protection
- ⬜ Progressive delays after failed attempts
- ⬜ Account lockout after X failures
- ⬜ Lockout notification email
- ⬜ IP-based blocking (optional)
- ⬜ CAPTCHA after failures (integrate with ReCaptcha module)

### Audit Logging
- ⬜ Login attempts (success/failure)
- ⬜ Password changes
- ⬜ 2FA changes
- ⬜ Session invalidations
- ⬜ Permission changes

---

## 8. Configuration

```php
// config/auth.php
return [
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
        ],
    ],
    
    'providers' => [
        'users' => [
            'driver' => 'database',
            'table' => 'users',
        ],
    ],
    
    'passwords' => [
        'users' => [
            'table' => 'password_resets',
            'expire' => 60, // minutes
            'throttle' => 60, // seconds between requests
        ],
    ],
    
    'password_rules' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'check_compromised' => true, // Check HaveIBeenPwned
    ],
    
    'lockout' => [
        'max_attempts' => 5,
        'decay_minutes' => 15,
        'lockout_minutes' => 60,
    ],
    
    'remember' => [
        'enabled' => true,
        'lifetime' => 43200, // 30 days in minutes
        'rotate' => true,
    ],
    
    '2fa' => [
        'enabled' => true,
        'enforce' => false, // Require 2FA for all users
        'issuer' => 'Infinri',
    ],
];
```

---

## 9. Database Schema

### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);
```

### password_resets
```sql
CREATE TABLE password_resets (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);
```

### roles
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### permissions
```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### role_user
```sql
CREATE TABLE role_user (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

### permission_role
```sql
CREATE TABLE permission_role (
    permission_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

### sessions (for database sessions)
```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);
```

### personal_access_tokens (for API)
```sql
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 10. File Structure

```
app/Modules/Auth/
├── module.php
├── routes.php
├── config.php
├── AuthServiceProvider.php
├── Controllers/
│   ├── LoginController.php
│   ├── LogoutController.php
│   ├── RegisterController.php
│   ├── ForgotPasswordController.php
│   ├── ResetPasswordController.php
│   ├── VerificationController.php
│   └── TwoFactorController.php
├── Guards/
│   ├── GuardInterface.php
│   ├── SessionGuard.php
│   └── TokenGuard.php
├── Models/
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   └── PersonalAccessToken.php
├── Middleware/
│   ├── Authenticate.php
│   ├── RedirectIfAuthenticated.php
│   ├── EnsureEmailIsVerified.php
│   ├── Require2FA.php
│   ├── CheckRole.php
│   └── CheckPermission.php
├── Events/
│   ├── UserRegistered.php
│   ├── UserLoggedIn.php
│   ├── UserLoggedOut.php
│   ├── UserFailedLogin.php
│   └── PasswordReset.php
├── Notifications/
│   ├── VerifyEmail.php
│   ├── ResetPassword.php
│   └── AccountLockedOut.php
├── Services/
│   ├── AuthManager.php
│   ├── PasswordHasher.php
│   ├── TwoFactorService.php
│   └── TokenService.php
├── Repositories/
│   ├── UserRepositoryInterface.php
│   └── DatabaseUserRepository.php
└── view/
    └── frontend/
        └── templates/
            ├── login.html.twig
            ├── register.html.twig
            ├── forgot-password.html.twig
            ├── reset-password.html.twig
            ├── verify-email.html.twig
            └── two-factor/
                ├── challenge.html.twig
                └── setup.html.twig
```

---

## Implementation Order

1. **Phase 1: Basic Auth**
   - User model & migration
   - Password hashing
   - Session guard
   - Login/Logout controllers
   - Basic middleware

2. **Phase 2: Registration & Email**
   - Registration flow
   - Email verification
   - Password reset

3. **Phase 3: Security Hardening**
   - Rate limiting
   - Session regeneration
   - Remember me tokens
   - Audit logging

4. **Phase 4: 2FA**
   - TOTP implementation
   - Recovery codes
   - 2FA middleware

5. **Phase 5: Authorization**
   - Roles & permissions
   - Gate & policies
   - Role middleware

6. **Phase 6: API Auth (Optional)**
   - Personal access tokens
   - Token guards
   - API rate limiting

---

## Dependencies

### Required Packages
```json
{
    "paragonie/constant_time_encoding": "^2.6",
    "pragmarx/google2fa": "^8.0"
}
```

### Core Dependencies (already available)
- Session management
- Cookie management
- CSRF protection
- Rate limiting
- Database layer
- Mail module
- Validation
