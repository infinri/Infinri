# Infinri

**A modular PHP platform built from scratch with SEI-compliant architecture.**

Designed for maintainability, security, and performance — no framework dependencies, pure PHP engineering. The core framework powers a modular platform where every subsystem is handcrafted, tested, and documented.

---

## Project Status

| Phase | Status | Description |
|-------|--------|-------------|
| **Phase 1: Core Framework** | ✅ Complete | DI Container, Router, Events, Config, Cache, Session, Database, Queue |
| **Phase 2: Platform Contracts** | ✅ Complete | 22 interfaces defining the public API surface |
| **Phase 3: Module Development** | 🔨 In Progress | Auth (complete), Admin, Forms, SEO, Marketing, Theme |
| **Phase 4: Business Modules** | 🗓 Planned | Blog, CMS, Media, Analytics |

---

## Project Metrics

### Codebase

| Category | Files | Lines of Code |
|----------|-------|---------------|
| **Core Framework** | 212 | 34,466 |
| **Application Total** | 265 | 41,855 |
| **Test Suite** | 179 | 40,161 |
| **Grand Total** | 444 | 82,016 |

### Test Suite

| Metric | Value |
|--------|-------|
| **Total Tests** | 2,314 |
| **Assertions** | 3,476 |
| **Test Duration** | ~38s |
| **Test Types** | Unit, Integration, Benchmark |

### Architecture

| Component | Count |
|-----------|-------|
| **Contracts (Interfaces)** | 22 |
| **Console Commands** | 25 |
| **Platform Modules** | 6 |
| **Architecture Docs** | 18 |

---

## Architecture

### Core Design Principles (SEI-Compliant)

- **Layers + Microkernel** — Core defines interfaces, modules implement them
- **Dependency Inversion** — All dependencies flow inward toward abstractions
- **Formal Contracts** — 22 interfaces define the entire public API surface
- **Theme Fallback Chain** — Theme → Module → Core template resolution
- **Compiled Infrastructure** — Config, routes, containers, and middleware are compiled to static PHP for production

### Quality Attributes

| Attribute | Rating | Implementation |
|-----------|--------|----------------|
| **Modifiability** | ⭐ Excellent | DI container, 22 interfaces, service providers |
| **Performance** | ⭐ Excellent | O(1) routing, 108µs config, ~11ms boot |
| **Security** | ⭐ Excellent | CSRF, rate limiting, CSP nonces, cookie encryption |
| **Testability** | ⭐ Excellent | 2,314 tests, pure PHP, full DI |
| **Deployability** | ⭐ Excellent | RoadRunner long-running process, Docker-ready |
| **Reusability** | ⭐ Very High | Contract-driven, provider-based architecture |
| **Integrability** | ⭐ High | Module system with dependency resolution |

---

## Performance Benchmarks

*Benchmarked on PHP 8.5.2*

### Routing

| Metric | Value | Rating |
|--------|-------|--------|
| **Scaling Pattern** | O(1) Constant | ✅ Excellent |
| **Static Routes** | ~1.3 µs (782,426 ops/sec) | ✅ |
| **Dynamic Routes** | ~1.5 µs (650,648 ops/sec) | ✅ |
| **1000 Routes (any position)** | ~2.4 µs | ✅ Consistent |
| **Position Sensitivity** | 1.06x | ✅ No degradation |

Indexed route storage with O(1) static lookup and O(k) dynamic segment matching.

### Configuration

| Metric | Value | Rating |
|--------|-------|--------|
| **Single File Load** | 6.4 µs | ✅ |
| **All Config Files** | 19.9 µs | ✅ |
| **Direct Array Access** | 48 ns | ✅ |
| **Dot Notation Access** | 217 ns | ✅ |
| **Cached Config Read** | 10.1 µs | ✅ |

Static compiled config class with OPcache interning.

### Middleware Pipeline

| Metric | Value | Rating |
|--------|-------|--------|
| **Per-Layer Cost** | 0.25 µs | ✅ < 30µs goal |
| **10 Middleware Stack** | 3.54 µs | ✅ Excellent |

### Runtime

| Metric | Value | Rating |
|--------|-------|--------|
| **Bootstrap Time** | 0.12 ms | ✅ Excellent |
| **Container Singleton** | 0.09 µs (11.3M ops/sec) | ✅ |
| **Container Factory** | 0.26 µs (3.9M ops/sec) | ✅ |
| **Event Dispatch (10 listeners)** | 0.08 µs (12.8M ops/sec) | ✅ |
| **Memory Pattern** | Stable (no leaks) | ✅ Excellent |

### Full Benchmark Suite

| Suite | Status | Duration |
|-------|--------|----------|
| autoloader | ✅ | 0.46s |
| module | ✅ | 0.09s |
| middleware | ✅ | 0.09s |
| routing | ✅ | 0.06s |
| database | ✅ | 0.02s |
| reflection | ✅ | <0.01s |
| serialization | ✅ | 1.19s |
| config | ✅ | 0.13s |
| error | ✅ | 0.02s |
| io | ✅ | 0.75s |
| longrunning | ✅ | 0.03s |
| security | ✅ | 0.02s |
| framework | ✅ | <0.01s |

**13 suites, 0 failures, 2.86s total**

---

## Quick Start

### Docker (Recommended)

```bash
git clone https://github.com/infinri/Infinri.git
cd Infinri
cp .env.example .env
docker compose up -d
```

Access at **http://localhost** — includes RoadRunner, PostgreSQL, Redis, and Caddy.

### Native

```bash
git clone https://github.com/infinri/Infinri.git
cd Infinri
composer install
cp .env.example .env
php bin/console s:up
rr serve -c .rr.yaml
```

See [DEPLOYMENT.md](DEPLOYMENT.md) for full production deployment instructions.

### CLI Commands

| Command | Alias | Description |
|---------|-------|-------------|
| `setup:update` | `s:up` | Full project setup (cache, assets, preload) |
| `setup:install` | `s:i` | Initial installation wizard |
| `cache:clear` | — | Clear all caches |
| `module:list` | — | List all modules and their status |
| `module:enable` | — | Enable a module |
| `module:disable` | — | Disable a module |
| `module:make` | — | Scaffold a new module |
| `assets:publish` | — | Publish module assets to pub/ |
| `assets:build` | — | Build production bundles (requires Node.js) |
| `preload:generate` | — | Generate OPcache preload file |
| `key:generate` | — | Generate application encryption key |
| `health:check` | — | Run application health checks |
| `queue:work` | — | Process queued jobs |
| `queue:status` | — | Show queue status |
| `metrics:show` | — | Display application metrics |
| `code:stats` | — | Show codebase statistics |

---

## Project Structure

```
app/
├── Core/                   Framework kernel (212 files, 34K LOC)
│   ├── Application.php         Application bootstrap & lifecycle
│   ├── Authorization/          Gate, policies, RBAC
│   ├── Cache/                  File, Redis, and Array stores
│   ├── Compiler/               Config, container, route, event compilers
│   ├── Config/                 Configuration management
│   ├── Console/                25 CLI commands
│   ├── Container/              IoC container with autowiring
│   ├── Contracts/              22 interfaces (public API)
│   ├── Database/               Query builder, models, relations, migrations
│   ├── Error/                  Exception handling
│   ├── Events/                 Event dispatcher + subscribers
│   ├── Http/                   Request/Response, middleware pipeline
│   ├── Indexer/                Search indexer system
│   ├── Log/                    Structured logging
│   ├── Metrics/                Application metrics collection
│   ├── Module/                 Module loader, registry, renderer
│   ├── Queue/                  Redis-backed job queue
│   ├── Redis/                  Redis manager with connection pooling
│   ├── Routing/                O(1) router with groups & middleware
│   ├── Security/               CSRF, rate limiting, encryption
│   ├── Session/                Session management (file + Redis)
│   ├── Setup/                  Schema management & data patches
│   ├── Support/                Helpers, utilities, environment
│   ├── Validation/             Input validation
│   └── View/                   Layout engine, template resolver, assets
├── Http/                   Application HTTP kernel & middleware
├── Modules/                Platform modules
│   ├── Admin/                  Administration panel
│   ├── Auth/                   Authentication & authorization (2FA, guards)
│   ├── Forms/                  Form handling
│   ├── Marketing/              Marketing tools
│   ├── Seo/                    SEO management
│   └── Theme/                  Theme assets and overrides
├── Providers/              Service providers (Redis, View)
└── config.php              Application configuration
bin/                        Console entry point
config/                     Site configuration (meta, services)
database/                   Migrations and seeders
docs/                       Architecture documentation (18 docs)
docker/                     Docker configuration
pub/                        Web root (public assets, entry point)
routes/                     Route definitions
tests/                      Test suite (179 files, 40K LOC)
var/                        Runtime data (cache, logs, sessions, state)
```

---

## Core Subsystems

### DI Container
Full IoC container with constructor autowiring, singleton management, circular dependency detection, and method injection via reflection.

### Router
O(1) static route lookup and O(k) dynamic segment matching. Supports route groups, named routes, URL generation, middleware attachment, and parameter constraints.

### Module System
Modules are self-contained feature packages with service providers, dependency resolution, lazy loading, and enable/disable lifecycle. The registry supports caching, topological dependency sorting, and cycle detection.

### Database
Complete database layer with PDO-based connection management, fluent query builder, model system with relations (HasOne, HasMany, BelongsTo, BelongsToMany), schema management, migrations, seeders, and factory support for testing.

### Event System
Priority-based event dispatcher with subscriber support, stoppable events, and both interface-based and legacy subscription patterns.

### Queue
Redis-backed job queue with retry logic, delayed jobs, failure tracking, and a dedicated worker process.

### View Engine
Layout-based rendering with template resolver, block inheritance, asset management (CSS/JS with CSP nonce support), and meta tag management.

---

## Module Architecture

Modules follow the service provider pattern. Each module can include routes, controllers, models, views, middleware, commands, and database schemas.

### Module Lifecycle

```
Discovery → Dependency Resolution → Registration → Boot
    │              │                      │           │
    │              │                      │           └── Routes, events, middleware
    │              │                      └── Bind services to container
    │              └── Topological sort, cycle detection
    └── Scan directories, load module.php
```

### Creating a Module

```bash
php bin/console module:make MyModule
```

Or manually create `app/Modules/MyModule/` with a service provider:

```php
namespace App\Modules\MyModule;

use App\Core\Container\ServiceProvider;

class MyModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
    }

    public function boot(): void
    {
        // Load routes, register events, middleware
    }
}
```

See [MODULE-ARCHITECTURE.md](MODULE-ARCHITECTURE.md) for the complete module development guide.

### Planned Modules

- [ ] **Blog** — Posts, categories, tags
- [ ] **CMS** — Page management
- [ ] **Media** — File uploads and management
- [ ] **Analytics** — Usage tracking

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| **Language** | PHP 8.4 (strict types, readonly, enums, fibers) |
| **App Server** | RoadRunner 2024.x (long-running PHP workers) |
| **Web Server** | Caddy 2.x (HTTP/2, automatic HTTPS) |
| **Database** | PostgreSQL 16 |
| **Cache / Queue / Sessions** | Redis 7 |
| **Email** | Symfony Mailer (Brevo API) |
| **HTTP Client** | Guzzle 7 |
| **Testing** | PestPHP + PHPUnit |
| **Static Analysis** | PHPStan (strict rules) |
| **Code Style** | PHP-CS-Fixer |
| **Asset Build** | clean-css + terser (Node.js, build-time only) |
| **CI/CD** | GitHub Actions (tests, static analysis, code style) |
| **Containerization** | Docker + Docker Compose |

---

## Documentation

Comprehensive SEI-style architecture documentation in [`docs/`](docs/):

| Document | Purpose |
|----------|---------|
| [`01-module-view.md`](docs/01-module-view.md) | Component decomposition and layer rules |
| [`02-component-connector-view.md`](docs/02-component-connector-view.md) | Runtime behavior and request flows |
| [`03-allocation-view.md`](docs/03-allocation-view.md) | Deployment mapping |
| [`04-quality-scenarios.md`](docs/04-quality-scenarios.md) | SEI quality attribute scenarios |
| [`05-atam-evaluation.md`](docs/05-atam-evaluation.md) | Architecture tradeoff analysis |
| [`06-architecture-patterns.md`](docs/06-architecture-patterns.md) | Patterns and architectural tactics |
| [`07-design-decisions.md`](docs/07-design-decisions.md) | ADRs with rationale |
| [`08-observability-requirements.md`](docs/08-observability-requirements.md) | Logging, metrics, health checks |
| [`09-first-migration-contact.md`](docs/09-first-migration-contact.md) | Migration strategy |
| [`10-theming-strategy.md`](docs/10-theming-strategy.md) | Theme fallback system |
| [`11-theming-strategy-v2.md`](docs/11-theming-strategy-v2.md) | Enhanced theming approach |
| [`13-extension-points.md`](docs/13-extension-points.md) | Plugin and extension mechanisms |
| [`14-operations-guide.md`](docs/14-operations-guide.md) | Operational procedures |
| [`SECURITY-AUDIT.md`](docs/SECURITY-AUDIT.md) | Security audit findings |

See also: [DEPLOYMENT.md](DEPLOYMENT.md) | [MODULE-ARCHITECTURE.md](MODULE-ARCHITECTURE.md)

---

## Security

- **CSRF Protection** — Token verification on all state-changing requests
- **Rate Limiting** — Configurable per-endpoint limits with Redis backing
- **Content Security Policy** — Per-request nonce generation for scripts and styles
- **Cookie Encryption** — All cookies encrypted with application key
- **Input Validation** — Strict validation with sanitization via Symfony Validator
- **XSS Prevention** — Output encoding with `e()` helper
- **HTTPS Enforcement** — Automatic redirect in production
- **Session Security** — Secure cookies, regeneration on authentication, Redis storage
- **Security Headers** — X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS

---

## License

**Proprietary Software** — All Rights Reserved  
Copyright (c) 2024-2026 Lucio Saldivar / Infinri

This software is the exclusive property of Lucio Saldivar / Infinri. Unauthorized copying, distribution, modification, or use is strictly prohibited.

| Document | Description |
|----------|-------------|
| [LICENSE](LICENSE) | Full proprietary license terms |
| [LEGAL.md](docs/LEGAL.md) | Terms of access and legal notices |
| [CONTRIBUTOR-AGREEMENT.md](docs/CONTRIBUTOR-AGREEMENT.md) | Collaborator and contractor terms |

For licensing inquiries: **legal@infinri.com**

---

## Contact

- **GitHub:** [github.com/infinri](https://github.com/infinri)
- **Website:** [infinri.com](https://infinri.com)
- **Licensing:** legal@infinri.com
