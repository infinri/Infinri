<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Shared\Middleware\RateLimitMiddleware;
use App\Modules\Shared\SharedModule;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Redis;
use Tests\Unit\Modules\ModuleTestCase;

class SharedModuleTest extends ModuleTestCase
{
    private SharedModule $module;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up required container entries
        $this->container->set(LoggerInterface::class, $this->createMock(LoggerInterface::class));
        $this->container->set(Redis::class, $this->createMock(Redis::class));
        $this->container->set('settings', [
            'rate_limiting' => [
                'enabled' => true,
                'limit' => 100,
                'interval' => 60,
            ]
        ]);
        
        $this->module = new SharedModule($this->container);
    }
    
    #[Test]
    public function it_registers_shared_services(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $this->assertTrue($this->container->has(RateLimitMiddleware::class));
    }
    
    #[Test]
    public function it_registers_rate_limit_middleware(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $middleware = $this->container->get(RateLimitMiddleware::class);
        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }
    
    #[Test]
    public function it_boots_without_errors(): void
    {
        // This is mostly to ensure the boot method doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        
        $this->module->boot();
    }
}
