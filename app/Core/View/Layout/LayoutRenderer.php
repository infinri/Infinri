<?php
declare(strict_types=1);
/**
 * Layout Renderer
 * 
 * Processes layout updates from modules and renders blocks into containers.
 * This is the core of the Magento-inspired layout system.
 * 
 * Supported operations:
 * - Add blocks to containers
 * - Remove blocks by name
 * - Move blocks between containers
 * - Position with before/after
 * - Conditional blocks with ifconfig
 * 
 * @package App\Core\View\Layout
 */

namespace App\Core\View\Layout;

use App\Core\Container\Container;
use App\Core\View\Block\AbstractBlock;

final class LayoutRenderer
{
    private Container $container;
    
    /**
     * Current layout handles
     * @var array<string>
     */
    private array $handles = [];
    
    /**
     * Blocks to remove
     * @var array<string>
     */
    private array $removeBlocks = [];
    
    /**
     * Blocks to move
     * @var array<string, array{container: string, before?: string, after?: string}>
     */
    private array $moveBlocks = [];
    
    /**
     * Registered blocks by name (after processing)
     * @var array<string, array{block: AbstractBlock, container: string, cache: ?array}>
     */
    private array $blocks = [];
    
    /**
     * Container contents (blocks grouped by container)
     * @var array<string, array<array>>
     */
    private array $containers = [];
    
    /**
     * CSS files to include
     * @var array<array{file: string, module: string, ifconfig?: string}>
     */
    private array $cssFiles = [];
    
    /**
     * JS files to include
     * @var array<array{file: string, module: string, ifconfig?: string}>
     */
    private array $jsFiles = [];
    
    /**
     * Current area (frontend/admin)
     */
    private string $area = 'frontend';
    
    /**
     * Path to modules directory
     */
    private string $modulesPath;
    
    /**
     * Whether layouts have been loaded
     */
    private bool $layoutsLoaded = false;
    
    /**
     * Whether blocks have been generated
     */
    private bool $blocksGenerated = false;
    
    public function __construct(Container $container, ?string $modulesPath = null)
    {
        $this->container = $container;
        $this->modulesPath = $modulesPath ?? app_path('Modules');
    }
    
    /**
     * Set the current area (frontend/admin)
     */
    public function setArea(string $area): static
    {
        $this->area = $area;
        return $this;
    }
    
    /**
     * Get current area
     */
    public function getArea(): string
    {
        return $this->area;
    }
    
    /**
     * Add layout handles
     */
    public function addHandle(string ...$handles): static
    {
        foreach ($handles as $handle) {
            if (!in_array($handle, $this->handles, true)) {
                $this->handles[] = $handle;
            }
        }
        return $this;
    }
    
    /**
     * Get current handles
     */
    public function getHandles(): array
    {
        return $this->handles;
    }
    
    /**
     * Check if a handle is active
     */
    public function hasHandle(string $handle): bool
    {
        return in_array($handle, $this->handles, true);
    }
    
    /**
     * Load layout updates from all modules for current handles
     */
    public function loadLayoutUpdates(): static
    {
        if ($this->layoutsLoaded) {
            return $this;
        }
        
        if (!is_dir($this->modulesPath)) {
            return $this;
        }
        
        $modules = glob($this->modulesPath . '/*', GLOB_ONLYDIR);
        
        // Process handles in order (later handles can override earlier)
        foreach ($this->handles as $handle) {
            foreach ($modules as $modulePath) {
                $layoutFile = "{$modulePath}/view/{$this->area}/layout/{$handle}.php";
                
                if (file_exists($layoutFile)) {
                    $updates = require $layoutFile;
                    
                    if (is_array($updates)) {
                        $moduleName = basename($modulePath);
                        $this->processLayoutUpdate($updates, $moduleName);
                    }
                }
            }
        }
        
        $this->layoutsLoaded = true;
        return $this;
    }
    
    /**
     * Process a layout update array
     */
    private function processLayoutUpdate(array $updates, string $moduleName): void
    {
        foreach ($updates as $key => $value) {
            // Handle special operations
            if ($key === 'remove') {
                // Remove blocks by name
                foreach ((array) $value as $blockName) {
                    $this->removeBlocks[] = $blockName;
                }
                continue;
            }
            
            if ($key === 'move') {
                // Move blocks: ['block.name' => ['container' => 'new.container', 'before' => 'other.block']]
                foreach ($value as $blockName => $moveConfig) {
                    $this->moveBlocks[$blockName] = $moveConfig;
                }
                continue;
            }
            
            // Handle asset injection
            if ($key === 'head.css') {
                foreach ($value as $item) {
                    $item['module'] = $moduleName;
                    $this->cssFiles[] = $item;
                }
                continue;
            }
            
            if ($key === 'body.js' || $key === 'head.js') {
                foreach ($value as $item) {
                    $item['module'] = $moduleName;
                    $this->jsFiles[] = $item;
                }
                continue;
            }
            
            // Regular container with blocks
            $containerName = $key;
            if (!isset($this->containers[$containerName])) {
                $this->containers[$containerName] = [];
            }
            
            foreach ($value as $item) {
                $item['_module'] = $moduleName;
                $this->containers[$containerName][] = $item;
            }
        }
    }
    
    /**
     * Generate all blocks from layout updates
     */
    public function generateBlocks(): static
    {
        if ($this->blocksGenerated) {
            return $this;
        }
        
        // First, sort blocks within containers by position
        foreach ($this->containers as $containerName => &$items) {
            $items = $this->sortBlocksByPosition($items);
        }
        unset($items);
        
        // Apply move operations
        foreach ($this->moveBlocks as $blockName => $moveConfig) {
            $this->applyMoveBlock($blockName, $moveConfig);
        }
        
        // Generate block instances
        foreach ($this->containers as $containerName => $items) {
            foreach ($items as $item) {
                // Skip if block is in remove list
                $blockName = $item['name'] ?? null;
                if ($blockName && in_array($blockName, $this->removeBlocks, true)) {
                    continue;
                }
                
                // Check ifconfig condition
                if (isset($item['ifconfig']) && !$this->checkIfConfig($item['ifconfig'])) {
                    continue;
                }
                
                if (!isset($item['block'])) {
                    continue;
                }
                
                // Instantiate block via container
                $block = $this->container->make($item['block']);
                
                if (!($block instanceof AbstractBlock)) {
                    continue;
                }
                
                // Configure block
                if (isset($item['template'])) {
                    $block->setTemplate($item['template']);
                }
                
                $name = $blockName ?? uniqid('block_');
                $block->setNameInLayout($name);
                
                // Store block info
                $this->blocks[$name] = [
                    'block' => $block,
                    'container' => $containerName,
                    'cache' => $item['cache'] ?? null,
                ];
            }
        }
        
        $this->blocksGenerated = true;
        return $this;
    }
    
    /**
     * Sort blocks by position (sort_order, before, after)
     */
    private function sortBlocksByPosition(array $items): array
    {
        // First pass: assign base sort orders
        $positioned = [];
        $beforeAfter = [];
        
        foreach ($items as $index => $item) {
            $name = $item['name'] ?? "unnamed_{$index}";
            
            if (isset($item['before']) || isset($item['after'])) {
                $beforeAfter[$name] = $item;
            } else {
                $item['_sort'] = $item['sort_order'] ?? 100;
                $positioned[$name] = $item;
            }
        }
        
        // Sort positioned items
        uasort($positioned, fn($a, $b) => ($a['_sort'] ?? 100) <=> ($b['_sort'] ?? 100));
        
        // Insert before/after items
        foreach ($beforeAfter as $name => $item) {
            if (isset($item['before'])) {
                $positioned = $this->insertBefore($positioned, $name, $item, $item['before']);
            } elseif (isset($item['after'])) {
                $positioned = $this->insertAfter($positioned, $name, $item, $item['after']);
            }
        }
        
        return array_values($positioned);
    }
    
    /**
     * Insert item before a reference item
     */
    private function insertBefore(array $items, string $name, array $item, string $reference): array
    {
        $result = [];
        $inserted = false;
        
        foreach ($items as $key => $existing) {
            if ($key === $reference && !$inserted) {
                $result[$name] = $item;
                $inserted = true;
            }
            $result[$key] = $existing;
        }
        
        // If reference not found, append at beginning
        if (!$inserted) {
            $result = [$name => $item] + $result;
        }
        
        return $result;
    }
    
    /**
     * Insert item after a reference item
     */
    private function insertAfter(array $items, string $name, array $item, string $reference): array
    {
        $result = [];
        $inserted = false;
        
        foreach ($items as $key => $existing) {
            $result[$key] = $existing;
            if ($key === $reference && !$inserted) {
                $result[$name] = $item;
                $inserted = true;
            }
        }
        
        // If reference not found, append at end
        if (!$inserted) {
            $result[$name] = $item;
        }
        
        return $result;
    }
    
    /**
     * Apply a move operation
     */
    private function applyMoveBlock(string $blockName, array $config): void
    {
        // Find and remove from current container
        $block = null;
        foreach ($this->containers as $containerName => &$items) {
            foreach ($items as $index => $item) {
                if (($item['name'] ?? null) === $blockName) {
                    $block = $item;
                    unset($items[$index]);
                    $items = array_values($items);
                    break 2;
                }
            }
        }
        unset($items);
        
        if ($block === null) {
            return;
        }
        
        // Add to new container with position
        $targetContainer = $config['container'];
        if (!isset($this->containers[$targetContainer])) {
            $this->containers[$targetContainer] = [];
        }
        
        if (isset($config['before'])) {
            $block['before'] = $config['before'];
            unset($block['after'], $block['sort_order']);
        } elseif (isset($config['after'])) {
            $block['after'] = $config['after'];
            unset($block['before'], $block['sort_order']);
        }
        
        $this->containers[$targetContainer][] = $block;
    }
    
    /**
     * Check ifconfig condition
     * 
     * @param string $configPath Config path like "module/feature/enabled"
     * @return bool
     */
    private function checkIfConfig(string $configPath): bool
    {
        // Use app config helper
        // Format: "section/group/field" or "MODULE_FEATURE_ENABLED" env var
        
        // Check as env var first (uppercase with underscores)
        $envKey = strtoupper(str_replace(['/', '.'], '_', $configPath));
        $envValue = env($envKey);
        
        if ($envValue !== null) {
            return filter_var($envValue, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Check as config path
        $value = config($configPath);
        
        if ($value === null) {
            return true; // Default to enabled if config not found
        }
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Render a container's contents
     */
    public function renderContainer(string $name): string
    {
        $output = [];
        
        foreach ($this->blocks as $blockName => $blockInfo) {
            if ($blockInfo['container'] !== $name) {
                continue;
            }
            
            $html = $this->renderBlock($blockInfo['block'], $blockInfo['cache']);
            if ($html !== '') {
                $output[] = $html;
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Check if container has any blocks
     */
    public function hasContainer(string $name): bool
    {
        foreach ($this->blocks as $blockInfo) {
            if ($blockInfo['container'] === $name) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Render a single block
     */
    private function renderBlock(AbstractBlock $block, ?array $cacheConfig): string
    {
        // Check cache
        if ($cacheConfig !== null && ($cacheConfig['enabled'] ?? false)) {
            $cacheKey = $block->getCacheKey();
            if ($cacheKey !== null) {
                $cached = $this->getFromCache($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }
            }
        }
        
        // Render template
        $template = $block->getTemplate();
        $data = $block->getPreparedData();
        
        $html = $this->renderTemplate($template, $data);
        
        // Store in cache
        if ($cacheConfig !== null && ($cacheConfig['enabled'] ?? false)) {
            $cacheKey = $block->getCacheKey();
            if ($cacheKey !== null) {
                $ttl = $cacheConfig['ttl'] ?? $block->getCacheTtl();
                $this->storeInCache($cacheKey, $html, $ttl);
            }
        }
        
        return $html;
    }
    
    /**
     * Render a template file
     */
    private function renderTemplate(string $template, array $data): string
    {
        $path = $this->resolveTemplatePath($template);
        
        if (!file_exists($path)) {
            return "<!-- Template not found: {$template} -->";
        }
        
        // Make data available to template
        extract(['data' => $data], EXTR_SKIP);
        
        ob_start();
        try {
            include $path;
            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();
            return "<!-- Error rendering {$template}: {$e->getMessage()} -->";
        }
    }
    
    /**
     * Resolve Module::path notation to file path
     * 
     * Supports:
     * - Module::path       → Modules/{Module}/view/{area}/templates/{path}.phtml
     * - Core::path         → Core/View/view/base/templates/{path}.phtml
     * - CoreFrontend::path → Core/View/view/frontend/templates/{path}.phtml
     * - CoreAdmin::path    → Core/View/view/admin/templates/{path}.phtml
     * - plain path         → Core/View/view/{area}/templates/{path}.phtml
     */
    private function resolveTemplatePath(string $template): string
    {
        if (str_contains($template, '::')) {
            [$prefix, $path] = explode('::', $template, 2);
            
            // Core templates
            if ($prefix === 'Core') {
                return app_path("Core/View/view/base/templates/{$path}.phtml");
            }
            if ($prefix === 'CoreFrontend') {
                return app_path("Core/View/view/frontend/templates/{$path}.phtml");
            }
            if ($prefix === 'CoreAdmin') {
                return app_path("Core/View/view/admin/templates/{$path}.phtml");
            }
            
            // Module templates
            return "{$this->modulesPath}/{$prefix}/view/{$this->area}/templates/{$path}.phtml";
        }
        
        // Fallback: Core area templates
        return app_path("Core/View/view/{$this->area}/templates/{$template}.phtml");
    }
    
    /**
     * Get CSS files to include (filtered by ifconfig)
     */
    public function getCssFiles(): array
    {
        return array_filter($this->cssFiles, function ($item) {
            if (!isset($item['ifconfig'])) {
                return true;
            }
            return $this->checkIfConfig($item['ifconfig']);
        });
    }
    
    /**
     * Get JS files to include (filtered by ifconfig)
     */
    public function getJsFiles(): array
    {
        return array_filter($this->jsFiles, function ($item) {
            if (!isset($item['ifconfig'])) {
                return true;
            }
            return $this->checkIfConfig($item['ifconfig']);
        });
    }
    
    /**
     * Get a block by name
     */
    public function getBlock(string $name): ?AbstractBlock
    {
        return $this->blocks[$name]['block'] ?? null;
    }
    
    /**
     * Get from cache
     */
    private function getFromCache(string $key): ?string
    {
        return cache()->get("block.{$key}");
    }
    
    /**
     * Store in cache
     */
    private function storeInCache(string $key, string $value, int $ttl): void
    {
        cache()->set("block.{$key}", $value, $ttl);
    }
    
    /**
     * Reset state
     */
    public function reset(): static
    {
        $this->handles = [];
        $this->removeBlocks = [];
        $this->moveBlocks = [];
        $this->blocks = [];
        $this->containers = [];
        $this->cssFiles = [];
        $this->jsFiles = [];
        $this->layoutsLoaded = false;
        $this->blocksGenerated = false;
        return $this;
    }
}
