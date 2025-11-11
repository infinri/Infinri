<?php
declare(strict_types=1);
/**
 * Base Module
 *
 * Abstract base class for OOP modules
 *
 * @package App\Base\Components
 */

namespace App\Base\Components;

use App\Helpers\View;
use App\Base\Helpers\{Assets, Meta};

abstract class BaseModule
{
    protected string $name = '';
    protected array $meta = [];
    protected array $css = [];
    protected array $js = [];

    /**
     * Render the module
     *
     * @return void
     */
    abstract public function render(): void;

    /**
     * Initialize module (load assets, set meta)
     *
     * @return void
     */
    public function init(): void
    {
        // Set meta tags
        if (count($this->meta) > 0) {
            Meta::setMultiple($this->meta);
        }

        // Load CSS
        foreach ($this->css as $file) {
            Assets::addCss($this->getAssetPath('css', $file));
        }

        // Load JS
        foreach ($this->js as $file) {
            Assets::addJs($this->getAssetPath('js', $file));
        }
    }

    /**
     * Get asset path for this module
     *
     * @param string $type Asset type (css or js)
     * @param string $file File name
     * @return string
     */
    protected function getAssetPath(string $type, string $file): string
    {
        return "/assets/modules/{$this->name}/view/frontend/{$type}/{$file}";
    }

    /**
     * Get meta tags
     *
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Get module name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
