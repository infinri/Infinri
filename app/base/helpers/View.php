<?php
declare(strict_types=1);
/**
 * View Helper
 *
 * Simple view rendering with layout support
 *
 * @package App\Helpers
 */

namespace App\Helpers;

use App\Base\Helpers\Assets;

final class View
{
    private static array $data = [];
    private static bool $layoutRendered = false;

    /**
     * Render a view file
     *
     * @param string $view View path (relative to modules)
     * @param array $data Data to pass to view
     * @return void
     */
    public static function render(string $view, array $data = []): void
    {
        self::$data = array_merge(self::$data, $data);
        extract(self::$data, EXTR_SKIP);

        $viewPath = dirname(__DIR__, 2) . "/{$view}";

        if (! file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        require $viewPath;
    }

    /**
     * Render with layout (head + content + footer)
     *
     * @param string $content Content module name
     * @param array $data Data to pass to views
     * @return void
     */
    public static function layout(string $content, array $data = []): void
    {
        if (self::$layoutRendered) {
            return;
        }

        self::$data = array_merge(self::$data, $data);
        self::$layoutRendered = true;

        // Render layout parts
        self::render("modules/head/index.php");
        self::render("modules/{$content}/index.php");
        self::render("modules/footer/index.php");
    }

    /**
     * Set view data
     *
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        self::$data[$key] = $value;
    }

    /**
     * Get view data
     *
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return self::$data[$key] ?? $default;
    }

    /**
     * Auto-load module assets if they exist (convention over configuration)
     *
     * @param string $module Module name
     * @return void
     */
    private static function autoLoadAssets(string $module): void
    {
        $basePath = dirname(__DIR__, 2) . "/modules/{$module}/view/frontend";

        // Auto-load CSS if exists
        if (file_exists("{$basePath}/css/{$module}.css")) {
            Assets::addCss("/assets/modules/{$module}/view/frontend/css/{$module}.css");
        }

        // Auto-load JS if exists
        if (file_exists("{$basePath}/js/{$module}.js")) {
            Assets::addJs("/assets/modules/{$module}/view/frontend/js/{$module}.js");
        }
    }

    /**
     * Enhanced layout with automatic asset loading
     *
     * @param string $content Content module name
     * @param array $data Data to pass to views
     * @return void
     */
    public static function layoutWithAssets(string $content, array $data = []): void
    {
        // Auto-load module assets
        self::autoLoadAssets($content);

        // Use existing layout method
        self::layout($content, $data);
    }
}
