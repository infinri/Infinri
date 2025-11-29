# SEO Module

Advanced SEO features that extend Core's MetaManager.

## Status: Planned

This module is planned but not yet implemented. Core's `MetaManager` handles basic meta tags. This module will add advanced SEO capabilities.

---

## Architecture

### Core vs SEO Module

| Feature | Core MetaManager | SEO Module |
|---------|:----------------:|:----------:|
| `<title>` tag | ✅ | - |
| Meta description | ✅ | Analysis |
| Meta keywords | ✅ | Suggestions |
| Open Graph | ✅ | Auto-populate |
| Twitter Cards | ✅ | Auto-populate |
| Canonical URL | ✅ | Auto-generate |
| Robots meta | ✅ | - |
| Favicon | ✅ | - |
| Sitemap.xml | - | ✅ |
| robots.txt | - | ✅ |
| JSON-LD Schema | - | ✅ |
| Breadcrumb Schema | - | ✅ |
| SEO Analysis | - | ✅ |
| Meta Preview | - | ✅ |

---

## Planned Structure

```
Seo/
├── module.json                    # Module manifest
├── SeoServiceProvider.php         # Service registration
├── Config/
│   └── seo.php                    # SEO configuration
├── Services/
│   ├── SitemapGenerator.php       # XML sitemap generation
│   ├── RobotsManager.php          # robots.txt management
│   ├── StructuredDataManager.php  # JSON-LD schema
│   ├── BreadcrumbBuilder.php      # Breadcrumb schema
│   └── SeoAnalyzer.php            # Content SEO analysis
├── Observers/
│   └── PageMetaObserver.php       # Auto-populate OG/Twitter from content
├── Console/
│   └── SitemapGenerateCommand.php # Generate sitemap CLI
└── view/
    └── frontend/
        └── templates/
            ├── sitemap.xml.php    # Sitemap template
            └── robots.txt.php     # Robots template
```

---

## Feature Details

### 1. Sitemap Generator

Automatically generates XML sitemap from:
- Static routes
- Database content (pages, blog posts, products)
- Module-registered URLs

```php
// Usage
$sitemap = app(SitemapGenerator::class);
$sitemap->addUrl('/about', priority: 0.8, changefreq: 'monthly');
$sitemap->addUrl('/blog/post-1', lastmod: '2024-01-15');
$sitemap->generate(); // Outputs XML
```

**Config (`config/seo.php`):**
```php
'sitemap' => [
    'enabled' => true,
    'cache_ttl' => 3600,
    'exclude' => ['/admin/*', '/api/*'],
    'default_priority' => 0.5,
    'default_changefreq' => 'weekly',
],
```

### 2. Robots.txt Manager

Dynamic robots.txt generation:

```php
$robots = app(RobotsManager::class);
$robots->allow('/');
$robots->disallow('/admin/');
$robots->disallow('/api/');
$robots->sitemap(url('/sitemap.xml'));
```

**Config:**
```php
'robots' => [
    'disallow' => ['/admin/', '/api/', '/tmp/'],
    'allow' => ['/'],
    'crawl_delay' => null,
],
```

### 3. Structured Data (JSON-LD)

Schema.org structured data for rich snippets:

```php
$schema = app(StructuredDataManager::class);

// Organization
$schema->setOrganization([
    'name' => 'Infinri',
    'url' => 'https://infinri.com',
    'logo' => 'https://infinri.com/logo.png',
]);

// Breadcrumbs
$schema->setBreadcrumbs([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Services', 'url' => '/services'],
    ['name' => 'Web Development'],
]);

// Article (for blog)
$schema->setArticle([
    'headline' => 'Post Title',
    'author' => 'Lucio Saldivar',
    'datePublished' => '2024-01-15',
    'image' => '/images/post.jpg',
]);

echo $schema->render(); // Outputs <script type="application/ld+json">
```

### 4. Auto Meta Population

Observer that automatically populates OG/Twitter from page content:

```php
// When a page is rendered, automatically:
// - Set og:title from <title>
// - Set og:description from meta description
// - Set og:url from current URL
// - Set og:image from first image in content (if not set)
```

### 5. SEO Analyzer (Admin Feature)

Analyzes content and provides suggestions:

```php
$analyzer = app(SeoAnalyzer::class);
$results = $analyzer->analyze($content, $meta);

// Returns:
[
    'score' => 85,
    'issues' => [
        ['type' => 'warning', 'message' => 'Meta description is too short (45 chars, recommended: 150-160)'],
        ['type' => 'info', 'message' => 'Consider adding more internal links'],
    ],
    'passed' => [
        'Title length is optimal',
        'Has meta description',
        'Has Open Graph tags',
    ],
]
```

---

## Integration with Core MetaManager

The SEO module **extends** Core's MetaManager, not replaces it.

```php
// In SeoServiceProvider::boot()
public function boot(): void
{
    // Listen for page render events
    $this->app->make('events')->listen('page.render', function ($page) {
        $meta = meta();
        
        // Auto-sync title/description to OG/Twitter
        $meta->sync();
        
        // Auto-set canonical if not set
        if (!$meta->get('canonical')) {
            $meta->setCanonical(request()->path());
        }
        
        // Add structured data
        $schema = $this->app->make(StructuredDataManager::class);
        $schema->setBreadcrumbsFromRoute();
    });
}
```

---

## Implementation Priority

1. **Phase 1** - Sitemap Generator (most SEO value)
2. **Phase 2** - Structured Data (rich snippets)
3. **Phase 3** - Robots.txt Manager
4. **Phase 4** - SEO Analyzer (admin feature)

---

## Dependencies

- Core's `MetaManager` (required)
- Core's Event system (for observers)
- Core's Cache (for sitemap caching)
- Database Module (for dynamic content URLs)

---

## Routes

```php
// Registered by SeoServiceProvider
GET /sitemap.xml    → SitemapController@index
GET /robots.txt     → RobotsController@index
```

---

## CLI Commands

```bash
# Generate sitemap
php bin/console seo:sitemap:generate

# Clear sitemap cache
php bin/console seo:sitemap:clear

# Analyze page SEO
php bin/console seo:analyze /about
```
