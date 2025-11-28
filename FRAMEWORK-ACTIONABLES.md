# Final Framework Actionables

> Enterprise-grade framework implementation plan for Infinri Core

---

## Current State Assessment

| Feature | Status | What Exists | What's Missing |
|---------|--------|-------------|----------------|
| **Module Discovery** | 🟡 Partial | `ModuleManager` scans dirs, finds `*Module.php` | No metadata, no registry, no dependency sorting |
| **Events** | 🟡 Partial | `EventDispatcher`, `Event` base class | No scanning, no compilation, no module events |
| **Container/DI** | 🟢 Exists | `Container`, `ServiceProvider` | No compilation, no caching, no provider scanning |
| **Cache** | 🟡 Partial | `CacheManager`, `FileStore`, `ArrayStore` | No pools, single flat cache |
| **Config** | 🟡 Partial | Single `app/config.php` | No per-module configs, no merging |
| **Migrations** | 🟢 Exists | `Database/Migrator.php` | Not integrated into `s:up` |
| **Hooks** | 🟡 Partial | `afterSetup()` in SetupCommand | No `beforeSetup`, no module hooks |
| **Reindexer** | 🔴 Missing | Nothing | Need full framework |
| **Class Discovery** | 🔴 Missing | Nothing | No attribute/annotation scanning |

---

## Phase 1: Module System (Foundation) 🔥

**Status: ✅ Complete**

### 1.1 Module Metadata
- [x] Create `module.php` structure for each module
- [x] Define module schema: name, version, dependencies, providers

```php
// modules/blog/module.php
return [
    'name' => 'blog',
    'version' => '1.0.0',
    'description' => 'Blog module',
    'dependencies' => [],
    'providers' => [
        \App\Modules\Blog\Providers\BlogServiceProvider::class,
    ],
    'commands' => [
        \App\Modules\Blog\Console\PublishCommand::class,
    ],
    'events' => 'events.php',
    'config' => 'config.php',
];
```

### 1.2 Module Registry
- [x] Create `ModuleRegistry` class
- [x] Scan and cache module metadata to `var/cache/modules.php`
- [x] Track module state (enabled/disabled)
- [x] Detect new/removed modules

### 1.3 Dependency Ordering
- [x] Topological sort for module loading order
- [x] Circular dependency detection
- [x] Load order caching

### 1.4 Module Service Provider Discovery
- [x] Auto-register module service providers
- [ ] Deferred provider support (future enhancement)

### 1.5 Commands Added
- [x] `module:list` (m:l) - List all registered modules
- [x] `s:up` - Now rebuilds module registry

### 1.6 Files Created
- `Core/Module/ModuleDefinition.php` - Module metadata class
- `Core/Module/ModuleRegistry.php` - Registry with caching
- `Core/Module/ModuleLoader.php` - Loads modules and registers providers
- `Core/Console/Commands/ModuleListCommand.php` - List modules command

---

## Phase 2: Compilation System 🔧

**Status: ✅ Complete**

### 2.1 Config Compiler
- [x] Per-module config files: `modules/*/config.php`
- [x] Config merger with precedence rules
- [x] Compile to `var/cache/config.php`
- [x] Deep merge support

### 2.2 DI Compiler
- [x] Scan module service providers
- [x] Cache provider metadata
- [x] Generate `var/cache/container.php`
- [x] Deferred provider support

### 2.3 Event Compiler
- [x] Per-module event files: `modules/*/events.php`
- [x] Scan and merge listeners
- [x] Compile to `var/cache/events.php`
- [x] Priority-based listener ordering

### 2.4 Files Created
- `Core/Compiler/ConfigCompiler.php` - Config merging and caching
- `Core/Compiler/EventCompiler.php` - Event listener compilation
- `Core/Compiler/ContainerCompiler.php` - DI provider compilation
- `Core/Compiler/CompilerManager.php` - Orchestrates all compilers

---

## Phase 3: Cache Pools 🗂️

**Status: ✅ Complete**

### 3.1 Cache Pool Structure
```
var/cache/
├── config.php      # Compiled config
├── container.php   # DI bindings
├── events.php      # Event listeners
├── modules.php     # Module registry
├── runtime/        # Runtime cache
├── views/          # View cache
└── data/           # Data cache
```

### 3.2 Granular Cache Clearing
- [x] Extended `CacheManager` with named pools
- [x] `cache:clear` command (cc alias)
- [x] `--pool=name` for specific pool
- [x] `--all` to include compiled files
- [x] `--compiled` for compiled files only

### 3.3 Files Modified
- `Core/Cache/CacheManager.php` - Added pool() method
- `Core/Console/Commands/CacheClearCommand.php` - New command

---

## Phase 4: Integration 🔗

**Status: ✅ Complete**

### 4.1 Migration Integration
- [x] Run pending migrations in `s:up`
- [x] Uses existing `Core/Database/Migrator`
- [x] Graceful skip when DB not configured
- [x] Tracks migration history in DB

### 4.2 Module Hooks
- [x] `onInstall()` - First time module setup
- [x] `onUpgrade($fromVersion)` - Version upgrades
- [x] `beforeSetup()` / `afterSetup()` - Setup hooks
- [x] Module state tracked in `var/state/modules.php`
- [x] `onEnable()` / `onDisable()` - State change hooks
- [x] `module:enable` / `module:disable` commands

### 4.3 Console Command Discovery
- [x] Core commands auto-discovered
- [x] Module commands via `module.php`

### 4.4 Files Created
- `Core/Module/ModuleHookRunner.php` - Executes lifecycle hooks
- `modules/contact/hooks.php` - Sample hooks file

---

## Phase 5: Advanced Features ⚙️

**Status: ✅ Complete**

### 5.1 Reindexer Framework (Module-Extensible)
- [x] `IndexerInterface` contract
- [x] `AbstractIndexer` base class
- [x] `IndexerRegistry` for module indexers
- [x] No built-in indexers (all module-specific)
- [x] Incremental reindexing support
- [x] `index:reindex [name]` command
- [x] `index:reindex --list` to see registered indexers

### 5.2 Preload Builder
- [x] `PreloadCompiler` generates preload.php
- [x] Includes core files and compiled cache
- [x] `preload:generate` command
- [x] OPcache configuration instructions

### 5.3 Route Caching
- [x] `RouteCompiler` compiles module routes
- [x] Caches to `var/cache/routes.php`
- [x] Integrated into `CompilerManager`

### 5.4 HTTP Pipeline Compilation
- [x] `MiddlewareCompiler` with priority ordering
- [x] Global, web, api middleware groups
- [x] Middleware aliases support
- [x] Caches to `var/cache/middleware.php`

### 5.5 Config Validation
- [x] `ConfigValidator` with schema support
- [x] Type, min/max, pattern, enum validation
- [x] Custom validator callbacks

### 5.6 Files Created
- `Core/Contracts/Indexer/IndexerInterface.php`
- `Core/Indexer/IndexerRegistry.php`
- `Core/Indexer/AbstractIndexer.php`
- `Core/Compiler/PreloadCompiler.php`
- `Core/Compiler/RouteCompiler.php`
- `Core/Compiler/MiddlewareCompiler.php`
- `Core/Config/ConfigValidator.php`
- `Core/Console/Commands/IndexReindexCommand.php`
- `Core/Console/Commands/PreloadGenerateCommand.php`
- `Core/Console/Commands/ModuleEnableCommand.php`
- `Core/Console/Commands/ModuleDisableCommand.php`
- `app/Http/middleware.php`

---

## Commands Available

```bash
# Setup
php bin/console s:i          # Install (create .env)
php bin/console s:up         # Update (migrate, compile, cache)
php bin/console s:p          # Fix permissions

# Cache
php bin/console cache:clear              # Clear all
php bin/console cache:clear --pool=di    # Clear specific pool

# Modules
php bin/console module:list              # List modules
php bin/console module:enable blog       # Enable module
php bin/console module:disable blog      # Disable module

# Indexing (module-registered indexers)
php bin/console index:reindex            # Reindex all
php bin/console index:reindex search     # Reindex specific
php bin/console index:reindex --list     # List available indexers

# Preload
php bin/console preload:generate         # Generate OPcache preload file
```

---

## File Structure After Implementation

```
app/
├── Core/
│   ├── Module/
│   │   ├── ModuleManager.php      # Discovery
│   │   ├── ModuleRegistry.php     # Registry + caching
│   │   └── ModuleLoader.php       # Loading + ordering
│   ├── Compiler/
│   │   ├── ConfigCompiler.php
│   │   ├── ContainerCompiler.php
│   │   └── EventCompiler.php
│   └── Console/Commands/
│       ├── CacheClearCommand.php
│       └── ModuleListCommand.php
│
├── modules/
│   └── blog/
│       ├── module.php             # Module metadata
│       ├── config.php             # Module config
│       ├── events.php             # Module events
│       ├── routes.php             # Module routes
│       └── Providers/
│           └── BlogServiceProvider.php
│
└── var/
    └── cache/
        ├── modules.php
        ├── config.php
        ├── container.php
        └── events.php
```

---

## Progress Log

| Date | Phase | Task | Status |
|------|-------|------|--------|
| 2024-11-27 | Setup | Console commands refactored | ✅ Done |
| 2024-11-27 | Setup | .env.example finalized | ✅ Done |
| 2024-11-27 | Setup | Auto-discovery console commands | ✅ Done |
| 2024-11-27 | Phase 1 | ModuleDefinition class | ✅ Done |
| 2024-11-27 | Phase 1 | ModuleRegistry with caching | ✅ Done |
| 2024-11-27 | Phase 1 | Dependency ordering (topological sort) | ✅ Done |
| 2024-11-27 | Phase 1 | module:list command | ✅ Done |
| 2024-11-27 | Phase 1 | s:up module registry integration | ✅ Done |
| 2024-11-27 | Phase 1.4 | ModuleLoader class | ✅ Done |
| 2024-11-27 | Phase 1.4 | Application.bootstrap() loads modules | ✅ Done |
| 2024-11-27 | Phase 1.4 | Sample ContactServiceProvider | ✅ Done |
| 2024-11-27 | Phase 2 | ConfigCompiler | ✅ Done |
| 2024-11-27 | Phase 2 | EventCompiler | ✅ Done |
| 2024-11-27 | Phase 2 | ContainerCompiler | ✅ Done |
| 2024-11-27 | Phase 2 | CompilerManager + s:up integration | ✅ Done |
| 2024-11-27 | Phase 3 | CacheManager pool support | ✅ Done |
| 2024-11-27 | Phase 3 | cache:clear command | ✅ Done |
| 2024-11-27 | Phase 4 | Migrator integration in s:up | ✅ Done |
| 2024-11-27 | Phase 4.2 | ModuleHookRunner class | ✅ Done |
| 2024-11-27 | Phase 4.2 | onInstall/onUpgrade hooks | ✅ Done |
| 2024-11-27 | Phase 4.2 | beforeSetup/afterSetup hooks | ✅ Done |
| 2024-11-27 | Phase 4.2 | Module state tracking | ✅ Done |
| 2024-11-27 | Cleanup | ModuleManager deprecated | ✅ Done |
| 2024-11-27 | Phase 5 | IndexerRegistry + AbstractIndexer | ✅ Done |
| 2024-11-27 | Phase 5 | index:reindex command | ✅ Done |
| 2024-11-27 | Phase 5 | PreloadCompiler + command | ✅ Done |
| 2024-11-27 | Phase 5 | RouteCompiler | ✅ Done |
| 2024-11-27 | Phase 5 | ConfigValidator | ✅ Done |
| 2024-11-27 | Phase 5 | module:enable/disable commands | ✅ Done |
| 2024-11-27 | Phase 5 | onEnable/onDisable hooks | ✅ Done |
| 2024-11-27 | Phase 5 | MiddlewareCompiler (HTTP pipeline) | ✅ Done |
| 2024-11-28 | Phase 0.9 | health:check command | ✅ Done |
| 2024-11-28 | Phase 0.9 | s:up flags (--skip-*, --dry-run) | ✅ Done |
| 2024-11-28 | Phase 0.9 | DB backup before migrations | ✅ Done |
| 2024-11-28 | Phase 1.0 | module:make generator | ✅ Done |
| 2024-11-28 | Phase 1.0 | make:migration generator | ✅ Done |
| 2024-11-28 | Phase 1.0 | make:seeder generator | ✅ Done |
| 2024-11-28 | Phase 0.9 | --env flag + env switching | ✅ Done |
| 2024-11-28 | Phase 0.9 | Git hash logging | ✅ Done |
| 2024-11-28 | Phase 0.9 | Final summary output | ✅ Done |
| 2024-11-28 | Phase 0.9 | MigrationState (rollback safety) | ✅ Done |
| 2024-11-28 | Phase 0.9 | migrate:reset-state command | ✅ Done |
| 2024-11-28 | Phase 1.1 | MetricsCollector | ✅ Done |
| 2024-11-28 | Phase 1.1 | metrics:show command | ✅ Done |
| 2024-11-28 | Phase 1.1 | MetricsMiddleware | ✅ Done |
| 2024-11-28 | Phase 1.1 | Prometheus MetricsEndpoint | ✅ Done |
| 2024-11-28 | Phase 1.2 | code:stats LOC measurement | ✅ Done |
| 2024-11-28 | Phase 1.1 | /_metrics endpoint wired | ✅ Done |
| 2024-11-28 | Phase 1.1 | DB query metrics tracking | ✅ Done |
| 2024-11-28 | Phase 1.1 | Cache hit/miss tracking | ✅ Done |
| 2024-11-28 | Bugfix | Connection::query() naming conflict | ✅ Fixed |
| 2024-11-28 | Phase 1.3 | RateLimitMiddleware | ✅ Done |
| 2024-11-28 | Phase 1.3 | SecurityHeadersMiddleware | ✅ Done |
| 2024-11-28 | Phase 1.3 | VerifyCsrfToken middleware | ✅ Done |
| 2024-11-28 | Phase 1.3 | Middleware config updated | ✅ Done |
| 2024-11-28 | Refactor | DatabaseBackup extracted | ✅ Done |
| 2024-11-28 | Refactor | EnvManager extracted | ✅ Done |
| 2024-11-28 | Refactor | HasAttributes trait extracted | ✅ Done |

---

## Phase 0.9 – Safety & Operations ✅

### Health Check
- [x] `health:check` command (aliases: `doctor`, `sys:check`)
- [x] Validates .env presence and required keys
- [x] Validates directory permissions (var/cache, var/log, var/state)
- [x] Validates database connectivity
- [x] Validates compiled caches presence
- [x] Validates module integrity

### s:up Flags
- [x] `--skip-db` - Skip database migrations
- [x] `--skip-compile` - Skip compilation step
- [x] `--skip-cache` - Skip cache clearing
- [x] `--skip-hooks` - Skip module hooks
- [x] `--no-backup` - Skip database backup before migrations
- [x] `--dry-run` - Show what would be done without executing
- [x] `--env=prod|dev|stage` - Set/switch environment

### Release Awareness
- [x] Git hash displayed in environment info
- [x] Final summary with env, version, git hash
- [x] Production warning when APP_DEBUG=true

### Database Backup
- [x] Auto-backup before migrations (PostgreSQL & MySQL)
- [x] Stored in `var/backups/backup_TIMESTAMP.sql`
- [x] Size displayed after backup

### Migration Safety
- [x] `MigrationState` tracks running/failed migrations
- [x] System marked "UNSAFE" on migration failure
- [x] Prevents running s:up until issue is resolved
- [x] `migrate:reset-state --force` to clear after recovery

---

## Phase 1.0 – Developer Experience ✅

### Generators
- [x] `module:make <name>` - Generate module scaffold
- [x] `make:migration <name>` - Generate migration file
- [x] `make:seeder <name>` - Generate seeder file

### Module Generator Creates
- module.php (metadata)
- index.php (view)
- config.php
- events.php
- hooks.php
- Providers/{Name}ServiceProvider.php
- Controllers/, Models/, view/ directories

---

## Phase 1.1 – Observability ✅

### Metrics
- [x] `MetricsCollector` for basic application metrics
- [x] Request count, response times, error rates
- [x] Database query tracking
- [x] Cache hit/miss ratios
- [x] Hourly breakdown
- [x] `metrics:show` command (alias: `metrics`)
- [x] Stored in `var/state/metrics.php`

### Automatic Collection
- [x] `MetricsMiddleware` for automatic request tracking
- [x] Added to global middleware (priority 200)

### Prometheus Export
- [x] `MetricsEndpoint` for Prometheus scraping
- [x] Wired to `/_metrics` in pub/index.php
- [x] Text format (default): Prometheus scrape format
- [x] JSON format: `?format=json`
- [x] Authorization: localhost or API key (`METRICS_API_KEY`)

### Integrated Tracking
- [x] Database queries tracked via `Connection::logQuery()`
- [x] Cache hits/misses tracked via `FileStore::get()`

---

## Phase 1.2 – Code Quality ✅

### LOC Measurement
- [x] `code:stats` command (alias: `loc`)
- [x] Per-directory breakdown
- [x] Largest files identification
- [x] Refactoring candidates flagged (>300 lines)

### Current Stats
- Core Framework: 22,707 LOC (148 files)
- Tests: 8,691 LOC (41 files)
- Total: ~36,369 LOC (232 files)

### Refactoring Results
| File | Before | After | Reduction |
|------|--------|-------|-----------|
| SetupCommand.php | 668 | 567 | -15% |
| Model.php | 527 | 339 | -36% |

### Extracted Classes
- `DatabaseBackup` - backup/restore/list/prune
- `EnvManager` - .env read/write operations
- `HasAttributes` trait - model attribute handling

---

## Phase 1.3 – Security Hardening ✅

### Rate Limiting
- [x] `RateLimitMiddleware` - thin wrapper around existing `Security\RateLimiter`
- [x] Uses `FileStore` cache (Redis-ready via `CacheInterface`)
- [x] Per-IP + path rate limiting
- [x] Standard headers (X-RateLimit-*)

### Security Headers
- [x] `SecurityHeadersMiddleware` with defaults
- [x] X-Content-Type-Options, X-Frame-Options, Referrer-Policy
- [x] Permissions-Policy, Cross-Origin policies
- [x] CSP nonce support, optional HSTS

### CSRF Protection
- [x] `VerifyCsrfToken` - thin wrapper around existing `Security\Csrf`
- [x] Form field: `csrf_token` (matches existing Csrf class)
- [x] Header: `X-CSRF-TOKEN`
- [x] Cookie: `XSRF-TOKEN` for JS frameworks

### Existing Security Classes (reused)
- `Security\Csrf` - token generation/verification with expiry
- `Security\RateLimiter` - cache-based rate limiting
- `Security\Sanitizer` - input sanitization

### Middleware Configuration
```php
'web' => [SecurityHeadersMiddleware, VerifyCsrfToken]
'api' => [RateLimitMiddleware, SecurityHeadersMiddleware]
'aliases' => ['csrf', 'throttle', 'security']i
```

---

## Notes

- Prioritize simplicity over Magento complexity
- Keep compiled files human-readable for debugging
- All compilation should be idempotent
- Support both file-based and Redis-based caching
