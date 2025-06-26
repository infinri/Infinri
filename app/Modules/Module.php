<?php declare(strict_types=1);

namespace App\Modules;

use Psr\Container\ContainerInterface;

abstract class Module
{
    protected string $name;
    protected string $basePath;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->basePath = $this->getBasePath();
        $this->name = $this->getName();
    }

    /**
     * Get the module name (derived from class name by default)
     */
    protected function getName(): string
    {
        $className = get_class($this);
        $namespaceParts = explode('\\', $className);
        return end($namespaceParts);
    }

    /**
     * Get the base path of the module
     */
    protected function getBasePath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName(), 2);
    }

    /**
     * Get the path to the module's views directory
     */
    public function getViewsPath(): string
    {
        return $this->basePath . '/Views';
    }

    /**
     * Get the module's namespace
     */
    public function getNamespace(): string
    {
        $className = get_class($this);
        return substr($className, 0, strrpos($className, '\\'));
    }

    /**
     * Register any module services
     */
    public function register(): void
    {
        // To be implemented by child classes
    }

    /**
     * Boot the module
     */
    public function boot(): void
    {
        // To be implemented by child classes
    }
}
