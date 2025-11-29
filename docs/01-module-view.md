# Module View - Static Architecture

**Type:** Decomposition View  
**Purpose:** Show system structure, responsibilities, and module relationships  
**Audience:** Developers, architects, maintainers

---

## 📐 Overview

Infinri uses a **Layers + Microkernel** architecture where:
- **Core** provides interfaces and container
- **Modules** implement core contracts
- **Application** coordinates modules to serve requests

### Dependency Rule
```
Application Layer
       ↓
  Module Layer (implements)
       ↓
  Core Layer (defines contracts)
       ↓
Foundation Layer (PHP, RoadRunner)
```

**Critical:** Dependencies flow DOWN only. Core never imports modules.

---

## 🏗️ Layer 0: Foundation

### PHP Runtime
- **Version:** 8.4+
- **Extensions:** PDO, mbstring, openssl, json, opcache
- **Server:** RoadRunner (worker-based)

### RoadRunner Workers
- **HTTP Workers:** Handle requests (persistent)
- **Queue Workers:** Background jobs
- **Metrics:** Prometheus exporter

---

## 🔧 Layer 1: Core (Interfaces & Container)

### 1.1 Container Subsystem

**Purpose:** Dependency injection and service resolution

**Modules:**
```
app/base/core/Container/
├── Container.php              # IoC container
├── ServiceProvider.php        # Base provider
├── BindingResolutionException.php
└── Contracts/
    └── ContainerInterface.php
```

**Responsibilities:**
- Resolve dependencies automatically
- Manage singletons
- Register service providers
- Handle constructor injection

**External Dependencies:** None (zero-dependency container)

**Provided Interfaces:**
- `ContainerInterface` - Bind, make, singleton
- `ServiceProviderInterface` - Register, boot

---

### 1.2 HTTP Subsystem

**Purpose:** Request/Response abstractions

**Modules:**
```
app/base/core/Http/
├── Request.php                # PSR-7-like request
├── Response.php               # Response builder
├── JsonResponse.php           # JSON responses
├── RedirectResponse.php       # Redirects
├── Kernel.php                 # HTTP kernel
└── ParameterBag.php           # Input collection
```

**Responsibilities:**
- Parse HTTP requests
- Build HTTP responses
- Handle middleware pipeline
- Manage sessions/cookies

**External Dependencies:** None

**Provided Interfaces:**
- `RequestInterface` - input(), file(), validate()
- `ResponseInterface` - json(), redirect(), view()
- `KernelInterface` - handle(), terminate()

---

### 1.3 Router Subsystem

**Purpose:** Route matching and dispatching

**Modules:**
```
app/base/core/Router/
├── Router.php                 # Route registration
├── Route.php                  # Single route
├── RouteCollection.php        # Route storage
└── UrlGenerator.php           # Named routes
```

**Responsibilities:**
- Match URLs to handlers
- Extract route parameters
- Generate URLs from names
- Apply route middleware

**External Dependencies:** None

**Provided Interfaces:**
- `RouterInterface` - get(), post(), match()
- `RouteInterface` - where(), middleware(), name()

---

### 1.4 Database Subsystem

**Purpose:** Database abstraction and query building

**Modules:**
```
app/base/core/Database/
├── Connection.php             # PDO wrapper
├── QueryBuilder.php           # Fluent queries
├── Model.php                  # Active Record base
└── SchemaBuilder.php          # DDL operations
```

**Responsibilities:**
- Execute queries safely
- Build SQL fluently
- Manage transactions
- Handle connections

**External Dependencies:** PDO

**Provided Interfaces:**
- `ConnectionInterface` - select(), insert(), update()
- `QueryBuilderInterface` - where(), join(), orderBy()
- `ModelInterface` - find(), save(), delete()

---

### 1.5 Schema Subsystem

**Purpose:** Database schema management

**Modules:**
```
app/base/core/Schema/
├── SchemaLoader.php           # Load schema files
├── SchemaComparator.php       # Diff schema vs DB
├── SchemaBuilder.php          # Generate DDL
├── PatchExecutor.php          # Run patches
└── Patch/
    ├── DataPatchInterface.php
    └── SchemaPatchInterface.php
```

**Responsibilities:**
- Load PHP array schemas
- Compare schema to database
- Generate SQL migrations
- Execute data patches

**External Dependencies:** Database subsystem

**Provided Interfaces:**
- `SchemaInterface` - install(), upgrade(), dump()
- `PatchInterface` - apply(), getDependencies()

---

### 1.6 Events Subsystem

**Purpose:** Publish-subscribe event system

**Modules:**
```
app/base/core/Events/
├── EventDispatcher.php        # Dispatch events
├── Event.php                  # Base event
└── Listener.php               # Base listener
```

**Responsibilities:**
- Dispatch events
- Register listeners
- Handle event priority
- Queue async events

**External Dependencies:** None

**Provided Interfaces:**
- `EventDispatcherInterface` - dispatch(), listen()
- `EventInterface` - stop propagation, metadata

---

## 🔌 Layer 2: Service Modules (Plugins)

These implement core contracts and provide business logic.

### 2.1 View Module

**Purpose:** Template rendering and asset management

**Location:** `app/base/providers/ViewServiceProvider.php`

**Implements:**
- `ViewInterface` - render(), share(), composer()

**Responsibilities:**
- Render PHP templates
- Manage layouts
- Handle view composers
- Load module assets

**Dependencies:** Container

**Files:**
```
app/base/view/
├── base/                      # Shared assets
├── frontend/                  # Frontend assets
└── admin/                     # Admin assets
```

---

### 2.2 Cache Module

**Purpose:** Data caching layer

**Location:** `app/base/providers/CacheServiceProvider.php`

**Implements:**
- `CacheInterface` - get(), set(), forget()

**Responsibilities:**
- Store cached data
- Handle TTL
- Support multiple drivers
- Tag-based invalidation

**Dependencies:** None

**Drivers:**
- File (default)
- Redis (optional)
- APCu (optional)

---

### 2.3 Session Module

**Purpose:** Session management

**Location:** `app/base/providers/SessionServiceProvider.php`

**Implements:**
- `SessionInterface` - get(), put(), flash()

**Responsibilities:**
- Store session data
- Generate CSRF tokens
- Handle flash messages
- Manage cookies

**Dependencies:** None

---

### 2.4 Mail Module

**Purpose:** Email sending

**Location:** `app/base/providers/MailServiceProvider.php`

**Implements:**
- `MailInterface` - send(), queue()

**Responsibilities:**
- Send emails
- Queue emails
- Support templates
- Track delivery

**Dependencies:** Brevo API, Queue (optional)

**Drivers:**
- Brevo (default)
- SMTP (optional)

---

### 2.5 Validation Module

**Purpose:** Input validation

**Location:** `app/base/providers/ValidationServiceProvider.php`

**Implements:**
- `ValidationInterface` - validate(), rules()

**Responsibilities:**
- Validate input
- Custom rules
- Error messages
- Form validation

**Dependencies:** None

---

### 2.6 Auth Module

**Purpose:** Authentication and authorization

**Location:** `app/base/providers/AuthServiceProvider.php`

**Implements:**
- `AuthInterface` - attempt(), check(), user()
- `GateInterface` - allows(), denies(), define()

**Responsibilities:**
- User authentication
- Password hashing
- Session guards
- Permission gates

**Dependencies:** Database, Session

---

## 🔄 Module Lifecycle

### Discovery Phase

```
Application Boot
    │
    ↓
ModuleRegistry::scan()
    │
    ├─→ Scan app/Modules/ directory
    ├─→ Load module.php config files
    ├─→ Create ModuleDefinition instances
    └─→ Cache module metadata (var/state/modules.php)
```

### Validation Phase

```
ModuleRegistry::resolveDependencies()
    │
    ├─→ Build dependency graph
    ├─→ Detect circular dependencies (throws exception)
    ├─→ Topological sort (dependency order)
    └─→ Return ordered module list
```

### Registration Phase

```
For each module (in dependency order):
    │
    ↓
ModuleInterface::register($container)
    │
    ├─→ Bind services to container
    ├─→ Register service providers
    ├─→ Register interfaces → implementations
    └─→ No dependencies resolved yet
```

### Boot Phase

```
For each module (in dependency order):
    │
    ↓
ModuleInterface::boot($container)
    │
    ├─→ Dependencies now available
    ├─→ Register event subscribers
    ├─→ Register routes
    ├─→ Configure services
    └─→ Publish assets (if needed)
```

### Module Integration Points

| Hook | Purpose | Example |
|------|---------|---------|
| `register()` | Bind services | `$container->bind(MailInterface::class, BrevoMail::class)` |
| `boot()` | Configure services | `$router->group('/api', ...)` |
| `getProviders()` | Service providers | `[ContactServiceProvider::class]` |
| `getCommands()` | CLI commands | `[ContactExportCommand::class]` |
| `getDependencies()` | Required modules | `['mail', 'validation']` |

### Creating a Module

```php
// Option 1: Extend AbstractModule (recommended)
class ContactModule extends AbstractModule
{
    protected string $name = 'contact';
    protected string $version = '1.0.0';
    protected array $dependencies = ['mail', 'validation'];
    protected array $providers = [ContactServiceProvider::class];
    protected array $commands = [ContactExportCommand::class];
    
    public function boot(ContainerInterface $container): void
    {
        // Register routes
        $router = $container->make(RouterInterface::class);
        $router->post('/contact', ContactController::class);
        
        // Register event subscribers
        $dispatcher = $container->make(EventDispatcherInterface::class);
        $dispatcher->addSubscriber(new ContactEventSubscriber());
    }
}

// Option 2: Config-based (legacy, backwards compatible)
// app/modules/contact/module.php
return [
    'name' => 'contact',
    'version' => '1.0.0',
    'depends' => ['mail', 'validation'],
    'providers' => [ContactServiceProvider::class],
];
```

---

## 📦 Layer 3: Feature Modules

User-facing modules that compose services.

### 3.1 Contact Module

**Location:** `app/Modules/Contact/`

**Purpose:** Handle contact form submissions

**Structure:**
```
Contact/
├── ContactServiceProvider.php
├── schema.php
├── Controllers/
│   └── ContactController.php
├── Models/
│   └── ContactSubmission.php
├── View/
│   ├── frontend/
│   └── admin/
└── routes.php
```

**Dependencies:**
- Mail (send emails)
- Validation (validate input)
- Database (store submissions)
- RateLimiter (prevent spam)

---

### 3.2 SEO Module

**Location:** `app/Modules/Seo/`

**Purpose:** SEO and meta tags

**Structure:**
```
Seo/
├── SeoServiceProvider.php
├── Services/
│   └── SeoService.php
├── Middleware/
│   └── InjectMetaTags.php
└── View/
    └── components/
        └── meta-tags.php
```

**Dependencies:**
- View (render meta tags)
- Events (listen to PageRendered)

---

### 3.3 Blog Module

**Location:** `app/Modules/Blog/`

**Purpose:** Blog functionality

**Structure:**
```
Blog/
├── BlogServiceProvider.php
├── schema.php
├── Models/
│   ├── Post.php
│   └── Category.php
├── Controllers/
│   ├── PostController.php
│   └── CategoryController.php
├── View/
│   ├── frontend/
│   └── admin/
└── routes.php
```

**Dependencies:**
- Database (store posts)
- Cache (cache queries)
- Auth (protect admin)
- SEO (generate meta)

---

## 🔗 Module Dependencies Graph

```
┌─────────────────────────────────────┐
│         Application Layer            │
│    (Controllers, Commands, Views)    │
└──────────┬──────────────────────────┘
           │
┌──────────▼──────────────────────────┐
│         Module Layer                 │
│  (Contact, Blog, SEO, Auth, etc.)    │
└──────────┬──────────────────────────┘
           │
┌──────────▼──────────────────────────┐
│         Core Services                │
│ (Cache, Mail, Session, Validation)   │
└──────────┬──────────────────────────┘
           │
┌──────────▼──────────────────────────┐
│         Core Infrastructure          │
│(Container, Router, HTTP, Database)   │
└──────────┬──────────────────────────┘
           │
┌──────────▼──────────────────────────┐
│         Foundation                   │
│      (PHP 8.4, RoadRunner)           │
└─────────────────────────────────────┘
```

---

## 📊 Module Metrics

| Layer | Modules | LOC (Target) | Interfaces |
|-------|---------|--------------|------------|
| Foundation | 2 | 0 | 0 |
| Core | 6 | 8,000 | 15 |
| Services | 7 | 12,000 | 7 |
| Features | 8-15 | 20,000 | 0 |
| **Total** | **23-30** | **~40,000** | **22** |

---

## 🎯 Module Responsibilities Matrix

| Module | Modifiability | Performance | Security | Testability |
|--------|---------------|-------------|----------|-------------|
| Container | ★★★ | ★★ | ★★★ | ★★★ |
| Router | ★★★ | ★★★ | ★★ | ★★★ |
| Database | ★★ | ★★★ | ★★★ | ★★ |
| Cache | ★★ | ★★★ | ★ | ★★★ |
| Auth | ★★ | ★★ | ★★★ | ★★ |
| Contact | ★★★ | ★★ | ★★ | ★★★ |

**Legend:** ★★★ Primary | ★★ Secondary | ★ Minor

---

## 🔒 Module Isolation Rules

1. **Core cannot import modules** - Only define interfaces
2. **Modules cannot import each other** - Use events or container
3. **Services provide facades** - Backward compatibility layer
4. **All dependencies injected** - No `new` keyword in constructors
5. **Modules are optional** - System works without any feature module

---

## 📜 Platform Contracts

### Module Contracts

**Location:** `app/Core/Contracts/Module/`

```php
// Core module contract - all platform modules implement this
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

// For components with boot phase
interface BootableInterface {
    public function boot(): void;
}

// For modules with lifecycle hooks
interface InstallableInterface {
    public function install(): void;
    public function upgrade(string $from, string $to): void;
    public function uninstall(): void;
}
```

**Base Implementation:** `App\Core\Module\AbstractModule`

### Event Subscriber Contract

**Location:** `app/Core/Contracts/Events/`

```php
// Formal connector definition per SEI C&C structure
interface EventSubscriberInterface {
    public static function getSubscribedEvents(): array;
}

// Usage:
class UserEventSubscriber implements EventSubscriberInterface {
    public static function getSubscribedEvents(): array {
        return [
            UserCreated::class => 'onUserCreated',
            UserDeleted::class => ['onUserDeleted', 10], // with priority
        ];
    }
}
```

### Template Resolution Contract

**Location:** `app/Core/Contracts/View/`

```php
// Theme fallback resolution
interface TemplateResolverInterface {
    public function resolve(string $template, string $module, string $area): ?string;
    public function exists(string $template, string $module, string $area): bool;
    public function setTheme(?string $theme): void;
    public function getTheme(): ?string;
}
```

**Resolution Order:**
```
1. Theme/view/{area}/templates/{module}/{template}  ← Theme override
2. Module/view/{area}/templates/{template}          ← Module default
3. Core/View/view/{area}/templates/{template}       ← Core fallback
```

---

## 📝 Change Impact Analysis

**Adding a new module:**
- ✅ No core changes needed
- ✅ Register in `config/providers.php`
- ✅ Run `schema:upgrade` if has database
- ⏱️ Estimated time: <2 days

**Modifying core interface:**
- ⚠️ All implementing modules must update
- ⚠️ Version bumping required
- ⚠️ Deprecation period needed
- ⏱️ Estimated time: 1-2 weeks

**Removing a module:**
- ✅ Disable in config
- ✅ Remove from filesystem
- ✅ Optional: keep database tables
- ⏱️ Estimated time: <1 hour

---

**Version:** 1.1  
**Last Updated:** November 28, 2025  
**Next Review:** After Phase 1 completion
