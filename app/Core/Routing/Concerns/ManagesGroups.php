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
namespace App\Core\Routing\Concerns;

use App\Core\Support\Str;
use Closure;

/**
 * Provides route group management
 *
 * Single responsibility: Route grouping with prefix, middleware, and name
 */
trait ManagesGroups
{
    /**
     * The current route group attributes stack
     */
    protected array $groupStack = [];

    /**
     * Pending middleware for next route(s)
     */
    protected array $pendingMiddleware = [];

    /**
     * Pending prefix for next route(s)
     */
    protected ?string $pendingPrefix = null;

    /**
     * Pending name prefix for next route(s)
     */
    protected ?string $pendingNamePrefix = null;

    /**
     * Create a route group
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $this->mergeGroupAttributes($attributes);
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Add middleware to subsequent routes
     */
    public function middleware(string|array $middleware): static
    {
        $this->pendingMiddleware = array_merge(
            $this->pendingMiddleware,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $this;
    }

    /**
     * Add prefix to subsequent routes
     */
    public function prefix(string $prefix): static
    {
        $this->pendingPrefix = $prefix;

        return $this;
    }

    /**
     * Add name prefix to subsequent routes
     */
    public function name(string $name): static
    {
        $this->pendingNamePrefix = $name;

        return $this;
    }

    /**
     * Merge group attributes with current group stack
     */
    protected function mergeGroupAttributes(array $attributes): array
    {
        $current = end($this->groupStack) ?: [];

        // Merge prefix
        if (isset($attributes['prefix'])) {
            $currentPrefix = $current['prefix'] ?? '';
            $attributes['prefix'] = Str::joinUri($currentPrefix, $attributes['prefix']);
        }

        // Merge middleware
        if (isset($attributes['middleware'])) {
            $currentMiddleware = $current['middleware'] ?? [];
            $newMiddleware = is_array($attributes['middleware'])
                ? $attributes['middleware']
                : [$attributes['middleware']];
            $attributes['middleware'] = array_merge($currentMiddleware, $newMiddleware);
        }

        // Merge name prefix (as)
        if (isset($attributes['as'])) {
            $currentAs = $current['as'] ?? '';
            $attributes['as'] = $currentAs . $attributes['as'];
        }

        return array_merge($current, $attributes);
    }

    /**
     * Apply group prefix to URI
     */
    protected function applyGroupPrefix(string $uri): string
    {
        $group = end($this->groupStack);

        if ($group && isset($group['prefix'])) {
            return Str::joinUri($group['prefix'], $uri);
        }

        return $uri;
    }

    /**
     * Get current group middleware
     */
    protected function getGroupMiddleware(): array
    {
        $group = end($this->groupStack);

        return $group['middleware'] ?? [];
    }

    /**
     * Get current group name prefix
     */
    protected function getGroupNamePrefix(): string
    {
        $group = end($this->groupStack);

        return $group['as'] ?? '';
    }

    /**
     * Consume and reset pending attributes, returning them
     */
    protected function consumePendingAttributes(): array
    {
        $attributes = [
            'middleware' => $this->pendingMiddleware,
            'prefix' => $this->pendingPrefix,
            'namePrefix' => $this->pendingNamePrefix,
        ];

        $this->pendingMiddleware = [];
        $this->pendingPrefix = null;
        $this->pendingNamePrefix = null;

        return $attributes;
    }
}
