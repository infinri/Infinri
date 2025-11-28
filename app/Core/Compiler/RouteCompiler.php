<?php

declare(strict_types=1);

namespace App\Core\Compiler;

/**
 * Route Compiler
 *
 * Compiles module routes into a cached route table.
 */
class RouteCompiler extends AbstractCompiler
{
    protected function getDefaultCachePath(): string
    {
        return $this->basePath . '/var/cache/routes.php';
    }

    public function compile(): array
    {
        $routes = [];
        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            $moduleRoutes = $module->loadRoutes();

            if (!empty($moduleRoutes)) {
                foreach ($moduleRoutes as $route) {
                    $route['module'] = $module->name;
                    $routes[] = $route;
                }
            }
        }

        $this->saveToCache($routes, 'Compiled Routes');
        return $routes;
    }

    public function getStats(): array
    {
        $routes = $this->load();
        $byModule = [];

        foreach ($routes as $route) {
            $module = $route['module'] ?? 'unknown';
            $byModule[$module] = ($byModule[$module] ?? 0) + 1;
        }

        return [
            'total' => count($routes),
            'by_module' => $byModule,
        ];
    }
}
