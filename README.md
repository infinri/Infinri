<div align="center">

# Infinri

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![RoadRunner](https://img.shields.io/badge/RoadRunner-3.x-FF6B00?logo=go&logoColor=white)](https://roadrunner.dev/)

A modern, high-performance web application built with PHP 8.4, RoadRunner, and a modular architecture.

</div>

## 📋 Table of Contents

- [🚀 Features](#-features)
- [🛠 Tech Stack](#-tech-stack)
- [🏗 Project Structure](#-project-structure)
- [🚀 Quick Start](#-quick-start)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [🔧 Development](#-development)
  - [Running Tests](#running-tests)
  - [Code Quality](#code-quality)
  - [Database Migrations](#database-migrations)
- [🌍 Environment Configuration](#-environment-configuration)
- [🚀 Deployment](#-deployment)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)
- [💼 Professional Services](#-professional-services)

## 🚀 Features

- **High Performance**: Built on PHP 8.4 with RoadRunner for optimal performance
- **Modular Architecture**: Clean, maintainable codebase with feature modules
- **Modern Frontend**: HTMX + Alpine.js for interactive UIs
- **Observability**: Built-in logging, metrics, and tracing
- **Developer Experience**: Comprehensive tooling and testing setup

## 🛠 Tech Stack

### Core
- **Runtime**: PHP 8.4 + RoadRunner 3
- **Web Server**: Caddy 2 (auto-TLS/HTTP3)
- **Database**: PostgreSQL 16 + PgBouncer
- **Cache/Queue**: Redis 7
- **ORM**: Cycle 3 + Migrations

### Frontend
- **Templating**: Plates
- **Interactivity**: HTMX + Alpine.js
- **Build Tools**: esbuild + LightningCSS
- **Package Manager**: Bun

### Observability
- **Logging**: Monolog → Loki
- **Metrics**: Prometheus + Grafana
- **Tracing**: OpenTelemetry

### Development
- **Testing**: Pest
- **Static Analysis**: PHPStan
- **CI/CD**: GitHub Actions

## 🏗 Project Structure

```
Infinri/
├── app/
│   └── Modules/          # Feature modules (self-contained components)
│       ├── ModuleName/    # Example module structure (e.g., Core, Contact, Pages)
│       │   ├── Actions/   # Module action classes
│       │   ├── Console/   # Module CLI commands
│       │   ├── Controllers/ # Module controllers
│       │   ├── Models/    # Module models
│       │   ├── Services/  # Module services
│       │   ├── Support/   # Module support classes
│       │   └── Views/     # Module views and layouts
│       │
│       └── Shared/       # Cross-cutting concerns
│           ├── Middleware/
│           ├── Traits/
│           └── Helpers/
│
├── bin/                 # Console scripts
├── config/              # Configuration files
│   ├── containers/      # DI container configs
│   ├── migrations/      # Database migrations
│   └── routes/          # Route definitions
│
├── public/            # Web server root
│   ├── assets/          # Compiled assets (JS/CSS)
│   └── index.php        # Front controller
│
├── resources/         # Source assets and templates
│   ├── views/          # Plates templates
│   │   ├── layouts/    # Base layouts
│   │   └── components/ # Reusable components
│   └── assets/         # Source assets
│       ├── js/         # JavaScript source
│       └── less/       # LESS source files
│
├── storage/           # Storage directory
│   ├── cache/          # Application cache
│   ├── logs/           # Log files
│   └── sessions/       # Session files
│
└── tests/             # Test suite
    ├── Unit/          # Unit tests
    ├── Feature/       # Feature tests
    └── Browser/       # Browser tests
```

## 🚀 Quick Start

### Prerequisites

- PHP 8.4 or higher
- Composer (latest version)
- PostgreSQL 16
- Redis 7
- Node.js 18+ and Bun (or npm/yarn)
- RoadRunner 3

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/infinri/Infinri.git
   cd Infinri
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install frontend dependencies**
   ```bash
   bun install
   # or
   npm install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database and application settings
   ```

5. **Set up database**
   ```bash
   # Run migrations
   php app/cli.php migrations:migrate
   
   # Seed initial data (if available)
   # php app/cli.php db:seed
   ```

6. **Build assets**
   ```bash
   bun run build
   # or
   npm run build
   ```

7. **Start the development server**
   ```bash
   # Start RoadRunner
   ./rr serve -d -c .rr.yaml
   ```

8. **Access the application**
   Open your browser to `http://localhost:8080`

## 🛠 Development

### Running Tests

```bash
# Run PHPUnit tests
./vendor/bin/phpunit

# Run Pest tests
./vendor/bin/pest
```

### Code Quality

```bash
# Run PHP-CS-Fixer
composer cs-fix

# Run PHPStan
composer analyse

# Run all code quality checks
composer check
```

### Database Migrations

```bash
# Create new migration
php app/cli.php migrations:create [migration_name]

# Run migrations
php app/cli.php migrations:migrate

# Rollback last migration
php app/cli.php migrations:rollback
```

## 🚀 Deployment

### Development vs Production

#### Development
For local development, follow the [Quick Start](#-quick-start) guide above. This will set up a development environment with debugging enabled.

#### Production
For production deployment, follow these additional steps:

1. **Server Requirements**
   - Linux server (Ubuntu 22.04 LTS recommended)
   - PHP 8.4 with required extensions
   - PostgreSQL 16
   - Redis 7
   - Node.js 18+ & Bun
   - Nginx or Caddy as reverse proxy

2. **Deployment Steps**
   - Clone the repository to `/var/www/infinri`
   - Install production dependencies: `composer install --optimize-autoloader --no-dev`
   - Build frontend assets: `bun install --production && bun run build`
   - Set up environment variables in `.env`
   - Run database migrations: `php app/cli.php migrations:migrate --force`
   - Configure RoadRunner service
   - Set up web server (Nginx/Caddy) with SSL
   - Configure process manager (e.g., Supervisor) to keep RoadRunner running
   - Set up log rotation and monitoring

3. **Monitoring** (Recommended)
   - Prometheus for metrics
   - Grafana for dashboards
   - Alerting for critical issues

4. **Backup Strategy**
   - Regular database dumps
   - Off-site backup of user uploads
   - Test restoration process periodically

## 📄 License

This project is open source and available under the [MIT License](LICENSE).