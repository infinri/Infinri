# Infinri

**A modular PHP platform built with SEI-compliant architecture.**

Designed for maintainability, security, and performance. The core framework is complete — modules are the next development phase.

---

## Project Status

| Phase | Status | Description |
|-------|--------|-------------|
| **Phase 1: Core Framework** | ✅ Complete | DI Container, Router, Events, Config, Cache, Session |
| **Phase 2: Platform Contracts** | ✅ Complete | ModuleInterface, EventSubscriberInterface, TemplateResolver |
| **Phase 3: Module Development** | 🚧 Next | Business logic modules (Blog, Auth, CMS, etc.) |

---

## Project Metrics

### Codebase Size

| Category | Files | Lines of Code |
|----------|-------|---------------|
| **Core Framework** | 166 | 25,017 |
| **Application Total** | 205 | 28,943 |
| **Test Suite** | 144 | 26,501 |
| **Total (with tests)** | 349 | 55,444 |

### Test Coverage

| Metric | Value |
|--------|-------|
| **Total Tests** | 1,885 |
| **Assertions** | 2,923 |
| **Test Duration** | ~4.5s |

### Production Footprint

| Metric | Value |
|--------|-------|
| **App Size (production)** | 3.4 MB |
| **Vendor (production)** | 20 MB |
| **Vendor (with dev)** | 76 MB |
| **Production Dependencies** | 29 packages |
| **Dev Dependencies** | 72 packages |

### Runtime Performance

| Metric | Value |
|--------|-------|
| **Autoload Time** | ~9ms |
| **Bootstrap Time** | ~2ms |
| **Total Boot Time** | ~11ms |
| **Memory (boot)** | 4 MB |
| **Peak Memory** | 6 MB |

### Architecture Components

| Component | Count |
|-----------|-------|
| **Contracts (Interfaces)** | 21 |
| **Console Commands** | 19 |
| **Feature Modules** | 8 |
| **ADRs (Design Decisions)** | 10 |

---

## Architecture

### Core Design (SEI-Compliant)

- **Layers + Microkernel** — Core defines interfaces, modules implement them
- **Dependency Inversion** — Dependencies flow downward only
- **Formal Contracts** — `ModuleInterface`, `EventSubscriberInterface`, `TemplateResolverInterface`
- **Theme Fallback Chain** — Theme → Module → Core template resolution

### Quality Attributes Achieved

| Attribute | Status | Implementation |
|-----------|--------|----------------|
| **Modifiability** | ⭐ Excellent | DI, interfaces, service providers |
| **Performance** | ⭐ Very Good | 11ms boot, 6MB peak memory |
| **Security** | ⭐ Excellent | CSRF, rate limiting, input validation |
| **Testability** | ⭐ Excellent | 1,885 tests, pure PHP, DI |
| **Deployability** | ⭐ Excellent | 3.4MB footprint, predictable |
| **Reusability** | ⭐ Very High | Contracts and providers |
| **Integrability** | ⭐ High | Modules plug in easily |

---

## Quick Start

```bash
git clone https://github.com/infinri/Infinri.git
cd Infinri
composer install
cp .env.example .env
# Edit .env with your configuration
php bin/console s:up  # Setup project
```

### Commands

| Command | Alias | Description |
|---------|-------|-------------|
| `setup:update` | `s:up` | Full project setup (cache, assets, preload) |
| `assets:publish` | - | Publish module assets to pub/ |
| `assets:build` | - | Build production bundles (requires Node.js) |
| `cache:clear` | - | Clear all caches |
| `preload:generate` | - | Generate OPcache preload file |

---

## Project Structure

```
app/
├── Core/               Framework kernel (25,017 LOC)
│   ├── Application.php     Application bootstrap
│   ├── Container/          DI container with autowiring
│   ├── Contracts/          21 interfaces (public API)
│   ├── Router/             HTTP routing
│   ├── Events/             Event dispatcher + subscribers
│   ├── Cache/              File-based caching
│   ├── Config/             Configuration management
│   ├── Console/            19 CLI commands
│   ├── Module/             Module system + registry
│   ├── View/               Layout engine + TemplateResolver
│   └── ...
├── modules/            Feature modules (8 modules)
│   ├── head/           Navigation header
│   ├── footer/         Site footer
│   ├── home/           Landing page
│   ├── about/          About section
│   ├── contact/        Contact form
│   ├── legal/          Legal pages
│   └── error/          Error pages
├── Modules/            Platform modules
│   ├── Theme/          Theme assets and overrides
│   └── Mail/           Email service (Brevo)
bin/                    Console entry point
config/                 Configuration files
docs/                   Architecture documentation (12 docs)
pub/                    Web root
tests/                  Test suite (1,885 tests)
var/                    Runtime data (cache, logs, sessions)
```

---

## Module Development (Next Phase)

The core framework is complete. Modules are the next development focus.

### Creating a Module

```php
use App\Core\Module\AbstractModule;
use App\Core\Contracts\Container\ContainerInterface;

class BlogModule extends AbstractModule
{
    protected string $name = 'blog';
    protected string $version = '1.0.0';
    protected array $dependencies = ['database', 'cache'];
    protected array $providers = [BlogServiceProvider::class];
    protected array $commands = [BlogImportCommand::class];
    
    public function boot(ContainerInterface $container): void
    {
        // Register routes
        $router = $container->make(RouterInterface::class);
        $router->get('/blog', [BlogController::class, 'index']);
        
        // Register event subscribers
        $dispatcher = $container->make(EventDispatcherInterface::class);
        $dispatcher->addSubscriber(new BlogEventSubscriber());
    }
}
```

### Module Lifecycle

```
Discovery → Validation → Registration → Boot
    │           │            │           │
    │           │            │           └─→ Routes, events, config
    │           │            └─→ Bind services to container
    │           └─→ Resolve dependencies, detect cycles
    └─→ Scan directories, load module.php
```

### Planned Modules

- [ ] **Auth** — User authentication and authorization
- [ ] **Blog** — Posts, categories, tags
- [ ] **CMS** — Page management
- [ ] **Media** — File uploads and management
- [ ] **SEO** — Meta tags, sitemaps
- [ ] **Analytics** — Usage tracking

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| **Runtime** | PHP 8.4 (strict types) |
| **Web Server** | Caddy 2.x (HTTP/2, auto-HTTPS) |
| **Database** | PostgreSQL 16 |
| **Cache** | File-based (Redis-ready) |
| **Email** | Brevo API |
| **Testing** | PestPHP + PHPUnit |
| **Assets** | esbuild via Node.js |

---

## Documentation

Comprehensive architecture documentation in `docs/`:

| Document | Purpose |
|----------|---------|
| `01-module-view.md` | Component decomposition, layer rules |
| `02-component-connector-view.md` | Runtime behavior, request flows |
| `03-allocation-view.md` | Deployment mapping |
| `04-quality-scenarios.md` | SEI quality attribute scenarios |
| `05-atam-evaluation.md` | Architecture tradeoff analysis |
| `06-architecture-patterns.md` | Patterns and tactics |
| `07-design-decisions.md` | 10 ADRs with rationale |
| `10-theming-strategy.md` | Theme fallback system |

---

## Security

- **CSRF Protection** — Token verification on all forms
- **Rate Limiting** — Configurable per-endpoint limits
- **Input Validation** — Strict validation with sanitization
- **XSS Prevention** — Output encoding
- **HTTPS Enforcement** — Production auto-redirect
- **Session Security** — Secure cookies, regeneration

---

## Contact

- **GitHub:** [github.com/infinri](https://github.com/infinri)
- **Website:** [infinri.com](https://infinri.com)
