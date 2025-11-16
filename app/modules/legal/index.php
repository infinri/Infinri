<?php
declare(strict_types=1);
/**
 * Legal Module Controller
 * 
 * Handles all legal documentation pages:
 * /terms, /privacy, /cookies, /disclaimer, /refund
 */

use App\Base\Helpers\Assets;

// Determine which legal document to show from URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/terms';
$path = parse_url($requestUri, PHP_URL_PATH);
$page = basename($path);

// Map routes to template files
$legalPages = [
    'terms' => 'Terms & Conditions',
    'privacy' => 'Privacy Policy',
    'cookies' => 'Cookie Policy',
    'disclaimer' => 'Portfolio Disclaimer',
    'refund' => 'Refund & Cancellation Policy'
];

// Validate page exists
if (!array_key_exists($page, $legalPages)) {
    http_response_code(404);
    $page = 'terms'; // fallback
}

$pageTitle = $legalPages[$page];
$lastUpdated = date('F j, Y'); // Can be customized per document

// Load legal-specific assets
$modulePath = __DIR__;
$assetBase = '/assets/modules/legal/view/frontend';

if (file_exists("{$modulePath}/view/frontend/css/legal.css")) {
    Assets::addCss("{$assetBase}/css/legal.css");
}

if (file_exists("{$modulePath}/view/frontend/js/legal.js")) {
    Assets::addJs("{$assetBase}/js/legal.js");
}

// Load template
require __DIR__ . "/view/frontend/templates/{$page}.php";
