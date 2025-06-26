<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Module;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Base test case for module tests
 */
abstract class ModuleTestCase extends TestCase
{
    protected ContainerInterface $container;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
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
