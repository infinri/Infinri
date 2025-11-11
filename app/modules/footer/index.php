<?php
declare(strict_types=1);
/**
 * Footer Module Controller
 *
 * Loads footer template and assets
 */

use App\Base\Helpers\Assets;

// Load footer-specific assets
$modulePath = __DIR__;
$assetBase = '/assets/modules/footer/view/frontend';

// Load CSS if exists
if (file_exists("{$modulePath}/view/frontend/css/footer.css")) {
    Assets::addCss("{$assetBase}/css/footer.css");
}

// Load JS if exists
if (file_exists("{$modulePath}/view/frontend/js/footer.js")) {
    Assets::addJs("{$assetBase}/js/footer.js");
}

// Load template
require __DIR__ . '/view/frontend/templates/footer.php';
