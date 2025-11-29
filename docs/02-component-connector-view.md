# Component & Connector View - Runtime Behavior

**Type:** Runtime View  
**Purpose:** Show how components interact during execution  
**Audience:** Developers, performance engineers, DevOps

---

## 📐 Overview

This view shows **runtime behavior** - how HTTP requests flow through the system, how modules communicate, and how data moves between components.

---

## 🌊 HTTP Request Flow

### Standard Request (Frontend Page)

```
Client Browser
    │
    ↓ [1] HTTP GET /blog/post/42
┌───────────────────┐
│   Caddy Server    │  ← TLS termination, static files
└─────────┬─────────┘
          │
          ↓ [2] Proxy to :8080
┌───────────────────┐
│  RoadRunner HTTP  │  ← Worker pool (persistent)
│    Worker #3       │
└─────────┬─────────┘
          │
          ↓ [3] pub/index.php boot
┌───────────────────┐
│   HTTP Kernel     │  ← Load app, fire request
└─────────┬─────────┘
          │
          ↓ [4] Middleware stack
┌───────────────────┐
│  Middleware 1-N   │  ← CSRF, Auth, Rate limit
└─────────┬─────────┘
          │
          ↓ [5] Route matching
┌───────────────────┐
│      Router       │  ← /blog/post/{id}
└─────────┬─────────┘
          │
          ↓ [6] Resolve from container
┌───────────────────┐
│   PostController  │  ← Container injects dependencies
└─────────┬─────────┘
          │
          ↓ [7] Call show($id)
┌───────────────────┐
│   PostService     │  ← Business logic
└─────────┬─────────┘
          │
          ↓ [8] Query database
┌───────────────────┐
│   Post Model      │  ← Active Record query
└─────────┬─────────┘
          │
          ↓ [9] SELECT via PDO
┌───────────────────┐
│   PostgreSQL 16   │  ← Database query
└─────────┬─────────┘
          │
          ↓ [10] Return data
┌───────────────────┐
│   PostController  │  ← Hydrated model
└─────────┬─────────┘
          │
          ↓ [11] Render view
┌───────────────────┐
│   View Engine     │  ← Compile template
└─────────┬─────────┘
          │
          ↓ [12] Return Response
┌───────────────────┐
│   HTTP Kernel     │  ← Response object
└─────────┬─────────┘
          │
          ↓ [13] Middleware (reverse)
┌───────────────────┐
│  Middleware N-1   │  ← Add headers, log
└─────────┬─────────┘
          │
          ↓ [14] Send to RoadRunner
┌───────────────────┐
│  RoadRunner HTTP  │  ← Worker ready for next request
└─────────┬─────────┘
          │
          ↓ [15] Proxy response
┌───────────────────┐
│   Caddy Server    │  ← Add cache headers
└─────────┬─────────┘
          │
          ↓ [16] HTTP 200 + HTML
Client Browser
```

**Timing Breakdown (Target):**
- [1-2] Caddy proxy: ~1ms
- [3] App boot (cached): ~5ms
- [4] Middleware stack: ~2ms
- [5] Route matching: ~0.5ms
- [6] Container resolution: ~1ms
- [7-10] Controller + Database: ~10-20ms
- [11] View rendering: ~5-10ms
- [12-16] Response + cleanup: ~2ms

**Total: ~30-50ms** (excluding database query time)

---

## 🔄 API Request Flow (JSON)

```
Client (API)
    │
    ↓ POST /api/contact
┌───────────────────┐
│      Caddy        │  ← TLS, rate limit
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│   RoadRunner      │
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│  CSRF Middleware  │  ← Validate token
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│ReCaptcha Middleware│ ← Verify score
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│ Rate Limiter      │  ← Check IP limit
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│ContactController  │  ← Validate input
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│ Validation Service│  ← Rules check
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│  Contact Model    │  ← Save to DB
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│   Mail Service    │  ← Queue email
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│  Event Dispatcher │  ← Fire ContactSubmitted
└─────────┬─────────┘
          │
          ↓
┌───────────────────┐
│  JSON Response    │  ← {"success": true}
└─────────┬─────────┘
          │
          ↓
Client (API)
```

**Timing: ~20-40ms** (fast, no view rendering)

---

## 🔌 Module Communication Patterns

### Pattern 1: Direct Injection (Preferred)

```php
class PostController {
    public function __construct(
        private CacheInterface $cache,
        private SeoInterface $seo
    ) {}
    
    public function show(int $id) {
        // Direct method call
        $post = $this->cache->remember("post.$id", fn() => 
            Post::find($id)
        );
        
        // Direct method call
        $this->seo->setMetaTags($post);
        
        return view('blog.post', compact('post'));
    }
}
```

**Connector:** Container (dependency injection)  
**Data Flow:** Constructor → Private property → Method call  
**Performance:** ★★★ Fast (direct calls)  
**Coupling:** Low (depends on interface)

---

### Pattern 2: Event-Based (Decoupled)

```php
// Module A: Dispatch event
class ContactController {
    public function store(Request $request) {
        $submission = ContactSubmission::create($request->validated());
        
        // Fire event
        event(new ContactSubmitted($submission));
        
        return response()->json(['success' => true]);
    }
}

// Module B: Listen to event (legacy approach)
class NotifyAdminListener {
    public function handle(ContactSubmitted $event) {
        Mail::send(new ContactNotification($event->submission));
    }
}
```

**Connector:** Event Dispatcher  
**Data Flow:** Fire → Dispatcher → Listeners (async possible)  
**Performance:** ★★ Slower (event overhead)  
**Coupling:** None (modules don't know each other)

---

### Pattern 2b: Event Subscriber (Formal Contract)

```php
use App\Core\Contracts\Events\EventSubscriberInterface;

// Module B: Subscriber implementing formal contract
class ContactEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ContactSubmitted::class => 'onContactSubmitted',
            ContactUpdated::class => ['onContactUpdated', 10], // priority
            ContactDeleted::class => [
                ['logDeletion', 100],   // high priority
                ['notifyAdmin', 0],     // normal priority
            ],
        ];
    }
    
    public function onContactSubmitted(ContactSubmitted $event): void
    {
        Mail::send(new ContactNotification($event->submission));
    }
    
    public function onContactUpdated(ContactUpdated $event): void { }
    public function logDeletion(ContactDeleted $event): void { }
    public function notifyAdmin(ContactDeleted $event): void { }
}

// Registration in module's boot():
$dispatcher->addSubscriber(new ContactEventSubscriber());

// Or via service provider:
$dispatcher->subscribe(ContactEventSubscriber::class);
```

**Event Subscriber Contract:**
```php
interface EventSubscriberInterface {
    public static function getSubscribedEvents(): array;
}
```

**Connector:** EventDispatcher with addSubscriber()/removeSubscriber()  
**Benefits:**
- Formal connector definition (SEI C&C compliance)
- Multiple events in one class
- Priority-based execution
- Removable at runtime

**Lifecycle:**
1. Module boots → registers subscriber
2. Event dispatched → dispatcher finds subscribers
3. Listeners called in priority order (high → low)
4. Stoppable events can halt propagation

---

### Pattern 3: Service Locator (Avoid)

```php
// ❌ NOT RECOMMENDED
class SomeController {
    public function index() {
        // Service locator anti-pattern
        $cache = app(CacheInterface::class);
        $mail = app(MailInterface::class);
        
        // ... use services
    }
}
```

**Why avoid:**
- Hides dependencies
- Hard to test
- Runtime errors possible

**When acceptable:**
- Legacy code migration
- Temporary backward compatibility

---

## 🗄️ Data Flow Patterns

### Pattern 1: Request → Database → Cache → Response

```
HTTP Request
    │
    ↓
Controller
    │
    ├─→ Check Cache ──→ Hit? ──→ Return cached
    │                    │
    │                    ↓ Miss
    └─→ Database Query
             │
             ↓
        Store in Cache
             │
             ↓
        Return Response
```

**Used for:** Blog posts, product listings  
**Cache TTL:** 1 hour  
**Cache key:** `post.{id}` or `posts.page.{page}`

---

### Pattern 2: Request → Validation → Database → Event → Response

```
HTTP Request
    │
    ↓
Validate Input
    │
    ↓ Valid
Store in Database
    │
    ↓
Fire Event (async)
    │
    ├─→ Send Email
    ├─→ Log Activity
    └─→ Update Cache
    │
    ↓
Return Response
```

**Used for:** Contact forms, user registration  
**Validation:** Rules + reCAPTCHA  
**Events:** ContactSubmitted, UserRegistered

---

## 🏃 Process View (RoadRunner Workers)

### Worker Pool Management

```
┌─────────────────────────────────────────┐
│         RoadRunner Master Process        │
└──────────┬──────────────────────────────┘
           │
     ┌─────┴─────┬──────┬──────┬──────┐
     │           │      │      │      │
     ↓           ↓      ↓      ↓      ↓
┌─────────┐ ┌─────────┐ ... ┌─────────┐
│Worker #1│ │Worker #2│     │Worker #N│
│ Ready   │ │ Busy    │     │ Ready   │
└─────────┘ └─────────┘     └─────────┘
     ↑           │
     │           ↓
     │    ┌──────────────┐
     │    │HTTP Request  │
     │    │Process PHP   │
     │    │Return Response│
     │    └──────────────┘
     │           │
     └───────────┘ Worker reset, ready for next
```

**Worker Configuration:**
- **Pool size:** 4-8 workers (CPU cores × 1-2)
- **Max jobs:** 1000 requests/worker before reset
- **Lifetime:** 3600s max per worker
- **Memory limit:** 128MB per worker

**Benefits:**
- ✅ No cold starts
- ✅ Persistent connections (DB, Cache)
- ✅ Shared opcode cache
- ✅ Fast request handling (~1ms overhead)

---

## 🔐 Security Flow (Request Validation)

```
HTTP Request
    │
    ↓
┌─────────────────┐
│ Rate Limiter    │  ← Check IP: 60 req/min
└────────┬────────┘
         │ Allowed
         ↓
┌─────────────────┐
│ CSRF Check      │  ← Validate token (POST only)
└────────┬────────┘
         │ Valid
         ↓
┌─────────────────┐
│ Auth Check      │  ← Verify session (if required)
└────────┬────────┘
         │ Authenticated
         ↓
┌─────────────────┐
│ Authorization   │  ← Check permissions
└────────┬────────┘
         │ Authorized
         ↓
┌─────────────────┐
│ Input Validation│  ← Sanitize & validate
└────────┬────────┘
         │ Valid
         ↓
   Controller Logic
```

**Timing:**
- Rate Limiter: <1ms (file-based check)
- CSRF: <1ms (session comparison)
- Auth: <2ms (session lookup)
- Authorization: <1ms (in-memory check)
- Validation: 2-5ms (depends on rules)

**Total Security Overhead: ~5-10ms**

---

## 📊 Database Connection Flow

### Connection Pooling (RoadRunner)

```
Worker #1              Worker #2              Worker #3
    │                      │                      │
    ↓                      ↓                      ↓
┌─────────┐          ┌─────────┐          ┌─────────┐
│ PDO Conn│          │ PDO Conn│          │ PDO Conn│
│ (reused)│          │ (reused)│          │ (reused)│
└────┬────┘          └────┬────┘          └────┬────┘
     │                    │                    │
     └────────────────────┴────────────────────┘
                          │
                          ↓
                ┌─────────────────┐
                │ PostgreSQL 16   │
                │  (max 50 conn)  │
                └─────────────────┘
```

**Benefits:**
- ✅ Connection reuse (no reconnect overhead)
- ✅ Persistent prepared statements
- ✅ Lower database load
- ✅ Faster queries (~5-10ms saved per request)

**Configuration:**
- **Max connections:** 8 workers × 1 conn = 8 connections
- **Keepalive:** 28800s (8 hours)
- **Charset:** utf8mb4

---

## 🎯 Component Interaction Matrix

| Component | Interacts With | Via | Frequency |
|-----------|----------------|-----|-----------|
| Router | Controller | Container | Every request |
| Controller | Model | Direct call | Most requests |
| Controller | View | Direct call | Frontend requests |
| Model | Database | PDO | Data requests |
| Service | Cache | Interface | Read-heavy ops |
| Service | Event | Dispatcher | State changes |
| Middleware | Request | Pipeline | Every request |
| Worker | Master | IPC | Health checks (1/min) |

---

## 🔄 Asynchronous Patterns

### Queue Processing (Future)

```
HTTP Request
    │
    ↓
Queue Job ──→ Redis Queue ──→ Queue Worker
    │                              │
    │                              ↓
    │                         Process Job
    │                              │
    │                              ↓
    │                         Fire Event
    │                              │
    └──────────────────────────────┘
         (Job result stored)
```

**Use cases:**
- Email sending (non-blocking)
- Image processing
- Report generation
- Third-party API calls

**Benefits:**
- ✅ Fast response times
- ✅ Retry failed jobs
- ✅ Horizontal scaling
- ✅ Priority queues

---

## 📈 Performance Characteristics

### Request Processing Timeline

```
0ms    5ms    10ms   20ms   40ms   50ms
├──────┼──────┼──────┼──────┼──────┤
│ Boot │ MW   │Route │DB    │View  │Response
```

**Breakdown:**
1. **Boot (0-5ms):** Load app, container
2. **Middleware (5-10ms):** Security checks
3. **Route (10-12ms):** Match + resolve
4. **Database (12-32ms):** Query + hydrate
5. **View (32-45ms):** Render template
6. **Response (45-50ms):** Send to client

**Optimization targets:**
- Cache DB queries → Save 15-20ms
- Precompile views → Save 5-10ms
- Optimize middleware → Save 2-5ms

---

## 🎯 Connector Types Summary

| Connector | Purpose | Performance | Coupling |
|-----------|---------|-------------|----------|
| **Container** | DI resolution | ★★★ Fast | Low |
| **Events** | Module communication | ★★ Medium | None |
| **HTTP Pipeline** | Middleware chain | ★★★ Fast | Medium |
| **PDO** | Database queries | ★★ Medium | Low |
| **RoadRunner IPC** | Worker management | ★★★ Fast | N/A |

---

**Version:** 1.1  
**Last Updated:** November 28, 2025  
**Next Review:** After Phase 2 completion
