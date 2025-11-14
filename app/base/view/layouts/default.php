<?php
declare(strict_types=1);
/**
 * Default Layout Template
 *
 * Core HTML structure for all pages
 * Head module injects header/hero, content injects into main, footer module injects at bottom
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\View;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    // Render all meta tags (charset, viewport, title, description, OG, Twitter)
    echo Meta::render();

    // Pre-load head and footer module assets before rendering CSS
    $headPath = dirname(__DIR__, 3) . '/modules/head/index.php';
    $footerPath = dirname(__DIR__, 3) . '/modules/footer/index.php';
    
    // Load head module assets (just the controller part that adds assets)
    if (file_exists($headPath)) {
        // Execute controller to load assets, capture output to prevent rendering yet
        ob_start();
        require $headPath;
        $headContent = ob_get_clean();
    }
    
    // Load footer module assets
    if (file_exists($footerPath)) {
        ob_start();
        require $footerPath;
        $footerContent = ob_get_clean();
    }
    ?>
    
    <!-- Preload LCP image (logo) for faster mobile LCP -->
    <link rel="preload" href="/assets/base/images/logo.svg" as="image" fetchpriority="high">
    
    <?= Assets::renderInlineCss() ?>
    <?= Assets::renderCss() ?>
</head>
<body>
    <?php
    // Output header/navigation that was buffered
    echo $headContent ?? '';
    ?>
    
    <main id="main-content" role="main">
        <?php
        // Page content injects here
        echo $content ?? '';
        ?>
    </main>
    
    <?php
    // Output footer that was buffered
    echo $footerContent ?? '';

    // Render JS in cascade order: base → frontend → head → footer → page module
    echo Assets::renderJs();
    
    // Render full CSS at end (production only, non-blocking)
    echo Assets::renderFullCss();
    ?>
</body>
</html>
