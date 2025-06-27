<?php declare(strict_types=1);

namespace App\Modules\Pages;

use App\Modules\ModuleState;

use App\Modules\Concerns\RegistersViewPaths;
use App\Modules\Concerns\RegistersControllers;
use App\Modules\BaseModule;
use App\Modules\Pages\Controllers\PageController;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;

/**
 * Pages module for handling static pages
 * 
 * This module provides functionality for serving static pages.
 * It handles the registration of page templates and their corresponding routes.
 */
class PagesModule extends BaseModule
{
    use RegistersViewPaths, RegistersControllers;

    /**
     * Register pages module services and configurations
     * 
     * Registers view paths and page controller.
     */
    public function register(): void
    {
        $this->registerViewPaths();
        $this->registerControllers();
    }

    /**
     * Boot the pages module
     * 
     * This method is called after all modules have been registered.
     */
    public function boot(): void
    {
        // Pages module bootstrapping logic can be added here
    }

    /**
     * Register pages module controllers
     */
    private function registerControllers(): void
    {
        $this->registerController(PageController::class, [
            Engine::class,
            'container' => ContainerInterface::class
        ]);
    }
    
    /**
     * Get the module's unique identifier.
     */
    public function getId(): string
    {
        return 'infinri/pages';
    }

    /**
     * Get the module's human-readable name.
     */
    public function getName(): string
    {
        return 'Pages';
    }

    /**
     * Get the module's semantic version.
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get the module's description.
     */
    public function getDescription(): string
    {
        return 'Provides functionality for managing static pages';
    }

    /**
     * Get the module's author information.
     *
     * @return array{name: string, email?: string, url?: string}
     */
    public function getAuthor(): array
    {
        return [
            'name' => 'Infinri Team',
            'email' => 'team@infinri.com',
            'url' => 'https://infinri.com',
        ];
    }

    /**
     * Get the module's base path.
     */
    public function getBasePath(): string
    {
        return dirname(__DIR__, 1) . '/Pages';
    }

    /**
     * Get the module's views path.
     */
    public function getViewsPath(): string
    {
        return $this->getBasePath() . '/resources/views';
    }

    /**
     * Get the module's root namespace.
     */
    public function getNamespace(): string
    {
        return 'App\\Modules\\Pages';
    }

    /**
     * Get the module's dependencies.
     *
     * @return array<class-string,string>
     */
    public function getDependencies(): array
    {
        return [
            'App\\Modules\\Core\\CoreModule' => '^1.0',
        ];
    }

    /**
     * Get the module's optional dependencies.
     *
     * @return array<class-string,string>
     */
    public function getOptionalDependencies(): array
    {
        return [];
    }

    /**
     * Get the module's conflicts.
     *
     * @return array<class-string,string>
     */
    public function getConflicts(): array
    {
        return [];
    }

    /**
     * Get the module's requirements.
     *
     * @return array<string,string>
     */
    public function getRequirements(): array
    {
        return [
            'php' => '^8.1',
            'ext-pdo' => '*',
            'ext-mbstring' => '*',
        ];
    }

    /**
     * Get the module's state.
     */
    public function getState(): ModuleState
    {
        return ModuleState::ENABLED;
    }

    /**
     * Check if the module is compatible with the current environment.
     */
    public function isCompatible(): bool
    {
        // Check for required PHP extensions
        $requiredExtensions = [
            'pdo',
            'pdo_mysql',
            'mbstring',
        ];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                trigger_error(
                    sprintf('Missing required PHP extension: %s', $ext),
                    E_USER_WARNING
                );
                return false;
            }
        }
        
        // Check for required environment variables
        $requiredEnvVars = [
            'APP_ENV',
        ];
        
        foreach ($requiredEnvVars as $var) {
            if (empty($_ENV[$var] ?? null)) {
                trigger_error(
                    sprintf('Missing required environment variable: %s', $var),
                    E_USER_WARNING
                );
                return false;
            }
        }
        
        return true;
    }
}
