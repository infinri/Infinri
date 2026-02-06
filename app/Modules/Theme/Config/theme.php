<?php declare(strict_types=1);

/**
 * Theme Configuration
 * 
 * Infinri brand settings - colors, typography, assets.
 */

return [
    'name' => 'Infinri',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Brand Colors
    |--------------------------------------------------------------------------
    | Primary purple palette with dark backgrounds.
    | These values match CSS variables in _variables.css
    */
    'colors' => [
        'primary' => '#9d4edd',
        'primary_light' => '#c77dff',
        'primary_dark' => '#7b2cbf',
        'bg' => '#0a0a0a',
        'bg_secondary' => '#141414',
        'text' => '#f5f5f5',
        'text_muted' => '#9a9a9a',
    ],

    /*
    |--------------------------------------------------------------------------
    | Typography
    |--------------------------------------------------------------------------
    */
    'fonts' => [
        'family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        'mono' => '"SF Mono", Consolas, "Liberation Mono", Menlo, monospace',
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    */
    'assets' => [
        'logo' => '/images/logo.svg',
        'logo_png' => '/images/logo.png',
        'favicon' => '/favicon.png',
        'og_image' => '/images/og-image.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Theme
    |--------------------------------------------------------------------------
    | GitKraken-inspired dark neon aesthetic
    */
    'admin' => [
        'style' => 'dark-neon',
        'sidebar_width' => '260px',
        'header_height' => '56px',
    ],
];
