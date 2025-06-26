<?php declare(strict_types=1);

namespace App\Modules\Test;

use App\Modules\Concerns\RegistersViewPaths;
use App\Modules\Module;
use App\Modules\Core\CoreModule;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Test module for demonstrating module dependencies
 */
class TestModule extends Module
{
    use RegistersViewPaths;
    
    /**
     * Get the module's dependencies
     * 
     * @return array<string> Array of module class names that this module depends on
     */
    public function getDependencies(): array
    {
        return [
            CoreModule::class
        ];
    }
    
    /**
     * Register module services
     */
    public function register(): void
    {
        $this->registerViewPaths();
        $this->registerServices();
    }
    
    /**
     * Register module services
     */
    private function registerServices(): void
    {
        $this->container->set('test.service', function(ContainerInterface $c) {
            return new class($c->get(LoggerInterface::class)) {
                public function __construct(
                    private LoggerInterface $logger
                ) {}
                
                public function test(): string
                {
                    $this->logger->debug('Test service called');
                    return 'Test service is working!';
                }
            };
        });
    }
    
    /**
     * Boot the module
     */
    public function boot(): void
    {
        if ($this->container->has(LoggerInterface::class)) {
            $this->container->get(LoggerInterface::class)->debug('Booting TestModule');
        }
    }
}
