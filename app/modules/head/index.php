<?php
declare(strict_types=1);
/**
 * Header Module Controller
 *
 * Loads header/navigation template and assets
 */

use App\Base\Helpers\Assets;

// Load header-specific assets
$modulePath = __DIR__;
$assetBase = '/assets/modules/head/view/frontend';

// Load CSS if exists
if (file_exists("{$modulePath}/view/frontend/css/header.css")) {
    Assets::addCss("{$assetBase}/css/header.css");
}

// Load JS if exists
if (file_exists("{$modulePath}/view/frontend/js/header.js")) {
    Assets::addJs("{$assetBase}/js/header.js");
}

// Load template
require __DIR__ . '/view/frontend/templates/head.php';
