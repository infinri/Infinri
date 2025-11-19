<?php
declare(strict_types=1);
/**
 * About Module Controller
 *
 * Loads about page template and assets
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\Env;

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'About - Portfolio',
    'description' => 'Learn about my experience in modern PHP development, testing, and software architecture',
    'keywords' => 'about, PHP developer, experience, skills, software architecture',
    'og:title' => 'About - Professional PHP Developer with 98% Test Coverage',
    'twitter:title' => 'About Me - PHP Developer'
]);

// Load about-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/about/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/about.css")) {
        Assets::addCss("{$assetBase}/css/about.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/about.js")) {
        Assets::addJs("{$assetBase}/js/about.js");
    }
}

// Load template
require __DIR__ . '/view/frontend/templates/about.php';
