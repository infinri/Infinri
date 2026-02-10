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
namespace App\Core\Routing;

use App\Core\Contracts\Routing\RouteInterface;
use App\Core\Support\Str;
use Closure;

/**
 * Route
 *
 * Represents a single route definition with URI pattern, methods, and action
 */
class Route implements RouteInterface
{
    /**
     * The route URI pattern
     */
    protected string $uri;

    /**
     * The HTTP methods the route responds to
     */
    protected array $methods;

    /**
     * The route action (Closure, controller, etc.)
     */
    protected Closure|array|string $action;

    /**
     * The route name
     */
    protected ?string $name = null;

    /**
     * Route middleware
     */
    protected array $middleware = [];

    /**
     * Parameter constraints (where clauses)
     */
    protected array $wheres = [];

    /**
     * Compiled regex pattern
     */
    protected ?string $compiledPattern = null;

    /**
     * Parameter names extracted from URI
     */
    protected array $parameterNames = [];

    /**
     * Extracted parameter values from last match
     */
    protected array $parameters = [];

    /**
     * Create a new route instance
     */
    public function __construct(
        array $methods,
        string $uri,
        Closure|array|string $action
    ) {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $this->normalizeUri($uri);
        $this->action = $action;

        $this->extractParameterNames();
        $this->compile();
    }

    /**
     * Get the route URI pattern
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the route HTTP methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the route action
     */
    public function getAction(): Closure|array|string
    {
        return $this->action;
    }

    /**
     * Get the route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the route name
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get route middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Add middleware to the route
     */
    public function middleware(string|array $middleware): static
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Get parameter constraints
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Add parameter constraint
     */
    public function where(string|array $name, ?string $expression = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->wheres[$key] = $value;
            }
        } else {
            $this->wheres[$name] = $expression;
        }

        // Recompile with new constraints
        $this->compile();

        return $this;
    }

    /**
     * Check if route matches the given request path and method
     */
    public function matches(string $path, string $method): bool
    {
        // Check method first (faster)
        if (! in_array(strtoupper($method), $this->methods, true)) {
            return false;
        }

        // Check path against compiled pattern
        $path = $this->normalizeUri($path);

        if (preg_match($this->compiledPattern, $path, $matches)) {
            // Extract named parameters
            $this->parameters = [];
            foreach ($this->parameterNames as $name) {
                if (isset($matches[$name]) && $matches[$name] !== '') {
                    $this->parameters[$name] = $matches[$name];
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get extracted parameters from the matched path
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the compiled regex pattern
     */
    public function getCompiledPattern(): string
    {
        return $this->compiledPattern ?? '';
    }

    /**
     * Get parameter names
     */
    public function getParameterNames(): array
    {
        return $this->parameterNames;
    }

    /**
     * Check if this is a static route (no parameters)
     */
    public function isStatic(): bool
    {
        return empty($this->parameterNames);
    }

    /**
     * Get the first URI segment for route indexing
     *
     * @return string The first segment (e.g., "users" from "/users/{id}")
     */
    public function getFirstSegment(): string
    {
        $segments = explode('/', trim($this->uri, '/'));

        return $segments[0];
    }

    /**
     * Normalize URI (ensure leading slash, no trailing slash)
     */
    protected function normalizeUri(string $uri): string
    {
        return Str::normalizeUri($uri);
    }

    /**
     * Extract parameter names from URI pattern
     */
    protected function extractParameterNames(): void
    {
        preg_match_all('/\{(\w+)\??}/', $this->uri, $matches);
        $this->parameterNames = $matches[1];
    }

    /**
     * Compile route to regex pattern
     */
    protected function compile(): void
    {
        $pattern = $this->uri;
        $segments = [];
        $lastPos = 0;

        // Find all parameters and build pattern
        preg_match_all('/\{(\w+)(\?)?\}/', $pattern, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $fullMatch = $match[0];
            $position = $match[1];
            $name = $matches[1][$i][0];
            $optional = isset($matches[2][$i][0]) && $matches[2][$i][0] === '?';
            $constraint = $this->wheres[$name] ?? '[^/]+';

            // Get static segment before this parameter
            $staticSegment = substr($pattern, $lastPos, $position - $lastPos);

            // Add parameter regex
            if ($optional) {
                // For optional parameters, make the preceding slash optional too
                if (str_ends_with($staticSegment, '/')) {
                    $segments[] = preg_quote(substr($staticSegment, 0, -1), '#');
                    $segments[] = "(?:/(?P<{$name}>{$constraint}))?";
                } else {
                    if ($staticSegment !== '') {
                        $segments[] = preg_quote($staticSegment, '#');
                    }
                    $segments[] = "(?:(?P<{$name}>{$constraint}))?";
                }
            } else {
                if ($staticSegment !== '') {
                    $segments[] = preg_quote($staticSegment, '#');
                }
                $segments[] = "(?P<{$name}>{$constraint})";
            }

            $lastPos = $position + strlen($fullMatch);
        }

        // Add any remaining static segment
        if ($lastPos < strlen($pattern)) {
            $segments[] = preg_quote(substr($pattern, $lastPos), '#');
        }

        $this->compiledPattern = '#^' . implode('', $segments) . '$#u';
    }

    /**
     * Set route prefix
     */
    public function setPrefix(string $prefix): static
    {
        $this->uri = $this->normalizeUri($prefix . '/' . ltrim($this->uri, '/'));
        $this->compile();

        return $this;
    }

    /**
     * Set name prefix
     */
    public function setNamePrefix(string $prefix): static
    {
        if ($this->name !== null) {
            $this->name = $prefix . $this->name;
        }

        return $this;
    }
}
