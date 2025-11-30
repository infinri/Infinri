<?php

declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Theme;

use App\Core\Container\ServiceProvider;

/**
 * Theme Service Provider
 * 
 * Registers Infinri brand theme configuration and assets.
 */
class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register theme services
     */
    public function register(): void
    {
        // Merge theme config into application config
        $this->mergeConfig();
    }

    /**
     * Bootstrap theme services
     */
    public function boot(): void
    {
        // Register view paths for theme templates
        $this->registerViews();
    }

    /**
     * Merge theme configuration
     */
    protected function mergeConfig(): void
    {
        $configPath = __DIR__ . '/Config/theme.php';
        
        if (file_exists($configPath)) {
            $themeConfig = require $configPath;
            
            // Make theme config available via config('theme')
            if ($this->app->has('config')) {
                $config = $this->app->get('config');
                $config->set('theme', $themeConfig);
            }
        }
    }

    /**
     * Register view paths
     */
    protected function registerViews(): void
    {
        // Frontend views: Theme::frontend/template-name
        // Admin views: Theme::admin/template-name
        
        if ($this->app->has('view')) {
            $view = $this->app->get('view');
            
            if (method_exists($view, 'addNamespace')) {
                $view->addNamespace('Theme', __DIR__ . '/view');
            }
        }
    }

    /**
     * Get asset path for theme
     */
    public static function assetPath(string $area, string $file): string
    {
        return sprintf(
            '/assets/modules/theme/%s/web/%s',
            $area,
            ltrim($file, '/')
        );
    }

    /**
     * Services provided by this provider
     */
    public function provides(): array
    {
        return ['theme'];
    }
}
