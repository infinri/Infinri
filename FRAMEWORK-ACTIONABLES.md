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
- [ ] `onEnable()` / `onDisable()` - Future enhancement

### 4.3 Console Command Discovery
- [x] Core commands auto-discovered
- [x] Module commands via `module.php`

### 4.4 Files Created
- `Core/Module/ModuleHookRunner.php` - Executes lifecycle hooks
- `modules/contact/hooks.php` - Sample hooks file

---

## Phase 5: Advanced Features ⚙️

**Status: ⏸️ Pending**

### 5.1 Class Discovery
- [ ] Attribute scanning for auto-registration
- [ ] Reflection-based discovery
- [ ] Discovery result caching

### 5.2 Reindexer Framework
- [ ] Indexer interface
- [ ] Built-in indexers: search, menu, sitemap
- [ ] Incremental reindexing
- [ ] CLI: `index:reindex [indexer]`

### 5.3 Preload Builder
- [ ] Generate `preload.php` for OPcache
- [ ] Include hot paths and compiled files

---

## Commands After Implementation

```bash
# Setup
php bin/console s:i          # Install (create .env)
php bin/console s:up         # Update (migrate, compile, cache)

# Cache
php bin/console cache:clear              # Clear all
php bin/console cache:clear --pool=di    # Clear specific pool

# Modules
php bin/console module:list              # List modules
php bin/console module:enable blog       # Enable module
php bin/console module:disable blog      # Disable module

# Indexing
php bin/console index:reindex            # Reindex all
php bin/console index:reindex search     # Reindex specific
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
| 2024-11-27 | Phase 5 | Advanced Features | ⏸️ Optional |

---

## Notes

- Prioritize simplicity over Magento complexity
- Keep compiled files human-readable for debugging
- All compilation should be idempotent
- Support both file-based and Redis-based caching
