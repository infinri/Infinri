# Portfolio

A modular PHP web application built with clean architecture principles. Designed for maintainability, security, and performance.

## Architecture

**Core Design**
- Modular monolith pattern with clear separation of concerns
- MVC-inspired structure with controller-view pattern
- DRY principles with centralized shared components
- SOLID design patterns applied throughout

**Code Organization**
- Feature-based module structure
- Standardized coding style (PSR-12)
- Type-safe with PHP 8.4 strict types
- Comprehensive docblock documentation

## Security

**Contact Form Protection**
- CSRF token verification on all submissions
- Rate limiting (5 attempts per 5 minutes per IP)
- Honeypot anti-spam field
- Input validation and sanitization
- XSS prevention with output encoding

**Application Security**
- Secure session management
- Environment-based configuration
- Error handling without information leakage
- HTTPS enforcement in production

## Email System

**SMTP Integration**
- PHPMailer for reliable email delivery
- SMTP configuration via environment variables
- Professional HTML email templates
- Reply-to header for direct customer responses
- Automatic space removal in SMTP passwords
- Plain text fallback support

## Performance

**Optimization**
- Caddy web server with HTTP/2 support
- Optimized for low memory usage
- Asset minification and bundling
- Browser caching headers
- Lazy loading for non-critical resources
- Minimal dependency footprint

## User Interface

**Design**
- Dark theme with purple accent color
- Responsive across all device sizes
- WCAG 2.1 AA accessibility standards
- Professional Lucide icon system
- Smooth animations and transitions
- Clean URL structure

## Quick Start

```bash
git clone https://github.com/infinri/Portfolio.git
cd Portfolio
composer install && npm install
cp .env.example .env
# Edit .env with your SMTP credentials
composer setup:upgrade
caddy run
```

Visit `http://localhost:8080`

For detailed setup instructions, environment configuration, and production deployment, see [DEPLOYMENT.md](DEPLOYMENT.md).

## Development

**Build Assets**
```bash
npm run build           # Build and minify CSS/JS
composer setup:upgrade  # Full setup (build + permissions + cache)
```

**Quality Checks**
```bash
composer test       # Run test suite
composer quality    # Run all checks (tests + style + analysis)
composer cs:check   # Check code style (PSR-12)
composer analyze    # Run static analysis (PHPStan Level 6)
```

## Project Structure

```
app/
├── base/           Core framework and shared components
│   ├── console/    CLI commands
│   ├── helpers/    Utility classes (Mail, RateLimiter, etc.)
│   └── view/       Base assets (CSS, JS)
├── modules/        Feature modules
│   ├── head/       Navigation and header
│   ├── footer/     Site footer
│   ├── home/       Landing page
│   ├── about/      About section
│   ├── services/   Services showcase
│   ├── contact/    Contact form with SMTP email
│   └── error/      Error pages (400, 404, 500, maintenance)
bin/                Console entry point
config/             Configuration files
├── services.php    Contact form service dropdown options
pub/                Web root
├── assets/         Published assets
└── index.php       Application entry point
tests/              Test suite
var/                Runtime data (logs, cache, sessions)
```

## Technology Stack

**Backend**
- PHP 8.4 with strict types
- PHPMailer 7.0.0 for SMTP email delivery
- Composer for dependency management

**Frontend**
- Vanilla JavaScript (ES6+)
- Modern CSS3 with custom properties
- Lucide icon system
- No framework dependencies

**Web Server**
- Caddy 2.x with HTTP/2 support
- Optimized for low memory usage
- Automatic HTTPS in production

**Development Tools**
- Pest PHP testing framework
- PHPStan (Level 6) for static analysis
- PHP_CodeSniffer (PSR-12) for code style
- npm for asset bundling and minification

**Infrastructure**
- File-based caching for rate limiting
- Session-based CSRF protection
- Environment-based configuration

## Configuration

**Environment Setup**

Copy `.env.example` to `.env` and configure:
- SMTP settings for email delivery
- Application environment (development/production)
- Security settings (CSRF, HTTPS)

**Customize Contact Form Services**

Edit `config/services.php` to change dropdown options:
```php
return [
    'general' => 'General Inquiry',
    'your-service' => 'Your Service Name',
];
```

## Contact

- **GitHub:** [github.com/infinri](https://github.com/infinri)
- **Repository:** [github.com/infinri/Portfolio](https://github.com/infinri/Portfolio)
- **Website:** [infinri.com](https://infinri.com)
