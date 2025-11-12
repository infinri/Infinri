<?php
declare(strict_types=1);
/**
 * Contact Module Controller
 *
 * Handles contact form display and submission
 */

use App\Base\Helpers\{Meta, Assets};
// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Contact - Infinri',
    'description' => 'Get in touch for project inquiries, consulting, or collaboration opportunities',
    'keywords' => 'contact, hire, consulting, collaboration',
    'og:title' => 'Contact Me - Professional PHP Developer',
    'twitter:title' => 'Contact Me'
]);

// Load contact-specific assets
$modulePath = __DIR__;
$assetBase = '/assets/modules/contact/view/frontend';

// Load CSS if exists
if (file_exists("{$modulePath}/view/frontend/css/contact.css")) {
    Assets::addCss("{$assetBase}/css/contact.css");
}

// Load JS if exists
if (file_exists("{$modulePath}/view/frontend/js/contact.js")) {
    Assets::addJs("{$assetBase}/js/contact.js");
}

// Load template
require __DIR__ . '/view/frontend/templates/contact.php';
