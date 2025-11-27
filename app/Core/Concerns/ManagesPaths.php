<?php

declare(strict_types=1);

namespace App\Core\Concerns;

/**
 * Manages Paths
 * 
 * Provides path resolution methods for the application.
 * Follows Single Responsibility Principle - only handles path logic.
 * 
 * Requires: protected string $basePath to be defined in the using class.
 */
trait ManagesPaths
{
    /**
     * Get the base path of the installation
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the application "app" directory
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the storage directory
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('var' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the public directory
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath('pub' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the configuration directory
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the database directory
     */
    public function databasePath(string $path = ''): string
    {
        return $this->basePath('database' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the resources directory
     */
    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}
