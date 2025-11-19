<?php
declare(strict_types=1);
/**
 * Contact Module Controller
 *
 * Handles contact form display and submission
 */

use App\Base\Helpers\{Meta, Assets, ReCaptcha};
use App\Helpers\Session;

// Handle POST requests (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit; // Must exit to prevent Router from wrapping JSON in HTML layout
}

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Contact - Infinri',
    'description' => 'Get in touch for project inquiries, consulting, or collaboration opportunities',
    'keywords' => 'contact, hire, consulting, collaboration',
    'og:title' => 'Contact Me - Professional PHP Developer',
    'twitter:title' => 'Contact Me'
]);

// Load contact-specific assets (development only - production uses bundles)
use App\Helpers\Env;

if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/contact/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/contact.css")) {
        Assets::addCss("{$assetBase}/css/contact.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/contact.js")) {
        Assets::addJs("{$assetBase}/js/contact.js");
    }
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
