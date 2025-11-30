# Admin Module

Admin panel for Infinri platform management.

## Status: Planned

**Depends on:** Auth module (must be implemented first)

---

## Overview

The Admin module provides:
1. **Admin Users** — Separate from frontend customers (Magento-style)
2. **Admin Panel Shell** — Sidebar, header, dashboard layout
3. **Admin Authentication** — Login/logout using Auth module infrastructure
4. **Roles & Permissions** — Admin-specific access control
5. **Menu System** — Other modules register their admin menus here

---

## Why Separate Admin Users?

| Admin Users | Customers (Future) |
|-------------|-------------------|
| `admin_users` table | `customers` table |
| Roles: super_admin, editor, viewer | Roles: customer |
| Access: `/admin/*` | Access: `/account/*` |
| Attributes: role, permissions, last_login_ip | Attributes: addresses, orders, wishlist |
| Different auth flow | Different auth flow |
| Security isolation | Security isolation |

**Benefits:**
- Compromised customer session can't access admin
- Different password policies possible
- Different session timeouts
- Clean separation of concerns

---

## Architecture

### Uses Auth Module For:
- Password hashing (`PasswordHasher`)
- Session management (`SessionManager`)
- Token generation (`TokenService`)
- Login throttling (`LoginThrottler`)
- CSRF protection
- 2FA infrastructure

### Admin Module Provides:
- `AdminUser` model (implements `AuthenticatableInterface`)
- `AdminGuard` (uses Auth's session infrastructure)
- Admin login/logout controllers
- Admin-specific middleware
- Dashboard and menu system

---

## Planned Structure

```
app/Modules/Admin/
├── AdminServiceProvider.php
├── module.json
├── routes.php
├── config/
│   └── admin.php
├── Controllers/
│   ├── DashboardController.php
│   ├── LoginController.php
│   ├── LogoutController.php
│   └── ProfileController.php
├── Guards/
│   └── AdminGuard.php
├── Models/
│   ├── AdminUser.php
│   └── AdminRole.php
├── Middleware/
│   ├── AdminAuthenticate.php
│   ├── CheckAdminRole.php
│   └── CheckAdminPermission.php
├── Services/
│   ├── AdminMenuManager.php
│   └── AdminDashboardService.php
├── View/
│   └── admin/
│       ├── templates/
│       │   ├── layouts/
│       │   │   └── app.phtml
│       │   ├── dashboard/
│       │   │   └── index.phtml
│       │   ├── auth/
│       │   │   └── login.phtml
│       │   └── profile/
│       │       └── edit.phtml
│       └── web/
│           ├── css/   → Uses Theme module's admin CSS
│           └── js/    → Uses Theme module's admin JS
└── Setup/
    └── Patch/
        └── Data/
            └── SeedDefaultAdmin.php
```

---

## Database Schema

### admin_users
```sql
CREATE TABLE admin_users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES admin_roles(id)
);
```

### admin_roles
```sql
CREATE TABLE admin_roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    permissions JSON NULL,
    is_super_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### admin_sessions
```sql
CREATE TABLE admin_sessions (
    id VARCHAR(255) PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_last_activity (last_activity)
);
```

---

## Default Roles

| Role | Permissions | Description |
|------|-------------|-------------|
| `super_admin` | All (bypass checks) | Full system access |
| `admin` | Most admin features | Standard admin |
| `editor` | Content management | Blog, pages, media |
| `viewer` | Read-only | View dashboards only |

---

## Menu Registration

Other modules register their admin menus:

```php
// In BlogServiceProvider::boot()
$menuManager = $this->app->get(AdminMenuManager::class);

$menuManager->addSection('content', 'Content', 100);
$menuManager->addItem('content', [
    'label' => 'Blog Posts',
    'route' => 'admin.blog.posts.index',
    'icon' => 'file-text',
    'permission' => 'blog.manage',
]);
```

---

## Implementation Order

1. **After Auth Module is complete**
2. Create AdminUser model + migration
3. Create AdminGuard using Auth infrastructure
4. Create admin login/logout flow
5. Create dashboard shell
6. Create menu system
7. Seed default super_admin user

---

## Configuration

```php
// config/admin.php
return [
    'route_prefix' => 'admin',
    
    'session' => [
        'lifetime' => 120, // minutes
        'expire_on_close' => true,
    ],
    
    'login' => [
        'max_attempts' => 5,
        'lockout_minutes' => 15,
    ],
    
    'password' => [
        'min_length' => 12,
        'require_2fa' => true, // Enforce 2FA for admins
    ],
    
    'default_admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'name' => 'Super Admin',
    ],
];
```

---

## Dependencies

- **Auth Module** — Password hashing, session, throttling, 2FA
- **Theme Module** — Admin CSS/JS (dark neon theme)
- Core's Router, Container, View system
