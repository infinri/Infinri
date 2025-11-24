# Module Architecture Guide
## How Modules Work in Infinri Platform

**Version:** 1.0  
**Date:** November 24, 2025

---

## 🎯 What is a Module?

A **module** is a self-contained feature package that follows these principles:

1. **Core defines contracts** (interfaces) → Modules implement them
2. **Dependency injection** → Container provides dependencies
3. **Service providers** → Register and boot module services
4. **Event-driven** → Communicate without tight coupling
5. **Enable/disable** → Toggle without code changes

---

## 🔄 Current vs Target Structure

### Current Module Structure (November 2025)

**Existing modules** (8 total):
```
app/modules/contact/
├── index.php              # ✅ Controller (renders view)
├── api.php                # ✅ POST endpoint handler
└── view/                  # ✅ View layer
    └── frontend/
        ├── css/
        │   └── contact.css
        ├── js/
        │   └── contact.js
        └── templates/
            └── contact.php
```

**What works:**
- ✅ Clean separation (controller, view, api)
- ✅ Asset auto-discovery
- ✅ Module-specific CSS/JS
- ✅ Production-ready

**What's missing:**
- ❌ No ServiceProvider (can't enable/disable)
- ❌ No schema (can't manage database)
- ❌ No Models (no data layer)
- ❌ No dependency injection

### Target Module Structure (After Migration)

```
app/Modules/Contact/
├── ContactServiceProvider.php  # ➕ NEW: Register & boot
├── module.json                 # ➕ NEW: Metadata
├── schema.php                  # ➕ NEW: Database schema
├── config/
│   └── contact.php             # ➕ NEW: Module config
├── Controllers/                # ⚠️ REFACTOR: From index.php
│   └── ContactController.php
├── Models/                     # ➕ NEW: Data models
│   └── ContactSubmission.php
├── View/                       # ✅ KEEP: Expand structure
│   ├── frontend/
│   │   ├── css/
│   │   │   └── contact.css
│   │   ├── js/
│   │   │   └── contact.js
│   │   └── templates/
│   │       └── contact.php
│   └── admin/                  # ➕ NEW: Admin interface
│       ├── css/
│       ├── js/
│       └── templates/
│           └── submissions.php
├── Setup/                      # ➕ NEW: Database patches
│   └── Patch/
│       └── Data/
│           └── SeedDefaults.php
└── routes.php                  # ⚠️ REFACTOR: From index.php
```

### Migration Path (Per Module)

**Phase 1: Add ServiceProvider (No Breaking Changes)**
```php
// Create ContactServiceProvider.php
class ContactServiceProvider extends ServiceProvider {
    public function register(): void {
        // Will wrap existing code initially
    }
    
    public function boot(): void {
        // Keep current index.php working
        $this->loadRoutesFrom(__DIR__ . '/index.php');
    }
}
```

**Phase 2: Add Database Support (Optional)**
- Create `schema.php` if module needs database
- Create `Models/` directory
- Create `Setup/Patch/Data/` for seeding

**Phase 3: Refactor Controller (When Ready)**
- Move logic from `index.php` to `Controllers/`
- Update `routes.php` to use controller
- Keep `index.php` as fallback during transition

**Phase 4: Add Admin Interface (If Needed)**
- Add `View/admin/` directory
- Create admin templates
- Register admin routes

### Backward Compatibility Strategy

**Old Code Keeps Working:**
```php
// OLD: Direct helper call (still works)
use App\Helpers\Cache;
Cache::get('user_' . $id);

// NEW: Container-based (preferred)
$cache = app(CacheInterface::class);
$cache->get('user_' . $id);

// HELPER BECOMES FACADE (automatic BC)
namespace App\Helpers;
class Cache {
    public static function get(string $key): mixed {
        // Calls container internally
        return app(CacheInterface::class)->get($key);
    }
}
```

**Module Loading:**
```php
// OLD: Router includes index.php directly (still works)
$router->get('/contact', 'contact');

// NEW: Router can use controller (when migrated)
$router->get('/contact', [ContactController::class, 'show']);

// BOTH work during transition
```

---

## 📦 Module Structure

### Minimum Required

```
app/Modules/MyModule/
├── MyModuleServiceProvider.php    # REQUIRED
└── module.json                    # REQUIRED
```

### Full Structure

```
app/Modules/Blog/
├── BlogServiceProvider.php        # Service provider
├── module.json                    # Metadata
├── config/
│   └── blog.php                   # Module config
├── Services/
│   └── BlogService.php           # Business logic
├── Controllers/
│   ├── PostController.php        # HTTP layer
│   └── CommentController.php
├── Models/
│   ├── Post.php                  # Data models
│   └── Category.php
├── View/                         # View layer (CSS + JS + Templates)
│   ├── frontend/
│   │   ├── css/
│   │   │   ├── blog.css          # Blog list styles
│   │   │   └── post.css          # Single post styles
│   │   ├── js/
│   │   │   ├── blog.js           # Filter, load more, infinite scroll
│   │   │   ├── post.js           # Share, reading progress
│   │   │   └── comments.js       # Comment form, replies
│   │   └── templates/
│   │       ├── post/
│   │       │   ├── list.php      # Blog listing page
│   │       │   └── view.php      # Single post page
│   │       └── components/
│   │           ├── post-card.php
│   │           └── comment-form.php
│   └── admin/
│       ├── css/
│       │   └── blog-admin.css
│       ├── js/
│       │   ├── post-editor.js    # Rich editor
│       │   └── media-library.js  # Media picker
│       └── templates/
│           └── post/
│               ├── edit.php
│               └── list.php
├── Setup/
│   └── Patch/
│       ├── Data/
│       │   └── SeedCategories.php
│       └── Schema/
│           └── AddFullTextIndex.php
├── Commands/
│   └── GenerateSitemapCommand.php
├── Middleware/
│   └── ValidatePostStatus.php
└── routes.php                    # Module routes
```

---

## 📝 module.json

```json
{
  "name": "blog",
  "version": "1.0.0",
  "description": "Blog and post management",
  "author": "Infinri",
  "requires": {
    "php": ">=8.4",
    "core": ">=1.0"
  },
  "provides": [
    "App\\Core\\Contracts\\Blog\\BlogInterface"
  ],
  "enabled": true
}
```

---

## 🔧 Service Provider

### Basic Structure

```php
<?php

namespace App\Modules\Seo;

use App\Core\Container\ServiceProvider;
use App\Core\Contracts\Seo\SeoInterface;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register bindings (FIRST)
     * NO external dependencies here
     */
    public function register(): void
    {
        // Bind interface to implementation
        $this->app->singleton(SeoInterface::class, function ($app) {
            return new SeoService(
                $app->make('config'),
                $app->make('view')
            );
        });
        
        // Create alias
        $this->app->alias(SeoInterface::class, 'seo');
        
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/seo.php', 'seo');
    }
    
    /**
     * Bootstrap services (AFTER all registered)
     * CAN use other services here
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        
        // Register views
        $this->loadViewsFrom(__DIR__ . '/View/frontend/templates', 'seo');
        
        // Register schema
        $this->loadSchemaFrom(__DIR__ . '/schema.php');
        
        // Register middleware
        $this->app['router']->middleware('seo', InjectMetaTags::class);
        
        // Register events
        $this->app['events']->listen(PageRendered::class, InjectSeoTags::class);
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([GenerateSitemapCommand::class]);
        }
    }
}
```

---

## 🎭 Module Types

### 1. Service Module (Logic Only)

**Purpose:** Provide business logic without HTTP layer  
**Examples:** Cache, Mail, Logger

```php
class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheInterface::class, function ($app) {
            return new CacheManager($app->make('config'));
        });
    }
}
```

**Structure:**
```
app/Modules/Cache/
├── CacheServiceProvider.php
├── module.json
├── CacheManager.php
└── Stores/
    ├── FileStore.php
    └── RedisStore.php
```

---

### 2. HTTP Module (Routes + Controllers)

**Purpose:** Handle HTTP requests  
**Examples:** Contact Form, Admin Panel, API

```php
class ContactServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadViewsFrom(__DIR__ . '/View/frontend/templates', 'contact');
    }
}
```

**routes.php:**
```php
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');
```

**Structure:**
```
app/Modules/Contact/
├── ContactServiceProvider.php
├── module.json
├── routes.php
├── Controllers/
│   └── ContactController.php
└── View/
    └── frontend/
        └── templates/
            └── form.php
```

---

### 3. Database Module (Models + Schema)

**Purpose:** Provide data models and database schema  
**Examples:** Pages, Users, Products

```php
class PagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register schema
        $this->loadSchemaFrom(__DIR__ . '/schema.php');
    }
    
    public function boot(): void
    {
        // Register observers
        Page::observe(PageObserver::class);
        
        // Register policies
        Gate::policy(Page::class, PagePolicy::class);
        
        // Register data patches
        $this->loadPatchesFrom(__DIR__ . '/Setup/Patch');
    }
}
```

**Structure:**
```
app/Modules/Pages/
├── PagesServiceProvider.php
├── module.json
├── schema.php              # Schema definition (PHP arrays)
├── Models/
│   ├── Page.php
│   └── PageVersion.php
├── Setup/
│   └── Patch/
│       ├── Data/
│       │   ├── SeedDefaultPages.php
│       │   └── MigrateOldPages.php
│       └── Schema/
│           └── AddFullTextIndex.php
├── Observers/
│   └── PageObserver.php
└── Policies/
    └── PagePolicy.php
```

**Schema Definition (schema.php):**
```php
<?php

return [
    'tables' => [
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
                'meta_title' => ['type' => 'string', 'length' => 255, 'nullable' => true],
                'meta_description' => ['type' => 'text', 'nullable' => true],
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
        
        'page_versions' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'page_id' => [
                    'type' => 'foreignId',
                    'references' => 'pages.id',
                    'onDelete' => 'cascade',
                ],
                'content' => ['type' => 'text'],
                'created_by' => ['type' => 'foreignId', 'references' => 'users.id'],
            ],
            'indexes' => [
                ['columns' => ['page_id']],
            ],
            'timestamps' => true,
        ],
    ],
];
```

**Data Patch (SeedDefaultPages.php):**
```php
<?php

namespace App\Modules\Pages\Setup\Patch\Data;

use App\Core\Setup\Patch\DataPatchInterface;
use App\Core\Database\Connection;

class SeedDefaultPages implements DataPatchInterface
{
    public function __construct(protected Connection $db) {}
    
    public function apply(): void
    {
        $pages = [
            [
                'slug' => 'home',
                'title' => 'Home',
                'content' => '<h1>Welcome</h1>',
                'status' => 'published',
            ],
            [
                'slug' => 'about',
                'title' => 'About Us',
                'content' => '<h1>About Us</h1>',
                'status' => 'published',
            ],
        ];
        
        foreach ($pages as $page) {
            $this->db->table('pages')->insert($page);
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

---

### 4. View-Heavy Module (Complete Frontend)

**Purpose:** Modules with sophisticated frontend (Header, Footer, Navigation, Blog, etc.)  
**Examples:** Header, Navigation, Footer, Blog, Products

**Structure:**

```
app/Modules/Blog/
├── BlogServiceProvider.php
├── module.json
├── View/                         # Complete view layer
│   ├── frontend/                 # Public-facing
│   │   ├── css/
│   │   │   ├── blog.css          # Module-specific styles (overrides only)
│   │   │   └── post.css
│   │   ├── js/
│   │   │   ├── blog.js           # Blog list interactions
│   │   │   ├── post.js           # Post interactions
│   │   │   └── comments.js       # Comment system
│   │   └── templates/
│   │       ├── post/
│   │       │   ├── list.php
│   │       │   └── view.php
│   │       └── components/
│   │           └── post-card.php
│   └── admin/                    # Admin area
│       ├── css/
│       │   └── blog-admin.css
│       ├── js/
│       │   ├── post-editor.js
│       │   └── media-library.js
│       └── templates/
│           └── post/
│               ├── edit.php
│               └── list.php
├── Controllers/
│   ├── PostController.php
│   └── CommentController.php
├── Models/
│   └── Post.php
└── routes.php
```

**Service Provider (Loading Views):**

```php
class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register view paths
        $this->loadViewsFrom(__DIR__ . '/View/frontend/templates', 'Blog');
        $this->loadViewsFrom(__DIR__ . '/View/admin/templates', 'BlogAdmin');
    }
    
    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        
        // Assets will be auto-discovered from View directory
    }
}
```

**Template Example (Inherits Base Layout):**

```php
<!-- app/Modules/Blog/View/frontend/templates/post/view.php -->

<?php 
// Extend base layout
$this->layout('frontend/layouts/one-column');
$this->setTitle($post->title);
?>

<?php $this->startBlock('head_css') ?>
<!-- Module-specific CSS -->
<link rel="stylesheet" href="<?= $this->moduleAsset('Blog', 'frontend/css/post.css') ?>">
<?php $this->endBlock() ?>

<?php $this->startBlock('footer_js') ?>
<!-- Module-specific JS -->
<script src="<?= $this->moduleAsset('Blog', 'frontend/js/post.js') ?>"></script>
<script src="<?= $this->moduleAsset('Blog', 'frontend/js/comments.js') ?>"></script>
<?php $this->endBlock() ?>

<?php $this->startBlock('main') ?>
<article class="blog-post" data-post-content>
    <!-- Reading progress bar -->
    <div class="reading-progress">
        <div class="reading-progress-bar" data-reading-progress></div>
    </div>
    
    <header class="post-header">
        <h1><?= e($post->title) ?></h1>
        <div class="post-meta">
            <span><?= $post->published_at->format('M d, Y') ?></span>
            <span data-reading-time></span>
        </div>
    </header>
    
    <div class="post-content">
        <?= $post->content ?>
    </div>
    
    <!-- Share buttons -->
    <div class="post-share">
        <button class="btn btn-sm" data-share="twitter">Share on Twitter</button>
        <button class="btn btn-sm" data-share="facebook">Share on Facebook</button>
        <button class="btn btn-sm" data-share="copy">Copy Link</button>
    </div>
    
    <!-- Comments -->
    <div class="post-comments" data-comments>
        <?= $this->render('Blog::components/comment-form') ?>
    </div>
</article>
<?php $this->endBlock() ?>
```

**Module JavaScript (post.js):**

```javascript
// app/Modules/Blog/View/frontend/js/post.js

class BlogPost {
    constructor() {
        this.setupShare();
        this.setupReadingProgress();
        this.calculateReadingTime();
    }
    
    setupShare() {
        App.$$('[data-share]').forEach(btn => {
            btn.addEventListener('click', () => {
                const platform = btn.dataset.share;
                const url = window.location.href;
                const title = document.title;
                
                switch (platform) {
                    case 'twitter':
                        window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`);
                        break;
                    case 'facebook':
                        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`);
                        break;
                    case 'copy':
                        navigator.clipboard.writeText(url);
                        App.EventBus.emit('notification:show', {
                            message: 'Link copied!',
                            type: 'success'
                        });
                        break;
                }
            });
        });
    }
    
    setupReadingProgress() {
        const bar = App.$('[data-reading-progress]');
        const content = App.$('[data-post-content]');
        
        if (!bar || !content) return;
        
        window.addEventListener('scroll', () => {
            const contentHeight = content.offsetHeight;
            const contentTop = content.offsetTop;
            const scrolled = window.pageYOffset - contentTop;
            const progress = (scrolled / contentHeight) * 100;
            
            bar.style.width = `${Math.min(Math.max(progress, 0), 100)}%`;
        });
    }
    
    calculateReadingTime() {
        const content = App.$('[data-post-content]');
        const timeEl = App.$('[data-reading-time]');
        
        if (!content || !timeEl) return;
        
        const text = content.textContent;
        const words = text.trim().split(/\s+/).length;
        const readingTime = Math.ceil(words / 200); // 200 WPM
        
        timeEl.textContent = `${readingTime} min read`;
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    if (App.$('[data-post-content]')) {
        new BlogPost();
    }
});
```

**Module CSS (Only Overrides):**

```css
/* app/Modules/Blog/View/frontend/css/post.css */

/* Module-specific styles - inherits all base component styles */

.blog-post {
    /* Uses base container, only adds specific styling */
    max-width: 800px;
    margin: 0 auto;
}

.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: var(--color-gray-200);
    z-index: 1000;
}

.reading-progress-bar {
    height: 100%;
    background-color: var(--color-primary);
    transition: width 150ms ease;
}

.post-share {
    /* Inherits .btn from base, only positions */
    display: flex;
    gap: var(--spacing-2);
    margin-top: var(--spacing-6);
}
```

**Key View Principles:**

1. **Inheritance First** - Use base components, only override what's different
2. **Minimal CSS** - Module CSS should be tiny (2-5 KB)
3. **Self-Contained JS** - Each module's JS is independent
4. **Layout Reuse** - Extend base layouts (one-column, two-column, etc.)
5. **Asset Discovery** - CSS/JS auto-loaded from `View/` directory

---

### 5. Event-Driven Module

**Purpose:** React to system events  
**Examples:** Analytics, Notifications, Logging

```php
class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Listen to events
        $this->app['events']->listen(
            PageViewed::class,
            TrackPageView::class
        );
        
        $this->app['events']->listen(
            UserRegistered::class,
            TrackNewUser::class
        );
    }
}
```

---

## 🔄 Module Lifecycle

### Phase 1: Discovery

Application scans `app/Modules/` for enabled modules:

```php
foreach (glob(app_path('Modules/*'), GLOB_ONLYDIR) as $path) {
    $json = $path . '/module.json';
    if (file_exists($json)) {
        $meta = json_decode(file_get_contents($json));
        if ($meta->enabled) {
            $modules[] = $meta;
        }
    }
}
```

### Phase 2: Registration

Call `register()` on all providers:

```php
foreach ($modules as $module) {
    $provider = new $module->provider($app);
    $provider->register();  // Bind services
}
```

**What happens:**
- Bind interfaces to implementations
- Register singletons
- Merge configurations
- **NO external dependencies allowed**

### Phase 3: Booting

Call `boot()` on all providers:

```php
foreach ($providers as $provider) {
    $provider->boot();  // Initialize features
}
```

**What happens:**
- Load routes, views, migrations
- Register middleware, events
- Register CLI commands
- **CAN use other services (dependency injection)**

### Phase 4: Runtime

Resolve services from container when needed:

```php
// Auto-injection in controller
public function show(SeoInterface $seo) {
    $seo->setTitle('My Page');
}

// Helper function
app('seo')->setTitle('My Page');

// Facade (if implemented)
Seo::setTitle('My Page');
```

---

## 💡 Complete Example: SEO Module

### SeoServiceProvider.php

```php
<?php

namespace App\Modules\Seo;

use App\Core\Container\ServiceProvider;
use App\Core\Contracts\Seo\SeoInterface;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoInterface::class, SeoService::class);
        $this->app->alias(SeoInterface::class, 'seo');
    }
    
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
```

### SeoService.php

```php
<?php

namespace App\Modules\Seo\Services;

use App\Core\Contracts\Seo\SeoInterface;

class SeoService implements SeoInterface
{
    protected array $tags = [];
    
    public function title(string $title): self
    {
        $this->tags['title'] = $title;
        return $this;
    }
    
    public function description(string $description): self
    {
        $this->tags['description'] = $description;
        return $this;
    }
    
    public function keywords(array $keywords): self
    {
        $this->tags['keywords'] = implode(', ', $keywords);
        return $this;
    }
    
    public function image(string $url): self
    {
        $this->tags['image'] = $url;
        return $this;
    }
    
    public function render(): string
    {
        $html = '';
        
        if (isset($this->tags['title'])) {
            $html .= '<title>' . e($this->tags['title']) . '</title>' . PHP_EOL;
            $html .= '<meta property="og:title" content="' . e($this->tags['title']) . '">' . PHP_EOL;
        }
        
        if (isset($this->tags['description'])) {
            $html .= '<meta name="description" content="' . e($this->tags['description']) . '">' . PHP_EOL;
            $html .= '<meta property="og:description" content="' . e($this->tags['description']) . '">' . PHP_EOL;
        }
        
        if (isset($this->tags['keywords'])) {
            $html .= '<meta name="keywords" content="' . e($this->tags['keywords']) . '">' . PHP_EOL;
        }
        
        if (isset($this->tags['image'])) {
            $html .= '<meta property="og:image" content="' . e($this->tags['image']) . '">' . PHP_EOL;
        }
        
        return $html;
    }
}
```

### Usage in Controller

```php
<?php

namespace App\Http\Controllers;

use App\Core\Contracts\Seo\SeoInterface;

class PageController
{
    public function about(SeoInterface $seo)
    {
        $seo->title('About Us')
            ->description('Learn more about our company')
            ->keywords(['about', 'company', 'team'])
            ->image('/images/about-og.jpg');
        
        return view('pages.about');
    }
}
```

### Usage in Blade

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {!! app('seo')->render() !!}
</head>
<body>
    @yield('content')
</body>
</html>
```

---

## 💡 Complete Example: Cache Module

### CacheServiceProvider.php

```php
<?php

namespace App\Modules\Cache;

use App\Core\Container\ServiceProvider;
use App\Core\Contracts\Cache\CacheInterface;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheInterface::class, function ($app) {
            return new CacheManager($app);
        });
        
        $this->app->alias(CacheInterface::class, 'cache');
        
        $this->mergeConfigFrom(__DIR__ . '/config/cache.php', 'cache');
    }
}
```

### CacheManager.php

```php
<?php

namespace App\Modules\Cache;

use App\Core\Contracts\Cache\CacheInterface;

class CacheManager implements CacheInterface
{
    protected $app;
    protected array $stores = [];
    
    public function __construct($app)
    {
        $this->app = $app;
    }
    
    public function store(string $name = null): CacheStore
    {
        $name = $name ?? config('cache.default');
        
        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->resolve($name);
        }
        
        return $this->stores[$name];
    }
    
    protected function resolve(string $name): CacheStore
    {
        $config = config("cache.stores.{$name}");
        
        return match($config['driver']) {
            'file' => new FileStore($config['path']),
            'redis' => new RedisStore($config),
            'array' => new ArrayStore(),
            default => new NullStore(),
        };
    }
    
    // Proxy methods to default store
    public function get(string $key, $default = null)
    {
        return $this->store()->get($key, $default);
    }
    
    public function put(string $key, $value, int $ttl = null): bool
    {
        return $this->store()->put($key, $value, $ttl);
    }
    
    public function forget(string $key): bool
    {
        return $this->store()->forget($key);
    }
    
    public function remember(string $key, int $ttl, callable $callback)
    {
        if ($value = $this->get($key)) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }
}
```

### FileStore.php

```php
<?php

namespace App\Modules\Cache\Stores;

class FileStore implements CacheStore
{
    protected string $path;
    
    public function __construct(string $path)
    {
        $this->path = $path;
    }
    
    public function get(string $key, $default = null)
    {
        $file = $this->path($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Check expiration
        if ($data['expires_at'] && time() > $data['expires_at']) {
            $this->forget($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    public function put(string $key, $value, int $ttl = null): bool
    {
        $data = [
            'value' => $value,
            'expires_at' => $ttl ? time() + $ttl : null,
        ];
        
        return (bool) file_put_contents(
            $this->path($key),
            serialize($data),
            LOCK_EX
        );
    }
    
    public function forget(string $key): bool
    {
        $file = $this->path($key);
        return file_exists($file) ? unlink($file) : false;
    }
    
    protected function path(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }
}
```

### Usage

```php
// Basic usage
cache()->put('key', 'value', 3600);  // 1 hour
$value = cache()->get('key');

// Remember pattern
$users = cache()->remember('users.all', 3600, function() {
    return User::all();
});

// Different stores
cache()->store('file')->put('key', 'value');
cache()->store('redis')->put('key', 'value');

// Helper
$value = cache('key');  // Get
cache(['key' => 'value'], 3600);  // Put
```

---

## ✅ Best Practices

### 1. Use Dependency Injection

**✅ DO:**
```php
class SeoService
{
    public function __construct(
        protected ConfigRepository $config,
        protected ViewFactory $view
    ) {}
}
```

**❌ DON'T:**
```php
class SeoService
{
    public function __construct()
    {
        $this->config = config();  // Global access
    }
}
```

---

### 2. Bind Interfaces, Not Concrete Classes

**✅ DO:**
```php
$this->app->singleton(CacheInterface::class, FileCache::class);
```

**❌ DON'T:**
```php
$this->app->singleton('cache', FileCache::class);  // String binding
```

---

### 3. Use Events for Module Communication

**✅ DO:**
```php
// In controller
event(new PagePublished($page));

// In other module
$events->listen(PagePublished::class, function($event) {
    Sitemap::update();
});
```

**❌ DON'T:**
```php
// Direct dependency on other module
$this->sitemapService->update();  // Tight coupling
```

---

### 4. Keep Modules Self-Contained

**✅ DO:**
```
app/Modules/Blog/
├── BlogServiceProvider.php
├── Models/Post.php
├── Controllers/PostController.php
└── Views/posts/
```

**❌ DON'T:**
```
app/Models/BlogPost.php           # ❌ Should be in module
app/Controllers/BlogController.php # ❌ Should be in module
```

---

### 5. Configuration Over Hard-Coding

**✅ DO:**
```php
// config/seo.php
return [
    'enabled' => env('SEO_ENABLED', true),
    'sitename' => env('SEO_SITENAME', 'Infinri'),
];

// In service
$this->config->get('seo.sitename');
```

**❌ DON'T:**
```php
class SeoService
{
    protected string $sitename = 'Infinri';  // Hardcoded
}
```

---

### 6. Write Tests for Your Modules

```php
namespace App\Modules\Seo\Tests\Unit;

use Tests\TestCase;
use App\Core\Contracts\Seo\SeoInterface;

class SeoServiceTest extends TestCase
{
    public function test_can_set_title()
    {
        $seo = app(SeoInterface::class);
        $seo->title('Test Title');
        
        $this->assertStringContainsString('Test Title', $seo->render());
    }
    
    public function test_can_set_description()
    {
        $seo = app(SeoInterface::class);
        $seo->description('Test Description');
        
        $this->assertStringContainsString('Test Description', $seo->render());
    }
}
```

---

## 🎓 Quick Reference

### Creating a Module

1. **Create directory:** `app/Modules/MyModule/`
2. **Create provider:** `MyModuleServiceProvider.php`
3. **Create metadata:** `module.json`
4. **Implement contract:** Create service class
5. **Register in provider:** Bind interface to implementation
6. **Bootstrap:** Load routes, views, etc. in `boot()`

### Service Provider Methods

- `register()` - Bind services (called first)
- `boot()` - Initialize features (called after all registered)
- `mergeConfigFrom($path, $key)` - Merge config
- `loadRoutesFrom($path)` - Load routes
- `loadViewsFrom($path, $namespace)` - Register views
- `loadSchemaFrom($path)` - Register schema
- `loadPatchesFrom($path)` - Register data/schema patches
- `commands($array)` - Register CLI commands

### Container Methods

- `$app->bind($abstract, $concrete)` - Bind class
- `$app->singleton($abstract, $concrete)` - Bind singleton
- `$app->alias($abstract, $alias)` - Create alias
- `$app->make($abstract)` - Resolve from container

---

## 🚀 Next Steps

1. **Review:** Read SCALABILITY-PLAN.md for full architecture
2. **Build:** Start with Phase 1 (Core Foundation)
3. **Implement:** Create your first module in Phase 4
4. **Test:** Write tests for all module functionality
5. **Document:** Add README for complex modules

---

**Related Documents:**
- SCALABILITY-PLAN.md - Full platform architecture
- ARCHITECTURE-AUDIT.md - Current system analysis
- README.md - Project overview

