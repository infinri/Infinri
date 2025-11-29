# Infinri Theming & Layout Architecture

**Version:** 1.0  
**Date:** November 28, 2025  
**Goal:** Define a DRY, scalable, secure theming system for Frontend and Admin areas

---

## 📐 Architecture Overview

### Design Philosophy

```
"Define once at the top, override sparingly at the bottom"
```

### Inheritance Hierarchy

```
                    ┌─────────────────────┐
                    │      BASE           │
                    │  (HTML skeleton)    │
                    │  <html><head><body> │
                    └──────────┬──────────┘
                               │
              ┌────────────────┴────────────────┐
              │                                 │
    ┌─────────▼─────────┐             ┌─────────▼─────────┐
    │     FRONTEND      │             │       ADMIN       │
    │  (public facing)  │             │  (authenticated)  │
    │  - Public header  │             │  - Admin sidebar  │
    │  - Public footer  │             │  - Admin header   │
    │  - Marketing CSS  │             │  - Dashboard CSS  │
    └─────────┬─────────┘             └─────────┬─────────┘
              │                                 │
    ┌─────────┴─────────┐             ┌─────────┴─────────┐
    │  FRONTEND LAYOUTS │             │   ADMIN LAYOUTS   │
    │  - one-column     │             │  - dashboard      │
    │  - two-column     │             │  - form           │
    │  - landing        │             │  - list           │
    │  - blog           │             │  - settings       │
    └─────────┬─────────┘             └─────────┬─────────┘
              │                                 │
    ┌─────────▼─────────┐             ┌─────────▼─────────┐
    │  MODULE TEMPLATES │             │  MODULE TEMPLATES │
    │  Home::home       │             │  Blog::admin/list │
    │  Blog::post       │             │  Users::edit      │
    │  Contact::form    │             │  Settings::index  │
    └───────────────────┘             └───────────────────┘
```

### Key Principles

1. **Single Source of Truth** - Base defines `<html>`, `<head>`, `<body>` ONCE
2. **Area Specialization** - Frontend/Admin define their chrome (header, nav, footer)
3. **Layout Flexibility** - Multiple layouts per area (1-col, 2-col, dashboard)
4. **Block Injection** - Modules inject CSS/JS without touching layouts
5. **Component Reuse** - Shared component library for both areas
6. **Theme Tokens** - CSS variables enable theming without code changes

---

## 📁 Directory Structure

```
app/
├── Core/
│   ├── View/
│   │   ├── View.php                    # Main view engine
│   │   ├── ViewFactory.php             # Creates view instances
│   │   ├── Block.php                   # Block management
│   │   ├── Layout.php                  # Layout resolution
│   │   └── Compiler/
│   │       └── TemplateCompiler.php    # Optional: Blade-like syntax (Phase 4)
│   │
│   └── Contracts/
│       └── View/
│           ├── ViewInterface.php
│           ├── LayoutInterface.php
│           └── BlockInterface.php
│
├── Providers/
│   └── ViewServiceProvider.php         # View bindings
│
├── Modules/
│   ├── Home/
│   │   ├── HomeServiceProvider.php
│   │   ├── Controllers/
│   │   │   └── HomeController.php
│   │   └── View/
│   │       └── frontend/
│   │           ├── templates/
│   │           │   └── home.php
│   │           ├── css/                 # Module overrides ONLY
│   │           │   └── home.css
│   │           └── js/
│   │               └── home.js
│   │
│   └── Blog/
│       ├── BlogServiceProvider.php
│       ├── Controllers/
│       │   ├── PostController.php       # Frontend
│       │   └── Admin/
│       │       └── PostController.php   # Admin
│       └── View/
│           ├── frontend/
│           │   ├── templates/
│           │   │   ├── list.php
│           │   │   └── show.php
│           │   └── css/
│           │       └── blog.css
│           └── admin/
│               ├── templates/
│               │   ├── list.php
│               │   └── edit.php
│               └── css/
│                   └── blog-admin.css
│
└── resources/
    ├── views/
    │   ├── layouts/
    │   │   ├── base.php                 # ROOT: <html>, <head>, <body>
    │   │   │
    │   │   ├── frontend/                # FRONTEND AREA
    │   │   │   ├── app.php              # Frontend chrome (header/footer)
    │   │   │   ├── one-column.php
    │   │   │   ├── two-column.php
    │   │   │   ├── landing.php
    │   │   │   └── blog.php
    │   │   │
    │   │   └── admin/                   # ADMIN AREA
    │   │       ├── app.php              # Admin chrome (sidebar/header)
    │   │       ├── dashboard.php
    │   │       ├── form.php
    │   │       ├── list.php
    │   │       └── settings.php
    │   │
    │   ├── blocks/
    │   │   ├── base/                    # Shared by both areas
    │   │   │   ├── html-head.php        # <head> content
    │   │   │   ├── scripts.php          # JS loading
    │   │   │   └── flash-messages.php
    │   │   │
    │   │   ├── frontend/                # Frontend-specific
    │   │   │   ├── header.php
    │   │   │   ├── navigation.php
    │   │   │   ├── footer.php
    │   │   │   └── cta-banner.php
    │   │   │
    │   │   └── admin/                   # Admin-specific
    │   │       ├── header.php
    │   │       ├── sidebar.php
    │   │       ├── breadcrumbs.php
    │   │       └── page-header.php
    │   │
    │   └── components/                  # Shared UI components
    │       ├── button.php
    │       ├── card.php
    │       ├── form/
    │       │   ├── input.php
    │       │   ├── select.php
    │       │   ├── textarea.php
    │       │   └── checkbox.php
    │       ├── table.php
    │       ├── pagination.php
    │       ├── modal.php
    │       ├── alert.php
    │       ├── badge.php
    │       └── dropdown.php
    │
    ├── css/
    │   ├── base/                        # Core CSS (both areas)
    │   │   ├── _variables.css           # Design tokens
    │   │   ├── _reset.css               # Normalize
    │   │   ├── _typography.css          # Font styles
    │   │   ├── _utilities.css           # Helpers
    │   │   └── _animations.css          # Transitions
    │   │
    │   ├── components/                  # Component styles
    │   │   ├── _buttons.css
    │   │   ├── _cards.css
    │   │   ├── _forms.css
    │   │   ├── _tables.css
    │   │   ├── _modals.css
    │   │   ├── _alerts.css
    │   │   └── _navigation.css
    │   │
    │   ├── frontend/                    # Frontend area
    │   │   ├── _layout.css              # Frontend grid/structure
    │   │   ├── _header.css
    │   │   ├── _footer.css
    │   │   ├── _hero.css
    │   │   └── frontend.css             # Imports all frontend
    │   │
    │   └── admin/                       # Admin area
    │       ├── _layout.css              # Admin grid/structure
    │       ├── _sidebar.css
    │       ├── _header.css
    │       ├── _dashboard.css
    │       └── admin.css                # Imports all admin
    │
    └── js/
        ├── base/
        │   ├── app.js                   # Core utilities
        │   └── components/
        │       ├── modal.js
        │       ├── dropdown.js
        │       └── form-validation.js
        │
        ├── frontend/
        │   └── frontend.js              # Frontend-specific
        │
        └── admin/
            ├── admin.js                 # Admin-specific
            └── components/
                ├── data-table.js
                └── rich-editor.js

pub/
└── assets/
    └── dist/                            # Production bundles
        ├── frontend.min.css             # Frontend bundle
        ├── frontend.min.js
        ├── admin.min.css                # Admin bundle
        ├── admin.min.js
        ├── critical-frontend.css        # Inlined critical CSS
        └── critical-admin.css
```

---

## 🔧 Layout Implementation

### Base Layout (Define ONCE)

```php
<?php
// app/resources/views/layouts/base.php
declare(strict_types=1);
/**
 * ROOT LAYOUT - The only place <html>, <head>, <body> are defined
 * 
 * Both Frontend and Admin inherit from this.
 * DO NOT duplicate this structure anywhere else.
 */

use App\Core\View\View;
?>
<!DOCTYPE html>
<html lang="<?= e($locale ?? 'en') ?>" dir="<?= e($dir ?? 'ltr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <?php // SEO & Meta - Set by controllers ?>
    <?= View::renderBlock('meta') ?>
    
    <?php // Preconnects for external resources ?>
    <?= View::renderBlock('preconnects') ?>
    
    <?php // Critical CSS (inlined for instant FCP) ?>
    <?= View::renderBlock('critical_css') ?>
    
    <?php // Area CSS bundle (frontend.css or admin.css) ?>
    <?= View::renderBlock('area_css') ?>
    
    <?php // Module-specific CSS injection ?>
    <?= View::renderBlock('head_css') ?>
    
    <?php // Head scripts (external APIs, analytics) ?>
    <?= View::renderBlock('head_scripts') ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>" <?= $bodyAttributes ?? '' ?>>
    <?php // Skip link for accessibility ?>
    <a href="#main-content" class="sr-only focus:not-sr-only">Skip to main content</a>
    
    <?php // Area-specific chrome (header, sidebar, etc.) ?>
    <?= View::renderBlock('body_start') ?>
    
    <?php // Main wrapper - structure defined by area layout ?>
    <?= View::renderBlock('wrapper') ?>
    
    <?php // Area-specific footer ?>
    <?= View::renderBlock('body_end') ?>
    
    <?php // Area JS bundle ?>
    <?= View::renderBlock('area_js') ?>
    
    <?php // Module-specific JS injection ?>
    <?= View::renderBlock('footer_js') ?>
    
    <?php // Inline scripts (e.g., page-specific data) ?>
    <?= View::renderBlock('inline_scripts') ?>
</body>
</html>
```

---

### Frontend Area Layout

```php
<?php
// app/resources/views/layouts/frontend/app.php
declare(strict_types=1);
/**
 * FRONTEND AREA LAYOUT
 * 
 * Defines frontend chrome: public header, navigation, footer
 * Extends base.php - DO NOT redefine <html>, <head>, <body>
 */

use App\Core\View\View;
use App\Base\Helpers\Assets;

// Set area-specific blocks
View::startBlock('area_css');
echo Assets::css('frontend'); // Loads frontend.min.css
View::endBlock();

View::startBlock('critical_css');
echo Assets::inlineCritical('frontend');
View::endBlock();

View::startBlock('area_js');
echo Assets::js('frontend'); // Loads frontend.min.js
View::endBlock();

// Frontend chrome
View::startBlock('body_start');
?>
<?= View::include('blocks/frontend/header') ?>
<?= View::include('blocks/base/flash-messages') ?>
<?php
View::endBlock();

View::startBlock('body_end');
echo View::include('blocks/frontend/footer');
View::endBlock();

// Main wrapper with layout structure
View::startBlock('wrapper');
?>
<div class="frontend-wrapper">
    <main id="main-content" class="main-content" role="main">
        <?= View::renderBlock('content') ?>
    </main>
</div>
<?php
View::endBlock();

// Extend base layout
include dirname(__DIR__) . '/base.php';
```

---

### Frontend Layout Variations

```php
<?php
// app/resources/views/layouts/frontend/one-column.php
declare(strict_types=1);
/**
 * ONE COLUMN LAYOUT (Frontend)
 * 
 * Simple centered content column.
 * Use for: Home, About, Contact, Legal pages
 */

use App\Core\View\View;

// Wrap page content in container
View::startBlock('content');
?>
<div class="container">
    <div class="content-column">
        <?= View::renderBlock('page_content') ?>
    </div>
</div>
<?php
View::endBlock();

// Extend frontend area layout
include __DIR__ . '/app.php';
```

```php
<?php
// app/resources/views/layouts/frontend/two-column.php
declare(strict_types=1);
/**
 * TWO COLUMN LAYOUT (Frontend)
 * 
 * Main content + sidebar.
 * Use for: Blog listing, category pages
 */

use App\Core\View\View;

View::startBlock('content');
?>
<div class="container">
    <div class="layout-two-column">
        <div class="content-column">
            <?= View::renderBlock('page_content') ?>
        </div>
        <aside class="sidebar" role="complementary">
            <?= View::renderBlock('sidebar') ?>
        </aside>
    </div>
</div>
<?php
View::endBlock();

include __DIR__ . '/app.php';
```

```php
<?php
// app/resources/views/layouts/frontend/blog.php
declare(strict_types=1);
/**
 * BLOG POST LAYOUT (Frontend)
 * 
 * Optimized for long-form reading.
 * Use for: Single blog posts, articles
 */

use App\Core\View\View;

View::startBlock('content');
?>
<article class="blog-post-layout">
    <div class="reading-progress" data-reading-progress></div>
    
    <header class="post-header">
        <div class="container container-narrow">
            <?= View::renderBlock('post_header') ?>
        </div>
    </header>
    
    <div class="post-body">
        <div class="container container-narrow">
            <?= View::renderBlock('page_content') ?>
        </div>
    </div>
    
    <footer class="post-footer">
        <div class="container container-narrow">
            <?= View::renderBlock('post_footer') ?>
        </div>
    </footer>
</article>
<?php
View::endBlock();

include __DIR__ . '/app.php';
```

---

### Admin Area Layout

```php
<?php
// app/resources/views/layouts/admin/app.php
declare(strict_types=1);
/**
 * ADMIN AREA LAYOUT
 * 
 * Defines admin chrome: sidebar, top bar, notifications
 * Extends base.php - DO NOT redefine <html>, <head>, <body>
 */

use App\Core\View\View;
use App\Base\Helpers\Assets;

// Admin-specific meta
View::startBlock('meta');
?>
<meta name="robots" content="noindex, nofollow">
<?= View::include('blocks/base/html-head') ?>
<?php
View::endBlock();

// Admin CSS/JS
View::startBlock('area_css');
echo Assets::css('admin');
View::endBlock();

View::startBlock('critical_css');
echo Assets::inlineCritical('admin');
View::endBlock();

View::startBlock('area_js');
echo Assets::js('admin');
View::endBlock();

// Admin chrome - sidebar + header
View::startBlock('wrapper');
?>
<div class="admin-wrapper">
    <?= View::include('blocks/admin/sidebar') ?>
    
    <div class="admin-main">
        <?= View::include('blocks/admin/header') ?>
        
        <main id="main-content" class="admin-content" role="main">
            <?= View::include('blocks/base/flash-messages') ?>
            <?= View::renderBlock('content') ?>
        </main>
    </div>
</div>
<?php
View::endBlock();

// Extend base layout
include dirname(__DIR__) . '/base.php';
```

---

### Admin Layout Variations

```php
<?php
// app/resources/views/layouts/admin/dashboard.php
declare(strict_types=1);
/**
 * DASHBOARD LAYOUT (Admin)
 * 
 * Grid-based dashboard with widgets/cards.
 */

use App\Core\View\View;

View::startBlock('content');
?>
<div class="dashboard-layout">
    <?php if (View::hasBlock('page_header')): ?>
    <header class="page-header">
        <?= View::renderBlock('page_header') ?>
    </header>
    <?php endif; ?>
    
    <div class="dashboard-grid">
        <?= View::renderBlock('page_content') ?>
    </div>
</div>
<?php
View::endBlock();

include __DIR__ . '/app.php';
```

```php
<?php
// app/resources/views/layouts/admin/list.php
declare(strict_types=1);
/**
 * LIST LAYOUT (Admin)
 * 
 * Table-based listing with filters, bulk actions.
 * Use for: Post list, User list, Order list
 */

use App\Core\View\View;

View::startBlock('content');
?>
<div class="list-layout">
    <header class="page-header">
        <?= View::include('blocks/admin/page-header', [
            'title' => $pageTitle ?? 'Listing',
            'actions' => $pageActions ?? []
        ]) ?>
    </header>
    
    <?php if (View::hasBlock('filters')): ?>
    <div class="list-filters">
        <?= View::renderBlock('filters') ?>
    </div>
    <?php endif; ?>
    
    <div class="list-content">
        <?= View::renderBlock('page_content') ?>
    </div>
    
    <?php if (View::hasBlock('pagination')): ?>
    <footer class="list-footer">
        <?= View::renderBlock('pagination') ?>
    </footer>
    <?php endif; ?>
</div>
<?php
View::endBlock();

include __DIR__ . '/app.php';
```

```php
<?php
// app/resources/views/layouts/admin/form.php
declare(strict_types=1);
/**
 * FORM LAYOUT (Admin)
 * 
 * Full-width form with optional sidebar.
 * Use for: Create/Edit pages
 */

use App\Core\View\View;

View::startBlock('content');
?>
<div class="form-layout">
    <header class="page-header">
        <?= View::include('blocks/admin/page-header', [
            'title' => $pageTitle ?? 'Form',
            'backUrl' => $backUrl ?? null
        ]) ?>
    </header>
    
    <form method="POST" 
          action="<?= e($formAction ?? '') ?>" 
          class="form-container"
          <?= $formAttributes ?? '' ?>>
        
        <?= csrf_field() ?>
        <?= method_field($formMethod ?? 'POST') ?>
        
        <div class="form-body <?= View::hasBlock('form_sidebar') ? 'has-sidebar' : '' ?>">
            <div class="form-main">
                <?= View::renderBlock('page_content') ?>
            </div>
            
            <?php if (View::hasBlock('form_sidebar')): ?>
            <aside class="form-sidebar">
                <?= View::renderBlock('form_sidebar') ?>
            </aside>
            <?php endif; ?>
        </div>
        
        <footer class="form-footer">
            <?= View::renderBlock('form_actions', View::include('components/form/default-actions')) ?>
        </footer>
    </form>
</div>
<?php
View::endBlock();

include __DIR__ . '/app.php';
```

---

## 🎨 CSS Architecture

### Design Tokens (Single Source of Truth)

```css
/* app/resources/css/base/_variables.css */

:root {
    /* ==================== Color Palette ==================== */
    
    /* Brand Colors */
    --color-primary: #9d4edd;
    --color-primary-light: #c77dff;
    --color-primary-dark: #7b2cbf;
    --color-primary-50: rgba(157, 78, 221, 0.1);
    --color-primary-100: rgba(157, 78, 221, 0.2);
    
    --color-secondary: #a855f7;
    --color-secondary-light: #c084fc;
    --color-secondary-dark: #7e22ce;
    
    /* Semantic Colors */
    --color-success: #10b981;
    --color-success-light: #34d399;
    --color-success-dark: #059669;
    
    --color-danger: #ef4444;
    --color-danger-light: #f87171;
    --color-danger-dark: #dc2626;
    
    --color-warning: #f59e0b;
    --color-warning-light: #fbbf24;
    --color-warning-dark: #d97706;
    
    --color-info: #06b6d4;
    --color-info-light: #22d3ee;
    --color-info-dark: #0891b2;
    
    /* Neutral Palette (Dark Theme) */
    --color-gray-50: #fafafa;
    --color-gray-100: #f4f4f5;
    --color-gray-200: #e4e4e7;
    --color-gray-300: #d4d4d8;
    --color-gray-400: #a1a1aa;
    --color-gray-500: #71717a;
    --color-gray-600: #52525b;
    --color-gray-700: #3f3f46;
    --color-gray-800: #27272a;
    --color-gray-900: #18181b;
    --color-gray-950: #09090b;
    
    /* Background Colors */
    --color-bg: #0a0a0a;
    --color-bg-secondary: #141414;
    --color-bg-tertiary: #1a1a1a;
    --color-bg-elevated: #1f1f1f;
    --color-bg-hover: #252525;
    
    /* Text Colors */
    --color-text: #f5f5f5;
    --color-text-secondary: #b8b8b8;
    --color-text-muted: #9a9a9a;
    --color-text-inverse: #0a0a0a;
    
    /* Border Colors */
    --color-border: #2a2a2a;
    --color-border-light: #333333;
    --color-border-focus: var(--color-primary);
    
    /* ==================== Typography ==================== */
    
    --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
                 "Helvetica Neue", Arial, sans-serif;
    --font-mono: "SF Mono", SFMono-Regular, Consolas, "Liberation Mono", 
                 Menlo, Courier, monospace;
    
    /* Font Sizes (modular scale 1.25) */
    --text-xs: 0.75rem;      /* 12px */
    --text-sm: 0.875rem;     /* 14px */
    --text-base: 1rem;       /* 16px */
    --text-lg: 1.125rem;     /* 18px */
    --text-xl: 1.25rem;      /* 20px */
    --text-2xl: 1.5rem;      /* 24px */
    --text-3xl: 1.875rem;    /* 30px */
    --text-4xl: 2.25rem;     /* 36px */
    --text-5xl: 3rem;        /* 48px */
    --text-6xl: 3.75rem;     /* 60px */
    
    /* Font Weights */
    --font-light: 300;
    --font-normal: 400;
    --font-medium: 500;
    --font-semibold: 600;
    --font-bold: 700;
    
    /* Line Heights */
    --leading-none: 1;
    --leading-tight: 1.25;
    --leading-snug: 1.375;
    --leading-normal: 1.5;
    --leading-relaxed: 1.625;
    --leading-loose: 2;
    
    /* ==================== Spacing ==================== */
    
    --space-0: 0;
    --space-1: 0.25rem;      /* 4px */
    --space-2: 0.5rem;       /* 8px */
    --space-3: 0.75rem;      /* 12px */
    --space-4: 1rem;         /* 16px */
    --space-5: 1.25rem;      /* 20px */
    --space-6: 1.5rem;       /* 24px */
    --space-8: 2rem;         /* 32px */
    --space-10: 2.5rem;      /* 40px */
    --space-12: 3rem;        /* 48px */
    --space-16: 4rem;        /* 64px */
    --space-20: 5rem;        /* 80px */
    --space-24: 6rem;        /* 96px */
    
    /* ==================== Layout ==================== */
    
    /* Container widths */
    --container-sm: 640px;
    --container-md: 768px;
    --container-lg: 1024px;
    --container-xl: 1280px;
    --container-2xl: 1536px;
    
    /* Content widths */
    --content-narrow: 680px;   /* Blog posts, articles */
    --content-default: 800px;  /* Standard content */
    --content-wide: 1200px;    /* Wide layouts */
    
    /* Border Radius */
    --radius-none: 0;
    --radius-sm: 0.25rem;     /* 4px */
    --radius-md: 0.375rem;    /* 6px */
    --radius-lg: 0.5rem;      /* 8px */
    --radius-xl: 0.75rem;     /* 12px */
    --radius-2xl: 1rem;       /* 16px */
    --radius-full: 9999px;
    
    /* ==================== Shadows ==================== */
    
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
    --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
    
    /* Glows (for dark theme accents) */
    --glow-primary: 0 0 20px rgba(157, 78, 221, 0.4);
    --glow-primary-lg: 0 0 40px rgba(157, 78, 221, 0.5);
    
    /* ==================== Transitions ==================== */
    
    --transition-fast: 150ms ease;
    --transition-base: 200ms ease;
    --transition-slow: 300ms ease;
    --transition-slower: 500ms ease;
    
    /* ==================== Z-Index Scale ==================== */
    
    --z-dropdown: 1000;
    --z-sticky: 1020;
    --z-fixed: 1030;
    --z-modal-backdrop: 1040;
    --z-modal: 1050;
    --z-popover: 1060;
    --z-tooltip: 1070;
    --z-toast: 1080;
    
    /* ==================== Admin-Specific ==================== */
    
    --admin-sidebar-width: 260px;
    --admin-sidebar-collapsed: 64px;
    --admin-header-height: 64px;
}

/* Light theme override (if needed in future) */
[data-theme="light"] {
    --color-bg: #ffffff;
    --color-bg-secondary: #f8f9fa;
    --color-bg-tertiary: #f1f3f5;
    --color-bg-elevated: #ffffff;
    --color-text: #1a1a1a;
    --color-text-secondary: #495057;
    --color-text-muted: #868e96;
    --color-border: #dee2e6;
    --color-border-light: #e9ecef;
}
```

---

### Component CSS (Define Once)

```css
/* app/resources/css/components/_buttons.css */

/* Base button */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    line-height: var(--leading-tight);
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-fast);
    white-space: nowrap;
}

.btn:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Variants */
.btn-primary {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.btn-primary:hover:not(:disabled) {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
}

.btn-secondary {
    background: var(--color-bg-elevated);
    color: var(--color-text);
    border-color: var(--color-border);
}

.btn-secondary:hover:not(:disabled) {
    background: var(--color-bg-hover);
    border-color: var(--color-border-light);
}

.btn-outline {
    background: transparent;
    color: var(--color-primary);
    border-color: var(--color-primary);
}

.btn-outline:hover:not(:disabled) {
    background: var(--color-primary);
    color: white;
}

.btn-ghost {
    background: transparent;
    color: var(--color-text-secondary);
    border-color: transparent;
}

.btn-ghost:hover:not(:disabled) {
    background: var(--color-bg-hover);
    color: var(--color-text);
}

.btn-danger {
    background: var(--color-danger);
    color: white;
    border-color: var(--color-danger);
}

.btn-danger:hover:not(:disabled) {
    background: var(--color-danger-dark);
    border-color: var(--color-danger-dark);
}

/* Sizes */
.btn-xs {
    padding: var(--space-1) var(--space-2);
    font-size: var(--text-xs);
}

.btn-sm {
    padding: var(--space-1) var(--space-3);
    font-size: var(--text-sm);
}

.btn-lg {
    padding: var(--space-3) var(--space-6);
    font-size: var(--text-base);
}

.btn-xl {
    padding: var(--space-4) var(--space-8);
    font-size: var(--text-lg);
}

/* Icon buttons */
.btn-icon {
    padding: var(--space-2);
    aspect-ratio: 1;
}

.btn-icon svg {
    width: 1.25em;
    height: 1.25em;
}

/* Full width */
.btn-block {
    width: 100%;
}

/* Button group */
.btn-group {
    display: inline-flex;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-radius: var(--radius-md) 0 0 var(--radius-md);
}

.btn-group .btn:last-child {
    border-radius: 0 var(--radius-md) var(--radius-md) 0;
}

.btn-group .btn:not(:last-child) {
    border-right-width: 0;
}
```

---

### Area-Specific CSS (Only What's Different)

```css
/* app/resources/css/frontend/frontend.css */

/* Import base */
@import '../base/_variables.css';
@import '../base/_reset.css';
@import '../base/_typography.css';

/* Import components */
@import '../components/_buttons.css';
@import '../components/_cards.css';
@import '../components/_forms.css';

/* Frontend-specific */
@import '_layout.css';
@import '_header.css';
@import '_footer.css';
@import '_hero.css';

/* Utilities last */
@import '../base/_utilities.css';
```

```css
/* app/resources/css/frontend/_layout.css */

/* Frontend wrapper */
.frontend-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.frontend-wrapper .main-content {
    flex: 1;
}

/* Container */
.container {
    width: 100%;
    max-width: var(--container-xl);
    margin: 0 auto;
    padding: 0 var(--space-4);
}

.container-narrow {
    max-width: var(--content-narrow);
}

/* Layout grids */
.layout-two-column {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-8);
}

@media (min-width: 1024px) {
    .layout-two-column {
        grid-template-columns: 1fr 320px;
    }
}
```

```css
/* app/resources/css/admin/admin.css */

/* Import base */
@import '../base/_variables.css';
@import '../base/_reset.css';
@import '../base/_typography.css';

/* Import components */
@import '../components/_buttons.css';
@import '../components/_cards.css';
@import '../components/_forms.css';
@import '../components/_tables.css';

/* Admin-specific */
@import '_layout.css';
@import '_sidebar.css';
@import '_header.css';
@import '_dashboard.css';

/* Utilities last */
@import '../base/_utilities.css';
```

```css
/* app/resources/css/admin/_layout.css */

/* Admin wrapper - sidebar + main */
.admin-wrapper {
    display: flex;
    min-height: 100vh;
}

.admin-main {
    flex: 1;
    margin-left: var(--admin-sidebar-width);
    display: flex;
    flex-direction: column;
    transition: margin var(--transition-base);
}

.admin-content {
    flex: 1;
    padding: var(--space-6);
}

/* Collapsed sidebar state */
.admin-wrapper.sidebar-collapsed .admin-main {
    margin-left: var(--admin-sidebar-collapsed);
}

/* Page layouts */
.dashboard-layout,
.list-layout,
.form-layout {
    max-width: var(--container-xl);
}

.page-header {
    margin-bottom: var(--space-6);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
}

.page-header h1 {
    font-size: var(--text-2xl);
    font-weight: var(--font-semibold);
    margin: 0;
}

/* Dashboard grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-6);
}

/* Form layout */
.form-body {
    display: flex;
    gap: var(--space-8);
}

.form-body.has-sidebar .form-main {
    flex: 1;
}

.form-body .form-sidebar {
    width: 320px;
    flex-shrink: 0;
}

.form-footer {
    margin-top: var(--space-6);
    padding-top: var(--space-6);
    border-top: 1px solid var(--color-border);
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}
```

---

## 🔌 View Engine Implementation

### Core View Class

```php
<?php
// app/Core/View/View.php
declare(strict_types=1);

namespace App\Core\View;

use App\Core\Contracts\View\ViewInterface;

final class View implements ViewInterface
{
    private array $blocks = [];
    private array $blockStack = [];
    private ?string $layout = null;
    private ?string $area = null;
    private array $shared = [];
    
    private string $basePath;
    private string $modulePath;
    
    public function __construct(string $basePath, string $modulePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->modulePath = rtrim($modulePath, '/');
    }
    
    /**
     * Set the area (frontend/admin)
     */
    public function area(string $area): self
    {
        $this->area = $area;
        return $this;
    }
    
    /**
     * Set layout for this render
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Share data globally
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }
    
    /**
     * Start capturing a block
     */
    public function startBlock(string $name): void
    {
        $this->blockStack[] = $name;
        ob_start();
    }
    
    /**
     * End block capture
     */
    public function endBlock(): void
    {
        if (empty($this->blockStack)) {
            throw new \RuntimeException('No block started');
        }
        
        $content = ob_get_clean();
        $name = array_pop($this->blockStack);
        
        $this->blocks[$name][] = $content;
    }
    
    /**
     * Append to a block
     */
    public function appendBlock(string $name, string $content): void
    {
        $this->blocks[$name][] = $content;
    }
    
    /**
     * Prepend to a block
     */
    public function prependBlock(string $name, string $content): void
    {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = [];
        }
        array_unshift($this->blocks[$name], $content);
    }
    
    /**
     * Replace a block entirely
     */
    public function setBlock(string $name, string $content): void
    {
        $this->blocks[$name] = [$content];
    }
    
    /**
     * Render a block
     */
    public function renderBlock(string $name, string $default = ''): string
    {
        if (!isset($this->blocks[$name]) || empty($this->blocks[$name])) {
            return $default;
        }
        return implode("\n", $this->blocks[$name]);
    }
    
    /**
     * Check if block has content
     */
    public function hasBlock(string $name): bool
    {
        return !empty($this->blocks[$name]);
    }
    
    /**
     * Include a partial
     */
    public function include(string $template, array $data = []): string
    {
        return $this->renderTemplate($template, $data);
    }
    
    /**
     * Render component
     */
    public function component(string $name, array $props = []): string
    {
        return $this->renderTemplate("components/{$name}", $props);
    }
    
    /**
     * Main render method
     */
    public function render(string $template, array $data = []): string
    {
        // Render the template
        $content = $this->renderTemplate($template, $data);
        
        // Store content in block
        $this->blocks['page_content'][] = $content;
        
        // Apply layout if set
        if ($this->layout !== null) {
            $layoutPath = $this->resolveLayoutPath($this->layout);
            $this->layout = null; // Reset
            
            $content = $this->renderFile($layoutPath, $data);
        }
        
        // Reset for next request
        $this->reset();
        
        return $content;
    }
    
    /**
     * Render a template file
     */
    private function renderTemplate(string $template, array $data): string
    {
        $path = $this->resolveTemplatePath($template);
        return $this->renderFile($path, $data);
    }
    
    /**
     * Render a file with data
     */
    private function renderFile(string $path, array $data): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("View not found: {$path}");
        }
        
        // Merge shared data
        $data = array_merge($this->shared, $data);
        
        // Make view available in templates
        $data['view'] = $this;
        
        // Extract data
        extract($data, EXTR_SKIP);
        
        ob_start();
        try {
            include $path;
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
    
    /**
     * Resolve template path
     * 
     * Formats:
     * - "Module::template" → app/Modules/Module/View/{area}/templates/template.php
     * - "components/card" → app/resources/views/components/card.php
     * - "blocks/header" → app/resources/views/blocks/header.php
     */
    private function resolveTemplatePath(string $template): string
    {
        // Module template
        if (str_contains($template, '::')) {
            [$module, $path] = explode('::', $template, 2);
            $area = $this->area ?? 'frontend';
            return "{$this->modulePath}/{$module}/View/{$area}/templates/{$path}.php";
        }
        
        // Base template
        return "{$this->basePath}/views/{$template}.php";
    }
    
    /**
     * Resolve layout path
     * 
     * Format: "one-column" → layouts/{area}/one-column.php
     */
    private function resolveLayoutPath(string $layout): string
    {
        $area = $this->area ?? 'frontend';
        return "{$this->basePath}/views/layouts/{$area}/{$layout}.php";
    }
    
    /**
     * Reset state
     */
    public function reset(): void
    {
        $this->blocks = [];
        $this->blockStack = [];
        $this->area = null;
    }
}
```

---

## 📝 Module Template Examples

### Frontend Module Template

```php
<?php
// app/Modules/Home/View/frontend/templates/home.php
declare(strict_types=1);
/**
 * Home Page Template
 * 
 * Layout: one-column (inherited from frontend area)
 */

use App\Base\Helpers\Meta;

// Set page meta
Meta::set('title', 'Infinri | Affordable Websites for Small Businesses');
Meta::set('description', 'Fast delivery, transparent pricing, no contracts.');

// Module-specific CSS (only what's different from base)
$view->startBlock('head_css');
?>
<link rel="stylesheet" href="/assets/modules/home/css/home.css">
<?php $view->endBlock(); ?>

<?php // Page content - injected into layout's page_content block ?>
<section class="hero">
    <div class="container">
        <h1 class="hero-title">Affordable Websites for Real Businesses</h1>
        <p class="hero-subtitle">Fast delivery, transparent pricing, no contracts.</p>
        <div class="hero-buttons">
            <a href="/services" class="btn btn-primary btn-lg">View Services</a>
            <a href="/contact" class="btn btn-outline btn-lg">Get Started</a>
        </div>
    </div>
</section>

<section class="services-section">
    <div class="container">
        <h2 class="section-title">How We Help</h2>
        
        <div class="services-grid">
            <?= $view->component('card', [
                'title' => 'Monthly Plans',
                'price' => 'From $10/mo',
                'description' => 'Ongoing support with predictable pricing.',
                'badge' => 'Popular'
            ]) ?>
            
            <?= $view->component('card', [
                'title' => 'Website Packages',
                'price' => '$10 - $50',
                'description' => 'Fast, affordable websites using proven templates.'
            ]) ?>
        </div>
    </div>
</section>
```

### Admin Module Template

```php
<?php
// app/Modules/Blog/View/admin/templates/list.php
declare(strict_types=1);
/**
 * Blog Posts Admin List
 * 
 * Layout: list (admin area)
 */

// Page header
$view->startBlock('page_header');
?>
<h1>Blog Posts</h1>
<div class="page-actions">
    <a href="/admin/blog/create" class="btn btn-primary">
        <svg class="icon"><!-- plus icon --></svg>
        New Post
    </a>
</div>
<?php $view->endBlock(); ?>

<?php // Filters ?>
<?php $view->startBlock('filters'); ?>
<form class="filters-form" method="GET">
    <input type="search" name="q" placeholder="Search posts..." class="form-input">
    <select name="status" class="form-select">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="published">Published</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
</form>
<?php $view->endBlock(); ?>

<?php // Main content - data table ?>
<table class="data-table">
    <thead>
        <tr>
            <th><input type="checkbox" data-select-all></th>
            <th>Title</th>
            <th>Author</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($posts as $post): ?>
        <tr>
            <td><input type="checkbox" name="ids[]" value="<?= $post->id ?>"></td>
            <td><?= e($post->title) ?></td>
            <td><?= e($post->author->name) ?></td>
            <td><?= $view->component('badge', ['status' => $post->status]) ?></td>
            <td><?= $post->created_at->format('M d, Y') ?></td>
            <td>
                <a href="/admin/blog/<?= $post->id ?>/edit" class="btn btn-ghost btn-sm">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php // Pagination ?>
<?php $view->startBlock('pagination'); ?>
<?= $view->component('pagination', ['paginator' => $posts]) ?>
<?php $view->endBlock(); ?>
```

---

## 🏗️ Build Pipeline

### CSS Build (PostCSS + cssnano)

```javascript
// build.js
const buildCSS = async (area) => {
    const input = `app/resources/css/${area}/${area}.css`;
    const output = `pub/assets/dist/${area}.min.css`;
    
    // Process with PostCSS
    // 1. @import inlining
    // 2. Autoprefixer
    // 3. cssnano minification
    
    // Generate critical CSS extract
    // 1. Above-fold selectors
    // 2. Inline in <head>
};

// Build both areas
await buildCSS('frontend');
await buildCSS('admin');
```

### Production Output

```
pub/assets/dist/
├── frontend.min.css        # ~15KB gzipped (all frontend CSS)
├── frontend.min.js         # ~8KB gzipped
├── admin.min.css           # ~18KB gzipped (all admin CSS)
├── admin.min.js            # ~12KB gzipped
├── critical-frontend.css   # ~3KB (inlined in <head>)
└── critical-admin.css      # ~2KB (inlined in <head>)
```

---

## 📊 Summary: Why This Puts You Ahead

| Feature | Traditional | This Architecture | Benefit |
|---------|-------------|-------------------|---------|
| **HTML Structure** | Repeated in each layout | Defined ONCE in `base.php` | Zero duplication |
| **Area Chrome** | Mixed everywhere | Frontend/Admin `app.php` | Clear separation |
| **Components** | Copy-paste | `components/` directory | Single source |
| **CSS Variables** | Scattered | `_variables.css` tokens | Theme from one file |
| **Module CSS** | Full stylesheets | Overrides only | Minimal LOC |
| **Block Injection** | Not supported | Full Magento-style | Modules don't touch layouts |
| **Build Output** | Many files | 2 bundles per area | Optimal HTTP |
| **Critical CSS** | Manual | Auto-extracted | Instant FCP |

### Lines of Code Reduction

- **Layouts**: 5 files instead of 20+ (inheritance)
- **Module CSS**: ~20-50 lines vs ~200+ (overrides only)
- **Components**: Shared across 50+ modules
- **Total estimate**: 40-50% less view code

---

## 🚀 Implementation Order

1. **Create `app/Core/View/`** - View engine with blocks (~300 LOC)
2. **Create `app/resources/`** - Directory structure
3. **Create `base.php`** - Root layout
4. **Create `frontend/app.php`** and `admin/app.php`** - Area layouts
5. **Create CSS tokens** - `_variables.css`
6. **Create component CSS** - `_buttons.css`, `_cards.css`, etc.
7. **Migrate first module** - Home to new structure
8. **Build pipeline** - CSS/JS bundling

Should I proceed with implementing this theming architecture?
