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
namespace App\Http;

use App\Core\Application;
use App\Core\Contracts\Routing\RouterInterface;
use App\Core\Http\Kernel as BaseKernel;

/**
 * Application HTTP Kernel
 * 
 * Loads middleware configuration from middleware.php (single source of truth).
 */
class Kernel extends BaseKernel
{
    public function __construct(Application $app, RouterInterface $router)
    {
        parent::__construct($app, $router);
        $this->loadMiddlewareConfig();
    }

    /**
     * Load middleware configuration from middleware.php
     */
    protected function loadMiddlewareConfig(): void
    {
        $config = require __DIR__ . '/middleware.php';

        // Load global middleware (sorted by priority descending)
        if (!empty($config['global'])) {
            $this->middleware = $this->sortByPriority($config['global']);
        }

        // Load middleware groups
        if (!empty($config['web'])) {
            $this->middlewareGroups['web'] = $this->sortByPriority($config['web']);
        }
        if (!empty($config['api'])) {
            $this->middlewareGroups['api'] = $this->sortByPriority($config['api']);
        }

        // Load aliases as route middleware
        if (!empty($config['aliases'])) {
            $this->routeMiddleware = $config['aliases'];
        }
    }

    /**
     * Sort middleware by priority (higher runs first) and return class names
     * 
     * @param array<string, array{priority?: int, args?: array}> $middleware
     * @return array<int, string>
     */
    protected function sortByPriority(array $middleware): array
    {
        uasort($middleware, fn($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));
        return array_keys($middleware);
    }
}
