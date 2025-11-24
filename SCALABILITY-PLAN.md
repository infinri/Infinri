# Infinri Platform Scalability Plan
## IoC Container-Based Architecture Transformation

**Version:** 1.0  
**Date:** November 24, 2025  
**Goal:** Transform Infinri from static portfolio to scalable platform with IoC container architecture  
**Pattern:** Magento/Shopware-style - Installable, extensible, single-tenant platform

---

## 📋 Table of Contents

- [Architecture Overview](#architecture-overview)
- [Phase 1: Core Foundation](#phase-1-core-foundation)
- [Phase 2: HTTP Layer & Routing](#phase-2-http-layer--routing)
- [Phase 3: Database & Models](#phase-3-database--models)
- [Phase 4: Modular Features](#phase-4-modular-features)
- [Phase 5: Admin Panel](#phase-5-admin-panel)
- [Phase 6: Advanced Features](#phase-6-advanced-features)
- [Phase 7: Testing & Documentation](#phase-7-testing--documentation)
- [Migration Strategy](#migration-strategy)
- [Dependencies & Tools](#dependencies--tools)

---

## 🎯 Architecture Overview

### Core Principles

**Inversion of Control (IoC)**
- Core defines contracts (interfaces)
- Modules implement contracts
- Zero hard dependencies between core and modules
- Dependency injection throughout

**Service Container Pattern**
- Automatic dependency resolution
- Constructor injection
- Singleton management
- Service provider registration

**Plugin Architecture**
- Core provides lifecycle and environment
- Modules extend via service providers
- Hook/event system for extensibility
- Enable/disable modules without code changes

### Architecture Layers

```
┌─────────────────────────────────────────────────────┐
│                  APPLICATION LAYER                   │
│              (Controllers, Commands)                 │
└──────────────────┬───────────────────────────────────┘
                   │
┌──────────────────▼───────────────────────────────────┐
│                 SERVICE CONTAINER                     │
│          (Binds interfaces → implementations)         │
└──────────────────┬───────────────────────────────────┘
                   │
        ┌──────────┼──────────┐
        ▼          ▼           ▼
   ┌────────┐ ┌────────┐ ┌────────┐
   │  CORE  │ │MODULES │ │PLUGINS │
   │Contracts│ │Implement│ │Extend  │
   └────────┘ └────────┘ └────────┘
```

### Directory Structure (New)

```
app/
├── Core/                          # Core framework (contracts only)
│   ├── Contracts/                 # Interfaces (NO implementations)
│   ├── Container/                 # IoC container
│   ├── Http/                      # Request/Response
│   ├── Routing/                   # Router
│   ├── Events/                    # Event dispatcher
│   ├── Support/                   # Helpers
│   └── Foundation/                # Application base
│
├── Providers/                     # Service providers
├── Modules/                       # Feature modules
├── Http/                          # HTTP layer
├── Console/                       # CLI layer
├── Models/                        # Data models
├── config/                        # Configuration
└── bootstrap/                     # Bootstrap logic

pub/index.php                      # HTTP entry point
bin/console                        # CLI entry point
```

### Technology Stack

**Core (Zero External Dependencies)**
- PHP 8.4+ with strict types
- Custom IoC container
- Custom router with middleware
- Custom template engine (Blade-inspired)
- File-based caching (Redis optional)

**Optional Extensions**
- PSR-3 Logger (Monolog optional)
- PSR-7 HTTP (if needed for external packages)
- Database: PostgreSQL 16+

---

## 📊 Current State & Migration Strategy

### What's Already Built (November 2025)

**Production-Ready Components** ✅
- ~30,000 LOC working portfolio site
- 8 functional modules (about, contact, error, footer, head, home, legal, services)
- 14 production-ready helpers (Assets, Cache, Env, Esc, Logger, Mail, Meta, Module, Path, RateLimiter, ReCaptcha, Session, Validator, BrevoContacts)
- 6 console commands (setup, assets, minify, permissions, install)
- Excellent security (CSP with nonce, CSRF, session security, rate limiting, input validation)
- Working asset cascade (base → frontend → module)
- Simple router (GET/POST exact match)
- PSR-4 autoloader with convention-based mapping
- Secure `.env` loader with validation

**Current Module Structure:**
```
app/modules/contact/
├── index.php              # Controller (includes view)
├── api.php                # POST endpoint handler
└── view/
    └── frontend/
        ├── css/contact.css
        ├── js/contact.js
        └── templates/contact.php
```

**Architecture Completeness:** ~30% of planned enterprise architecture

### Gap Analysis

| Component | Current | Planned | Status |
|-----------|---------|---------|--------|
| IoC Container | ❌ | ✅ | Phase 1 |
| Service Providers | ❌ | ✅ | Phase 1 |
| Router | ✅ Basic | ✅ Enhanced | Phase 2 |
| Middleware | ❌ | ✅ | Phase 2 |
| Request/Response | ❌ Globals | ✅ Objects | Phase 2 |
| Database Layer | ❌ | ✅ | Phase 3 |
| Schema System | ❌ | ✅ PHP Arrays | Phase 3 |
| Models (ORM) | ❌ | ✅ Active Record | Phase 3 |
| View Engine | ✅ PHP | ✅ Blade-like | Phase 4 |
| Helpers | ✅ Static | ⚠️ Providers | Phase 4 |

### Migration Approach: Parallel Track (Zero Downtime)

**Strategy:** Build new architecture **alongside** existing system, migrate incrementally

**Why Parallel:**
- ✅ Zero breaking changes - production site keeps working
- ✅ Incremental testing - validate each piece before switching
- ✅ Easy rollback - fall back to old system if needed
- ✅ Learn as we go - refine architecture based on real usage
- ✅ Current code is too good to throw away (security, asset management, module pattern)

**Migration Rules:**
1. **Backward Compatibility First** - Never break existing functionality
2. **Test Before Switch** - Each phase has rollback plan
3. **Incremental Adoption** - Don't force all modules at once
4. **Document Everything** - Track all changes and decisions

### Phase-by-Phase Migration Path

**Phase 1 (Container):**
- Create container **without** breaking current system
- Boot container after helpers load
- Old code continues working
- New code can use container
- **Result:** Container exists, helpers still work as static classes

**Phase 2 (HTTP Layer):**
- Add Request/Response **alongside** current router
- Enhance router with parameters (keep exact match as fallback)
- Dual mode: use new if available, else use old
- **Result:** New HTTP layer available, old router still works

**Phase 3 (Database):**
- Add database layer for **future modules**
- No existing modules need changes
- **Result:** Database ready, but modules stay static until Phase 5

**Phase 4 (Refactor Helpers):**
- Convert helpers to service providers
- Helpers become **facades** that call container
- Old code still works (backward compatible)
- **Example:**
  ```php
  // OLD (still works)
  Cache::get('key');
  
  // NEW (container)
  app(CacheInterface::class)->get('key');
  
  // HELPER AS FACADE (BC layer)
  class Cache {
      public static function get($key) {
          return app(CacheInterface::class)->get($key);
      }
  }
  ```

**Phase 5 (Module Migration):**
- Migrate modules one-by-one to new structure
- Order: Contact → Legal → Head → Footer → Home/About/Services → Error
- Each module adds:
  - `{Name}ServiceProvider.php`
  - `schema.php` (if needs database)
  - `Models/` (if needs database)
  - `Controllers/` (refactored from index.php)
  - `Setup/Patch/` (data migrations)

### What to Keep vs Replace

**✅ KEEP (Already Excellent):**
- PSR-4 Autoloader
- Bootstrap (.env loader)
- Security implementation (CSP, CSRF, validation)
- Asset management system
- Console framework
- Module discovery
- View cascade (base → frontend → module)
- All helper **functionality** (wrap in providers)

**⚠️ ENHANCE (Extend, Don't Replace):**
- Router (add param matching, keep exact match)
- Module system (add providers, keep structure)
- View layer (add Blade-like syntax)
- Console (add DB commands)

**➕ ADD (New Capabilities):**
- IoC Container
- Service Providers
- Database layer (PDO, Query Builder, ORM)
- Schema system (PHP Array Schema + Patches)
- Middleware pipeline
- Event system (Phase 6)
- Queue system (Phase 6)

### Success Metrics Per Phase

**Before starting each phase:**
- [ ] All existing functionality working
- [ ] Test suite passing
- [ ] Backup created
- [ ] Rollback plan documented

**After each phase:**
1. **Performance:** Page load time (should stay same or better)
2. **Stability:** Zero 500 errors introduced
3. **Code Quality:** PHPStan level maintained
4. **Test Coverage:** Coverage increases
5. **Developer Experience:** Easier to add features

**Expected Impact:**
- Memory: +1MB (IoC overhead)
- Page Load: +0-20ms (minimal)
- Database Queries: 0 → 2-5 (Phase 3+)
- Total Assets: ~40KB (unchanged)

---

## 🏗️ Phase 1: Core Foundation

**Duration:** 2-3 weeks  
**Goal:** Build the foundational IoC container and core contracts

---

### ⚠️ STRICT SCOPE RULES

**ALLOWED in Phase 1:**
- ✅ Container + dependency injection
- ✅ Core contracts (interfaces only)
- ✅ Config system
- ✅ Application class
- ✅ Helper facades (wrap existing helpers)
- ✅ Observability: Logger, correlation ID, `/health` endpoint

**FORBIDDEN in Phase 1:**
- ❌ **NO Database** - Use existing file-based systems
- ❌ **NO Router changes** - Keep existing router working
- ❌ **NO View engine changes** - Keep plain PHP templates
- ❌ **NO New modules** - Only refactor existing
- ❌ **NO HTTP layer changes** - Keep `pub/index.php` working

**If you're tempted:**
- "I'll just add a quick database query" → NO, Phase 3
- "Let me improve the router while I'm here" → NO, Phase 2
- "This validation logic should be centralized" → NO, Phase 4+
- "I need to refactor the SEO module" → NO, Phase 5+

---

### 🧪 MANDATORY TESTING REQUIREMENTS

**Phase 1 CANNOT MERGE without:**

**Unit Tests (Minimum):**
- [ ] `ContainerTest` - Binding & resolution (10+ assertions)
- [ ] `ContainerTest` - Singleton behavior (5+ assertions)
- [ ] `ContainerTest` - Constructor injection (8+ assertions)
- [ ] `ContainerTest` - Circular dependency detection (3+ assertions)
- [ ] `ContainerTest` - Interface binding (5+ assertions)
- [ ] `ConfigTest` - Config loading and access (8+ assertions)
- [ ] `ApplicationTest` - Bootstrap process (5+ assertions)
- [ ] `LoggerTest` - JSON logging with correlation ID (6+ assertions)

**Integration Tests (Minimum):**
- [ ] **Phase 1 Integration Test:**
  - Bootstrap application with container
  - Load config from `.env`
  - Resolve service with dependencies
  - Access config through injected service
  - Log entry with correlation ID
  - Health check returns valid JSON
  - **Time limit:** <50ms
  - **Memory limit:** <10MB

**Coverage Target:**
- Minimum: 85% code coverage
- Container: 95% coverage
- Config: 90% coverage

**Test Command:**
```bash
# Run Phase 1 tests
vendor/bin/phpunit --testsuite=Phase1

# Coverage report
vendor/bin/phpunit --coverage-html coverage/
```

---

### 1.1 Service Container Implementation

**Files to Create:**

```
app/Core/Container/
├── Container.php                  # Main IoC container
├── ServiceProvider.php            # Base service provider
├── BindingResolutionException.php # Custom exception
└── ContainerContract.php          # Container interface
```

**Implementation Details:**

**Container.php** - Core IoC container with auto-resolution
- `bind(string $abstract, $concrete, bool $singleton)` - Register binding
- `singleton(string $abstract, $concrete)` - Register singleton
- `make(string $abstract, array $params)` - Resolve dependency
- `instance(string $abstract, $instance)` - Register existing instance
- `alias(string $abstract, string $alias)` - Create alias
- Auto-resolve dependencies via reflection
- Recursive dependency resolution
- Constructor parameter injection
- Circular dependency detection

**ServiceProvider.php** - Base class for all providers
- `register()` - Register bindings (required abstract method)
- `boot()` - Bootstrap services (optional, called after all registered)
- Access to `$app` (container instance)
- Deferred loading support (future enhancement)

**Key Features:**
- Automatic constructor injection
- Type-hint based resolution
- Singleton vs transient instances
- Closure-based factories
- Interface-to-implementation binding

**Example Usage:**
```php
// Bind interface to implementation
$container->bind(CacheInterface::class, FileCache::class);

// Singleton binding
$container->singleton(DatabaseInterface::class, function($app) {
    return new Database($app->make('config')->get('database'));
});

// Resolve with auto-injection
$service = $container->make(UserService::class);
// UserService dependencies automatically injected
```

**Testing:**
- Unit test: Simple binding and resolution
- Unit test: Constructor injection
- Unit test: Singleton vs transient
- Unit test: Circular dependency detection
- Unit test: Interface binding

---

### 1.2 Core Contracts (Interfaces)

**Files to Create:**

```
app/Core/Contracts/
├── Container/
│   └── ContainerInterface.php
├── Config/
│   └── ConfigInterface.php
├── Http/
│   ├── KernelInterface.php
│   ├── RequestInterface.php
│   ├── ResponseInterface.php
│   └── MiddlewareInterface.php
├── Routing/
│   ├── RouterInterface.php
│   ├── RouteInterface.php
│   └── RouteCollectionInterface.php
├── View/
│   ├── ViewInterface.php
│   └── ViewFactoryInterface.php
├── Cache/
│   └── CacheInterface.php
├── Events/
│   ├── EventDispatcherInterface.php
│   └── ListenerInterface.php
├── Database/
│   ├── ConnectionInterface.php
│   ├── QueryBuilderInterface.php
│   └── ModelInterface.php
└── Logging/
    └── LoggerInterface.php
```

**Key Contracts:**

**ContainerInterface** - Service container contract
```php
interface ContainerInterface {
    public function bind(string $abstract, $concrete, bool $singleton): void;
    public function singleton(string $abstract, $concrete): void;
    public function make(string $abstract, array $parameters = []);
    public function bound(string $abstract): bool;
    public function alias(string $abstract, string $alias): void;
}
```

**RequestInterface** - HTTP request abstraction
```php
interface RequestInterface {
    public function method(): string;
    public function path(): string;
    public function input(string $key, $default = null);
    public function all(): array;
    public function header(string $key, $default = null);
    public function ip(): string;
    public function isJson(): bool;
}
```

**ResponseInterface** - HTTP response abstraction
```php
interface ResponseInterface {
    public function setContent(string $content): self;
    public function getContent(): string;
    public function setStatusCode(int $code): self;
    public function header(string $key, string $value): self;
    public function send(): void;
}
```

**RouterInterface** - Routing contract
```php
interface RouterInterface {
    public function get(string $path, $handler): RouteInterface;
    public function post(string $path, $handler): RouteInterface;
    public function group(array $attributes, callable $callback): void;
    public function middleware($middleware): self;
    public function dispatch(RequestInterface $request): ResponseInterface;
}
```

**CacheInterface** - Caching contract
```php
interface CacheInterface {
    public function get(string $key, $default = null);
    public function put(string $key, $value, int $ttl = null): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function remember(string $key, int $ttl, callable $callback);
}
```

**Testing:**
- Interface validation (syntax check)
- Contract completeness review
- Method signature consistency

---

### 1.3 Configuration System

**Files to Create:**

```
app/Core/Config/
├── Repository.php                 # Config repository
└── ConfigServiceProvider.php      # Config service provider

app/config/                        # Configuration files
├── app.php                        # Application config
├── cache.php                      # Cache config
├── database.php                   # Database config
├── mail.php                       # Mail config
├── modules.php                    # Module registry
└── providers.php                  # Service providers list
```

**Repository.php** - Configuration management
- Load config files from `app/config/`
- Dot notation access: `config('database.default')`
- Environment variable replacement: `env('DB_HOST', 'localhost')`
- Nested array support
- Config caching (production)
- Type-safe getters

**Config Files Structure:**

**app.php** - Application configuration
```php
return [
    'name' => env('APP_NAME', 'Infinri'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'America/Chicago',
    'locale' => 'en',
];
```

**cache.php** - Cache configuration
```php
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],
    ],
];
```

**providers.php** - Service providers to load
```php
return [
    // Core providers
    App\Providers\ConfigServiceProvider::class,
    App\Providers\RoutingServiceProvider::class,
    App\Providers\ViewServiceProvider::class,
    
    // Feature providers
    App\Providers\CacheServiceProvider::class,
    App\Providers\DatabaseServiceProvider::class,
    
    // Module providers
    App\Modules\Seo\SeoServiceProvider::class,
    App\Modules\Mail\MailServiceProvider::class,
];
```

**Key Features:**
- Lazy loading of config files
- Environment-specific overrides
- Config caching for production
- Helper function: `config($key, $default)`
- Nested key access with dot notation

**Testing:**
- Unit test: Load config file
- Unit test: Dot notation access
- Unit test: Environment variable replacement
- Unit test: Default values
- Unit test: Nested arrays

---

### 1.4 Application Foundation

**Files to Create:**

```
app/Core/Foundation/
├── Application.php                # Main application class
├── Bootstrap/
│   ├── LoadEnvironment.php        # Load .env
│   ├── LoadConfiguration.php      # Load configs
│   ├── RegisterProviders.php      # Register service providers
│   ├── BootProviders.php          # Boot providers
│   └── RegisterFacades.php        # Register facades (optional)

bootstrap/
├── app.php                        # Create app instance
└── console.php                    # Create console app
```

**Application.php** - Main application container
- Extends `Container`
- Implements `ContainerInterface`
- Application lifecycle management
- Service provider registration and booting
- Base path management
- Environment detection
- Singleton instance

**Methods:**
- `__construct(string $basePath)` - Initialize app
- `register(ServiceProvider $provider)` - Register provider
- `boot()` - Boot all registered providers
- `handle(Request $request): Response` - Handle HTTP request
- `terminate(Request $request, Response $response)` - Cleanup
- `basePath(string $path = '')` - Get base path
- `storagePath(string $path = '')` - Get storage path
- `publicPath(string $path = '')` - Get public path

**Bootstrap Sequence:**

```php
// bootstrap/app.php
use App\Core\Foundation\Application;

$app = new Application(
    basePath: dirname(__DIR__)
);

// Bind core contracts
$app->singleton(
    App\Core\Contracts\Http\KernelInterface::class,
    App\Http\Kernel::class
);

$app->singleton(
    App\Core\Contracts\Console\KernelInterface::class,
    App\Console\Kernel::class
);

return $app;
```

**pub/index.php** - HTTP entry point
```php
require __DIR__ . '/../bootstrap/app.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(App\Core\Contracts\Http\KernelInterface::class);

$response = $kernel->handle(
    $request = App\Core\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
```

**Testing:**
- Integration test: Application bootstrap
- Integration test: Service provider registration
- Integration test: Provider booting order
- Integration test: Path helpers

---

### 1.5 Support Utilities

**Files to Create:**

```
app/Core/Support/
├── Arr.php                        # Array helpers
├── Str.php                        # String helpers
├── Collection.php                 # Collection class
├── Facades/
│   ├── Facade.php                 # Base facade
│   ├── Config.php                 # Config facade
│   ├── Cache.php                  # Cache facade
│   └── Route.php                  # Route facade
└── helpers.php                    # Global helper functions
```

**Arr.php** - Array manipulation helpers
- `Arr::get(array $array, string $key, $default)` - Dot notation get
- `Arr::set(array &$array, string $key, $value)` - Dot notation set
- `Arr::has(array $array, string $key)` - Check if key exists
- `Arr::forget(array &$array, string $key)` - Remove key
- `Arr::only(array $array, array $keys)` - Get subset
- `Arr::except(array $array, array $keys)` - Exclude keys
- `Arr::flatten(array $array)` - Flatten nested array
- `Arr::wrap($value)` - Wrap value in array

**Str.php** - String manipulation helpers
- `Str::slug(string $string, string $separator = '-')` - URL-friendly slug
- `Str::camel(string $string)` - camelCase
- `Str::snake(string $string)` - snake_case
- `Str::studly(string $string)` - StudlyCase
- `Str::contains(string $haystack, $needles)` - Check contains
- `Str::startsWith(string $haystack, $needles)` - Check starts with
- `Str::endsWith(string $haystack, $needles)` - Check ends with
- `Str::limit(string $string, int $limit = 100)` - Limit length
- `Str::random(int $length = 16)` - Generate random string

**Collection.php** - Array wrapper with fluent methods
- `map(callable $callback)` - Transform items
- `filter(callable $callback)` - Filter items
- `reduce(callable $callback, $initial)` - Reduce to single value
- `each(callable $callback)` - Iterate over items
- `pluck(string $key)` - Extract column
- `first()` - Get first item
- `last()` - Get last item
- `count()` - Count items
- `toArray()` - Convert to array
- `toJson()` - Convert to JSON

**helpers.php** - Global helper functions
```php
// Application helpers
function app(string $abstract = null);
function config(string $key, $default = null);
function cache(string $key = null);
function env(string $key, $default = null);

// Path helpers
function base_path(string $path = '');
function storage_path(string $path = '');
function public_path(string $path = '');
function config_path(string $path = '');

// Response helpers
function response($content = '', int $status = 200);
function json($data, int $status = 200);
function redirect(string $url, int $status = 302);

// Utility helpers
function collect(array $items = []);
function value($value);
function tap($value, callable $callback);
```

**Testing:**
- Unit tests for all Arr methods
- Unit tests for all Str methods
- Unit tests for Collection methods
- Integration tests for helper functions

---

### Phase 1 Deliverables Checklist

- [ ] Service Container with auto-resolution
- [ ] Core Contracts (all interfaces)
- [ ] Configuration system with env support
- [ ] Application foundation with bootstrap
- [ ] Support utilities (Arr, Str, Collection)
- [ ] Global helper functions
- [ ] Unit tests for container
- [ ] Unit tests for config system
- [ ] Integration tests for bootstrap
- [ ] Documentation for Phase 1

**Estimated Time:** 2-3 weeks  
**Lines of Code:** ~3,000-4,000 LOC  
**Test Coverage Target:** 85%+

---

## 🌐 Phase 2: HTTP Layer & Routing

**Duration:** 2-3 weeks  
**Goal:** Build Request/Response abstractions, enhanced router with middleware, and HTTP kernel

---

### ⚠️ STRICT SCOPE RULES

**ALLOWED in Phase 2:**
- ✅ Request/Response abstractions
- ✅ Router with param matching
- ✅ Middleware pipeline
- ✅ HTTP Kernel
- ✅ ONE test route `/healthz` through new system
- ✅ Observability: Request timing, HTTP metrics

**FORBIDDEN in Phase 2:**
- ❌ **NO Database** - Still Phase 3
- ❌ **NO Controllers** - Keep existing module `index.php` files
- ❌ **NO Auth middleware** - Phase 4+ feature
- ❌ **NO CORS middleware** - Phase 4+ feature
- ❌ **NO Rate limiting** - Phase 4+ feature
- ❌ **NO Validation** - Phase 4+ feature

**Start small:**
- Wire `/healthz` route ONLY through new system
- Keep existing routes on old router temporarily
- Don't migrate modules yet

**If you're tempted:**
- "Let me add authentication while I'm here" → NO, Phase 4
- "I should migrate all routes now" → NO, do `/healthz` first
- "This needs database for session storage" → NO, Phase 3
- "Let me add CORS support" → NO, Phase 4+

---

### 🧪 MANDATORY TESTING REQUIREMENTS

**Phase 2 CANNOT MERGE without:**

**Unit Tests (Minimum):**
- [ ] `RequestTest` - Input, query, headers (12+ assertions)
- [ ] `ResponseTest` - Status codes, headers, body (10+ assertions)
- [ ] `RouterTest` - Route registration & matching (15+ assertions)
- [ ] `RouterTest` - Parameter extraction (8+ assertions)
- [ ] `MiddlewareTest` - Pipeline execution order (6+ assertions)
- [ ] `KernelTest` - Request lifecycle (8+ assertions)

**Integration Tests (Minimum):**
- [ ] **Phase 2 Integration Test:**
  - Bootstrap app + HTTP kernel
  - Register `/healthz` route
  - Send GET request
  - Middleware executes in order
  - Response returns JSON
  - Timing metrics recorded
  - Correlation ID present
  - **Time limit:** <100ms
  - **Memory limit:** <15MB

**Coverage Target:**
- Minimum: 85% code coverage
- Router: 95% coverage
- Request/Response: 90% coverage

**Test Command:**
```bash
# Run Phase 2 tests
vendor/bin/phpunit --testsuite=Phase2

# Test new route
curl http://localhost:8080/healthz
```

---

### 2.1 Request & Response Abstractions

**Files to Create:**

```
app/Core/Http/
├── Request.php                    # HTTP request wrapper
├── Response.php                   # HTTP response
├── JsonResponse.php               # JSON response
├── RedirectResponse.php           # Redirect response
├── StreamedResponse.php           # Streamed response
├── BinaryFileResponse.php         # File download response
├── ParameterBag.php               # Parameter container
├── FileBag.php                    # Uploaded files container
└── HeaderBag.php                  # Headers container
```

**Request.php** - HTTP request abstraction
- `capture()` - Create from globals ($_GET, $_POST, $_SERVER, etc.)
- `method()` - Get HTTP method (GET, POST, PUT, DELETE, PATCH)
- `path()` - Get path without query string
- `url()` - Get full URL without query string
- `fullUrl()` - Get full URL with query string
- `input(string $key, $default)` - Get input from query or body
- `query(string $key, $default)` - Get from query string
- `post(string $key, $default)` - Get from POST body
- `all()` - Get all input
- `only(array $keys)` - Get subset of input
- `except(array $keys)` - Get all except specified
- `has(string $key)` - Check if input exists
- `filled(string $key)` - Check if input exists and not empty
- `header(string $key, $default)` - Get header
- `bearerToken()` - Get bearer token from Authorization header
- `ip()` - Get client IP address
- `userAgent()` - Get user agent string
- `isMethod(string $method)` - Check HTTP method
- `isJson()` - Check if request expects JSON
- `expectsJson()` - Check Accept header
- `wantsJson()` - Check if wants JSON response
- `ajax()` - Check if AJAX request
- `pjax()` - Check if PJAX request
- `secure()` - Check if HTTPS
- `file(string $key)` - Get uploaded file
- `hasFile(string $key)` - Check if file uploaded
- `validate(array $rules)` - Validate input (Phase 4)
- `user()` - Get authenticated user (Phase 4)

**ParameterBag Implementation:**
```php
class ParameterBag {
    protected array $parameters = [];
    
    public function __construct(array $parameters = []) {
        $this->parameters = $parameters;
    }
    
    public function all(): array;
    public function get(string $key, $default = null);
    public function set(string $key, $value): void;
    public function has(string $key): bool;
    public function remove(string $key): void;
    public function keys(): array;
}
```

**Response.php** - HTTP response abstraction
- `setContent(string $content)` - Set response body
- `getContent()` - Get response body
- `setStatusCode(int $code, string $text = null)` - Set status
- `getStatusCode()` - Get status code
- `isSuccessful()` - Check if 2xx status
- `isRedirect()` - Check if 3xx status
- `isClientError()` - Check if 4xx status
- `isServerError()` - Check if 5xx status
- `header(string $key, string $value)` - Set header
- `withHeaders(array $headers)` - Set multiple headers
- `cookie(...)` - Set cookie
- `send()` - Send response to client
- `sendHeaders()` - Send headers only
- `sendContent()` - Send content only

**JsonResponse.php** - JSON response
```php
class JsonResponse extends Response {
    public function __construct(
        $data = null, 
        int $status = 200, 
        array $headers = [], 
        int $options = 0
    );
    
    public function setData($data): self;
    public function getData(): mixed;
}
```

**RedirectResponse.php** - Redirect response
```php
class RedirectResponse extends Response {
    public function __construct(string $url, int $status = 302);
    
    public function getTargetUrl(): string;
    public function with(string $key, $value): self;  // Flash data
    public function withInput(array $input): self;    // Flash input
    public function withErrors(array $errors): self;  // Flash errors
}
```

**Key Features:**
- PSR-7 inspired (but not bound to it)
- Input sanitization and validation
- File upload handling
- Header management
- Cookie support
- Flash data (session-based, Phase 4)

**Testing:**
- Unit test: Request creation from globals
- Unit test: Request input methods
- Unit test: Request header methods
- Unit test: Response content and headers
- Unit test: JSON response encoding
- Unit test: Redirect response
- Integration test: Request/Response lifecycle

---

### 2.2 Enhanced Router

**Files to Create:**

```
app/Core/Routing/
├── Router.php                     # Main router
├── Route.php                      # Single route
├── RouteCollection.php            # Route storage
├── RouteCompiler.php              # Compile routes to regex
├── RouteRegistrar.php             # Fluent route registration
├── RouteGroup.php                 # Route grouping
├── UrlGenerator.php               # Generate URLs from routes
└── Exceptions/
    ├── RouteNotFoundException.php
    └── MethodNotAllowedException.php
```

**Router.php** - Enhanced routing system
- `get(string $uri, $action)` - Register GET route
- `post(string $uri, $action)` - Register POST route
- `put(string $uri, $action)` - Register PUT route
- `patch(string $uri, $action)` - Register PATCH route
- `delete(string $uri, $action)` - Register DELETE route
- `options(string $uri, $action)` - Register OPTIONS route
- `any(string $uri, $action)` - Register route for all methods
- `match(array $methods, string $uri, $action)` - Register for specific methods
- `resource(string $name, string $controller)` - Register RESTful resource
- `group(array $attributes, callable $callback)` - Group routes
- `middleware($middleware)` - Add middleware to routes
- `prefix(string $prefix)` - Add prefix to routes
- `name(string $name)` - Name route group
- `where(array $constraints)` - Add parameter constraints
- `dispatch(Request $request): Response` - Dispatch request

**Route Parameters:**
```php
// Simple parameter
$router->get('/user/{id}', 'UserController@show');

// Optional parameter
$router->get('/user/{id?}', 'UserController@show');

// Parameter with constraint
$router->get('/user/{id}', 'UserController@show')
       ->where('id', '[0-9]+');

// Multiple parameters
$router->get('/post/{category}/{slug}', 'PostController@show')
       ->where(['category' => '[a-z]+', 'slug' => '[a-z0-9-]+']);
```

**Route Groups:**
```php
// Middleware group
$router->middleware(['auth', 'verified'])->group(function($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/profile', 'ProfileController@show');
});

// Prefix group
$router->prefix('admin')->group(function($router) {
    $router->get('/users', 'Admin\UserController@index');
    $router->get('/posts', 'Admin\PostController@index');
});

// Nested groups
$router->prefix('api')->middleware('api')->group(function($router) {
    $router->prefix('v1')->group(function($router) {
        $router->get('/users', 'Api\V1\UserController@index');
    });
});

// Named routes
$router->name('admin.')->group(function($router) {
    $router->get('/dashboard', 'DashboardController@index')->name('dashboard');
    // Results in route name: 'admin.dashboard'
});
```

**RESTful Resources:**
```php
// Register all RESTful routes
$router->resource('posts', 'PostController');

// Generates:
// GET    /posts              → index
// GET    /posts/create       → create
// POST   /posts              → store
// GET    /posts/{id}         → show
// GET    /posts/{id}/edit    → edit
// PUT    /posts/{id}         → update
// DELETE /posts/{id}         → destroy

// Partial resource
$router->resource('photos', 'PhotoController')
       ->only(['index', 'show']);

$router->resource('comments', 'CommentController')
       ->except(['create', 'edit']);
```

**RouteCompiler.php** - Compile routes to regex
- Convert route URI to regex pattern
- Extract parameter names
- Apply parameter constraints
- Handle optional parameters
- Cache compiled routes (production)

**Route Compilation Example:**
```php
// Input: /user/{id}/post/{slug?}
// Output: #^/user/(?P<id>[^/]+)/post(?:/(?P<slug>[^/]+))?$#

// With constraints:
// Input: /user/{id} where id => '[0-9]+'
// Output: #^/user/(?P<id>[0-9]+)$#
```

**UrlGenerator.php** - Generate URLs from named routes
```php
// Named route
$router->get('/user/{id}', 'UserController@show')->name('user.show');

// Generate URL
$url = route('user.show', ['id' => 123]);
// Result: /user/123

// With query string
$url = route('user.show', ['id' => 123, 'tab' => 'posts']);
// Result: /user/123?tab=posts

// Full URL
$url = route('user.show', ['id' => 123], true);
// Result: https://example.com/user/123
```

**Testing:**
- Unit test: Route registration
- Unit test: Route parameter extraction
- Unit test: Route compilation to regex
- Unit test: Route matching
- Unit test: Route groups
- Unit test: RESTful resources
- Unit test: Named routes
- Unit test: URL generation
- Integration test: Full request routing

---

### 2.3 Middleware Pipeline

**Files to Create:**

```
app/Core/Http/Middleware/
├── MiddlewareInterface.php        # Middleware contract
├── Pipeline.php                   # Middleware pipeline
└── MiddlewareStack.php            # Middleware collection

app/Http/Middleware/               # Application middleware
├── TrimStrings.php                # Trim input strings
├── ConvertEmptyStringsToNull.php  # Convert empty to null
├── ValidatePostSize.php           # Validate POST size
├── SubstituteBindings.php         # Route model binding (Phase 3)
└── VerifyCsrfToken.php            # CSRF verification (Phase 4)
```

**MiddlewareInterface.php** - Middleware contract
```php
interface MiddlewareInterface {
    public function handle(Request $request, Closure $next): Response;
}
```

**Pipeline.php** - Middleware execution pipeline
```php
class Pipeline {
    protected $passable;           // Request
    protected array $pipes = [];   // Middleware
    protected $destination;        // Final handler
    
    public function send($passable): self;
    public function through(array $pipes): self;
    public function then(Closure $destination): mixed;
    
    // Execute middleware stack
    protected function carry(): Closure {
        return function($stack, $pipe) {
            return function($passable) use ($stack, $pipe) {
                return $pipe->handle($passable, $stack);
            };
        };
    }
}
```

**Middleware Execution Flow:**
```
Request
   ↓
Middleware 1 (before)
   ↓
Middleware 2 (before)
   ↓
Controller/Handler
   ↓
Middleware 2 (after)
   ↓
Middleware 1 (after)
   ↓
Response
```

**Example Middleware:**
```php
class TrimStrings implements MiddlewareInterface {
    public function handle(Request $request, Closure $next): Response {
        // Before: Trim all input
        $input = array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $request->all());
        
        $request->replace($input);
        
        // Call next middleware
        $response = $next($request);
        
        // After: (optional modifications)
        
        return $response;
    }
}
```

**Global vs Route Middleware:**
```php
// Global middleware (runs on all requests)
protected $middleware = [
    \App\Http\Middleware\TrimStrings::class,
    \App\Http\Middleware\ValidatePostSize::class,
];

// Route middleware (assigned to specific routes)
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'throttle' => \App\Http\Middleware\ThrottleRequests::class,
];

// Usage
$router->middleware('auth')->group(function($router) {
    // Protected routes
});
```

**Middleware Groups:**
```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \App\Http\Middleware\StartSession::class,
    ],
    
    'api' => [
        'throttle:60,1',
        \App\Http\Middleware\SubstituteBindings::class,
    ],
];

// Usage
$router->middleware('web')->group(function($router) {
    // Web routes
});
```

**Testing:**
- Unit test: Middleware execution order
- Unit test: Pipeline construction
- Unit test: Before/after execution
- Unit test: Middleware termination
- Integration test: Global middleware
- Integration test: Route middleware
- Integration test: Middleware groups

---

### 2.4 HTTP Kernel

**Files to Create:**

```
app/Core/Http/
├── Kernel.php                     # Base HTTP kernel
└── KernelInterface.php            # Kernel contract

app/Http/
└── Kernel.php                     # Application HTTP kernel
```

**Kernel.php** - HTTP request lifecycle manager
- Bootstrap application
- Load middleware
- Route request
- Handle exceptions
- Send response
- Terminate middleware

**Request Lifecycle:**
```php
class Kernel implements KernelInterface {
    public function handle(Request $request): Response {
        try {
            // 1. Run global middleware (before)
            $request = $this->sendRequestThroughMiddleware($request);
            
            // 2. Dispatch to router
            $response = $this->router->dispatch($request);
            
            // 3. Run global middleware (after)
            $response = $this->sendResponseThroughMiddleware($response);
            
            return $response;
            
        } catch (Throwable $e) {
            // Handle exception
            return $this->handleException($request, $e);
        }
    }
    
    public function terminate(Request $request, Response $response): void {
        // Run terminable middleware
        $this->terminateMiddleware($request, $response);
        
        // Cleanup tasks
        $this->app->terminate();
    }
}
```

**Bootstrap Process:**
```php
protected $bootstrappers = [
    \App\Bootstrap\LoadEnvironment::class,
    \App\Bootstrap\LoadConfiguration::class,
    \App\Bootstrap\HandleExceptions::class,
    \App\Bootstrap\RegisterProviders::class,
    \App\Bootstrap\BootProviders::class,
];

public function bootstrap(): void {
    if ($this->app->hasBeenBootstrapped()) {
        return;
    }
    
    foreach ($this->bootstrappers as $bootstrapper) {
        $this->app->make($bootstrapper)->bootstrap($this->app);
    }
    
    $this->app->markAsBootstrapped();
}
```

**Testing:**
- Integration test: Full request lifecycle
- Integration test: Bootstrap process
- Integration test: Exception handling
- Integration test: Middleware execution
- Integration test: Terminable middleware

---

### 2.5 Controller Base & Resolution

**Files to Create:**

```
app/Http/
├── Controller.php                 # Base controller
└── Controllers/
    └── .gitkeep

app/Core/Routing/
└── ControllerDispatcher.php       # Controller resolution & dispatch
```

**Controller.php** - Base controller class
```php
abstract class Controller {
    protected array $middleware = [];
    
    public function middleware($middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    public function getMiddleware(): array {
        return $this->middleware;
    }
    
    // Validation (Phase 4)
    protected function validate(Request $request, array $rules);
    
    // Authorization (Phase 4)
    protected function authorize(string $ability, $arguments = []);
}
```

**Controller Resolution:**
```php
// String syntax
$router->get('/users', 'UserController@index');

// Callable syntax
$router->get('/users', [UserController::class, 'index']);

// Closure syntax
$router->get('/users', function(Request $request) {
    return response()->json(['users' => []]);
});

// Invokable controller
$router->get('/users', UserController::class);
// Calls __invoke() method
```

**ControllerDispatcher.php** - Resolve and dispatch controller
- Parse controller string ('Controller@method')
- Resolve controller from container (auto-injection)
- Resolve method parameters from route
- Execute controller method
- Return response

**Method Parameter Injection:**
```php
class UserController extends Controller {
    // Request injection
    public function show(Request $request, int $id) {
        // $request automatically injected
        // $id extracted from route parameter
    }
    
    // Service injection + route parameters
    public function update(
        Request $request,
        UserRepository $users,  // Injected from container
        int $id                 // From route
    ) {
        $user = $users->find($id);
        // ...
    }
}
```

**Testing:**
- Unit test: Controller string parsing
- Unit test: Controller resolution
- Unit test: Method parameter injection
- Integration test: Controller dispatch
- Integration test: Middleware on controllers

---

### Phase 2 Deliverables Checklist

- [ ] Request abstraction with input handling
- [ ] Response abstraction (standard, JSON, redirect)
- [ ] Enhanced router with parameters and groups
- [ ] Route compiler for regex matching
- [ ] URL generator for named routes
- [ ] Middleware pipeline implementation
- [ ] HTTP kernel with lifecycle management
- [ ] Controller base class and dispatcher
- [ ] RESTful resource routing
- [ ] Global and route middleware
- [ ] Unit tests for all components
- [ ] Integration tests for request lifecycle
- [ ] Documentation for Phase 2

**Estimated Time:** 2-3 weeks  
**Lines of Code:** ~4,000-5,000 LOC  
**Test Coverage Target:** 85%+

---

## 💾 Phase 3: Database & Models

**Duration:** 2-3 weeks  
**Goal:** Build database abstraction layer, query builder, migrations, and Active Record ORM

---

### ⚠️ STRICT SCOPE RULES

**ALLOWED in Phase 3:**
- ✅ Database connection (PostgreSQL only)
- ✅ Query builder
- ✅ Schema system (PHP array schema + patches)
- ✅ ONE test model: `Page` (or `User`)
- ✅ Database migrations
- ✅ Seeders (test data only)
- ✅ Observability: Query logging, connection pool monitoring

**FORBIDDEN in Phase 3:**
- ❌ **NO Admin panel** - Phase 5
- ❌ **NO Authentication system** - Phase 4
- ❌ **NO Complex validation** - Phase 4
- ❌ **NO Multiple models** - Start with ONE model only
- ❌ **NO SEO module** - Phase 4+
- ❌ **NO Blog/CMS features** - Phase 5+

**Start with ONE model:**
- Create `Page` model for static content
- OR create `User` model if you need auth prep
- Don't create: Post, Category, Tag, Comment, Product, etc.
- Don't create: BlogPost, ContactSubmission, etc.

**If you're tempted:**
- "Let me add the full blog schema while I'm here" → NO, one model
- "I need auth tables for login" → NO, Phase 4
- "This needs a CMS backend" → NO, Phase 5
- "Let me optimize all queries" → NO, get it working first

---

### 🧪 MANDATORY TESTING REQUIREMENTS

**Phase 3 CANNOT MERGE without:**

**Unit Tests (Minimum):**
- [ ] `ConnectionTest` - Connect, disconnect, errors (8+ assertions)
- [ ] `QueryBuilderTest` - SELECT, WHERE, JOIN (20+ assertions)
- [ ] `QueryBuilderTest` - INSERT, UPDATE, DELETE (12+ assertions)
- [ ] `SchemaBuilderTest` - Create, alter, drop table (15+ assertions)
- [ ] `ModelTest` - Find, save, delete (10+ assertions)
- [ ] `PatchExecutorTest` - Apply patches, dependencies (8+ assertions)

**Integration Tests (Minimum):**
- [ ] **Phase 3 Integration Test:**
  - Bootstrap app + database
  - Run schema:install
  - Create `Page` model instance
  - Save to database
  - Query with builder
  - Update and delete
  - Verify data persists
  - Rollback changes
  - **Time limit:** <200ms
  - **Memory limit:** <20MB

**Database Tests:**
- Use PostgreSQL test database (not production!)
- Create test DB: `infinri_test`
- Run migrations before tests
- Clean DB after tests
- Use transactions where possible

**Coverage Target:**
- Minimum: 85% code coverage
- Query Builder: 95% coverage
- Schema System: 90% coverage
- Models: 85% coverage

**Test Command:**
```bash
# Run Phase 3 tests
vendor/bin/phpunit --testsuite=Phase3

# Test database connection
php bin/console db:test

# Run migrations
php bin/console schema:install

# Verify
psql -U postgres -d infinri_test -c "SELECT * FROM pages;"
```

---

### 3.1 Database Connection & Configuration

**Files to Create:**

```
app/Core/Database/
├── Connection.php                 # Database connection wrapper
├── ConnectionInterface.php        # Connection contract
├── DatabaseManager.php            # Multi-connection manager
└── Connectors/
    ├── Connector.php              # Base connector
    └── PostgresConnector.php      # PostgreSQL connector

app/config/
└── database.php                   # Database configuration

app/Providers/
└── DatabaseServiceProvider.php    # Database service provider
```

**database.php** - Database configuration
```php
return [
    'default' => env('DB_CONNECTION', 'pgsql'),
    
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'infinri'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],
    ],
];
```

**Connection.php** - PDO wrapper with connection pooling
- `__construct(PDO $pdo, string $database, string $prefix)`
- `select(string $query, array $bindings)` - Execute SELECT query
- `insert(string $query, array $bindings)` - Execute INSERT query
- `update(string $query, array $bindings)` - Execute UPDATE query
- `delete(string $query, array $bindings)` - Execute DELETE query
- `statement(string $query, array $bindings)` - Execute statement
- `affectingStatement(string $query, array $bindings)` - Execute with affected rows
- `unprepared(string $query)` - Execute unprepared statement
- `transaction(Closure $callback)` - Run in transaction
- `beginTransaction()` - Begin transaction
- `commit()` - Commit transaction
- `rollBack()` - Roll back transaction
- `lastInsertId()` - Get last insert ID
- `getTablePrefix()` - Get table prefix
- `setTablePrefix(string $prefix)` - Set table prefix

**DatabaseManager.php** - Manage multiple connections
```php
class DatabaseManager {
    protected array $connections = [];
    protected array $config;
    
    public function connection(string $name = null): Connection {
        $name = $name ?? $this->getDefaultConnection();
        
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }
        
        return $this->connections[$name];
    }
    
    protected function makeConnection(string $name): Connection {
        $config = $this->getConfig($name);
        $connector = $this->getConnector($config);
        
        return $connector->connect($config);
    }
}
```

**Key Features:**
- PDO-based connections
- Connection pooling
- Transaction support
- Prepared statements
- PostgreSQL support
- Table prefix support
- Connection configuration per environment

**Testing:**
- Unit test: Connection creation
- Unit test: Query execution
- Unit test: Transaction handling
- Integration test: Multiple connections
- Integration test: Connection pooling

---

### 3.2 Query Builder

**Files to Create:**

```
app/Core/Database/Query/
├── Builder.php                    # Query builder
├── Grammar.php                    # Base grammar
├── Grammars/
│   └── PostgresGrammar.php       # PostgreSQL grammar
├── Expression.php                 # Raw expression
└── JoinClause.php                # JOIN clause builder
```

**Builder.php** - Fluent query builder
```php
class Builder {
    // SELECT methods
    public function select($columns = ['*']): self;
    public function selectRaw(string $expression, array $bindings = []): self;
    public function addSelect($columns): self;
    public function distinct(): self;
    
    // FROM methods
    public function from(string $table, string $as = null): self;
    public function fromRaw(string $expression, array $bindings = []): self;
    
    // JOIN methods
    public function join(string $table, $first, $operator = null, $second = null): self;
    public function leftJoin(string $table, $first, $operator = null, $second = null): self;
    public function rightJoin(string $table, $first, $operator = null, $second = null): self;
    public function crossJoin(string $table): self;
    
    // WHERE methods
    public function where($column, $operator = null, $value = null): self;
    public function orWhere($column, $operator = null, $value = null): self;
    public function whereIn(string $column, array $values): self;
    public function whereNotIn(string $column, array $values): self;
    public function whereBetween(string $column, array $values): self;
    public function whereNull(string $column): self;
    public function whereNotNull(string $column): self;
    public function whereRaw(string $sql, array $bindings = []): self;
    
    // GROUP BY & HAVING
    public function groupBy(...$groups): self;
    public function having($column, $operator = null, $value = null): self;
    
    // ORDER BY
    public function orderBy(string $column, string $direction = 'asc'): self;
    public function orderByRaw(string $sql, array $bindings = []): self;
    public function latest(string $column = 'created_at'): self;
    public function oldest(string $column = 'created_at'): self;
    
    // LIMIT & OFFSET
    public function limit(int $value): self;
    public function offset(int $value): self;
    public function skip(int $value): self;
    public function take(int $value): self;
    
    // Execution methods
    public function get($columns = ['*']): Collection;
    public function first($columns = ['*']): ?object;
    public function find($id, $columns = ['*']): ?object;
    public function value(string $column);
    public function pluck(string $column): Collection;
    public function count(string $columns = '*'): int;
    public function max(string $column);
    public function min(string $column);
    public function avg(string $column);
    public function sum(string $column);
    public function exists(): bool;
    
    // Insert methods
    public function insert(array $values): bool;
    public function insertGetId(array $values, string $sequence = null): int;
    
    // Update methods
    public function update(array $values): int;
    public function increment(string $column, int $amount = 1, array $extra = []): int;
    public function decrement(string $column, int $amount = 1, array $extra = []): int;
    
    // Delete methods
    public function delete($id = null): int;
    public function truncate(): void;
    
    // Utility methods
    public function toSql(): string;
    public function getBindings(): array;
    public function dd(): void;  // Dump and die
    public function dump(): self;  // Dump query
}
```

**Usage Examples:**
```php
// Simple SELECT
$users = DB::table('users')->get();

// WHERE clause
$users = DB::table('users')
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->get();

// JOIN
$posts = DB::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name')
    ->get();

// Aggregates
$count = DB::table('users')->count();
$avg = DB::table('orders')->avg('total');

// INSERT
DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

$id = DB::table('users')->insertGetId([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
]);

// UPDATE
DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'John Smith']);

// DELETE
DB::table('users')->where('id', 1)->delete();
```

**Testing:**
- Unit test: SELECT query building
- Unit test: WHERE clause combinations
- Unit test: JOIN query building
- Unit test: INSERT/UPDATE/DELETE
- Unit test: Aggregates
- Unit test: Query to SQL conversion
- Integration test: Actual database queries

---

### 3.3 Schema System (Declarative PHP Arrays)

**Approach:** PHP Array Schema + Data Patches (inspired by Magento's db_schema.xml but using native PHP arrays)

**Files to Create:**

```
app/Core/Database/Schema/
├── SchemaManager.php              # Main schema manager
├── SchemaLoader.php               # Load schema files
├── SchemaComparator.php           # Compare schema vs database
├── SchemaBuilder.php              # Build SQL from schema
├── SchemaDumper.php               # Dump current DB schema
├── Grammars/
│   └── PostgresSchemaGrammar.php  # PostgreSQL DDL generator
└── ColumnDefinition.php           # Column helper

app/Core/Setup/Patch/
├── PatchInterface.php             # Patch contract
├── DataPatchInterface.php         # Data patch contract
├── SchemaPatchInterface.php       # Schema patch contract
└── PatchExecutor.php              # Execute patches

app/Console/Commands/
├── SchemaUpgradeCommand.php       # Apply schema changes
├── SchemaInstallCommand.php       # Fresh install
├── SchemaDumpCommand.php          # Dump current schema
└── PatchListCommand.php           # List applied patches

database/
├── schema/
│   ├── core.php                   # Core tables schema
│   └── .gitkeep
└── patches/
    └── .gitkeep
```

**Core Schema Definition (database/schema/core.php):**
```php
<?php

return [
    'tables' => [
        'users' => [
            'columns' => [
                'id' => ['type' => 'id'],  // bigint unsigned, auto_increment, primary
                'email' => ['type' => 'string', 'length' => 255],
                'password' => ['type' => 'string', 'length' => 255],
                'name' => ['type' => 'string', 'length' => 255],
                'role' => [
                    'type' => 'enum',
                    'values' => ['admin', 'editor', 'viewer'],
                    'default' => 'viewer',
                ],
                'status' => [
                    'type' => 'enum',
                    'values' => ['active', 'inactive'],
                    'default' => 'active',
                ],
                'last_login_at' => ['type' => 'timestamp', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['email'], 'unique' => true],
                ['columns' => ['role', 'status']],
            ],
            'timestamps' => true,  // Auto: created_at, updated_at
        ],
        
        'pages' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'author_id' => [
                    'type' => 'foreignId',
                    'references' => 'users.id',
                    'onDelete' => 'cascade',
                ],
                'slug' => ['type' => 'string', 'length' => 255],
                'title' => ['type' => 'string', 'length' => 255],
                'content' => ['type' => 'text', 'nullable' => true],
                'status' => [
                    'type' => 'enum',
                    'values' => ['draft', 'published'],
                    'default' => 'draft',
                ],
                'published_at' => ['type' => 'timestamp', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['slug'], 'unique' => true],
                ['columns' => ['status']],
                ['columns' => ['author_id']],
            ],
            'timestamps' => true,
        ],
        
        'settings' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'key' => ['type' => 'string', 'length' => 100],
                'value' => ['type' => 'text', 'nullable' => true],
                'type' => ['type' => 'string', 'length' => 50, 'default' => 'string'],
            ],
            'indexes' => [
                ['columns' => ['key'], 'unique' => true],
            ],
        ],
        
        'patch_list' => [
            'columns' => [
                'patch_id' => ['type' => 'integer', 'primary' => true, 'auto_increment' => true],
                'patch_name' => ['type' => 'string', 'length' => 255],
                'applied_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'indexes' => [
                ['columns' => ['patch_name'], 'unique' => true],
            ],
        ],
    ],
];
```

**Module Schema Example (app/Modules/Blog/schema.php):**
```php
<?php

return [
    'tables' => [
        'blog_posts' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'user_id' => [
                    'type' => 'foreignId',
                    'references' => 'users.id',
                    'onDelete' => 'cascade',
                ],
                'title' => ['type' => 'string', 'length' => 255],
                'slug' => ['type' => 'string', 'length' => 255],
                'content' => ['type' => 'text', 'nullable' => true],
                'excerpt' => ['type' => 'text', 'nullable' => true],
                'status' => [
                    'type' => 'enum',
                    'values' => ['draft', 'published', 'archived'],
                    'default' => 'draft',
                ],
                'featured' => ['type' => 'boolean', 'default' => false],
                'view_count' => ['type' => 'integer', 'default' => 0],
                'published_at' => ['type' => 'timestamp', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['slug'], 'unique' => true],
                ['columns' => ['status']],
                ['columns' => ['user_id', 'status']],
                ['columns' => ['featured']],
            ],
            'timestamps' => true,
        ],
        
        'blog_categories' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'name' => ['type' => 'string', 'length' => 100],
                'slug' => ['type' => 'string', 'length' => 100],
                'description' => ['type' => 'text', 'nullable' => true],
            ],
            'indexes' => [
                ['columns' => ['slug'], 'unique' => true],
            ],
        ],
        
        'blog_post_category' => [  // Pivot table
            'columns' => [
                'post_id' => [
                    'type' => 'foreignId',
                    'references' => 'blog_posts.id',
                    'onDelete' => 'cascade',
                ],
                'category_id' => [
                    'type' => 'foreignId',
                    'references' => 'blog_categories.id',
                    'onDelete' => 'cascade',
                ],
            ],
            'indexes' => [
                ['columns' => ['post_id', 'category_id'], 'unique' => true],
            ],
        ],
    ],
];
```

**Data Patch Example (app/Modules/Blog/Setup/Patch/Data/SeedCategories.php):**
```php
<?php

namespace App\Modules\Blog\Setup\Patch\Data;

use App\Core\Setup\Patch\DataPatchInterface;
use App\Core\Database\Connection;

class SeedCategories implements DataPatchInterface
{
    public function __construct(
        protected Connection $connection
    ) {}
    
    /**
     * Apply patch
     */
    public function apply(): void
    {
        $categories = [
            ['name' => 'Announcements', 'slug' => 'announcements', 'description' => 'Company announcements'],
            ['name' => 'Tutorials', 'slug' => 'tutorials', 'description' => 'How-to guides'],
            ['name' => 'Updates', 'slug' => 'updates', 'description' => 'Product updates'],
        ];
        
        foreach ($categories as $category) {
            $this->connection->table('blog_categories')->insert($category);
        }
    }
    
    /**
     * Get patch dependencies
     */
    public static function getDependencies(): array
    {
        return [];  // No dependencies
    }
    
    /**
     * Get aliases (for backwards compatibility)
     */
    public function getAliases(): array
    {
        return [];
    }
}
```

**Schema Patch Example (app/Modules/Blog/Setup/Patch/Schema/AddFullTextIndex.php):**
```php
<?php

namespace App\Modules\Blog\Setup\Patch\Schema;

use App\Core\Setup\Patch\SchemaPatchInterface;
use App\Core\Database\Connection;

class AddFullTextIndex implements SchemaPatchInterface
{
    public function __construct(
        protected Connection $connection
    ) {}
    
    public function apply(): void
    {
        // PostgreSQL full-text index
        if ($this->connection->getDriverName() === 'pgsql') {
            $this->connection->statement('
                CREATE INDEX blog_posts_fulltext_idx 
                ON blog_posts USING GIN(to_tsvector(''english'', title || '' '' || content));
            ');
        }
        
        /*
        
        // PostgreSQL uses different approach
        if ($this->connection->getDriverName() === 'pgsql') {
            $this->connection->statement("
                CREATE INDEX idx_search_title ON blog_posts 
                USING GIN (to_tsvector('english', title))
            ");
        }
    }
    
    public static function getDependencies(): array
    {
        return [];
    }
    
    public function getAliases(): array
    {
        return [];
    }
}
```

**SchemaManager.php** - Main schema manager
```php
class SchemaManager
{
    public function install(): void
    {
        // 1. Load all schema files
        $schemas = $this->loader->loadAll();
        
        // 2. Generate SQL from schemas
        $sql = $this->builder->buildFromSchemas($schemas);
        
        // 3. Execute SQL
        $this->connection->transaction(function() use ($sql) {
            foreach ($sql as $statement) {
                $this->connection->statement($statement);
            }
        });
        
        // 4. Run patches
        $this->patchExecutor->executeAll();
    }
    
    public function upgrade(): void
    {
        // 1. Load all schema files
        $schemas = $this->loader->loadAll();
        
        // 2. Get current database schema
        $dbSchema = $this->dumper->dump();
        
        // 3. Compare and generate diff
        $diff = $this->comparator->diff($schemas, $dbSchema);
        
        // 4. Generate ALTER TABLE statements
        $sql = $this->builder->buildFromDiff($diff);
        
        // 5. Execute SQL
        $this->connection->transaction(function() use ($sql) {
            foreach ($sql as $statement) {
                $this->connection->statement($statement);
            }
        });
        
        // 6. Run new patches
        $this->patchExecutor->executeNew();
    }
}
```

**CLI Commands:**
```bash
# Install schema (fresh install)
php bin/console schema:install

# Upgrade schema (detect and apply changes)
php bin/console schema:upgrade

# Dump current database schema
php bin/console schema:dump

# List applied patches
php bin/console patch:list

# Run specific patch
php bin/console patch:apply App\\Modules\\Blog\\Setup\\Patch\\Data\\SeedCategories
```

**Key Features:**
- **Declarative:** Define what you want, not how to get there
- **Idempotent:** Safe to run multiple times
- **PostgreSQL native:** Optimized for PostgreSQL 16+
- **Module-friendly:** Each module includes its schema
- **Data separation:** Schema (structure) vs Patches (data)
- **Dependency management:** Patches can depend on other patches
- **Version tracking:** Track applied patches in database

**Benefits over Traditional Migrations:**
- ✅ Single source of truth for schema
- ✅ No sequential migration numbers
- ✅ No migration conflicts
- ✅ Clear schema visibility
- ✅ Fast fresh installs
- ✅ Easy to understand

**Testing:**
- Unit test: Schema array parsing
- Unit test: SQL generation from schema
- Unit test: Schema comparison/diff
- Integration test: Create tables from schema
- Integration test: Detect schema changes
- Integration test: Apply schema updates
- Integration test: Patch execution with dependencies

---

### 3.4 Active Record ORM (Models)

**Files to Create:**

```
app/Core/Database/
├── Model.php                      # Base model class
├── Relations/
│   ├── HasOne.php                # One-to-one relation
│   ├── HasMany.php               # One-to-many relation
│   ├── BelongsTo.php             # Inverse of HasOne/HasMany
│   ├── BelongsToMany.php         # Many-to-many relation
│   └── Relation.php              # Base relation
└── Collection.php                 # Model collection

app/Models/
├── User.php                       # User model
├── Page.php                       # Page model
└── Setting.php                    # Setting model
```

**Model.php** - Base Active Record model
```php
abstract class Model {
    protected static string $table;          // Table name
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected array $fillable = [];          // Mass-assignable attributes
    protected array $guarded = ['*'];        // Guarded attributes
    protected array $hidden = [];            // Hidden from JSON
    protected array $casts = [];             // Type casting
    protected array $dates = [];             // Date columns
    protected array $attributes = [];        // Model data
    protected array $original = [];          // Original data
    protected array $relations = [];         // Loaded relations
    
    // Static query methods
    public static function all($columns = ['*']): Collection;
    public static function find($id, $columns = ['*']): ?static;
    public static function findOrFail($id, $columns = ['*']): static;
    public static function where($column, $operator = null, $value = null): Builder;
    public static function create(array $attributes): static;
    public static function query(): Builder;
    
    // Instance methods
    public function save(): bool;
    public function update(array $attributes = []): bool;
    public function delete(): bool;
    public function fresh($with = []): ?static;
    public function refresh(): static;
    
    // Attribute methods
    public function getAttribute(string $key);
    public function setAttribute(string $key, $value): void;
    public function fill(array $attributes): self;
    public function toArray(): array;
    public function toJson(int $options = 0): string;
    
    // Relationship methods (defined in child classes)
    protected function hasOne(string $related, string $foreignKey = null): HasOne;
    protected function hasMany(string $related, string $foreignKey = null): HasMany;
    protected function belongsTo(string $related, string $foreignKey = null): BelongsTo;
    protected function belongsToMany(string $related, string $table = null): BelongsToMany;
    
    // Scopes
    public function scopeActive(Builder $query): Builder;
    // Custom scopes defined in child classes
    
    // Events (hooks)
    protected static function boot(): void;
    protected static function booting(): void;
    protected static function booted(): void;
    // creating, created, updating, updated, deleting, deleted, saving, saved
}
```

**Example Model:**
```php
namespace App\Models;

use App\Core\Database\Model;

class User extends Model {
    protected static string $table = 'users';
    
    protected array $fillable = [
        'email',
        'password',
        'name',
        'role',
        'status',
    ];
    
    protected array $hidden = [
        'password',
    ];
    
    protected array $casts = [
        'last_login_at' => 'datetime',
    ];
    
    // Relationships
    public function pages() {
        return $this->hasMany(Page::class, 'author_id');
    }
    
    // Scopes
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }
    
    public function scopeAdmins($query) {
        return $query->where('role', 'admin');
    }
    
    // Mutators
    public function setPasswordAttribute($value) {
        $this->attributes['password'] = password_hash($value, PASSWORD_ARGON2ID);
    }
    
    // Accessors
    public function getFullNameAttribute() {
        return $this->name;
    }
}

class Page extends Model {
    protected static string $table = 'pages';
    
    protected array $fillable = [
        'slug',
        'title',
        'content',
        'status',
        'author_id',
    ];
    
    protected array $casts = [
        'published_at' => 'datetime',
    ];
    
    public function author() {
        return $this->belongsTo(User::class, 'author_id');
    }
    
    public function scopePublished($query) {
        return $query->where('status', 'published');
    }
}
```

**Model Usage:**
```php
// Find by ID
$user = User::find(1);

// Query with conditions
$users = User::where('status', 'active')
    ->where('role', 'admin')
    ->get();

// Scopes
$activeAdmins = User::active()->admins()->get();

// Create
$user = User::create([
    'email' => 'john@example.com',
    'password' => 'secret',
    'name' => 'John Doe',
]);

// Update
$user->name = 'Jane Doe';
$user->save();

// Or
$user->update(['name' => 'Jane Doe']);

// Delete
$user->delete();

// Mass operations
User::where('status', 'inactive')->delete();
User::where('role', 'viewer')->update(['status' => 'active']);

// Relationships
$user = User::find(1);
$pages = $user->pages;  // HasMany

$page = Page::find(1);
$author = $page->author;  // BelongsTo

// Eager loading (avoid N+1)
$users = User::with('pages')->get();

// Nested eager loading
$pages = Page::with('author.pages')->get();
```

**Relationships:**
```php
// One-to-One
class User extends Model {
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}
$profile = $user->profile;

// One-to-Many
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class);
    }
}
$posts = $user->posts;

// Belongs To (Inverse)
class Post extends Model {
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
$author = $post->author;

// Many-to-Many
class User extends Model {
    public function roles() {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}
$roles = $user->roles;
```

**Testing:**
- Unit test: Model CRUD operations
- Unit test: Attribute casting
- Unit test: Mutators and accessors
- Unit test: Scopes
- Integration test: Relationships (HasOne, HasMany, BelongsTo)
- Integration test: Eager loading
- Integration test: Many-to-many

---

### 3.5 Seeders & Factories

**Files to Create:**

```
app/Core/Database/
├── Seeder.php                     # Base seeder class
└── Factory.php                    # Model factory

app/Console/Commands/
├── SeederMakeCommand.php          # Create seeder
├── SeedCommand.php                # Run seeders
└── FactoryMakeCommand.php         # Create factory

database/
├── seeders/
│   ├── DatabaseSeeder.php         # Main seeder
│   ├── UserSeeder.php             # User seeder
│   └── PageSeeder.php             # Page seeder
└── factories/
    ├── UserFactory.php             # User factory
    └── PageFactory.php             # Page factory
```

**Seeder Usage:**
```php
class UserSeeder extends Seeder {
    public function run(): void {
        User::create([
            'email' => 'admin@infinri.com',
            'password' => 'password',
            'name' => 'Admin User',
            'role' => 'admin',
        ]);
        
        // Or use factory
        User::factory()->count(10)->create();
    }
}

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([
            UserSeeder::class,
            PageSeeder::class,
        ]);
    }
}
```

**Factory Usage:**
```php
class UserFactory extends Factory {
    protected string $model = User::class;
    
    public function definition(): array {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password',
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['admin', 'editor', 'viewer']),
            'status' => 'active',
        ];
    }
    
    public function admin(): self {
        return $this->state(['role' => 'admin']);
    }
}

// Usage
$user = User::factory()->create();
$admin = User::factory()->admin()->create();
$users = User::factory()->count(10)->create();
```

**CLI Commands:**
```bash
# Run all seeders
php bin/console db:seed

# Run specific seeder
php bin/console db:seed --class=UserSeeder

# Create seeder
php bin/console make:seeder UserSeeder

# Create factory
php bin/console make:factory UserFactory
```

---

### Phase 3 Deliverables Checklist

- [ ] Database connection wrapper (PDO)
- [ ] Connection manager (multi-database)
- [ ] Query builder with fluent interface
- [ ] Schema system (PHP Array Schema)
- [ ] Schema CLI commands (install, upgrade, dump)
- [ ] Patch system (Data & Schema patches)
- [ ] Active Record ORM (Model base class)
- [ ] Model relationships (HasOne, HasMany, BelongsTo, BelongsToMany)
- [ ] Eager loading support
- [ ] Scopes and query methods
- [ ] Mutators and accessors
- [ ] Seeders and factories
- [ ] Unit tests for all components
- [ ] Integration tests with real database
- [ ] Documentation for Phase 3

**Estimated Time:** 2-3 weeks  
**Lines of Code:** ~5,000-6,000 LOC  
**Test Coverage Target:** 85%+

---

## 🔌 Phase 4: Modular Features (Service Providers)

**Duration:** 3-4 weeks  
**Goal:** Implement core feature modules using IoC container and contracts

This phase implements View/Template engine, Session, Cache, Mail, Authentication, CSRF, Validation, and SEO modules. Each module uses service providers and implements core contracts.

**Key Modules:**
- **View Layer** (CSS + JS + Templates)
- Session Management
- Cache System (File, Redis, Memory)
- Mail System (Brevo, SMTP)
- Authentication & Authorization
- CSRF Protection
- Validation System
- SEO Module

**Estimated Time:** 3-4 weeks  
**Lines of Code:** ~6,000-8,000 LOC  
**Test Coverage Target:** 85%+

---

### 4.1 View Layer Architecture

**Goal:** Clean, lean, fast view system with zero redundancy. Outperforms Magento in simplicity and speed.

#### Directory Structure

```
app/base/view/
├── base/                           # Shared (admin + frontend)
│   ├── css/
│   │   ├── reset.css              # Normalize
│   │   ├── variables.css          # CSS custom properties
│   │   ├── utilities.css          # Utility classes
│   │   ├── components/            # Shared components
│   │   │   ├── button.css         # .btn, .btn-primary, .btn-secondary
│   │   │   ├── card.css           # .card, .card-header, .card-body
│   │   │   ├── form.css           # .form-group, .form-input, .form-label
│   │   │   ├── table.css          # .table, .table-striped
│   │   │   └── modal.css          # .modal, .modal-overlay
│   │   └── layouts/               # Layout structures
│   │       ├── grid.css           # .container, .row, .col-*
│   │       └── flex.css           # Flexbox utilities
│   ├── js/
│   │   ├── core.js                # Fetch wrapper, DOM helpers
│   │   ├── event-bus.js           # Simple pub/sub system
│   │   └── components/            # Reusable JS components
│   │       ├── modal.js
│   │       ├── dropdown.js
│   │       ├── tabs.js
│   │       └── form-validator.js
│   └── templates/
│       └── components/            # Reusable PHP components
│
├── frontend/                       # Frontend-specific
│   ├── css/
│   │   ├── frontend.css
│   │   ├── theme.css              # Colors, fonts
│   │   └── layouts/
│   │       └── main.css
│   ├── js/
│   │   ├── app.js                 # Frontend init
│   │   └── utils/
│   │       ├── analytics.js
│   │       └── lazy-load.js
│   └── templates/
│       └── layouts/
│           ├── base.php           # Base layout
│           ├── one-column.php     # Inherits base
│           ├── two-column.php     # Inherits base
│           └── full-width.php     # Inherits base
│
└── admin/                          # Admin-specific
    ├── css/
    │   ├── admin.css
    │   ├── theme.css              # Dark theme
    │   ├── sidebar.css
    │   └── topbar.css
    ├── js/
    │   ├── admin.js
    │   ├── sidebar.js
    │   └── components/
    │       ├── data-table.js
    │       ├── rich-editor.js
    │       └── file-uploader.js
    └── templates/
        └── layouts/
            ├── base.php
            └── dashboard.php
```

#### Core Modules (Header, Footer, Navigation)

**These are full modules** with their own CSS, JS, and templates:

```
app/Modules/Header/
├── HeaderServiceProvider.php
├── module.json
├── View/
│   ├── frontend/
│   │   ├── css/header.css
│   │   ├── js/
│   │   │   ├── header.js          # Mobile menu, sticky header
│   │   │   └── search.js
│   │   └── templates/header.php
│   └── admin/
│       ├── css/topbar.css
│       ├── js/topbar.js
│       └── templates/topbar.php
└── Controllers/SearchController.php

app/Modules/Navigation/
├── NavigationServiceProvider.php
├── module.json
├── schema.php                     # navigation_items table
├── Models/NavigationItem.php
├── View/
│   ├── frontend/
│   │   ├── css/
│   │   │   ├── navigation.css
│   │   │   └── mega-menu.css
│   │   ├── js/
│   │   │   ├── navigation.js
│   │   │   └── mega-menu.js       # Lazy loading, interactions
│   │   └── templates/navigation.php
│   └── admin/
│       ├── css/sidebar-nav.css
│       ├── js/sidebar-nav.js
│       └── templates/sidebar.php
└── Controllers/NavigationController.php

app/Modules/Footer/
├── FooterServiceProvider.php
├── module.json
├── View/
│   ├── frontend/
│   │   ├── css/footer.css
│   │   ├── js/footer.js           # Newsletter, back-to-top
│   │   └── templates/footer.php
│   └── admin/
│       └── templates/footer.php
└── Controllers/NewsletterController.php
```

#### CSS Layer System

**Load Order (Cascading):**

1. **Base Reset** → `base/css/reset.css` (normalize)
2. **Variables** → `base/css/variables.css` (CSS custom properties)
3. **Utilities** → `base/css/utilities.css` (`.text-center`, `.mb-2`)
4. **Base Components** → `base/css/components/*` (shared)
5. **Base Layouts** → `base/css/layouts/*` (grid system)
6. **Area Theme** → `frontend/css/theme.css` OR `admin/css/theme.css`
7. **Area Layouts** → `frontend/css/layouts/*` OR `admin/css/layouts/*`
8. **Module Styles** → `modules/*/View/*/css/*` (overrides only)

**Example - Shared Button Component:**

```css
/* app/base/view/base/css/components/button.css */

.btn {
    display: inline-flex;
    align-items: center;
    padding: var(--spacing-2) var(--spacing-4);
    font-size: var(--font-size-sm);
    border-radius: var(--radius-md);
    transition: all var(--transition-base);
}

.btn-primary {
    background-color: var(--color-primary);
    color: var(--color-white);
}

.btn-sm { padding: var(--spacing-1) var(--spacing-3); }
.btn-lg { padding: var(--spacing-3) var(--spacing-6); }
```

**Module Override (only what's different):**

```css
/* app/Modules/Blog/View/frontend/css/blog.css */

/* Inherits all .btn styles, only adds differences */
.blog-post-card {
    border-left: 4px solid var(--color-primary);
}
```

#### JavaScript Architecture

**Base Layer (Shared):**

```javascript
// app/base/view/base/js/core.js

window.App = window.App || {};

// Fetch with CSRF
window.App.fetch = async (url, options = {}) => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    return fetch(url, {
        ...options,
        headers: { 'X-CSRF-Token': csrf, ...options.headers }
    });
};

// Helpers
window.App.$ = (s) => document.querySelector(s);
window.App.$$ = (s) => document.querySelectorAll(s);
```

**Module JavaScript Example:**

```javascript
// app/Modules/Blog/View/frontend/js/post.js

class BlogPost {
    constructor() {
        this.setupShare();
        this.setupReadingProgress();
    }
    
    setupShare() {
        App.$$('[data-share]').forEach(btn => {
            btn.addEventListener('click', () => {
                const platform = btn.dataset.share;
                const url = window.location.href;
                
                if (platform === 'twitter') {
                    window.open(`https://twitter.com/intent/tweet?url=${url}`);
                }
            });
        });
    }
    
    setupReadingProgress() {
        const bar = App.$('[data-reading-progress]');
        const content = App.$('[data-post-content]');
        
        window.addEventListener('scroll', () => {
            const progress = (window.pageYOffset / content.offsetHeight) * 100;
            bar.style.width = `${Math.min(progress, 100)}%`;
        });
    }
}

document.addEventListener('DOMContentLoaded', () => new BlogPost());
```

#### Layout Inheritance

**Base Layout:**

```php
<!-- app/base/view/frontend/templates/layouts/base.php -->

<!DOCTYPE html>
<html lang="<?= $this->getLocale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= $this->getTitle() ?></title>
    
    <!-- Base CSS (shared) -->
    <link rel="stylesheet" href="<?= $this->asset('base/css/reset.css') ?>">
    <link rel="stylesheet" href="<?= $this->asset('base/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= $this->asset('base/css/components/button.css') ?>">
    <link rel="stylesheet" href="<?= $this->asset('base/css/components/form.css') ?>">
    
    <!-- Frontend CSS -->
    <link rel="stylesheet" href="<?= $this->asset('frontend/css/theme.css') ?>">
    
    <!-- Module CSS -->
    <?= $this->renderBlock('head_css') ?>
</head>
<body>
    <div class="header-wrapper">
        <?= $this->renderBlock('header') ?>
    </div>
    
    <main class="main-content">
        <?= $this->renderBlock('content') ?>
    </main>
    
    <div class="footer-wrapper">
        <?= $this->renderBlock('footer') ?>
    </div>
    
    <!-- Base JS -->
    <script src="<?= $this->asset('base/js/core.js') ?>"></script>
    <script src="<?= $this->asset('base/js/event-bus.js') ?>"></script>
    
    <!-- Module JS -->
    <?= $this->renderBlock('footer_js') ?>
</body>
</html>
```

**Module Page (Inherits Layout):**

```php
<!-- app/Modules/Blog/View/frontend/templates/post/view.php -->

<?php $this->layout('frontend/layouts/one-column') ?>

<?php $this->startBlock('head_css') ?>
<link rel="stylesheet" href="<?= $this->moduleAsset('Blog', 'frontend/css/post.css') ?>">
<?php $this->endBlock() ?>

<?php $this->startBlock('footer_js') ?>
<script src="<?= $this->moduleAsset('Blog', 'frontend/js/post.js') ?>"></script>
<?php $this->endBlock() ?>

<?php $this->startBlock('main') ?>
<article data-post-content>
    <h1><?= e($post->title) ?></h1>
    <div><?= $post->content ?></div>
</article>
<?php $this->endBlock() ?>
```

#### Performance Targets

| Asset Type | Size (gzipped) |
|------------|----------------|
| Base CSS | ~20-30 KB |
| Frontend CSS | ~10-15 KB |
| Module CSS | ~2-5 KB each |
| Base JS | ~5-10 KB |
| Module JS | ~2-5 KB each |
| **Total (Frontend)** | **~40-60 KB** ✅ |

**vs Magento 2:**
- Magento: 200-400KB CSS, 100KB+ JS
- **Our approach: 80-85% smaller** ✅

#### Key Benefits

✅ **Zero Redundancy** - Base components used everywhere  
✅ **Module Independence** - Header/Footer/Nav are modules  
✅ **Clean Inheritance** - Layouts extend base, modules extend layouts  
✅ **Fast Loading** - Small CSS/JS files, no compilation  
✅ **Easy Overrides** - Modules override only what's needed  
✅ **Scalable** - 5-6x faster than Magento's XML layouts  

**Testing:**
- Unit test: Template inheritance
- Unit test: Asset loading order
- Integration test: Module CSS/JS loading
- Integration test: Layout rendering
- Performance test: Asset size validation

---

---

## 🎛️ Phase 5: Admin Panel Module

**Duration:** 3-4 weeks  
**Goal:** Build comprehensive admin panel for content and user management

This phase builds the complete admin panel with dashboard, CRUD generators, media library, settings management, user management, and page management.

**Key Features:**
- Admin Panel Foundation
- Dashboard with Widgets & Statistics
- CRUD Generators
- Media Library (Upload/Browse)
- Settings Management
- User Management (Roles & Permissions)
- Page Management (WYSIWYG Editor)

**Estimated Time:** 3-4 weeks  
**Lines of Code:** ~7,000-9,000 LOC  
**Test Coverage Target:** 80%+

---

## 🚀 Phase 6: Advanced Features

**Duration:** 2-3 weeks  
**Goal:** Add event system, queue system, logging, and advanced CLI

This phase adds advanced features like event dispatcher, job queue system, comprehensive logging, and production-ready CLI commands.

**Key Features:**
- Event Dispatcher with Listeners
- Queue System (Database, Redis)
- Background Jobs with Retry Logic
- PSR-3 Logging System
- Advanced CLI Commands (cache, optimize, maintenance)

**Estimated Time:** 2-3 weeks  
**Lines of Code:** ~3,000-4,000 LOC  
**Test Coverage Target:** 85%+

---

## ✅ Phase 7: Testing & Documentation

**Duration:** 2-3 weeks  
**Goal:** Comprehensive testing and documentation

This phase focuses on achieving high test coverage and creating comprehensive documentation for all platform features.

**Key Deliverables:**
- PHPUnit test infrastructure
- Unit tests for all core components
- Integration tests for features
- Feature tests for admin panel
- Complete documentation suite
- API reference
- Deployment guides

**Test Coverage Target:** 85%+ overall  
**Estimated Time:** 2-3 weeks

---

## 🔄 Migration Strategy

### Current System → New Architecture

**Phase-by-Phase Migration:**

1. **Phase 1 (Foundation)** - No migration needed, builds new foundation
   - New code only, no conflicts with existing system
   - Existing modules continue to work

2. **Phase 2 (HTTP Layer)** - Replace existing router
   - **Before:** Simple `Router.php` with exact match
   - **After:** Enhanced router with parameters and middleware
   - **Migration:** Update `pub/index.php` to use new HTTP kernel
   - **Backward Compatible:** Keep old router temporarily

3. **Phase 3 (Database)** - Add database layer
   - **Before:** No database (static content)
   - **After:** Full database with migrations
   - **Migration:** 
     - Create database and run migrations
     - Convert existing static pages to database records
     - Keep static pages as fallback during transition

4. **Phase 4 (Modules)** - Refactor existing modules
   - **Before:** Simple module system with direct includes
   - **After:** Service provider-based modules
   - **Migration:**
     - Wrap existing modules in service providers
     - Gradually refactor to use new contracts
     - Test each module after migration

5. **Phase 5 (Admin)** - New feature, no migration needed
   - Pure addition, no breaking changes

6. **Phase 6 (Advanced)** - New features, no migration needed
   - Pure addition, no breaking changes

7. **Phase 7 (Testing)** - Add tests for migrated code
   - No migration needed

### Backward Compatibility Strategy

**Keep Working During Migration:**
- Run old and new systems side-by-side
- Feature flags for new functionality
- Gradual migration of routes
- Database fallback to static content

**Example Feature Flag:**
```php
// In config/app.php
'features' => [
    'use_new_router' => env('USE_NEW_ROUTER', false),
    'use_database' => env('USE_DATABASE', false),
    'enable_admin' => env('ENABLE_ADMIN', false),
];

// In code
if (config('features.use_new_router')) {
    // Use new enhanced router
} else {
    // Use legacy router
}
```

### Data Migration Plan

**Existing Content → Database:**

```php
// Console command: php bin/console migrate:content

class MigrateContentCommand extends Command {
    public function handle() {
        // 1. Migrate pages
        $this->migratePages();
        
        // 2. Migrate settings from .env to database
        $this->migrateSettings();
        
        // 3. Create initial admin user
        $this->createAdminUser();
        
        $this->info('Content migration completed!');
    }
    
    private function migratePages() {
        $modules = ['home', 'about', 'contact', 'legal'];
        
        foreach ($modules as $slug) {
            // Extract content from existing templates
            $content = $this->extractContent($slug);
            
            // Create database record
            Page::create([
                'slug' => $slug,
                'title' => $content['title'],
                'content' => $content['body'],
                'status' => 'published',
                'template' => $slug,
            ]);
        }
    }
}
```

### Deployment Strategy

**Zero-Downtime Migration:**

1. **Prepare:**
   - Deploy new code alongside old code
   - Test new features in staging
   - Run migrations in separate database

2. **Switch:**
   - Enable new features via environment variables
   - Monitor for errors
   - Keep old code as fallback

3. **Verify:**
   - Test all critical paths
   - Check database integrity
   - Verify admin panel access

4. **Cleanup:**
   - After 2 weeks of stable operation
   - Remove old code
   - Remove feature flags
   - Archive old system

---

## 📦 Dependencies & Tools

### Zero External PHP Dependencies (Core)

**Philosophy:** Keep core framework dependency-free

**What We Build:**
- IoC Container
- Router
- Database abstraction (PDO wrapper)
- Template engine
- All helper classes

**Benefits:**
- Full control
- No version conflicts
- Smaller footprint
- Learning opportunity
- No licensing issues

### Optional Dependencies (Features)

**Development:**
```json
{
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "php-cs-fixer/shim": "^3.45"
  }
}
```

**Production (Optional):**
```json
{
  "suggest": {
    "predis/predis": "Required for Redis cache driver",
    "guzzlehttp/guzzle": "Required for external API calls",
    "league/flysystem": "Required for cloud storage"
  }
}
```

### Frontend Tools (Asset Building)

**package.json:**
```json
{
  "devDependencies": {
    "esbuild": "^0.19.0",
    "sass": "^1.69.0",
    "autoprefixer": "^10.4.16",
    "postcss": "^8.4.31"
  }
}
```

### Database

**Required:**
- PostgreSQL 16+

**Why PostgreSQL:**
- Superior JSON support
- Better full-text search
- More advanced features
- Excellent performance
- Open source

### Web Server

**Development:**
- PHP built-in server: `php -S localhost:8000 -t pub`

**Production:**
- **Caddy** (recommended) - HTTP/2, automatic HTTPS
- **Nginx** + PHP-FPM
- **Apache** + mod_php

### PHP Requirements

**Minimum:** PHP 8.4

**Extensions Required:**
- PDO (pdo_pgsql)
- mbstring
- openssl
- json
- ctype
- tokenizer

**Extensions Optional:**
- redis (for Redis cache)
- opcache (recommended for production)
- apcu (for APCu cache)

---

## 📊 Timeline Summary

### Total Duration: 17-23 weeks (4-6 months)

| Phase | Duration | Lines of Code | Focus |
|-------|----------|---------------|-------|
| Phase 1: Core Foundation | 2-3 weeks | 3,000-4,000 | IoC container, contracts, config |
| Phase 2: HTTP Layer | 2-3 weeks | 4,000-5,000 | Request/Response, Router, Middleware |
| Phase 3: Database | 2-3 weeks | 5,000-6,000 | Connection, Query Builder, ORM |
| Phase 4: Modules | 3-4 weeks | 6,000-8,000 | View, Auth, Cache, Mail, Validation |
| Phase 5: Admin Panel | 3-4 weeks | 7,000-9,000 | Dashboard, CRUD, Media, Settings |
| Phase 6: Advanced | 2-3 weeks | 3,000-4,000 | Events, Queue, Logging, CLI |
| Phase 7: Testing & Docs | 2-3 weeks | - | Tests, Documentation |

**Total Lines of Code:** ~30,000-35,000 LOC

### Parallel Work Opportunities

**Can be done in parallel:**
- Phase 4 modules can be built independently
- Documentation can start in Phase 3
- Testing can start as soon as code is written

**Critical Path:**
1. Phase 1 (Foundation) → BLOCKS everything
2. Phase 2 (HTTP) → BLOCKS Phase 4-5
3. Phase 3 (Database) → BLOCKS Phase 5

---

## 🎯 Success Criteria

### Technical Metrics

- [ ] **Test Coverage:** 85%+ for core, 80%+ overall
- [ ] **Performance:** Page load < 200ms (database queries)
- [ ] **Memory Usage:** < 10MB per request
- [ ] **Code Quality:** PSR-12 compliant
- [ ] **Security:** No OWASP Top 10 vulnerabilities
- [ ] **Documentation:** 100% of public APIs documented

### Feature Completeness

- [ ] IoC container with auto-resolution
- [ ] Router with parameters and middleware
- [ ] Database with migrations and ORM
- [ ] Authentication and authorization
- [ ] Admin panel with CRUD
- [ ] Media library
- [ ] Event and queue systems
- [ ] Comprehensive CLI tools

### User Experience

- [ ] Installation wizard works
- [ ] Admin panel is intuitive
- [ ] Documentation is clear
- [ ] Error messages are helpful
- [ ] Performance is acceptable

---

## 🚧 Known Limitations & Future Enhancements

### Phase 1 Limitations

**What's NOT included:**
- Multi-language support (i18n)
- API versioning
- GraphQL support
- Websockets
- Full-text search
- Image processing
- PDF generation
- Excel import/export

### Future Enhancements (Post Phase 7)

**Version 2.0 Features:**
- REST API with authentication
- API rate limiting
- Localization (i18n)
- Theme system
- Plugin marketplace
- Backup/restore system
- Activity log
- Two-factor authentication
- OAuth providers (Google, GitHub)
- Webhooks
- Scheduled tasks (cron alternative)

**Version 3.0 Features:**
- Multi-site support
- Advanced permissions (ACL)
- Content versioning
- Workflow system
- Form builder
- Report generator
- Real-time notifications
- Analytics dashboard

---

## 📝 Development Guidelines

### Code Standards

**PSR Compliance:**
- PSR-1: Basic Coding Standard
- PSR-12: Extended Coding Style
- PSR-3: Logger Interface
- PSR-4: Autoloading

**Naming Conventions:**
- Classes: `StudlyCase`
- Methods: `camelCase`
- Variables: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Database tables: `snake_case`
- Database columns: `snake_case`

**Documentation:**
- All public methods must have docblocks
- Include parameter types and return types
- Add examples for complex features
- Keep README.md updated

### Git Workflow

**Branch Strategy:**
- `main` - Production-ready code
- `develop` - Integration branch
- `feature/*` - Feature branches
- `bugfix/*` - Bug fix branches
- `release/*` - Release preparation

**Commit Messages:**
```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:** feat, fix, docs, style, refactor, test, chore

**Example:**
```
feat(router): add route parameter constraints

- Add where() method for parameter validation
- Support regex patterns
- Add tests for constraints

Closes #123
```

### Testing Requirements

**Coverage Minimums:**
- Core framework: 90%+
- Application code: 85%+
- Controllers: 80%+

**Test Types:**
- Unit tests for all helpers and utilities
- Integration tests for database operations
- Feature tests for user-facing features
- Browser tests for admin panel (optional)

### Security Checklist

- [ ] All user input is validated
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS prevention (output escaping)
- [ ] CSRF protection on forms
- [ ] Password hashing (argon2id)
- [ ] Rate limiting on authentication
- [ ] Secure session management
- [ ] HTTPS enforcement in production
- [ ] Security headers (CSP, X-Frame-Options, etc.)
- [ ] File upload validation
- [ ] SQL injection in query builder

---

## 🎓 Learning Resources

### For Developers Using This Platform

**Getting Started:**
1. Read INSTALLATION.md
2. Follow quickstart tutorial
3. Review example projects
4. Join community forum

**Key Concepts:**
1. Service Container and Dependency Injection
2. Service Providers
3. Routing and Middleware
4. Models and Relationships
5. Views and Templates
6. Authentication and Authorization

### For Contributors

**Understanding the Architecture:**
1. Laravel documentation (similar patterns)
2. Symfony documentation (IoC concepts)
3. Design Patterns (Factory, Strategy, Observer)
4. SOLID Principles
5. PSR Standards

**Required Knowledge:**
- PHP 8.4+ features (strict types, constructor promotion, etc.)
- PDO and prepared statements
- Regular expressions (for router)
- Reflection API (for container)
- Composer and PSR-4 autoloading

---

## 📞 Support & Community

### Getting Help

**Documentation:** `/docs` directory
**Examples:** `/examples` directory  
**GitHub Issues:** Report bugs and request features  
**Discussions:** Ask questions and share ideas

### Contributing

**How to Contribute:**
1. Fork the repository
2. Create feature branch
3. Write tests
4. Submit pull request
5. Wait for review

**Contribution Types:**
- Bug fixes
- New features
- Documentation improvements
- Tests
- Examples

---

## ✅ Final Checklist

### Before Starting Development

- [ ] Read this plan thoroughly
- [ ] Understand IoC container concept
- [ ] Review service provider pattern
- [ ] Set up development environment
- [ ] Install PHP 8.4+
- [ ] Install Composer
- [ ] Install PostgreSQL 16+
- [ ] Install Node.js (for asset building)

### During Development

- [ ] Follow phase order (don't skip ahead)
- [ ] Write tests alongside code
- [ ] Document as you build
- [ ] Commit frequently with clear messages
- [ ] Review code before committing
- [ ] Keep phases focused and complete

### Before Deployment

- [ ] All tests passing
- [ ] Documentation complete
- [ ] Security audit completed
- [ ] Performance testing done
- [ ] Migration plan ready
- [ ] Backup strategy in place
- [ ] Monitoring configured

---

## 🎉 Conclusion

This plan transforms Infinri from a static portfolio site into a **scalable, modular, IoC-based platform** similar to Magento or Shopware.

**Key Achievements:**
- ✅ Proper IoC container with dependency injection
- ✅ Service provider architecture
- ✅ Contracts-based design
- ✅ Zero hard dependencies
- ✅ Extensible plugin system
- ✅ Production-ready features
- ✅ Comprehensive admin panel
- ✅ Full database abstraction
- ✅ Modern development tools

**Timeline:** 17-23 weeks (4-6 months)  
**Code Size:** ~30,000-35,000 LOC  
**Test Coverage:** 85%+

**Result:** A professional, installable platform that developers can use to build websites, similar to WordPress/Laravel but cleaner and more modern.

---

**Document Version:** 1.0  
**Last Updated:** November 24, 2025  
**Next Review:** After Phase 1 completion

---

