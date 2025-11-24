# Architecture Patterns

**Based on:** SEI Software Architecture Patterns  
**Purpose:** Document pattern decisions and rationale  
**Audience:** Architects, senior developers

---

## 📐 Overview

Infinri combines **two proven architectural patterns**:
1. **Layers Pattern** - Vertical separation of concerns
2. **Microkernel Pattern** - Horizontal plugin architecture

This combination is standard practice for extensible platforms (Magento, WordPress, VSCode, Stripe).

---

## 🏛️ Pattern 1: Layers (Vertical)

### Definition

**From SEI:** "Layers pattern organizes software into groups where each group (layer) provides services to the layer above it and uses services from the layer below it."

### Our Implementation

```
┌─────────────────────────────────────┐
│     Layer 4: Application            │  ← Controllers, Commands, Views
├─────────────────────────────────────┤
│     Layer 3: Service Modules        │  ← Cache, Mail, Auth, Blog
├─────────────────────────────────────┤
│     Layer 2: Core Infrastructure    │  ← Container, Router, HTTP, DB
├─────────────────────────────────────┤
│     Layer 1: Foundation             │  ← PHP 8.4, RoadRunner
└─────────────────────────────────────┘
```

### Layer Responsibilities

#### Layer 4: Application (Business Logic)
**Location:** `app/Modules/*/Controllers`, `app/Console/Commands`

**Responsibilities:**
- Handle HTTP requests
- Coordinate services
- Render views
- Execute commands

**Dependencies:** Service modules (Layer 3)

**Example:**
```php
// Layer 4: Application
class PostController {
    public function __construct(
        private CacheInterface $cache,      // Layer 3
        private SeoInterface $seo            // Layer 3
    ) {}
    
    public function show(int $id) {
        $post = $this->cache->remember("post.$id", 
            fn() => Post::find($id)          // Layer 2
        );
        
        $this->seo->setMetaTags($post);
        
        return view('blog.post', compact('post'));
    }
}
```

---

#### Layer 3: Service Modules (Business Services)
**Location:** `app/base/providers/*`, `app/Modules/*/Services`

**Responsibilities:**
- Implement business logic
- Provide reusable services
- Handle cross-cutting concerns
- Communicate via events

**Dependencies:** Core infrastructure (Layer 2)

**Allowed:**
- ✅ Call Layer 2 (database, events)
- ✅ Implement Layer 2 interfaces
- ✅ Use Layer 2 abstractions

**NOT Allowed:**
- ❌ Direct Layer 4 dependencies
- ❌ Knowledge of controllers
- ❌ HTTP-specific logic (use interfaces)

**Example:**
```php
// Layer 3: Service Module
class CacheService implements CacheInterface {
    public function __construct(
        private ConnectionInterface $db  // Layer 2
    ) {}
    
    public function remember(string $key, Closure $callback): mixed {
        if ($value = $this->get($key)) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value);
        
        return $value;
    }
}
```

---

#### Layer 2: Core Infrastructure (Framework)
**Location:** `app/base/core/*`

**Responsibilities:**
- Define contracts (interfaces)
- Provide IoC container
- Route HTTP requests
- Manage database connections
- Dispatch events

**Dependencies:** Foundation only (Layer 1)

**Allowed:**
- ✅ Use PHP features
- ✅ Use RoadRunner APIs
- ✅ Define interfaces ONLY

**NOT Allowed:**
- ❌ Import Layer 3 modules
- ❌ Know about specific services
- ❌ Business logic

**Example:**
```php
// Layer 2: Core Infrastructure
namespace App\Core\Contracts\Cache;

interface CacheInterface {
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function forget(string $key): bool;
}
```

---

#### Layer 1: Foundation (Runtime)
**Location:** PHP runtime, RoadRunner process

**Responsibilities:**
- Execute PHP code
- Manage HTTP workers
- Provide system libraries
- Handle process lifecycle

**No application code at this layer**

---

### Layering Rules (Strict)

#### Rule 1: Dependencies Flow DOWN Only

```
✅ ALLOWED:
Layer 4 → Layer 3 → Layer 2 → Layer 1

❌ FORBIDDEN:
Layer 1 → Layer 2  (No upward calls)
Layer 2 → Layer 3  (No upward calls)
Layer 3 → Layer 4  (No upward calls)
```

**Enforcement:**
- PHPStan dependency analysis
- Namespace restrictions
- Code review

---

#### Rule 2: Skip Layers is ALLOWED (But Discouraged)

```
⚠️ ALLOWED (but not recommended):
Layer 4 → Layer 2 (skip Layer 3)

✅ PREFERRED:
Layer 4 → Layer 3 → Layer 2
```

**Why discourage:** Bypassing layers reduces modularity.

---

#### Rule 3: Core (Layer 2) NEVER Imports Modules (Layer 3)

```
❌ FORBIDDEN:
// In app/base/core/Router.php
use App\Services\CacheService;  // ← NO!

✅ CORRECT:
// In app/base/core/Router.php
use App\Core\Contracts\Cache\CacheInterface;  // ← YES!
```

**Rationale:** Core must work without any service modules.

---

### Advantages of Layers

✅ **Modifiability**
- Change Layer 3 without touching Layer 2
- Swap implementations easily
- Clear boundaries

✅ **Testability**
- Test layers independently
- Mock lower layers
- Unit tests don't need full stack

✅ **Reusability**
- Layer 2 reusable across projects
- Layer 3 modules shareable
- Clear contracts

✅ **Understandability**
- Clear structure
- Predictable dependencies
- Easy onboarding

---

### Disadvantages of Layers

⚠️ **Performance Overhead**
- Function call hops (+1-2ms/request)
- Abstraction cost
- Interface dispatch overhead

**Mitigation:** OPcache, RoadRunner persistence

⚠️ **More Files**
- Interfaces + implementations
- More directories
- Larger codebase

**Mitigation:** IDE navigation, generators

⚠️ **Complexity**
- Learning curve
- More abstraction
- Harder debugging

**Mitigation:** Documentation, examples

---

## 🔌 Pattern 2: Microkernel (Horizontal)

### Definition

**From SEI:** "Microkernel pattern applies to software with a core system that provides minimal functionality, and other features are provided by plug-in modules."

### Our Implementation

```
         ┌──────────────────────┐
         │   Core (Minimal)     │
         │ - Container          │
         │ - Interfaces         │
         │ - HTTP/DB/Events     │
         └──────────┬───────────┘
                    │
        ┌───────────┼───────────┬───────────┐
        │           │           │           │
        ↓           ↓           ↓           ↓
   ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐
   │ Cache  │  │  Mail  │  │  Blog  │  │  SEO   │
   │ Plugin │  │ Plugin │  │ Plugin │  │ Plugin │
   └────────┘  └────────┘  └────────┘  └────────┘
```

### Microkernel Components

#### Core (Microkernel)
**What it provides:**
- Plugin registration system (ServiceProvider)
- Plugin discovery (Module::discover())
- Plugin lifecycle (register → boot)
- Plugin communication (Events)
- Shared services (Container)

**What it does NOT provide:**
- Business logic (plugins do this)
- Specific features (plugins do this)
- Data models (plugins do this)

---

#### Plugins (Service Modules)
**What they provide:**
- Specific functionality
- Optional features
- Custom business logic
- Data models

**How they register:**
```php
// Plugin registration
class BlogServiceProvider extends ServiceProvider {
    // 1. Register (bindings)
    public function register(): void {
        $this->app->singleton(BlogInterface::class, BlogService::class);
    }
    
    // 2. Boot (initialization)
    public function boot(): void {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadViewsFrom(__DIR__ . '/View');
        $this->loadSchemaFrom(__DIR__ . '/schema.php');
    }
}
```

---

### Plugin Discovery

**Automatic:**
```php
// Core discovers plugins
$modules = Module::discover();  // Scans app/Modules/
// Returns: ['Blog', 'Contact', 'SEO', ...]

// Load each plugin
foreach ($modules as $module) {
    $provider = "{$module}ServiceProvider";
    $app->register(new $provider($app));
}
```

**Manual (config):**
```php
// config/app.php
'providers' => [
    App\Modules\Blog\BlogServiceProvider::class,
    App\Modules\SEO\SeoServiceProvider::class,
    // ...
]
```

---

### Plugin Communication

#### Method 1: Direct Injection (Preferred)
```php
class PostController {
    public function __construct(
        private SeoInterface $seo      // Plugin 1
    ) {}
    
    public function show() {
        $this->seo->setMetaTags(...);  // Direct call
    }
}
```

**Pros:** Fast, type-safe, testable  
**Cons:** Creates dependency

---

#### Method 2: Events (Decoupled)
```php
// Plugin A: Fires event
event(new PostPublished($post));

// Plugin B: Listens (doesn't know about A)
class SeoListener {
    public function handle(PostPublished $event) {
        $this->generateSitemap();
    }
}
```

**Pros:** Zero coupling, async possible  
**Cons:** Slower, harder to trace

---

### Advantages of Microkernel

✅ **Extensibility**
- Add features without core changes
- Disable features easily
- Custom client configurations

✅ **Isolation**
- Plugin bugs don't crash core
- Test plugins independently
- Version plugins separately

✅ **Developer Happiness**
- Work on one plugin at a time
- Clear boundaries
- Easy to reason about

✅ **Customization**
- Each client gets unique plugins
- A/B test features
- Gradual rollouts

---

### Disadvantages of Microkernel

⚠️ **Plugin Dispatch Overhead**
- Service provider loading (+2-5ms)
- Plugin discovery (+1-2ms)
- Event dispatch (+1-2ms/event)

**Mitigation:** Cache plugin list, worker persistence

⚠️ **Coordination Complexity**
- Plugin dependencies
- Load order matters
- Version conflicts

**Mitigation:** Dependency graph, semantic versioning

⚠️ **Debugging Difficulty**
- Plugin interactions hard to trace
- Event chains unclear
- Side effects hidden

**Mitigation:** Logging, event monitoring, debugbar

---

## 🔗 Combining Layers + Microkernel

### Why Combine?

**Layers provide:**
- ✅ Vertical structure (Core → Services → App)
- ✅ Clear dependency flow
- ✅ Testable boundaries

**Microkernel provides:**
- ✅ Horizontal structure (Core + Plugins)
- ✅ Optional features
- ✅ Runtime extensibility

**Together:**
- ✅✅ Modular AND structured
- ✅✅ Extensible AND maintainable
- ✅✅ Flexible AND organized

---

### Combined Structure

```
                Application Layer (Layer 4)
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ↓               ↓               ↓
   ┌────────────┐ ┌────────────┐ ┌────────────┐
   │   Blog     │ │  Contact   │ │    SEO     │  ← Plugins (Layer 3)
   │  Plugin    │ │   Plugin   │ │   Plugin   │
   └─────┬──────┘ └─────┬──────┘ └─────┬──────┘
         │              │              │
         └──────────────┼──────────────┘
                        │
                        ↓
            ┌───────────────────────┐
            │   Core Infrastructure │  ← Microkernel (Layer 2)
            │  - Container          │
            │  - Interfaces         │
            │  - HTTP/DB/Events     │
            └───────────┬───────────┘
                        │
                        ↓
            ┌───────────────────────┐
            │      Foundation       │  ← Runtime (Layer 1)
            │   PHP + RoadRunner    │
            └───────────────────────┘
```

---

### Real Example: Blog Module

**Layer 4 (Application):**
```php
// Controllers handle HTTP
class PostController {
    public function index() {
        $posts = Post::paginate(10);  // Layer 3 (Blog plugin)
        return view('blog.index', compact('posts'));
    }
}
```

**Layer 3 (Blog Plugin):**
```php
// Plugin provides blog feature
class BlogServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton(BlogInterface::class, BlogService::class);
    }
    
    public function boot(): void {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
```

**Layer 2 (Core):**
```php
// Core defines interface ONLY
interface BlogInterface {
    public function getPosts(int $limit): Collection;
    public function createPost(array $data): Post;
}
```

**Layer 1 (Foundation):**
- RoadRunner executes the code
- PHP handles the logic
- No application code

---

## 📊 Pattern Comparison

| Pattern | Infinri | WordPress | Magento | Laravel | Symfony |
|---------|---------|-----------|---------|---------|---------|
| **Layers** | ✅ | ⚠️ Weak | ✅ | ✅ | ✅ |
| **Microkernel** | ✅ | ✅ | ✅ | ⚠️ Weak | ⚠️ Weak |
| **Modularity** | ★★★ | ★★ | ★★★ | ★★ | ★★ |
| **Simplicity** | ★★ | ★★★ | ★ | ★★★ | ★★ |
| **Performance** | ★★★ | ★★ | ★ | ★★★ | ★★ |

**Infinri's Advantage:** Combines best of both patterns

---

## 🎯 Pattern Trade-offs Summary

| Aspect | Layers | Microkernel | Combined |
|--------|--------|-------------|----------|
| **Modifiability** | ★★★ | ★★★ | ★★★★ |
| **Performance** | ★★ | ★★ | ★★ |
| **Testability** | ★★★ | ★★ | ★★★ |
| **Complexity** | ★★ | ★★ | ★ |
| **Understandability** | ★★★ | ★★ | ★★ |

**Verdict:** Combined approach is **best for our goals** (modular platform)

---

## 🚀 Implementation Guidelines

### DO:
✅ Keep core interfaces stable  
✅ Use dependency injection everywhere  
✅ Make plugins optional  
✅ Document layer boundaries  
✅ Enforce dependency rules  

### DON'T:
❌ Let core import plugins  
❌ Skip layers unnecessarily  
❌ Create circular dependencies  
❌ Mix business logic in core  
❌ Tight-couple plugins  

---

## 📚 References

- **SEI Software Architecture in Practice** (3rd Ed.) - Chapter 13 (Patterns)
- **Pattern-Oriented Software Architecture** (POSA) - Volume 1
- **Design Patterns** (Gang of Four) - Structural patterns
- **Clean Architecture** (Uncle Bob) - Dependency Rule

---

**Version:** 1.0  
**Last Updated:** November 24, 2025  
**Next Review:** After Phase 1 completion
