# Portfolio Modular Monolith

A lightweight, **multi-context modular monolith** web architecture for a freelance portfolio built with **Pure PHP**, **Vanilla JavaScript**, and **Pure CSS** - no frameworks, no build tools.

## Architecture

**Multi-Context Modular Monolith**: Centralized base layer with context separation (frontend/admin) and self-contained page modules.

```
/app
 ├── base/                      # Centralized shared layer
 │   ├── components/            # Reusable PHP components
 │   └── view/                  # Multi-context view layer
 │       ├── base/              # Universal (shared by all contexts)
 │       │   ├── css/           # Resets, utilities, variables
 │       │   └── js/            # Polyfills, shared scripts
 │       ├── frontend/          # Frontend-specific
 │       │   ├── css/           # Public site theme
 │       │   └── js/            # Frontend interactivity
 │       └── admin/             # Admin-specific (future)
 │           ├── css/           # Dashboard theme
 │           └── js/            # Admin interactivity
 ├── modules/                   # Self-contained page modules
 │   ├── home/
 │   │   ├── index.php
 │   │   └── view/
 │   │       └── frontend/      # Module frontend assets
 │   │           ├── css/
 │   │           └── js/
 │   ├── about/
 │   ├── services/
 │   ├── contact/
 │   ├── head/                  # Header module
 │   ├── footer/                # Footer module
 │   └── error/                 # Error pages
 ├── router.php                 # Central routing logic
 ├── config.php                 # Configuration constants
 └── .htaccess                  # URL rewriting

/pub
 └── media/                     # Public static media

/var
 └── logs/                      # Runtime logs
```

## Core Principles

- **DRY (Don't Repeat Yourself)**: All shared code in `/base` directory
- **Multi-Context Design**: Frontend and admin contexts cleanly separated
- **SOLID Principles**: Single responsibility, dependency injection, open/closed
- **KISS (Keep It Simple)**: No frameworks, no over-engineering
- **Big-O Performance**: O(1) or O(n) operations, no nested loops
- **Security First**: Input sanitization, output encoding, CSRF protection, SQL injection prevention
- **Consistent Structure**: Base and modules follow the same pattern

## Features

- ✅ Clean URL routing (`/services` instead of `?page=services`)
- ✅ SEO-friendly with per-page meta tags
- ✅ Multi-context architecture (frontend + future admin)
- ✅ Context-specific asset loading (efficient, no bloat)
- ✅ Modular architecture - easy to add/remove pages
- ✅ Security hardened (input validation, output escaping, CSRF tokens)
- ✅ Performance optimized (lazy loading, caching headers, minimal overhead)
- ✅ Accessibility compliant (WCAG 2.1 AA)
- ✅ Responsive design
- ✅ Zero dependencies
- ✅ Future-proof for admin panel without refactoring

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Portfolio
   ```

2. **Configure web server**
   - Point document root to project root (not `/app`)
   - Ensure `.htaccess` is enabled (Apache) or configure nginx accordingly

3. **Set permissions**
   ```bash
   chmod 644 app/**/*.php
   chmod 755 app/modules/*/
   ```

4. **Configure environment** (optional)
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

5. **Access the site**
   - Development: `http://localhost/`
   - The home page should load automatically

## Creating New Modules

1. **Create module directory structure**
   ```bash
   mkdir -p app/modules/my-page/view/frontend/css
   mkdir -p app/modules/my-page/view/frontend/js
   ```

2. **Create `index.php`**
   ```bash
   cat > app/modules/my-page/index.php << 'EOF'
   <?php
   if (!defined('APP_INIT')) {
       die('Direct access not permitted');
   }
   
   $meta = [
       'title' => 'My Page - ' . SITE_NAME,
       'description' => 'Page description',
   ];
   ?>
   
   <main class="my-page">
       <div class="container">
           <h1>My Page</h1>
           <p>Content goes here...</p>
       </div>
   </main>
   EOF
   ```

3. **Add optional frontend assets**
   ```bash
   # Module-specific styles
   touch app/modules/my-page/view/frontend/css/my-page.css
   
   # Module-specific scripts
   touch app/modules/my-page/view/frontend/js/my-page.js
   ```

4. **Update whitelist in `config.php`**
   ```php
   define('ALLOWED_MODULES', [
       'home',
       'services',
       'contact',
       'about',
       'my-page',  // Add your module
       '404'
   ]);
   ```

5. **Add navigation link in `modules/head/index.php`**
   ```html
   <li><a href="/my-page">My Page</a></li>
   ```

### Asset Loading (Automatic)
Assets are auto-detected and loaded in this order:
1. **Universal base** → `/app/base/view/base/css/` (always)
2. **Context-specific** → `/app/base/view/frontend/css/` (per context)
3. **Module-specific** → `/app/modules/my-page/view/frontend/css/` (if exists)

## Development Guidelines

### Documentation
- **`QUICKSTART.md`** - Quick reference and common tasks
- **`MODULE_STRUCTURE.md`** - Module development patterns
- **`docs/ARCHITECTURE_GUIDE.md`** - Multi-context architecture guide

### Key Guidelines
- **Code centralization** - All shared code in `/base/view/`
- **Multi-context separation** - Frontend vs admin assets
- **DRY practices** - Universal base shared across contexts
- **SOLID principles** - Single responsibility, dependency injection
- **Security requirements** - Input sanitization, output escaping (non-negotiable)
- **Performance optimization** - O(1) or O(n) operations only
- **Documentation standards** - Docblocks and meaningful comments
- **Accessibility requirements** - WCAG 2.1 AA compliance

## Security Checklist

- ✅ All user input sanitized with `filter_input()`
- ✅ All output escaped with `htmlspecialchars()`
- ✅ Path traversal prevention with whitelist validation
- ✅ CSRF tokens on all forms
- ✅ Prepared statements for database queries
- ✅ Security headers configured in `.htaccess`
- ✅ Sensitive files protected from direct access

## Performance

- **Routing**: O(1) - Direct file checks, no iteration
- **Asset Loading**: O(1) - Direct includes
- **Lazy Loading**: Images load on demand
- **Caching**: Browser caching headers for static assets
- **Minification**: CSS/JS minified in production

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## License

[Your License Here]

## Author

[Your Name]
- Website: [Your Website]
- Email: [Your Email]
- GitHub: [Your GitHub]

## Version

**1.0.0** - Initial release (2025-11-09)
