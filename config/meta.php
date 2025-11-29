<?php
declare(strict_types=1);
/**
 * Meta Configuration
 * 
 * Site-wide default meta tags.
 * These are loaded by Core's MetaManager and can be overridden per-page.
 * 
 * @package Config
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Title Configuration
    |--------------------------------------------------------------------------
    |
    | The title suffix is appended to all page titles (e.g., "About | Site Name").
    | Set default_title for pages that don't specify their own title.
    |
    */
    'title_suffix' => ' | Infinri',
    'default_title' => 'Infinri',
    
    /*
    |--------------------------------------------------------------------------
    | Document Settings
    |--------------------------------------------------------------------------
    */
    'charset' => 'UTF-8',
    'viewport' => 'width=device-width, initial-scale=1.0',
    
    /*
    |--------------------------------------------------------------------------
    | Default Robots
    |--------------------------------------------------------------------------
    |
    | Default robots directive for all pages.
    | Individual pages can override with $meta->setRobots() or $meta->noIndex().
    |
    */
    'robots' => 'index, follow',
    
    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */
    'favicon' => '/favicon.png',
    
    /*
    |--------------------------------------------------------------------------
    | Default Meta Tags
    |--------------------------------------------------------------------------
    |
    | These defaults are used when pages don't specify their own values.
    | Supports standard meta, Open Graph (og:*), and Twitter Cards (twitter:*).
    |
    */
    'defaults' => [
        // Standard meta
        'description' => 'Website development, hosting, and maintenance starting at $10. Transparent pricing, fast delivery, no surprises.',
        'keywords' => 'web development, website design, small business websites, affordable hosting',
        'author' => 'Lucio Saldivar',
        
        // Open Graph
        'og:type' => 'website',
        'og:site_name' => 'Infinri',
        'og:image' => '/assets/images/og-image.jpg',
        
        // Twitter Cards
        'twitter:card' => 'summary_large_image',
        'twitter:image' => '/assets/images/og-image.jpg',
    ],
];
