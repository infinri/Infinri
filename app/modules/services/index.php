<?php
declare(strict_types=1);
/**
 * Services Module Controller
 *
 * Loads services page template and assets
 */

use App\Base\Helpers\{Meta, Assets};

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Services - Portfolio',
    'description' => 'Professional PHP development services: web applications, API development, consulting',
    'keywords' => 'services, PHP development, web applications, API, consulting',
    'og:title' => 'Development Services - Professional PHP Developer',
    'twitter:title' => 'Development Services'
]);

// Load services-specific assets
$modulePath = __DIR__;
$assetBase = '/assets/modules/services/view/frontend';

// Load CSS if exists
if (file_exists("{$modulePath}/view/frontend/css/services.css")) {
    Assets::addCss("{$assetBase}/css/services.css");
}

// Load JS if exists
if (file_exists("{$modulePath}/view/frontend/js/services.js")) {
    Assets::addJs("{$assetBase}/js/services.js");
}

// Load template
require __DIR__ . '/view/frontend/templates/services.php';
