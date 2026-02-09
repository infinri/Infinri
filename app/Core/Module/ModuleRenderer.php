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
namespace App\Core\Module;

/**
 * Module Renderer
 *
 * Renders modules with layout wrapping.
 * Handles the view layer for module-based routing.
 */
class ModuleRenderer
{
    /**
     * Modules directory path
     */
    protected string $modulesPath;

    /**
     * Layout file path
     */
    protected string $layoutPath;

    public function __construct(?string $modulesPath = null, ?string $layoutPath = null)
    {
        $this->modulesPath = $modulesPath ?? $this->getDefaultModulesPath();
        $this->layoutPath = $layoutPath ?? $this->getDefaultLayoutPath();
    }

    /**
     * Render a module with layout and return the content
     */
    public function render(string $module): string
    {
        // Security: Validate module name
        if (! preg_match('/^[a-z0-9_-]+$/', $module)) {
            return $this->renderError(500);
        }

        $modulePath = $this->modulesPath . "/{$module}/index.php";

        if (! file_exists($modulePath)) {
            return $this->renderError(404);
        }

        // Capture module content
        ob_start();
        require $modulePath;
        $content = ob_get_clean();

        // Render with layout
        return $this->renderWithLayout($content);
    }

    /**
     * Render error page and return the content
     */
    public function renderError(int $code, ?string $type = null): string
    {
        $type ??= match ($code) {
            400 => '400',
            404 => '404',
            500, 503 => '500',
            default => '404'
        };

        // Make error type available to error module
        $errorType = $type;

        $errorModulePath = $this->modulesPath . "/error/index.php";

        if (! file_exists($errorModulePath)) {
            return "Error {$code}";
        }

        // Capture error module content
        ob_start();
        require $errorModulePath;
        $content = ob_get_clean();

        // Render with layout
        return $this->renderWithLayout($content);
    }

    /**
     * Render maintenance page
     */
    public function renderMaintenance(): string
    {
        return $this->renderError(503, 'maintenance');
    }

    /**
     * Render content with layout and return as string
     */
    protected function renderWithLayout(string $content): string
    {
        if (file_exists($this->layoutPath)) {
            ob_start();
            require $this->layoutPath;

            return ob_get_clean();
        }

        return $content;
    }

    /**
     * Get modules path
     */
    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    /**
     * Set modules path
     */
    public function setModulesPath(string $path): void
    {
        $this->modulesPath = $path;
    }

    /**
     * Get layout path
     */
    public function getLayoutPath(): string
    {
        return $this->layoutPath;
    }

    /**
     * Set layout path
     */
    public function setLayoutPath(string $path): void
    {
        $this->layoutPath = $path;
    }

    /**
     * Get default modules path
     */
    protected function getDefaultModulesPath(): string
    {
        return base_path('app/modules');
    }

    /**
     * Get default layout path
     */
    protected function getDefaultLayoutPath(): string
    {
        return base_path('app/base/view/layouts/default.php');
    }
}
