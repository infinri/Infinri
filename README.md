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
- Optimized algorithms (O(1) and O(n) complexity)
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

# Run quality checks
composer quality

# Start development server
php -S localhost:8080 -t pub
```

Visit `http://localhost:8080` to view the application.

## Project Structure

- **`app/base/`** - Core framework and shared components
- **`app/modules/`** - Feature modules
  - `head/` - Navigation and header
  - `footer/` - Site footer
  - `home/` - Landing page
  - `about/` - About section with stats and skills
  - `services/` - Services showcase
  - `contact/` - Contact form with validation
  - `error/` - Error pages (400, 404, 500, maintenance)
- **`pub/`** - Public entry point
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
