<?php

declare(strict_types=1);

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
     * Render a module with layout
     */
    public function render(string $module): void
    {
        // Security: Validate module name
        if (!preg_match('/^[a-z0-9_-]+$/', $module)) {
            $this->renderError(500);
            return;
        }

        $modulePath = $this->modulesPath . "/{$module}/index.php";

        if (!file_exists($modulePath)) {
            $this->renderError(404);
            return;
        }

        // Capture module content
        ob_start();
        require $modulePath;
        $content = ob_get_clean();

        // Render with layout
        $this->renderWithLayout($content);
    }

    /**
     * Render error page
     */
    public function renderError(int $code, ?string $type = null): void
    {
        http_response_code($code);

        $type = $type ?? match ($code) {
            400 => '400',
            404 => '404',
            500, 503 => '500',
            default => '404'
        };

        // Make error type available to error module
        $errorType = $type;

        $errorModulePath = $this->modulesPath . "/error/index.php";

        if (!file_exists($errorModulePath)) {
            echo "Error {$code}";
            exit;
        }

        // Capture error module content
        ob_start();
        require $errorModulePath;
        $content = ob_get_clean();

        // Render with layout
        $this->renderWithLayout($content);
        exit;
    }

    /**
     * Render maintenance page
     */
    public function renderMaintenance(): void
    {
        $this->renderError(503, 'maintenance');
    }

    /**
     * Render content with layout
     */
    protected function renderWithLayout(string $content): void
    {
        if (file_exists($this->layoutPath)) {
            require $this->layoutPath;
        } else {
            echo $content;
        }
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
