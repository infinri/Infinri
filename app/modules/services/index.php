<?php
declare(strict_types=1);
/**
 * Services Module Controller
 *
 * Loads services page template and assets
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\Env;

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Services — Infinri',
    'description' => 'Professional PHP development services: web applications, API development, consulting',
    'keywords' => 'services, PHP development, web applications, API, consulting',
    'og:title' => 'Development Services - Professional PHP Developer',
    'twitter:title' => 'Development Services'
]);

// Load services-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/services/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/services.css")) {
        Assets::addCss("{$assetBase}/css/services.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/services.js")) {
        Assets::addJs("{$assetBase}/js/services.js");
    }
}

// Load template
require __DIR__ . '/view/frontend/templates/services.php';
