<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Compiler;

use App\Core\Module\ModuleRegistry;

/**
 * Compiler Manager
 *
 * Orchestrates all compilation tasks.
 */
class CompilerManager
{
    protected string $basePath;
    protected ModuleRegistry $registry;

    protected ?ConfigCompiler $configCompiler = null;
    protected ?EventCompiler $eventCompiler = null;
    protected ?ContainerCompiler $containerCompiler = null;
    protected ?RouteCompiler $routeCompiler = null;
    protected ?MiddlewareCompiler $middlewareCompiler = null;

    public function __construct(?string $basePath = null, ?ModuleRegistry $registry = null)
    {
        $this->basePath = $basePath ?? base_path();
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Compile everything
     */
    public function compileAll(): array
    {
        return [
            'config' => $this->compileConfig(),
            'events' => $this->compileEvents(),
            'container' => $this->compileContainer(),
            'routes' => $this->compileRoutes(),
            'middleware' => $this->compileMiddleware(),
        ];
    }

    /**
     * Clear all compiled caches
     */
    public function clearAll(): void
    {
        $this->getConfigCompiler()->clear();
        $this->getEventCompiler()->clear();
        $this->getContainerCompiler()->clear();
        $this->getRouteCompiler()->clear();
        $this->getMiddlewareCompiler()->clear();
    }

    /**
     * Compile config
     */
    public function compileConfig(): array
    {
        return $this->getConfigCompiler()->compile();
    }

    /**
     * Compile events
     */
    public function compileEvents(): array
    {
        return $this->getEventCompiler()->compile();
    }

    /**
     * Compile container
     */
    public function compileContainer(): array
    {
        return $this->getContainerCompiler()->compile();
    }

    /**
     * Compile routes
     */
    public function compileRoutes(): array
    {
        return $this->getRouteCompiler()->compile();
    }

    /**
     * Compile middleware
     */
    public function compileMiddleware(): array
    {
        return $this->getMiddlewareCompiler()->compile();
    }

    /**
     * Get compilation stats
     */
    public function getStats(): array
    {
        return [
            'config_cached' => $this->getConfigCompiler()->isCached(),
            'events_cached' => $this->getEventCompiler()->isCached(),
            'container_cached' => $this->getContainerCompiler()->isCached(),
            'routes_cached' => $this->getRouteCompiler()->isCached(),
            'middleware_cached' => $this->getMiddlewareCompiler()->isCached(),
            'container' => $this->getContainerCompiler()->getStats(),
            'events' => $this->getEventCompiler()->getStats(),
            'routes' => $this->getRouteCompiler()->getStats(),
            'middleware' => $this->getMiddlewareCompiler()->getStats(),
        ];
    }

    /**
     * Get config compiler
     */
    public function getConfigCompiler(): ConfigCompiler
    {
        if ($this->configCompiler === null) {
            $this->configCompiler = new ConfigCompiler(
                $this->basePath,
                $this->basePath . '/var/cache/config.php',
                $this->registry
            );
        }

        return $this->configCompiler;
    }

    /**
     * Get event compiler
     */
    public function getEventCompiler(): EventCompiler
    {
        if ($this->eventCompiler === null) {
            $this->eventCompiler = new EventCompiler(
                $this->basePath,
                $this->basePath . '/var/cache/events.php',
                $this->registry
            );
        }

        return $this->eventCompiler;
    }

    /**
     * Get container compiler
     */
    public function getContainerCompiler(): ContainerCompiler
    {
        if ($this->containerCompiler === null) {
            $this->containerCompiler = new ContainerCompiler(
                $this->basePath,
                $this->basePath . '/var/cache/container.php',
                $this->registry
            );
        }

        return $this->containerCompiler;
    }

    /**
     * Get route compiler
     */
    public function getRouteCompiler(): RouteCompiler
    {
        if ($this->routeCompiler === null) {
            $this->routeCompiler = new RouteCompiler(
                $this->basePath,
                $this->basePath . '/var/cache/routes.php',
                $this->registry
            );
        }

        return $this->routeCompiler;
    }

    /**
     * Get middleware compiler
     */
    public function getMiddlewareCompiler(): MiddlewareCompiler
    {
        if ($this->middlewareCompiler === null) {
            $this->middlewareCompiler = new MiddlewareCompiler(
                $this->basePath,
                $this->basePath . '/var/cache/middleware.php',
                $this->registry
            );
        }

        return $this->middlewareCompiler;
    }
}
