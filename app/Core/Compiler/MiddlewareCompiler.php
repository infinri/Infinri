<?php

declare(strict_types=1);

namespace App\Core\Compiler;

/**
 * Middleware Compiler
 * 
 * Compiles middleware definitions from modules with priority ordering.
 */
class MiddlewareCompiler extends AbstractCompiler
{
    protected function getDefaultCachePath(): string
    {
        return $this->basePath . '/var/cache/middleware.php';
    }

    public function compile(): array
    {
        $compiled = [
            'global' => [],
            'web' => [],
            'api' => [],
            'aliases' => [],
            'priority' => [],
        ];

        $compiled = $this->loadAppMiddleware($compiled);

        $this->registry->load();
        foreach ($this->registry->getEnabled() as $module) {
            $compiled = $this->loadModuleMiddleware($module, $compiled);
        }

        $compiled['global'] = $this->sortByPriority($compiled['global'], $compiled['priority']);
        $compiled['web'] = $this->sortByPriority($compiled['web'], $compiled['priority']);
        $compiled['api'] = $this->sortByPriority($compiled['api'], $compiled['priority']);

        unset($compiled['priority']);

        $this->saveToCache($compiled, 'Compiled Middleware');

        return $compiled;
    }

    protected function loadAppMiddleware(array $compiled): array
    {
        $middlewarePath = $this->basePath . '/app/Http/middleware.php';

        if (file_exists($middlewarePath)) {
            $middleware = require $middlewarePath;
            $compiled = $this->mergeMiddleware($compiled, $middleware, 'app');
        }

        return $compiled;
    }

    protected function loadModuleMiddleware($module, array $compiled): array
    {
        $middlewarePath = $module->getFilePath('middleware.php');

        if (file_exists($middlewarePath)) {
            $middleware = require $middlewarePath;
            $compiled = $this->mergeMiddleware($compiled, $middleware, $module->name);
        }

        return $compiled;
    }

    protected function mergeMiddleware(array $compiled, array $middleware, string $source): array
    {
        foreach (['global', 'web', 'api'] as $group) {
            if (isset($middleware[$group])) {
                foreach ($middleware[$group] as $key => $value) {
                    if (is_array($value)) {
                        $class = $key;
                        $priority = $value['priority'] ?? 0;
                    } else {
                        $class = $value;
                        $priority = 0;
                    }

                    if (!in_array($class, $compiled[$group])) {
                        $compiled[$group][] = $class;
                        $compiled['priority'][$class] = $priority;
                    }
                }
            }
        }

        if (isset($middleware['aliases'])) {
            $compiled['aliases'] = array_merge($compiled['aliases'], $middleware['aliases']);
        }

        return $compiled;
    }

    protected function sortByPriority(array $middleware, array $priorities): array
    {
        usort($middleware, function ($a, $b) use ($priorities) {
            $priorityA = $priorities[$a] ?? 0;
            $priorityB = $priorities[$b] ?? 0;
            return $priorityB <=> $priorityA;
        });

        return $middleware;
    }

    public function getStats(): array
    {
        $compiled = $this->load();
        return [
            'global' => count($compiled['global']),
            'web' => count($compiled['web']),
            'api' => count($compiled['api']),
            'aliases' => count($compiled['aliases']),
        ];
    }
}
