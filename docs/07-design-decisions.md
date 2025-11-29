# Architecture Decision Records (ADRs)

**Purpose:** Document significant architectural decisions  
**Format:** Lightweight ADR template  
**Audience:** Future developers, architects

---

## 📐 Overview

This document records **why** we made key architectural decisions. Each ADR includes:
- Context (what forced the decision)
- Decision (what we chose)
- Consequences (trade-offs we accept)

---

## ADR-001: Use Layers + Microkernel Pattern

**Status:** ✅ ACCEPTED  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

We need an architecture that:
- Allows adding modules without changing core
- Supports multiple client sites
- Keeps costs low ($24/mo Droplet)
- Enables fast development (<2 days per module)

**Considered options:**
1. Monolithic MVC (simple but not extensible)
2. Microservices (extensible but expensive)
3. Layers + Microkernel (balanced)

### Decision

We will use **Layers + Microkernel** pattern:
- **Layers:** Core → Services → Application
- **Microkernel:** Core + plugins (service modules)

**Core principles:**
- Core defines interfaces only
- Modules implement interfaces
- Dependencies flow downward only
- Modules are optional and isolated

### Consequences

**Positive:**
- ✅ Can add modules in 1-2 days
- ✅ Each client gets custom module set
- ✅ Core remains stable (no module dependencies)
- ✅ Test modules independently
- ✅ Scale individual modules

**Negative:**
- ⚠️ More abstraction (+1-2ms per request)
- ⚠️ More files than monolith
- ⚠️ Learning curve for new developers

**Trade-off accepted:** Modifiability > Performance > Simplicity

---

## ADR-002: Use RoadRunner over PHP-FPM

**Status:** ✅ ACCEPTED  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

PHP-FPM spawns new process per request:
- Bootstrap app every time (~20-50ms overhead)
- No persistent connections
- No shared memory between requests

We need <300ms response time on a $24/mo Droplet.

**Considered options:**
1. PHP-FPM (traditional, proven)
2. Swoole (PHP extension, complex)
3. RoadRunner (worker-based, Go)

### Decision

We will use **RoadRunner** application server.

**Why:**
- Worker persistence (no bootstrap overhead)
- Graceful reload (zero downtime deploys)
- HTTP/2 support out of the box
- Prometheus metrics built-in
- No PHP extensions needed (pure PHP app)

### Consequences

**Positive:**
- ✅ 5-10x faster request handling
- ✅ Persistent database connections
- ✅ Shared opcode cache across workers
- ✅ Zero-downtime deployments
- ✅ Worker monitoring built-in

**Negative:**
- ⚠️ New technology (less familiar)
- ⚠️ Memory leaks persist across requests
- ⚠️ Global state issues possible
- ⚠️ Worker tuning needed

**Mitigation:**
- Max jobs per worker (1000)
- Worker lifetime limit (1 hour)
- Memory limit per worker (128MB)
- Graceful restart on leak detection

**Trade-off accepted:** Performance > Familiarity

---

## ADR-003: Use PHP Array Schema over Migrations

**Status:** ✅ ACCEPTED  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

Traditional migrations have issues:
- Sequential numbers cause merge conflicts
- Developers run migrations in different orders
- No single source of truth for schema
- Fresh installs slow (run all migrations)

Magento uses XML declarative schema (inspired us).

**Considered options:**
1. Traditional migrations (Laravel/Symfony style)
2. Doctrine migrations (ORM-based)
3. XML declarative schema (Magento style)
4. PHP array schema (our approach)

### Decision

We will use **PHP Array Schema + Data Patches**.

**Schema definition:**
```php
// schema.php
return [
    'tables' => [
        'posts' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'title' => ['type' => 'string', 'length' => 255],
                'content' => ['type' => 'text'],
            ],
            'indexes' => [
                ['columns' => ['title']],
            ],
            'timestamps' => true,
        ],
    ],
];
```

**How it works:**
1. SchemaLoader loads all schema.php files
2. SchemaComparator diffs schema vs database
3. SchemaBuilder generates SQL (CREATE, ALTER, DROP)
4. PatchExecutor runs data patches with dependencies

### Consequences

**Positive:**
- ✅ Single source of truth (schema.php)
- ✅ No migration conflicts
- ✅ Fast fresh installs (~500ms)
- ✅ Idempotent (safe to re-run)
- ✅ Clear schema visibility
- ✅ Easier than XML (~1200 LOC vs 2500 LOC parser)

**Negative:**
- ⚠️ Custom system (not off-the-shelf)
- ⚠️ Schema diff bugs possible
- ⚠️ Complex schema changes need patches
- ⚠️ Learning curve for team

**Mitigation:**
- Extensive testing of schema diff
- Data patches for complex changes
- Documentation with examples
- Dry-run mode for validation

**Trade-off accepted:** Simplicity > Familiarity

---

## ADR-004: Module Depends on Core, Not Vice Versa

**Status:** ✅ ACCEPTED  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

Two possible dependency directions:

**Option A (wrong):**
```
Core imports modules
  ↓
Core breaks when module changes
```

**Option B (correct):**
```
Modules import core interfaces
  ↓
Core stable, modules optional
```

**Considered options:**
1. Core depends on modules (tightly coupled)
2. Modules depend on core (Dependency Inversion)

### Decision

We will use **Dependency Inversion Principle** (SOLID):

```
Core Interfaces  ← Modules implement
     ↑
  Modules
```

**Rules:**
- ✅ Core defines interfaces only
- ✅ Modules implement interfaces
- ✅ Core NEVER imports module code
- ✅ Modules are optional (can disable)

### Consequences

**Positive:**
- ✅ Core remains stable
- ✅ Add/remove modules without core changes
- ✅ No circular dependencies
- ✅ Test modules independently
- ✅ Swap module implementations

**Negative:**
- ⚠️ More interface files
- ⚠️ Interface changes break all modules
- ⚠️ Abstraction overhead

**Mitigation:**
- Semantic versioning for interfaces
- Deprecation periods (2 versions)
- Interface versioning (v1, v2 coexist)

**Trade-off accepted:** Stability > Simplicity

---

## ADR-005: Use File-Based Cache by Default

**Status:** ✅ ACCEPTED  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

Caching options for $24/mo Droplet:
- File-based (built-in, zero setup)
- Redis (fast, requires install)
- APCu (memory, limited)
- Memcached (fast, requires install)

**Requirements:**
- Must work on cheap hosting
- Zero configuration for new projects
- <10ms cache access time

**Considered options:**
1. File-based (simple, portable)
2. Redis required (fast, complex setup)
3. APCu required (fast, limited memory)

### Decision

We will use **file-based cache by default**, with Redis as optional upgrade.

**Implementation:**
```php
// Default (file-based)
'cache' => [
    'driver' => 'file',
    'path' => '/var/cache',
]

// Optional (Redis)
'cache' => [
    'driver' => 'redis',
    'host' => '127.0.0.1',
]
```

### Consequences

**Positive:**
- ✅ Zero setup needed
- ✅ Works on any hosting
- ✅ Easy debugging (view cache files)
- ✅ Scales to 50k req/day easily
- ✅ Can upgrade to Redis later

**Negative:**
- ⚠️ Slower than Redis (~5ms vs ~1ms)
- ⚠️ Disk I/O overhead
- ⚠️ Doesn't scale to millions of requests

**Performance:**
- File cache: ~5-10ms read
- Redis cache: ~1-2ms read
- Difference: ~4-8ms (acceptable)

**Trade-off accepted:** Simplicity > Performance (for now)

**Upgrade path:** Swap to Redis when traffic > 100k req/day

---

## ADR-006: Use Events for Inter-Module Communication

**Status:** ✅ ACCEPTED  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

Modules need to communicate without knowing about each other:
- Blog publishes post → SEO generates sitemap
- Contact submits form → Mail sends email
- User registers → Analytics tracks signup

**Considered options:**
1. Direct module coupling (Blog imports SEO)
2. Service locator (app()->get('seo'))
3. Event system (fire event, listeners react)

### Decision

We will use **event-driven architecture** for inter-module communication.

**How it works:**
```php
// Module A: Fire event (doesn't know who listens)
event(new PostPublished($post));

// Module B: Listen (doesn't know who fired)
class GenerateSitemapListener {
    public function handle(PostPublished $event) {
        $this->generateSitemap();
    }
}
```

### Consequences

**Positive:**
- ✅ Zero coupling between modules
- ✅ Add listeners without changing emitter
- ✅ Easy to disable modules
- ✅ Async processing possible (queue)
- ✅ Event log for debugging

**Negative:**
- ⚠️ Event dispatch overhead (~1-2ms)
- ⚠️ Harder to trace execution flow
- ⚠️ Event flooding possible
- ⚠️ Side effects hidden

**Mitigation:**
- Limit listeners per event
- Event profiling in debug mode
- Queue heavy listeners
- Document all events

**Trade-off accepted:** Decoupling > Traceability

---

## ADR-007: Plain PHP Templates, Not Blade (Initially)

**Status:** ⚠️ TEMPORARY  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

Template engine options:
- Blade (Laravel syntax, needs compiler)
- Twig (Symfony syntax, needs compiler)
- Plain PHP (no compilation, simple)

**Phase 1 goal:** Get core working quickly.

**Considered options:**
1. Blade from start (feature-rich, complex)
2. Twig from start (robust, complex)
3. Plain PHP first, Blade later (phased)

### Decision

We will use **plain PHP templates** initially, **add Blade-like syntax in Phase 4**.

**Current (Phase 1-3):**
```php
<!-- Plain PHP -->
<h1><?= $post->title ?></h1>
<?php foreach ($posts as $post): ?>
    <div><?= $post->title ?></div>
<?php endforeach; ?>
```

**Future (Phase 4):**
```php
<!-- Blade-like -->
<h1>{{ $post->title }}</h1>
@foreach ($posts as $post)
    <div>{{ $post->title }}</div>
@endforeach
```

### Consequences

**Positive (now):**
- ✅ Zero compilation needed
- ✅ Fast development
- ✅ Familiar to all PHP developers
- ✅ Easy debugging

**Positive (future):**
- ✅ Can add Blade later
- ✅ Templates still work (BC)
- ✅ Gradual migration

**Negative:**
- ⚠️ Verbose syntax
- ⚠️ No template inheritance (yet)
- ⚠️ Manual escaping needed

**Mitigation:**
- Helper functions for common tasks
- Partial templates for reuse
- Strict escaping by default

**Trade-off accepted:** Speed > Features (for Phase 1)

**Upgrade path:** Phase 4 adds Blade compiler

---

## ADR-008: PostgreSQL as Exclusive Database

**Status:** ✅ ACCEPTED  
**Date:** November 24, 2025  
**Deciders:** Architecture team

### Context

We need a database that supports:
- Advanced full-text search
- JSON/JSONB for flexible schemas
- Future AI/ML features (vector extensions)
- Complex analytics queries
- Better indexing capabilities
- Open-source with strong community

**Considered options:**
1. MySQL 8.0+ (popular, widely supported)
2. PostgreSQL 16+ (advanced features, extensible)
3. Support both (flexibility but complexity)

### Decision

We will use **PostgreSQL 16+ exclusively**.

**Why PostgreSQL:**
- **Full-text search:** Built-in `tsvector`, `pg_trgm` for fuzzy search
- **JSON support:** Native JSONB with indexing and query operators
- **Vector search:** `pgvector` extension for AI embeddings (future)
- **Advanced indexing:** GIN, GiST, BRIN indexes
- **Window functions:** Superior analytics capabilities
- **Extensions:** Rich ecosystem (uuid-ossp, hstore, PostGIS)
- **Performance:** Excellent query optimizer
- **Standards:** Better SQL compliance

**No MySQL support:**
- Reduces maintenance burden
- Simpler schema grammar
- No cross-database compatibility issues
- Focus on PostgreSQL-specific optimizations

### Required PostgreSQL Extensions

**Core (Phase 3):**
- `uuid-ossp` - UUID generation
- `pg_trgm` - Trigram matching for fuzzy search
- `btree_gin` - Multi-column GIN indexes

**Future (Phase 5+):**
- `pgvector` - Vector similarity search (AI/ML)
- `pg_stat_statements` - Query performance monitoring
- `hstore` - Key-value storage (if needed)

### Schema Grammar Strategy

**PostgreSQL-first:**
```php
// Schema system uses PostgreSQL syntax natively
'indexes' => [
    ['type' => 'gin', 'columns' => ['search_vector']], // PostgreSQL
    ['type' => 'gist', 'columns' => ['location']],     // PostgreSQL
]

'extensions' => ['pg_trgm', 'uuid-ossp'], // Declared in schema
```

**No MySQL compatibility layer:**
- All examples use PostgreSQL syntax
- Schema grammar optimized for PostgreSQL
- No feature detection for MySQL
- Documentation shows PostgreSQL only

### Consequences

**Positive:**
- ✅ Use latest PostgreSQL features without constraints
- ✅ Simpler codebase (single database target)
- ✅ Better full-text search out of the box
- ✅ Future-proof for AI/ML features
- ✅ Superior JSON query capabilities
- ✅ Excellent community and documentation

**Negative:**
- ⚠️ Steeper learning curve for MySQL devs
- ⚠️ Less common in shared hosting
- ⚠️ Requires PostgreSQL knowledge for ops

**Mitigation:**
- Comprehensive PostgreSQL documentation
- Example queries in docs
- Development environment setup guide
- Troubleshooting guide for common issues

**Trade-off accepted:** Advanced features > Wider compatibility

### Version Requirements

**Minimum:** PostgreSQL 16+

**Why 16:**
- JSON improvements
- Performance enhancements
- Better logical replication
- SQL/JSON standard support

**Upgrade path:** Follow PostgreSQL major version releases

### Development Environment

**Required:**
```bash
# Ubuntu/Debian
sudo apt install postgresql-16 postgresql-contrib-16

# macOS
brew install postgresql@16

# Docker
docker run -d -p 5432:5432 -e POSTGRES_PASSWORD=secret postgres:16-alpine
```

**Configuration:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=infinri
DB_USERNAME=postgres
DB_PASSWORD=secret
```

### Production Deployment

**Managed PostgreSQL (Recommended):**
- DigitalOcean Managed Database ($15/mo)
- AWS RDS PostgreSQL
- Heroku Postgres
- Supabase (free tier available)

**Self-hosted:**
- PostgreSQL 16 on Droplet
- Automated backups (`pg_dump`)
- Monitoring with `pg_stat_statements`

### Migration from MySQL (Not Applicable)

Since this is a new platform, no MySQL migration needed. If supporting legacy data:

**DO NOT support MySQL natively.**

Instead:
- One-time migration script (MySQL → PostgreSQL)
- Document migration process
- Provide conversion tools

**Trade-off accepted:** Clean start > Backward compatibility

---

## ADR-009: Formal Platform Contracts for Modules and Events

**Status:** ✅ ACCEPTED  
**Date:** November 28, 2025  
**Deciders:** Architecture team

### Context

Modules and events work but lack formal contracts:
- Modules use `module.php` config files but no interface
- Events work but subscribers have no defined contract
- Third-party modules have no guaranteed API
- SEI architecture requires "defined elements and relationships"

**Considered options:**
1. Keep config-based module system (simpler, less safe)
2. Require all modules to extend base class (inheritance coupling)
3. Define formal interfaces (composition, contract-based)

### Decision

We will define **formal contracts** for platform components:

**Module Contracts:**
```php
interface ModuleInterface {
    public function getName(): string;
    public function getVersion(): string;
    public function getDependencies(): array;
    public function register(ContainerInterface $container): void;
    public function boot(ContainerInterface $container): void;
    public function getProviders(): array;
    public function getCommands(): array;
    public function isEnabled(): bool;
}

interface BootableInterface {
    public function boot(): void;
}

interface InstallableInterface {
    public function install(): void;
    public function upgrade(string $from, string $to): void;
    public function uninstall(): void;
}
```

**Event Subscriber Contract:**
```php
interface EventSubscriberInterface {
    public static function getSubscribedEvents(): array;
}
```

**Template Resolution Contract:**
```php
interface TemplateResolverInterface {
    public function resolve(string $template, string $module, string $area): ?string;
    public function exists(string $template, string $module, string $area): bool;
    public function setTheme(?string $theme): void;
    public function getTheme(): ?string;
}
```

**Implementations:**
- `AbstractModule` - Base class implementing `ModuleInterface`
- `TemplateResolver` - Theme → Module → Core fallback chain

### Consequences

**Positive:**
- ✅ Modules have architectural identity (SEI compliance)
- ✅ Third-party modules have clear contract
- ✅ Type safety for module operations
- ✅ IDE autocompletion and static analysis
- ✅ Event subscribers are formally defined connectors
- ✅ Template resolution is extensible

**Negative:**
- ⚠️ Existing modules need to implement interface (migration)
- ⚠️ More boilerplate for simple modules

**Mitigation:**
- `AbstractModule` provides default implementations
- Config-based modules still work (backwards compatible)
- Gradual migration path for existing modules

**Trade-off accepted:** Type safety > Simplicity

---

## ADR-010: Theme Fallback Chain for Templates

**Status:** ✅ ACCEPTED  
**Date:** November 28, 2025  
**Deciders:** Architecture team

### Context

Template resolution is hardcoded:
- No way to override module templates from theme
- No fallback chain for missing templates
- Each module resolves its own templates

**Considered options:**
1. Module-only templates (simple, no customization)
2. Theme-first resolution (flexible, complex)
3. Fallback chain with caching (balanced)

### Decision

Implement **TemplateResolver** with fallback chain:

```
Resolution Order:
1. Theme/view/{area}/templates/{module}/{template}  ← Theme override
2. Module/view/{area}/templates/{template}          ← Module default
3. Core/View/view/{area}/templates/{template}       ← Core fallback
4. modules/{module}/view/{area}/templates/          ← Legacy support
```

**Features:**
- Caches resolved paths for performance
- Supports frontend and admin areas
- Resolves templates, layouts, and assets
- Theme can be changed at runtime

### Consequences

**Positive:**
- ✅ Themes can override any module template
- ✅ Modules don't need to know about themes
- ✅ Core provides sensible defaults
- ✅ Legacy module paths still work
- ✅ Performance via caching

**Negative:**
- ⚠️ More file system lookups (mitigated by cache)
- ⚠️ Debugging requires knowing fallback order

**Trade-off accepted:** Customizability > Simplicity

---

## 📋 Decision Log

| ADR | Decision | Status | Impact | Review Date |
|-----|----------|--------|--------|-------------|
| ADR-001 | Layers + Microkernel | ✅ Accepted | Architecture | After Phase 1 |
| ADR-002 | RoadRunner | ✅ Accepted | Performance | After Phase 2 |
| ADR-003 | PHP Array Schema | ✅ Accepted | Database | After Phase 3 |
| ADR-004 | Dependency Inversion | ✅ Accepted | Architecture | After Phase 1 |
| ADR-005 | File Cache Default | ✅ Accepted | Performance | After Phase 4 |
| ADR-006 | Events for Communication | ✅ Accepted | Architecture | After Phase 4 |
| ADR-007 | Plain PHP Templates | ⚠️ Temporary | Developer UX | Phase 4 |
| ADR-008 | PostgreSQL Exclusive | ✅ Accepted | Database | After Phase 3 |
| ADR-009 | Platform Contracts | ✅ Accepted | Architecture | After Phase 1 |
| ADR-010 | Theme Fallback Chain | ✅ Accepted | Theming | After Phase 4 |

---

## 🔄 Decision Review Process

### When to Review

**Triggers:**
- End of each phase
- Major performance issue
- Significant user feedback
- Technology changes
- 6 months since decision

### Review Questions

1. **Does the decision still make sense?**
2. **Have constraints changed?**
3. **Are consequences acceptable?**
4. **Should we reverse or modify?**

### Modification Process

1. Document new context
2. Propose alternative
3. Evaluate trade-offs
4. Update ADR (keep history)
5. Communicate change

---

## 📝 Creating New ADRs

### Template

```markdown
## ADR-XXX: [Title]

**Status:** 🟡 PROPOSED | ✅ ACCEPTED | ❌ REJECTED | ⚠️ DEPRECATED  
**Date:** YYYY-MM-DD  
**Deciders:** [Names/Roles]

### Context
[What forces the decision?]

**Considered options:**
1. Option A
2. Option B
3. Option C

### Decision
[What we chose and why]

### Consequences

**Positive:**
- ✅ Benefit 1
- ✅ Benefit 2

**Negative:**
- ⚠️ Cost 1
- ⚠️ Cost 2

**Mitigation:**
[How we reduce negative consequences]

**Trade-off accepted:** [What we prioritize]
```

---

**Version:** 1.2  
**Last Updated:** November 28, 2025  
**Next Review:** After Phase 1 completion
