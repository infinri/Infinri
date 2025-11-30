# Auth Module

Authentication, authorization, and session management for Infinri Framework.

## Features

- **Authentication**: Login, logout, remember me, session management
- **Password Security**: Argon2id hashing, strength validation, breach detection
- **Two-Factor Auth**: TOTP-based 2FA with recovery codes
- **Authorization**: Role-based access control (RBAC) with permissions
- **Rate Limiting**: Login attempt throttling and lockout protection
- **API Tokens**: Personal access tokens for API authentication

## Installation

Auth is a core module and is enabled by default. Run setup to create tables:

```bash
php bin/console s:up
```

This will:
1. Create auth tables (roles, permissions, sessions, tokens, etc.)
2. Seed default roles (super_admin, admin, customer)
3. Seed default permissions

## Configuration

Edit `Config/auth.php` to customize:

```php
return [
    'guards' => [
        'web' => ['driver' => 'session'],
        'api' => ['driver' => 'token'],
    ],
    'passwords' => [
        'expire' => 60,        // Reset token expiry (minutes)
        'throttle' => 60,      // Throttle between requests
    ],
    'two_factor' => [
        'enabled' => true,
        'issuer' => 'Infinri',
    ],
];
```

## Usage

### Authentication

```php
// Check if authenticated
if (auth()->check()) {
    $user = auth()->user();
}

// Login
auth()->attempt(['email' => $email, 'password' => $password]);

// Logout
auth()->logout();
```

### Authorization

```php
// Check permission
if (auth()->user()->can('users.edit')) {
    // ...
}

// Check role
if (auth()->user()->hasRole('admin')) {
    // ...
}

// In controllers
$this->authorize('edit', $post);
```

### Two-Factor Authentication

```php
// Enable 2FA
$twoFactor = app(TwoFactorService::class);
$secret = $twoFactor->generateSecret($user);
$qrCode = $twoFactor->getQrCodeUrl($user, $secret);

// Verify code
$twoFactor->verify($user, $code);
```

## Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/login` | Login form |
| POST | `/login` | Process login |
| POST | `/logout` | Logout |
| GET | `/register` | Registration form |
| POST | `/register` | Process registration |
| GET | `/forgot-password` | Password reset request |
| POST | `/forgot-password` | Send reset email |
| GET | `/reset-password/{token}` | Reset password form |
| POST | `/reset-password` | Process password reset |
| GET | `/two-factor/challenge` | 2FA challenge |
| POST | `/two-factor/challenge` | Verify 2FA code |

## Structure

```
Auth/
├── module.json
├── AuthServiceProvider.php
├── Config/
│   ├── auth.php
│   ├── routes.php
│   └── Api/routes_api.php
├── Setup/
│   ├── schema.php              # Database tables
│   └── Data/
│       └── SeedDefaultRoles.php
├── Contracts/
│   ├── GuardInterface.php
│   └── UserRepositoryInterface.php
├── Controllers/
│   ├── LoginController.php
│   ├── RegisterController.php
│   ├── PasswordResetController.php
│   └── TwoFactorController.php
├── Guards/
│   ├── SessionGuard.php
│   └── TokenGuard.php
├── Middleware/
│   ├── Authenticate.php
│   ├── Guest.php
│   └── RequireTwoFactor.php
├── Services/
│   ├── AuthManager.php
│   ├── PasswordResetService.phpb
│   └── TwoFactorService.php
└── view/base/templates/
    ├── login.phtml
    ├── register.phtml
    └── two-factor/
        ├── challenge.phtml
        └── setup.phtml
```

## Database Tables

| Table | Description |
|-------|-------------|
| `passwords` | Password hashes with history (supports any user type) |
| `password_resets` | Password reset tokens |
| `roles` | User roles |
| `permissions` | Granular permissions |
| `role_user` | User-role assignments |
| `permission_role` | Role-permission assignments |
| `sessions` | Database sessions |
| `personal_access_tokens` | API tokens |
| `remember_tokens` | Remember me tokens |
| `login_attempts` | Security audit log |

## Events

- `UserLoggedIn` - Fired after successful login
- `UserLoggedOut` - Fired after logout
- `PasswordReset` - Fired after password reset
- `TwoFactorEnabled` - Fired when 2FA is enabled

## Security

- Passwords hashed with Argon2id (bcrypt fallback)
- CSRF protection on all forms
- Rate limiting on login attempts
- Secure session handling with regeneration
- HttpOnly, Secure, SameSite cookies

## Testing

```bash
./vendor/bin/phpunit tests/Unit/Modules/Auth
```

## License

Proprietary - Infinri Framework
