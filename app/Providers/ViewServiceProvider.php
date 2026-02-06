<?php declare(strict_types=1);
/**
 * View Service Provider
 * 
 * Registers view-related services:
 * - HandleGenerator: Route-to-handle mapping
 * - LayoutRenderer: Layout update processor
 * - AssetManager: CSS/JS asset management
 * 
 * @package App\Providers
 */

namespace App\Providers;

use App\Core\Container\ServiceProvider;
use App\Core\View\HandleGenerator;
use App\Core\View\Layout\LayoutRenderer;
use App\Core\View\Asset\AssetManager;
use App\Core\View\Meta\MetaManager;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register view services
     */
    public function register(): void
    {
        // HandleGenerator - singleton, stateless
        $this->app->singleton(HandleGenerator::class, function () {
            return new HandleGenerator();
        });
        
        // MetaManager - singleton per request
        $this->app->singleton(MetaManager::class, function () {
            return new MetaManager();
        });
        
        // AssetManager - singleton per request
        $this->app->singleton(AssetManager::class, function () {
            return new AssetManager();
        });
        
        // LayoutRenderer - singleton per request
        $this->app->singleton(LayoutRenderer::class, function ($app) {
            return new LayoutRenderer(
                $app,
                app_path('Modules')
            );
        });
    }
    
    /**
     * Bootstrap view services
     */
    public function boot(): void
    {
        // Pre-load base CSS files in development
        if (env('APP_ENV', 'production') !== 'production') {
            $assets = $this->app->make(AssetManager::class);
            
            // Base CSS files (variables, reset, etc.)
            $baseCssPath = app_path('Core/View/view/base/web/css');
            if (is_dir($baseCssPath)) {
                $cssFiles = glob($baseCssPath . '/*.css');
                foreach ($cssFiles as $file) {
                    $assets->addCss('/assets/core/view/base/web/css/' . basename($file));
                }
            }
        }
    }
}
