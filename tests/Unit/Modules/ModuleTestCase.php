<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Module;
use DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Base test case for module tests
 */
abstract class ModuleTestCase extends TestCase
{
    protected ContainerInterface|MockObject $container;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock container that supports the extend method
        $this->container = $this->createMock(TestContainer::class);
        
        // Configure the mock container to handle basic operations using callbacks
        // Stubs are defined below to track services and apply extensions.
        
        // Implement extend method to store extensions
        $services = [];
        $extensions = [];
        
        $this->container->method('set')->willReturnCallback(
            function (string $id, $value) use (&$services) {
                $services[$id] = $value;
                return $this->container;
            }
        );
        
        $this->container->method('get')->willReturnCallback(
            function (string $id) use (&$services, &$extensions) {
                if (isset($services[$id])) {
                    $service = $services[$id];
                    // Apply any extensions
                    if (isset($extensions[$id])) {
                        foreach ($extensions[$id] as $extension) {
                            $service = $extension($service, $this->container);
                        }
                    }
                    return $service;
                }
                return $id; // Return the ID as a fallback
            }
        );
        
        $this->container->method('has')->willReturnCallback(
            function (string $id) use (&$services) {
                return isset($services[$id]);
            }
        );
        
        $this->container->method('extend')->willReturnCallback(
            function (string $id, callable $extension) use (&$extensions) {
                if (!isset($extensions[$id])) {
                    $extensions[$id] = [];
                }
                $extensions[$id][] = $extension;
                return $this->container;
            }
        );
    }
    
    protected function tearDown(): void
    {
        unset($this->container);
        parent::tearDown();
    }
    
    /**
     * Create a mock module instance for testing
     */
    protected function createModule(string $moduleClass): Module
    {
        return new $moduleClass($this->container);
    }
}

/**
 * Test container interface that extends ContainerInterface with additional methods
 */
interface TestContainer extends ContainerInterface
{
    public function set(string $id, $value): self;
    public function extend(string $id, callable $extension): self;
}
