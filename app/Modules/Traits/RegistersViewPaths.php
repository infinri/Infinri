<?php declare(strict_types=1);

namespace App\Modules\Traits;

use Psr\Container\ContainerInterface;

/**
 * Trait for modules that need to register view paths
 */
trait RegistersViewPaths
{
    /**
     * Register view paths for the module
     * 
     * @param ContainerInterface $container
     * @param string $modulePath Path to the module directory
     */
    protected function registerViewPaths(ContainerInterface $container, string $modulePath): void
    {
        $viewPaths = $container->get('settings')['view_paths'] ?? [];
        
        // Add module views directory if it exists
        $moduleViewsPath = rtrim($modulePath, '/') . '/Views';
        if (is_dir($moduleViewsPath)) {
            array_unshift($viewPaths, $moduleViewsPath);
        }
        
        // Update container with new view paths
        $container->get('settings')['view_paths'] = $viewPaths;
        
        // If using Plates, update the Plates engine
        if ($container->has('view')) {
            $view = $container->get('view');
            if (method_exists($view, 'addFolder')) {
                $moduleName = basename($modulePath);
                $view->addFolder(strtolower($moduleName), $moduleViewsPath);
            }
        }
    }
}
