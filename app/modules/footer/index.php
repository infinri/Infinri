<?php
declare(strict_types=1);
/**
 * Footer Module Controller
 *
 * Loads footer template and assets
 */

use App\Base\Helpers\Assets;
use App\Helpers\Env;

// Load footer-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/footer/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/footer.css")) {
        Assets::addCss("{$assetBase}/css/footer.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/footer.js")) {
        Assets::addJs("{$assetBase}/js/footer.js");
    }
}

// Load template
require __DIR__ . '/view/frontend/templates/footer.php';
