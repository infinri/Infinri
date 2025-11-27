<?php

declare(strict_types=1);

namespace App\Core\Compiler;

use App\Core\Module\ModuleRegistry;

/**
 * Event Compiler
 * 
 * Scans module event listeners and compiles them into a cached file.
 * 
 * Module events.php format:
 * return [
 *     UserRegistered::class => [
 *         SendWelcomeEmail::class,
 *         AddToNewsletter::class,
 *     ],
 *     OrderPlaced::class => [
 *         [NotifyAdmin::class, 'handle', 10], // with priority
 *     ],
 * ];
 */
class EventCompiler
{
    protected string $basePath;
    protected string $cachePath;
    protected ModuleRegistry $registry;

    public function __construct(
        ?string $basePath = null,
        ?string $cachePath = null,
        ?ModuleRegistry $registry = null
    ) {
        $this->basePath = $basePath ?? $this->getDefaultBasePath();
        $this->cachePath = $cachePath ?? $this->basePath . '/var/cache/events.php';
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Compile all event listeners into cache
     */
    public function compile(): array
    {
        $listeners = [];

        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            $moduleEvents = $module->loadEvents();
            
            foreach ($moduleEvents as $event => $eventListeners) {
                if (!isset($listeners[$event])) {
                    $listeners[$event] = [];
                }
                
                foreach ((array) $eventListeners as $listener) {
                    $listeners[$event][] = $this->normalizeListener($listener, $module->name);
                }
            }
        }

        // Sort listeners by priority
        foreach ($listeners as $event => &$eventListeners) {
            usort($eventListeners, fn($a, $b) => $b['priority'] <=> $a['priority']);
        }

        $this->saveToCache($listeners);

        return $listeners;
    }

    /**
     * Normalize listener definition
     */
    protected function normalizeListener(mixed $listener, string $moduleName): array
    {
        // Simple class name
        if (is_string($listener)) {
            return [
                'class' => $listener,
                'method' => 'handle',
                'priority' => 0,
                'module' => $moduleName,
            ];
        }

        // Array format: [class, method, priority]
        if (is_array($listener)) {
            return [
                'class' => $listener[0],
                'method' => $listener[1] ?? 'handle',
                'priority' => $listener[2] ?? 0,
                'module' => $moduleName,
            ];
        }

        throw new \InvalidArgumentException("Invalid listener format in module: {$moduleName}");
    }

    /**
     * Load compiled listeners (from cache or compile fresh)
     */
    public function load(): array
    {
        if ($this->isCached()) {
            return $this->loadFromCache();
        }

        return $this->compile();
    }

    /**
     * Check if compiled cache exists
     */
    public function isCached(): bool
    {
        return file_exists($this->cachePath);
    }

    /**
     * Clear the event cache
     */
    public function clear(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Get listener count per event
     */
    public function getStats(): array
    {
        $listeners = $this->load();
        $stats = [];
        
        foreach ($listeners as $event => $eventListeners) {
            $stats[$event] = count($eventListeners);
        }
        
        return $stats;
    }

    /**
     * Save compiled listeners to cache
     */
    protected function saveToCache(array $listeners): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $content = "<?php\n\n"
            . "// Compiled Event Listeners - Generated: " . date('Y-m-d H:i:s') . "\n"
            . "// DO NOT EDIT - Run 'php bin/console s:up' to regenerate\n\n"
            . "return " . var_export($listeners, true) . ";\n";

        file_put_contents($this->cachePath, $content);
    }

    /**
     * Load listeners from cache
     */
    protected function loadFromCache(): array
    {
        return require $this->cachePath;
    }

    protected function getDefaultBasePath(): string
    {
        if (function_exists('app')) {
            try {
                return app()->basePath();
            } catch (\Throwable) {}
        }
        return dirname(__DIR__, 3);
    }
}
