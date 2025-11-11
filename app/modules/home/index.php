<?php
declare(strict_types=1);
/**
 * Home Module Controller
 *
 * Loads home page template and assets
 */

use App\Base\Helpers\{Meta, Assets};

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Home - Portfolio',
    'description' => 'Professional portfolio showcasing modern PHP development with clean architecture',
    'keywords' => 'portfolio, PHP, web development, modern architecture',
    'og:title' => 'Professional PHP Developer Portfolio',
    'og:description' => 'Showcasing modern PHP development with 98% test coverage',
    'twitter:title' => 'Professional PHP Developer Portfolio'
]);

// Load home-specific assets
$modulePath = __DIR__;
$assetBase = '/assets/modules/home/view/frontend';

// Load CSS if exists
if (file_exists("{$modulePath}/view/frontend/css/home.css")) {
    Assets::addCss("{$assetBase}/css/home.css");
}

// Load JS if exists
if (file_exists("{$modulePath}/view/frontend/js/home.js")) {
    Assets::addJs("{$assetBase}/js/home.js");
}

// Load template
require __DIR__ . '/view/frontend/templates/home.php';
