<?php
declare(strict_types=1);
/**
 * Home Module Controller
 *
 * Loads home page template and assets
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\Env;

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Infinri — Affordable Web Development for Small Businesses',
    'description' => 'Website development, hosting, and maintenance starting at $10. From quick templates to monthly support plans — transparent pricing, fast delivery.',
    'keywords' => 'web development, small business websites, affordable hosting, monthly support, PHP development, custom websites',
    'og:title' => 'Infinri — Affordable Web Development for Small Businesses',
    'og:description' => 'Websites starting at $10. Monthly support from $10/mo. Transparent pricing, fast delivery.',
    'twitter:title' => 'Infinri — Affordable Web Development'
]);

// Load home-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/home/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/home.css")) {
        Assets::addCss("{$assetBase}/css/home.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/home.js")) {
        Assets::addJs("{$assetBase}/js/home.js");
    }
}

// Load template
require __DIR__ . '/view/frontend/templates/home.php';
