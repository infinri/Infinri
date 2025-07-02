<?php declare(strict_types=1);

namespace App\Modules\Traits;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Trait for modules that need to register controllers and routes
 */
trait RegistersControllers
{
    /**
     * Register controllers and routes for the module
     * 
     * @param ContainerInterface $container
     * @param string $modulePath Path to the module directory
     */
    protected function registerControllers(ContainerInterface $container, string $modulePath): void
    {
        $controllersDir = rtrim($modulePath, '/') . '/Controllers';
        
        if (!is_dir($controllersDir)) {
            return;
        }
        
        $router = $container->get('router');
        $namespace = $this->getModuleNamespace();
        
        // Register controller routes
        $this->registerControllerRoutes($router, $namespace, $controllersDir);
    }
    
    /**
     * Get the module's namespace
     * 
     * @return string
     */
    private function getModuleNamespace(): string
    {
        $className = get_class($this);
        return substr($className, 0, strrpos($className, '\\'));
    }
    
    /**
     * Register routes for all controllers in the module
     * 
     * @param mixed $router
     * @param string $namespace
     * @param string $directory
     */
    private function registerControllerRoutes($router, string $namespace, string $directory): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname(), $namespace);
                
                if ($className && class_exists($className)) {
                    $this->registerController($router, $className);
                }
            }
        }
    }
    
    /**
     * Extract class name from file
     * 
     * @param string $file
     * @param string $namespace
     * @return string|null
     */
    private function getClassNameFromFile(string $file, string $namespace): ?string
    {
        $contents = file_get_contents($file);
        
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }
        
        if (preg_match('/class\s+([^\s]+)/', $contents, $matches)) {
            return $namespace . '\\' . $matches[1];
        }
        
        return null;
    }
    
    /**
     * Register routes for a controller
     * 
     * @param mixed $router
     * @param string $className
     */
    private function registerController($router, string $className): void
    {
        if (!class_exists($className)) {
            return;
        }
        
        $reflection = new \ReflectionClass($className);
        
        foreach ($reflection->getMethods() as $method) {
            $route = $this->getRouteFromDocComment($method->getDocComment());
            
            if ($route) {
                $this->addRoute($router, $route['method'], $route['path'], [$className, $method->getName()]);
            }
        }
    }
    
    /**
     * Extract route information from docblock
     * 
     * @param string|false $docComment
     * @return array|null
     */
    private function getRouteFromDocComment($docComment): ?array
    {
        if ($docComment === false) {
            return null;
        }
        
        if (preg_match('/@(get|post|put|delete|patch|options|any)\s+(\S+)/i', $docComment, $matches)) {
            return [
                'method' => strtoupper($matches[1]),
                'path' => $matches[2]
            ];
        }
        
        return null;
    }
    
    /**
     * Add a route to the router
     * 
     * @param mixed $router
     * @param string $method
     * @param string $path
     * @param callable $handler
     */
    private function addRoute($router, string $method, string $path, callable $handler): void
    {
        if (method_exists($router, 'map')) {
            $router->map([$method], $path, $handler);
        }
    }
}
