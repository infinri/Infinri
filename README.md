# Portfolio

A web application built with clean architecture and best practices. Features modular design, comprehensive testing, and modern development standards.

## Key Features

**Architecture & Design**
- Modular monolith pattern for scalability and maintainability
- Separation of concerns with clean MVC-inspired structure
- DRY principles with centralized shared components
- SOLID design patterns throughout codebase

**Security & Reliability**
- CSRF protection and XSS prevention
- Error handling for common HTTP codes (400, 404, 500) and maintenance mode
- Input validation and output encoding
- Secure session management

**Performance**
- Efficient asset loading with lazy loading
- Browser caching headers configured
- Minimal dependencies

**User Experience**
- Dark theme with royal purple accents
- Responsive design across devices
- WCAG 2.1 AA accessible
- Smooth animations
- Clean URLs

## Technical Stack

**Development Practices**
- Component-based architecture
- Automated testing (168 tests)
- Static analysis for type safety
- CI-ready with quality gates

**Code Organization**
- Separation of concerns (Controller-View pattern)
- Modular structure
- Standardized coding style (PSR-12)
- Documented with docblocks

**Infrastructure**
- Environment-based configuration
- Centralized logging with rotation
- Error pages with smart URL suggestions
- Router-based error handling

## Quick Start

```bash
# Clone repository
git clone https://github.com/infinri/Portfolio.git
cd Portfolio

# Install dependencies
composer install

# Configure environment
cp .env.example .env

# Setup project (publishes assets, clears caches)
composer setup:update

# Run quality checks
composer quality

# Start development server
php -S localhost:8080 -t pub
```

Visit `http://localhost:8080` to view the application.

## Asset Management

The project uses a **copy-based asset management system** instead of symbolic links for better cross-platform compatibility:

```bash
# Publish all assets to pub/assets/
composer assets:publish
# OR
php bin/console assets:publish

# Clear published assets
composer assets:clear
# OR  
php bin/console assets:clear

# Complete project setup (recommended after git clone)
composer setup:update
# OR
php bin/console setup:update
```

**Asset Structure:**
- **Source**: Assets stored in `app/base/view/` and `app/modules/*/view/`
- **Published**: Copied to `pub/assets/` for web access
- **Layers**: base → frontend → module (proper cascade loading)

## Project Structure

- **`app/base/`** - Core framework and shared components
  - `console/` - Command-line interface
  - `view/` - Base assets (CSS, JS)
- **`app/modules/`** - Feature modules
  - `head/` - Navigation and header
  - `footer/` - Site footer
  - `home/` - Landing page
  - `about/` - About section with stats and skills
  - `services/` - Services showcase
  - `contact/` - Contact form with validation
  - `error/` - Error pages (400, 404, 500, maintenance)
- **`bin/`** - Console commands
- **`pub/`** - Public entry point and published assets
- **`tests/`** - Test suite (168 tests)

## Testing & Quality

Run quality checks:

```bash
composer quality    # Run all checks
composer test       # Run test suite
composer cs:check   # Check code style
composer analyze    # Run static analysis
```

## Technology Stack

- **Backend:** PHP 8.4 with strict types
- **Frontend:** Vanilla JavaScript (ES6+), Modern CSS3
- **Testing:** Pest PHP testing framework
- **Quality:** PHPStan (Level 6), PHP_CodeSniffer (PSR-12)
- **Architecture:** Modular monolith with MVC-inspired patterns

## Contact

- **GitHub:** [github.com/infinri](https://github.com/infinri)
- **Repository:** [github.com/infinri/Portfolio](https://github.com/infinri/Portfolio)
