<?php declare(strict_types=1);

namespace App\Modules\Pages;

use App\Modules\Concerns\RegistersViewPaths;
use App\Modules\Concerns\RegistersControllers;
use App\Modules\Module;
use App\Modules\Pages\Controllers\PageController;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;

/**
 * Pages module for handling static pages
 * 
 * This module provides functionality for serving static pages.
 * It handles the registration of page templates and their corresponding routes.
 */
class PagesModule extends Module
{
    use RegistersViewPaths, RegistersControllers;

    /**
     * Register pages module services and configurations
     * 
     * Registers view paths and page controller.
     */
    public function register(): void
    {
        $this->registerViewPaths();
        $this->registerControllers();
    }

    /**
     * Boot the pages module
     * 
     * This method is called after all modules have been registered.
     */
    public function boot(): void
    {
        // Pages module bootstrapping logic can be added here
    }

    /**
     * Register pages module controllers
     */
    private function registerControllers(): void
    {
        $this->registerController(PageController::class, [
            Engine::class,
            'container' => ContainerInterface::class
        ]);
    }
}
