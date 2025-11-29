<?php
declare(strict_types=1);
/**
 * Abstract Block
 * 
 * Base class for all view blocks. Blocks contain the logic for preparing
 * data that templates render. This separates concerns:
 * - Block: Data preparation and business logic
 * - Template: HTML presentation only
 * 
 * @package App\Core\View\Block
 */

namespace App\Core\View\Block;

abstract class AbstractBlock
{
    /**
     * Template path (Module::path/to/template notation)
     */
    protected string $template = '';
    
    /**
     * Block name in layout (unique identifier)
     */
    protected string $nameInLayout = '';
    
    /**
     * Cached data from getData()
     */
    private ?array $cachedData = null;
    
    /**
     * Get data for template
     * 
     * Override this method to prepare data for your template.
     * This is where business logic and data transformation happens.
     * 
     * @return array<string, mixed> Data to pass to template
     */
    abstract public function getData(): array;
    
    /**
     * Set template path
     * 
     * @param string $template Template path in Module::path notation
     * @return static
     */
    public function setTemplate(string $template): static
    {
        $this->template = $template;
        return $this;
    }
    
    /**
     * Get template path
     * 
     * @return string Template path
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
    
    /**
     * Set block name in layout
     * 
     * @param string $name Unique block name
     * @return static
     */
    public function setNameInLayout(string $name): static
    {
        $this->nameInLayout = $name;
        return $this;
    }
    
    /**
     * Get block name in layout
     * 
     * @return string Block name
     */
    public function getNameInLayout(): string
    {
        return $this->nameInLayout;
    }
    
    /**
     * Get cache key for this block
     * 
     * Override to enable block caching. Return null to disable caching.
     * Cache key should be unique and include any variables that affect output.
     * 
     * @return string|null Cache key or null if not cacheable
     */
    public function getCacheKey(): ?string
    {
        return null;
    }
    
    /**
     * Get cache TTL in seconds
     * 
     * @return int TTL in seconds (default: 1 hour)
     */
    public function getCacheTtl(): int
    {
        return 3600;
    }
    
    /**
     * Get cache tags for invalidation
     * 
     * @return array<string> Cache tags
     */
    public function getCacheTags(): array
    {
        return [];
    }
    
    /**
     * Check if block output should be cached
     * 
     * @return bool True if cacheable
     */
    public function isCacheable(): bool
    {
        return $this->getCacheKey() !== null;
    }
    
    /**
     * Get prepared data (with caching)
     * 
     * @return array<string, mixed>
     */
    public function getPreparedData(): array
    {
        if ($this->cachedData === null) {
            $this->cachedData = $this->getData();
        }
        
        return $this->cachedData;
    }
    
    /**
     * Reset cached data (useful for testing or re-rendering)
     * 
     * @return static
     */
    public function resetData(): static
    {
        $this->cachedData = null;
        return $this;
    }
    
    /**
     * Convert block to array (for debugging/serialization)
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->nameInLayout,
            'template' => $this->template,
            'class' => static::class,
            'cacheable' => $this->isCacheable(),
            'cache_key' => $this->getCacheKey(),
            'cache_ttl' => $this->getCacheTtl(),
        ];
    }
}
