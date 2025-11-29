# Infinri Theming & Layout Architecture v2

**Version:** 2.0  
**Date:** November 28, 2025  
**Goal:** Magento-inspired modular theming with strict separation of concerns

---

## 📐 Core Philosophy

### Key Principles

1. **Module-Owned Everything** - Blocks, templates, CSS, JS live in their module
2. **Layout Updates** - Modules can inject blocks into any page via layout config
3. **Strict Separation** - CSS in `.css`, JS in `.js`, HTML in `.phtml` - no mixing
4. **Dev/Prod Asset Modes** - Individual files in dev, bundled in production
5. **CSP Compliance** - Nonce-based inline when absolutely necessary

---

## 📁 Module Structure (Magento-Inspired)

```
app/Modules/
├── GlobalCallout/                      # Example: Cross-page block module
│   ├── GlobalCalloutServiceProvider.php
│   ├── module.json
│   │
│   ├── Block/                          # Block classes (logic)
│   │   └── Callout.php                 # Block class with data logic
│   │
│   ├── view/
│   │   ├── frontend/
│   │   │   ├── layout/                 # Layout updates (where to inject)
│   │   │   │   └── home_index.php      # Inject into home page
│   │   │   │
│   │   │   ├── templates/              # HTML templates (.phtml)
│   │   │   │   └── callout.phtml       # Pure HTML, no inline CSS/JS
│   │   │   │
│   │   │   ├── css/                    # Module CSS
│   │   │   │   └── callout.css
│   │   │   │
│   │   │   └── js/                     # Module JS
│   │   │       └── callout.js
│   │   │
│   │   └── admin/                      # Admin views (same structure)
│   │       └── ...
│   │
│   └── routes.php
│
├── Home/
│   ├── HomeServiceProvider.php
│   ├── module.json
│   │
│   ├── Block/
│   │   ├── Hero.php
│   │   ├── Services.php
│   │   └── Testimonials.php
│   │
│   ├── Controller/
│   │   └── IndexController.php
│   │
│   └── view/
│       └── frontend/
│           ├── layout/
│           │   └── default.php         # Default layout config for this module
│           │
│           ├── templates/
│           │   ├── index.phtml         # Main page template
│           │   ├── hero.phtml
│           │   ├── services.phtml
│           │   └── testimonials.phtml
│           │
│           ├── css/
│           │   ├── hero.css
│           │   └── services.css
│           │
│           └── js/
│               └── hero.js
│
├── Header/                             # Core header module
│   ├── HeaderServiceProvider.php
│   ├── Block/
│   │   └── Navigation.php
│   └── view/
│       └── frontend/
│           ├── templates/
│           │   └── navigation.phtml
│           ├── css/
│           │   └── header.css
│           └── js/
│               └── header.js
│
├── Footer/                             # Core footer module
│   └── ...
│
└── Blog/                               # Feature module with both areas
    ├── Block/
    │   ├── PostList.php
    │   ├── PostView.php
    │   ├── Sidebar.php
    │   └── Admin/
    │       ├── PostGrid.php
    │       └── PostForm.php
    │
    └── view/
        ├── frontend/
        │   ├── layout/
        │   │   ├── blog_index.php      # List page layout
        │   │   └── blog_post.php       # Single post layout
        │   ├── templates/
        │   ├── css/
        │   └── js/
        │
        └── admin/
            ├── layout/
            ├── templates/
            ├── css/
            └── js/
```

---

## 🔧 Layout System

### Layout Update Files

Layout updates define WHERE blocks appear. Each module can inject into any page.

```php
<?php
// app/Modules/GlobalCallout/view/frontend/layout/home_index.php
declare(strict_types=1);
/**
 * Layout Update: Inject GlobalCallout into Home page
 * 
 * This file is auto-discovered when rendering the "home_index" handle.
 * Multiple modules can contribute to the same handle.
 */

return [
    // Reference a container defined in the page layout
    'header.after' => [
        // Add our block to this container
        [
            'block' => \App\Modules\GlobalCallout\Block\Callout::class,
            'template' => 'GlobalCallout::callout',  // Module::template notation
            'name' => 'global.callout',              // Unique block name
            'sort_order' => 10,                      // Position in container
            'cache' => [
                'enabled' => true,
                'ttl' => 3600,                       // Cache for 1 hour
            ],
        ],
    ],
    
    // Can also inject CSS/JS for this page only
    'head.css' => [
        ['file' => 'GlobalCallout::css/callout.css'],
    ],
    
    'body.js' => [
        ['file' => 'GlobalCallout::js/callout.js'],
    ],
];
```

### Layout Handles (Hybrid Auto-Generation)

Infinri uses a **hybrid approach** that combines automatic handle generation with explicit control:

#### How Handles Are Generated

```
Request: GET /blog/post/123
Route: BlogController@view

Auto-generated handles (in order):
1. 'default'           ← Always applied (site-wide)
2. 'frontend_default'  ← Area-based (frontend or admin)
3. 'blog_view'         ← Auto from route: {module}_{action}

Controller can add more:
4. 'featured_post'     ← Explicit (e.g., for featured content)
5. 'holiday_theme'     ← Explicit (e.g., seasonal)
```

#### Handle Generation Logic

```php
// app/Core/View/Layout/HandleGenerator.php
declare(strict_types=1);

namespace App\Core\View\Layout;

final class HandleGenerator
{
    /**
     * Generate handles from route
     * 
     * Pattern: {module}_{action}
     * Examples:
     *   GET /           → home_index
     *   GET /blog       → blog_index
     *   GET /blog/123   → blog_view
     *   GET /contact    → contact_index
     *   POST /contact   → contact_post
     *   GET /admin/users → admin_users_index
     */
    public function generate(Route $route, string $area): array
    {
        $handles = [];
        
        // 1. Universal default
        $handles[] = 'default';
        
        // 2. Area default
        $handles[] = "{$area}_default";
        
        // 3. Route-based handle
        $module = strtolower($route->getModule());  // 'Blog' → 'blog'
        $action = strtolower($route->getAction());  // 'view' → 'view'
        $handles[] = "{$module}_{$action}";
        
        // 4. Admin prefix for admin routes
        if ($area === 'admin') {
            $handles[] = "admin_{$module}_{$action}";
        }
        
        return $handles;
    }
}
```

#### Controller Can Add Explicit Handles

```php
// app/Modules/Blog/Controller/PostController.php
public function view(int $id, LayoutRenderer $layout): Response
{
    $post = $this->postRepository->find($id);
    
    // Auto handles already applied: default, frontend_default, blog_view
    
    // Add conditional handles
    if ($post->isFeatured()) {
        $layout->addHandle('blog_featured');
    }
    
    if ($post->hasVideo()) {
        $layout->addHandle('blog_video_post');
    }
    
    // Seasonal/campaign handles
    if ($this->isBlackFriday()) {
        $layout->addHandle('black_friday_sale');
    }
    
    return $this->render('Blog::post', ['post' => $post]);
}
```

#### Handle Priority & Loading Order

Layout files are loaded in handle order. Later handles can override earlier ones:

```
Request: GET /
Handles: ['default', 'frontend_default', 'home_index']

Layout files scanned (all modules):
1. */view/frontend/layout/default.php        ← Header, Footer (all pages)
2. */view/frontend/layout/frontend_default.php  ← Frontend-specific
3. */view/frontend/layout/home_index.php     ← Home page only (GlobalCallout)
```

#### Standard Handle Reference

| Handle | When Applied | Use Case |
|--------|--------------|----------|
| `default` | Every page | Header, Footer, Analytics |
| `frontend_default` | All frontend pages | Frontend nav, public footer |
| `admin_default` | All admin pages | Admin sidebar, admin header |
| `{module}_index` | Module list/home | `home_index`, `blog_index` |
| `{module}_view` | Single item view | `blog_view`, `product_view` |
| `{module}_create` | Create form | `blog_create`, `user_create` |
| `{module}_edit` | Edit form | `blog_edit`, `user_edit` |
| `admin_{module}_{action}` | Admin actions | `admin_blog_list`, `admin_users_edit` |
| Custom handles | Controller-added | `featured_post`, `holiday_theme`, `ab_test_v2` |

### Page Layout (Container Definitions)

```php
<?php
// app/Core/View/Layout/PageLayout.php
declare(strict_types=1);

namespace App\Core\View\Layout;

/**
 * Defines the page structure with named containers.
 * Blocks are injected into these containers via layout updates.
 */
final class PageLayout
{
    /**
     * Frontend page containers
     */
    public const FRONTEND_CONTAINERS = [
        'head.meta',           // Meta tags
        'head.css',            // CSS files
        'head.scripts',        // Head JS (external APIs)
        
        'body.start',          // Right after <body>
        'header.before',       // Before header
        'header',              // Main header
        'header.after',        // After header (banners, callouts)
        
        'breadcrumbs',         // Breadcrumb navigation
        
        'content.before',      // Before main content
        'content.top',         // Top of content area
        'content',             // Main content
        'content.bottom',      // Bottom of content area
        'content.after',       // After main content
        
        'sidebar.left',        // Left sidebar
        'sidebar.right',       // Right sidebar
        
        'footer.before',       // Before footer
        'footer',              // Main footer
        'footer.after',        // After footer
        
        'body.end',            // Before </body>
        'body.js',             // JS files (end of body)
    ];
    
    /**
     * Admin page containers
     */
    public const ADMIN_CONTAINERS = [
        'head.meta',
        'head.css',
        'head.scripts',
        
        'body.start',
        'sidebar',             // Admin sidebar
        'header',              // Admin header bar
        
        'page.title',          // Page title area
        'page.actions',        // Action buttons
        'messages',            // Flash messages
        
        'content.before',
        'content',
        'content.after',
        
        'body.end',
        'body.js',
    ];
}
```

---

## 🧱 Block Classes

Blocks contain the **logic** for preparing data. Templates handle **presentation**.

```php
<?php
// app/Modules/GlobalCallout/Block/Callout.php
declare(strict_types=1);

namespace App\Modules\GlobalCallout\Block;

use App\Core\View\Block\AbstractBlock;

final class Callout extends AbstractBlock
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'title' => 'Special Offer!',
            'message' => 'Get 20% off your first project.',
            'cta_text' => 'Learn More',
            'cta_url' => '/services',
            'dismissible' => true,
        ], $config);
    }
    
    /**
     * Prepare data for the template
     */
    public function getData(): array
    {
        return [
            'title' => $this->config['title'],
            'message' => $this->config['message'],
            'cta_text' => $this->config['cta_text'],
            'cta_url' => $this->config['cta_url'],
            'dismissible' => $this->config['dismissible'],
            'callout_id' => 'callout-' . uniqid(),
        ];
    }
    
    /**
     * Cache key for this block
     */
    public function getCacheKey(): string
    {
        return 'global_callout_' . md5(json_encode($this->config));
    }
}
```

```php
<?php
// app/Core/View/Block/AbstractBlock.php
declare(strict_types=1);

namespace App\Core\View\Block;

abstract class AbstractBlock
{
    protected string $template = '';
    protected string $nameInLayout = '';
    
    /**
     * Get data for template
     */
    abstract public function getData(): array;
    
    /**
     * Set template path
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }
    
    /**
     * Get template path
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
    
    /**
     * Set block name in layout
     */
    public function setNameInLayout(string $name): self
    {
        $this->nameInLayout = $name;
        return $this;
    }
    
    /**
     * Get block name in layout
     */
    public function getNameInLayout(): string
    {
        return $this->nameInLayout;
    }
    
    /**
     * Cache key (override for caching)
     */
    public function getCacheKey(): ?string
    {
        return null; // No caching by default
    }
    
    /**
     * Cache TTL in seconds
     */
    public function getCacheTtl(): int
    {
        return 3600;
    }
}
```

---

## 📄 Templates (.phtml)

Templates are **pure HTML** with PHP for output only. No inline CSS or JS.

```php
<?php
// app/Modules/GlobalCallout/view/frontend/templates/callout.phtml
declare(strict_types=1);
/**
 * Global Callout Block Template
 * 
 * RULES:
 * - No inline styles (use CSS file)
 * - No inline JavaScript (use JS file)
 * - Data-attributes for JS hooks
 * - Use e() for all output escaping
 * 
 * @var array $data Block data from Callout::getData()
 */

// Extract for cleaner template
extract($data);
?>
<div class="global-callout" 
     id="<?= e($callout_id) ?>"
     data-callout
     data-dismissible="<?= $dismissible ? 'true' : 'false' ?>">
    
    <div class="callout-content">
        <h3 class="callout-title"><?= e($title) ?></h3>
        <p class="callout-message"><?= e($message) ?></p>
        <a href="<?= e($cta_url) ?>" class="callout-cta btn btn-primary">
            <?= e($cta_text) ?>
        </a>
    </div>
    
    <?php if ($dismissible): ?>
    <button type="button" 
            class="callout-dismiss" 
            data-callout-dismiss
            aria-label="Dismiss">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
        </svg>
    </button>
    <?php endif; ?>
</div>
```

### Corresponding CSS (Separate File)

```css
/* app/Modules/GlobalCallout/view/frontend/css/callout.css */

.global-callout {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-4) var(--space-6);
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: white;
    gap: var(--space-4);
}

.global-callout[data-dismissed="true"] {
    display: none;
}

.callout-content {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.callout-title {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    margin: 0;
}

.callout-message {
    margin: 0;
    opacity: 0.9;
}

.callout-cta {
    flex-shrink: 0;
}

.callout-dismiss {
    background: transparent;
    border: none;
    color: white;
    opacity: 0.7;
    cursor: pointer;
    padding: var(--space-2);
    transition: opacity var(--transition-fast);
}

.callout-dismiss:hover {
    opacity: 1;
}
```

### Corresponding JS (Separate File)

```javascript
// app/Modules/GlobalCallout/view/frontend/js/callout.js

/**
 * Global Callout Component
 * 
 * Handles dismissal with localStorage persistence.
 * Uses data-attributes for DOM hooks (no IDs in CSS).
 */
(function() {
    'use strict';
    
    const STORAGE_KEY = 'infinri_dismissed_callouts';
    
    function getDismissed() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        } catch {
            return [];
        }
    }
    
    function setDismissed(id) {
        const dismissed = getDismissed();
        if (!dismissed.includes(id)) {
            dismissed.push(id);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(dismissed));
        }
    }
    
    function init() {
        const callouts = document.querySelectorAll('[data-callout]');
        const dismissed = getDismissed();
        
        callouts.forEach(callout => {
            const id = callout.id;
            
            // Check if already dismissed
            if (dismissed.includes(id)) {
                callout.setAttribute('data-dismissed', 'true');
                return;
            }
            
            // Handle dismiss button
            const dismissBtn = callout.querySelector('[data-callout-dismiss]');
            if (dismissBtn) {
                dismissBtn.addEventListener('click', () => {
                    callout.setAttribute('data-dismissed', 'true');
                    setDismissed(id);
                });
            }
        });
    }
    
    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
```

---

## 🏗️ Layout Renderer

```php
<?php
// app/Core/View/Layout/LayoutRenderer.php
declare(strict_types=1);

namespace App\Core\View\Layout;

use App\Core\Container\Container;
use App\Core\View\Block\AbstractBlock;

final class LayoutRenderer
{
    private Container $container;
    private array $handles = [];
    private array $blocks = [];
    private array $containers = [];
    private string $area = 'frontend';
    
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Set the area (frontend/admin)
     */
    public function setArea(string $area): self
    {
        $this->area = $area;
        return $this;
    }
    
    /**
     * Add layout handles for this request
     */
    public function addHandle(string ...$handles): self
    {
        foreach ($handles as $handle) {
            if (!in_array($handle, $this->handles, true)) {
                $this->handles[] = $handle;
            }
        }
        return $this;
    }
    
    /**
     * Load layout updates from all modules for current handles
     */
    public function loadLayoutUpdates(): self
    {
        // Default handles
        $allHandles = array_merge(
            ['default', "{$this->area}_default"],
            $this->handles
        );
        
        // Scan all modules for layout updates
        $modulesPath = app_path('Modules');
        $modules = glob($modulesPath . '/*', GLOB_ONLYDIR);
        
        foreach ($modules as $modulePath) {
            foreach ($allHandles as $handle) {
                $layoutFile = "{$modulePath}/view/{$this->area}/layout/{$handle}.php";
                
                if (file_exists($layoutFile)) {
                    $updates = require $layoutFile;
                    $this->mergeLayoutUpdates($updates);
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Merge layout updates into containers
     */
    private function mergeLayoutUpdates(array $updates): void
    {
        foreach ($updates as $container => $items) {
            if (!isset($this->containers[$container])) {
                $this->containers[$container] = [];
            }
            
            foreach ($items as $item) {
                $this->containers[$container][] = $item;
            }
            
            // Sort by sort_order
            usort($this->containers[$container], function($a, $b) {
                return ($a['sort_order'] ?? 100) <=> ($b['sort_order'] ?? 100);
            });
        }
    }
    
    /**
     * Generate all blocks
     */
    public function generateBlocks(): self
    {
        foreach ($this->containers as $containerName => $items) {
            foreach ($items as $item) {
                if (isset($item['block'])) {
                    $block = $this->container->make($item['block']);
                    
                    if ($block instanceof AbstractBlock) {
                        $block->setTemplate($item['template'] ?? '');
                        $block->setNameInLayout($item['name'] ?? '');
                        
                        $this->blocks[$item['name'] ?? uniqid()] = [
                            'block' => $block,
                            'container' => $containerName,
                            'cache' => $item['cache'] ?? null,
                        ];
                    }
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Render a specific container
     */
    public function renderContainer(string $name): string
    {
        $output = [];
        
        foreach ($this->blocks as $blockName => $blockInfo) {
            if ($blockInfo['container'] === $name) {
                $output[] = $this->renderBlock($blockInfo['block'], $blockInfo['cache']);
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Render a block
     */
    private function renderBlock(AbstractBlock $block, ?array $cacheConfig): string
    {
        // Check cache
        if ($cacheConfig && ($cacheConfig['enabled'] ?? false)) {
            $cacheKey = $block->getCacheKey();
            if ($cacheKey && $cached = cache()->get($cacheKey)) {
                return $cached;
            }
        }
        
        // Render template
        $template = $block->getTemplate();
        $data = $block->getData();
        
        $html = $this->renderTemplate($template, $data);
        
        // Store in cache
        if ($cacheConfig && ($cacheConfig['enabled'] ?? false) && $block->getCacheKey()) {
            cache()->set($block->getCacheKey(), $html, $cacheConfig['ttl'] ?? 3600);
        }
        
        return $html;
    }
    
    /**
     * Render a template file
     */
    private function renderTemplate(string $template, array $data): string
    {
        $path = $this->resolveTemplatePath($template);
        
        if (!file_exists($path)) {
            return "<!-- Template not found: {$template} -->";
        }
        
        extract(['data' => $data], EXTR_SKIP);
        
        ob_start();
        include $path;
        return ob_get_clean();
    }
    
    /**
     * Resolve Module::template to file path
     */
    private function resolveTemplatePath(string $template): string
    {
        if (str_contains($template, '::')) {
            [$module, $path] = explode('::', $template, 2);
            return app_path("Modules/{$module}/view/{$this->area}/templates/{$path}.phtml");
        }
        
        return app_path("resources/views/{$template}.phtml");
    }
    
    /**
     * Get CSS files for current layout
     */
    public function getCssFiles(): array
    {
        return $this->containers['head.css'] ?? [];
    }
    
    /**
     * Get JS files for current layout
     */
    public function getJsFiles(): array
    {
        return $this->containers['body.js'] ?? [];
    }
}
```

---

## 🎨 Asset Management

### Dev vs Production Mode

```php
<?php
// app/Core/View/Asset/AssetManager.php
declare(strict_types=1);

namespace App\Core\View\Asset;

final class AssetManager
{
    private bool $isProduction;
    private ?string $cspNonce;
    private array $css = [];
    private array $js = [];
    private string $area = 'frontend';
    
    public function __construct()
    {
        $this->isProduction = env('APP_ENV', 'production') === 'production';
        $this->cspNonce = $GLOBALS['cspNonce'] ?? null;
    }
    
    /**
     * Set current area
     */
    public function setArea(string $area): self
    {
        $this->area = $area;
        return $this;
    }
    
    /**
     * Add CSS file from module
     * 
     * @param string $file Module::path notation (e.g., "Home::css/hero.css")
     */
    public function addCss(string $file): self
    {
        if (!$this->isProduction) {
            $this->css[] = $this->resolveAssetPath($file);
        }
        return $this;
    }
    
    /**
     * Add JS file from module
     */
    public function addJs(string $file): self
    {
        if (!$this->isProduction) {
            $this->js[] = $this->resolveAssetPath($file);
        }
        return $this;
    }
    
    /**
     * Resolve Module::path to URL
     */
    private function resolveAssetPath(string $file): string
    {
        if (str_contains($file, '::')) {
            [$module, $path] = explode('::', $file, 2);
            return "/assets/modules/{$module}/view/{$this->area}/{$path}";
        }
        
        return $file;
    }
    
    /**
     * Render CSS includes
     */
    public function renderCss(): string
    {
        $version = $this->getVersion();
        $output = '';
        
        if ($this->isProduction) {
            // Single bundle in production
            $bundle = "/assets/dist/{$this->area}.min.css?v={$version}";
            $output .= '<link rel="stylesheet" href="' . e($bundle) . '">' . PHP_EOL;
        } else {
            // Individual files in development
            foreach ($this->css as $file) {
                $url = e($file) . '?v=' . e($version);
                $output .= '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
            }
        }
        
        return $output;
    }
    
    /**
     * Render JS includes
     */
    public function renderJs(): string
    {
        $version = $this->getVersion();
        $output = '';
        
        if ($this->isProduction) {
            // Single bundle in production
            $bundle = "/assets/dist/{$this->area}.min.js?v={$version}";
            $output .= '<script src="' . e($bundle) . '" defer></script>' . PHP_EOL;
        } else {
            // Individual files in development
            foreach ($this->js as $file) {
                $url = e($file) . '?v=' . e($version);
                $output .= '<script src="' . $url . '" defer></script>' . PHP_EOL;
            }
        }
        
        return $output;
    }
    
    /**
     * Render critical CSS inline (with CSP nonce)
     */
    public function renderCriticalCss(): string
    {
        $criticalPath = $this->isProduction
            ? public_path("assets/dist/critical-{$this->area}.css")
            : app_path("resources/css/{$this->area}/critical.css");
        
        if (!file_exists($criticalPath)) {
            return '';
        }
        
        $css = file_get_contents($criticalPath);
        
        // Minify if not production (production is pre-minified)
        if (!$this->isProduction) {
            $css = $this->minifyCss($css);
        }
        
        $nonceAttr = $this->cspNonce ? ' nonce="' . e($this->cspNonce) . '"' : '';
        
        return '<style' . $nonceAttr . '>' . $css . '</style>' . PHP_EOL;
    }
    
    /**
     * Render inline script (with CSP nonce)
     * Use sparingly - prefer external JS files
     */
    public function renderInlineScript(string $script): string
    {
        $nonceAttr = $this->cspNonce ? ' nonce="' . e($this->cspNonce) . '"' : '';
        return '<script' . $nonceAttr . '>' . $script . '</script>' . PHP_EOL;
    }
    
    /**
     * Render inline style (with CSP nonce)
     * Use sparingly - prefer external CSS files
     */
    public function renderInlineStyle(string $css): string
    {
        $nonceAttr = $this->cspNonce ? ' nonce="' . e($this->cspNonce) . '"' : '';
        return '<style' . $nonceAttr . '>' . $css . '</style>' . PHP_EOL;
    }
    
    /**
     * Get asset version for cache busting
     */
    private function getVersion(): string
    {
        return env('APP_VERSION', (string) time());
    }
    
    /**
     * Simple CSS minification
     */
    private function minifyCss(string $css): string
    {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s{2,}/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        return trim($css);
    }
}
```

---

## 📜 Base Layout

```php
<?php
// app/resources/views/layouts/base.phtml
declare(strict_types=1);
/**
 * ROOT LAYOUT - The only place <html>, <head>, <body> are defined
 * 
 * @var \App\Core\View\Layout\LayoutRenderer $layout
 * @var \App\Core\View\Asset\AssetManager $assets
 */
?>
<!DOCTYPE html>
<html lang="<?= e($locale ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php // Meta tags from layout ?>
    <?= $layout->renderContainer('head.meta') ?>
    
    <?php // Critical CSS (inlined with nonce) ?>
    <?= $assets->renderCriticalCss() ?>
    
    <?php // Area CSS bundle (or individual files in dev) ?>
    <?= $assets->renderCss() ?>
    
    <?php // Module CSS from layout updates ?>
    <?= $layout->renderContainer('head.css') ?>
    
    <?php // External head scripts ?>
    <?= $layout->renderContainer('head.scripts') ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
    <?php // Body start (skip links, etc.) ?>
    <a href="#main-content" class="sr-only focus:not-sr-only">Skip to main content</a>
    <?= $layout->renderContainer('body.start') ?>
    
    <?php // Header area ?>
    <?= $layout->renderContainer('header.before') ?>
    <?= $layout->renderContainer('header') ?>
    <?= $layout->renderContainer('header.after') ?>
    
    <?php // Main content wrapper - structure defined by area layout ?>
    <main id="main-content" role="main">
        <?= $layout->renderContainer('breadcrumbs') ?>
        <?= $layout->renderContainer('content.before') ?>
        <?= $layout->renderContainer('content.top') ?>
        <?= $layout->renderContainer('content') ?>
        <?= $layout->renderContainer('content.bottom') ?>
        <?= $layout->renderContainer('content.after') ?>
    </main>
    
    <?php // Footer area ?>
    <?= $layout->renderContainer('footer.before') ?>
    <?= $layout->renderContainer('footer') ?>
    <?= $layout->renderContainer('footer.after') ?>
    
    <?php // Body end ?>
    <?= $layout->renderContainer('body.end') ?>
    
    <?php // JS (bundled in production, individual in dev) ?>
    <?= $assets->renderJs() ?>
    <?= $layout->renderContainer('body.js') ?>
</body>
</html>
```

---

## 🧩 Module Layout Updates Example

### Header Module (Always Present)

```php
<?php
// app/Modules/Header/view/frontend/layout/default.php
declare(strict_types=1);
/**
 * Header module - adds to every frontend page (default handle)
 */

return [
    'header' => [
        [
            'block' => \App\Modules\Header\Block\Navigation::class,
            'template' => 'Header::navigation',
            'name' => 'header.navigation',
            'sort_order' => 10,
        ],
    ],
    
    'head.css' => [
        ['file' => 'Header::css/header.css'],
    ],
    
    'body.js' => [
        ['file' => 'Header::js/header.js'],
    ],
];
```

### GlobalCallout Module (Only on Home Page)

```php
<?php
// app/Modules/GlobalCallout/view/frontend/layout/home_index.php
declare(strict_types=1);
/**
 * GlobalCallout - only appears on home page
 */

return [
    'header.after' => [
        [
            'block' => \App\Modules\GlobalCallout\Block\Callout::class,
            'template' => 'GlobalCallout::callout',
            'name' => 'global.callout.promo',
            'sort_order' => 10,
            'cache' => [
                'enabled' => true,
                'ttl' => 3600,
            ],
        ],
    ],
    
    'head.css' => [
        ['file' => 'GlobalCallout::css/callout.css'],
    ],
    
    'body.js' => [
        ['file' => 'GlobalCallout::js/callout.js'],
    ],
];
```

### Blog Module (Multiple Handles)

```php
<?php
// app/Modules/Blog/view/frontend/layout/blog_index.php
declare(strict_types=1);
/**
 * Blog listing page layout
 */

return [
    'content' => [
        [
            'block' => \App\Modules\Blog\Block\PostList::class,
            'template' => 'Blog::post-list',
            'name' => 'blog.post.list',
            'sort_order' => 10,
        ],
    ],
    
    'sidebar.right' => [
        [
            'block' => \App\Modules\Blog\Block\Sidebar::class,
            'template' => 'Blog::sidebar',
            'name' => 'blog.sidebar',
            'sort_order' => 10,
        ],
    ],
    
    'head.css' => [
        ['file' => 'Blog::css/blog.css'],
    ],
];
```

---

## 🔒 CSP Compliance

### When Inline Code is Necessary

In rare cases where inline code is unavoidable:

```php
<?php
// Example: Page-specific data that must be inline
// app/Modules/Analytics/view/frontend/layout/default.php

return [
    'body.end' => [
        [
            'block' => \App\Modules\Analytics\Block\PageData::class,
            'template' => 'Analytics::page-data',
            'name' => 'analytics.page.data',
            'sort_order' => 999,
        ],
    ],
];
```

```php
<?php
// app/Modules/Analytics/view/frontend/templates/page-data.phtml
declare(strict_types=1);
/**
 * Inline page data for analytics
 * Uses nonce for CSP compliance
 */

$nonce = $GLOBALS['cspNonce'] ?? '';
$nonceAttr = $nonce ? ' nonce="' . e($nonce) . '"' : '';

// Page data that external JS will read
$pageData = json_encode($data['page_data'], JSON_HEX_TAG | JSON_HEX_APOS);
?>
<script<?= $nonceAttr ?>>
window.pageData = <?= $pageData ?>;
</script>
```

---

## 📊 Summary: What Changed from v1

| Aspect | v1 | v2 (Magento-style) |
|--------|----|--------------------|
| **Block Location** | `app/resources/views/blocks/` | `app/Modules/*/Block/` |
| **Template Location** | `app/resources/views/` | `app/Modules/*/view/*/templates/` |
| **Cross-module Injection** | Not supported | Layout update files |
| **Layout Config** | PHP includes | Declarative PHP arrays |
| **Template Extension** | `.php` | `.phtml` (pure HTML) |
| **Inline CSS/JS** | Allowed | Forbidden (except with nonce) |
| **Asset Mode** | Always bundled | Dev: individual, Prod: bundled |
| **Block Caching** | Manual | Declarative in layout |

### Benefits of v2

1. **True Modularity** - GlobalCallout can inject anywhere without touching target
2. **Clean Separation** - CSS/JS/HTML never mixed
3. **CSP Compliant** - Nonce support for unavoidable inline
4. **Dev Friendly** - Individual files for debugging
5. **Prod Optimized** - Single bundles for performance
6. **Cacheable Blocks** - Declarative block caching
7. **Discoverable** - Layout updates auto-scanned from modules

---

## 🚀 Implementation Order

1. **Create `HandleGenerator`** - Route-to-handle mapping
2. **Create `AbstractBlock`** - Base block class
3. **Create `LayoutRenderer`** - Layout update processor with handle loading
4. **Create `AssetManager`** - Dev/prod asset handling with nonce support
5. **Create `base.phtml`** - Root layout with all containers
6. **Migrate Header module** - First module with block pattern
7. **Migrate Footer module** - Second module
8. **Create GlobalCallout** - Test cross-module injection into `home_index`
9. **Build pipeline** - CSS/JS bundling for production

---

## 📋 Quick Reference Card

```
LAYOUT HANDLE NAMING:
  default.php           → Every page
  frontend_default.php  → All frontend pages
  admin_default.php     → All admin pages
  {module}_{action}.php → Specific page (home_index, blog_view, contact_post)
  
BLOCK DEFINITION:
  return [
      'container.name' => [
          ['block' => Block::class, 'template' => 'Module::template', 'sort_order' => 10],
      ],
  ];

ASSET INJECTION:
  'head.css' => [['file' => 'Module::css/style.css']],
  'body.js'  => [['file' => 'Module::js/script.js']],

CONTAINER NAMES:
  header, header.before, header.after
  content, content.before, content.after, content.top, content.bottom
  sidebar.left, sidebar.right
  footer, footer.before, footer.after
  head.css, head.scripts, body.js

TEMPLATE PATH:
  'Module::path/to/template' → app/Modules/Module/view/{area}/templates/path/to/template.phtml
```
