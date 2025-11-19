<?php
declare(strict_types=1);
/**
 * Contact Module Controller
 *
 * Handles contact form display and submission
 */

use App\Base\Helpers\{Meta, Assets, ReCaptcha};
use App\Helpers\Session;

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

// Load reCAPTCHA v3 script in head (only on contact page for performance)
if (ReCaptcha::isEnabled() && !empty(ReCaptcha::getSiteKey())) {
    Assets::addHeadScript(
        'https://www.google.com/recaptcha/api.js?render=' . ReCaptcha::getSiteKey()
    );
}

// Generate CSRF token for the form
$csrf = Session::csrf();

// Load template
require __DIR__ . '/view/frontend/templates/contact.php';
