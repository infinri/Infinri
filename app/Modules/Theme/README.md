# Theme Module (Infinri)

Site-specific theming for the Infinri brand.

## Status: Active

This module contains Infinri-specific theming that overrides Core's generic components.

### Completed
- ✅ `module.json` — Module manifest
- ✅ `ThemeServiceProvider.php` — Registers theme with Core
- ✅ `Config/theme.php` — Brand colors, fonts, asset paths
- ✅ `view/frontend/web/css/*` — Frontend theme CSS
- ✅ `view/frontend/web/js/theme.js` — Frontend interactions
- ✅ `view/admin/web/css/*` — Admin dark neon theme (GitKraken-inspired)
- ✅ `view/admin/web/js/admin.js` — Admin sidebar toggle

---

## Architecture

### Core vs Theme Module

| Layer | Location | Contains |
|-------|----------|----------|
| **Core Base** | `app/Core/View/view/base/` | Generic components (buttons, forms, grids) |
| **Core Frontend** | `app/Core/View/view/frontend/` | Generic layout structure |
| **Theme** | `app/Modules/Theme/` | Infinri colors, fonts, custom components |

### CSS Cascade

```
1. Core Base CSS        → Generic reset, variables, components
2. Core Frontend CSS    → Generic layout
3. Theme CSS            → Infinri overrides (colors, fonts, custom styles)
4. Module CSS           → Page-specific styles (home hero, contact form)
```

---

## Planned Structure

```
Theme/
├── module.json                    # Module manifest
├── ThemeServiceProvider.php       # Registers theme assets
├── Config/
│   └── theme.php                  # Theme configuration
└── view/
    └── frontend/
        ├── layout/
        │   └── default.php        # Layout update (inject theme blocks)
        ├── templates/
        │   ├── header.phtml       # Infinri header
        │   ├── footer.phtml       # Infinri footer
        │   └── components/
        │       ├── logo.phtml
        │       └── nav.phtml
        └── web/
            ├── css/
            │   ├── variables.css  # Infinri colors (purple palette)
            │   ├── typography.css # Infinri fonts
            │   ├── header.css     # Header styles
            │   ├── footer.css     # Footer styles
            │   └── theme.css      # Main theme overrides
            ├── js/
            │   ├── mobile-menu.js # Mobile navigation
            │   └── theme.js       # Theme interactions
            └── images/
                ├── logo.svg
                ├── og-image.jpg
                └── favicon.png
```

---

## Theme Configuration

**`config/theme.php`:**
```php
return [
    'name' => 'Infinri',
    'version' => '1.0.0',
    
    // Colors (CSS variable overrides)
    'colors' => [
        'primary' => '#9d4edd',
        'primary-light' => '#c77dff',
        'primary-dark' => '#7b2cbf',
        'bg-primary' => '#0a0a0a',
        'bg-secondary' => '#141414',
    ],
    
    // Typography
    'fonts' => [
        'base' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        'heading' => 'inherit',
    ],
    
    // Assets
    'logo' => '/assets/theme/images/logo.svg',
    'favicon' => '/favicon.png',
    'og_image' => '/assets/theme/images/og-image.jpg',
];
```

---

## ThemeServiceProvider

```php
<?php
namespace App\Modules\Theme;

use App\Core\Container\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register theme config
        $this->mergeConfigFrom(__DIR__ . '/Config/theme.php', 'theme');
    }
    
    public function boot(): void
    {
        // Register layout updates
        $this->loadLayoutsFrom(__DIR__ . '/view/frontend/layout');
        
        // Register theme assets (in dev mode)
        if (env('APP_ENV') !== 'production') {
            $assets = $this->app->make(\App\Core\View\Asset\AssetManager::class);
            $assets->addCss('Theme::css/variables.css');
            $assets->addCss('Theme::css/theme.css');
            $assets->addJs('Theme::js/theme.js');
        }
        
        // Apply theme colors to meta config
        $meta = $this->app->make(\App\Core\View\Meta\MetaManager::class);
        $meta->setFavicon(config('theme.favicon'));
    }
}
```

---

## Layout Updates

**`view/frontend/layout/default.php`:**
```php
<?php
// Layout update for default handle (all pages)
return [
    'header' => [
        'theme.header' => [
            'class' => \App\Modules\Theme\Block\Header::class,
            'template' => 'Theme::header',
        ],
    ],
    
    'footer' => [
        'theme.footer' => [
            'class' => \App\Modules\Theme\Block\Footer::class,
            'template' => 'Theme::footer',
        ],
    ],
    
    'head.css' => [
        'theme.css' => [
            'file' => 'Theme::css/theme.css',
        ],
    ],
    
    'body.js' => [
        'theme.js' => [
            'file' => 'Theme::js/theme.js',
        ],
    ],
];
```

---

## Infinri Brand Guidelines

### Colors

| Name | Hex | Usage |
|------|-----|-------|
| Primary | `#9d4edd` | Buttons, links, accents |
| Primary Light | `#c77dff` | Hover states |
| Primary Dark | `#7b2cbf` | Active states, dark buttons |
| Background | `#0a0a0a` | Main background |
| Surface | `#141414` | Cards, elevated surfaces |
| Text | `#f5f5f5` | Primary text |
| Text Muted | `#9a9a9a` | Secondary text |

### Typography

- **Base Font**: System fonts (fast loading)
- **Headings**: Same as base
- **Code**: Monospace stack

### Shadows & Glows

- **Purple Glow**: `0 0 20px rgba(157, 78, 221, 0.4)` - For featured elements
- **Elevated**: Dark shadows with subtle purple tint

---

## Dependencies

- Core's `AssetManager`
- Core's `LayoutRenderer`
- Core's `MetaManager`
- Core's Block system

---

## Notes

- Theme module is **site-specific** - another site would have a different theme module
- Core components remain **generic** and **reusable**
- Theme **overrides** Core, doesn't replace it
- CSS specificity: Theme CSS loads after Core CSS, so it wins
