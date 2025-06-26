<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Module;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Modules\ModuleTestCase;

class BaseModuleTest extends ModuleTestCase
{
    private string $testModuleClass = 'Tests\Unit\Modules\TestModule';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Define a test module class
        if (!class_exists($this->testModuleClass)) {
            eval("
                namespace Tests\Unit\Modules;
                
                use App\Modules\Module;
                
                class TestModule extends Module {
                    public function register(): void {}
                    public function boot(): void {}
                    public function getBasePath(): string { return __DIR__; }
                    public function getViewsPath(): string { return __DIR__ . '/views'; }
                    public function getNamespace(): string { return __NAMESPACE__; }
                }
            ");
        }
    }
    
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $module = $this->createModule($this->testModuleClass);
        $this->assertInstanceOf(Module::class, $module);
    }
    
    #[Test]
    public function it_returns_correct_paths(): void
    {
        $module = $this->createModule($this->testModuleClass);
        
        $this->assertEquals(__DIR__, $module->getBasePath());
        $this->assertEquals(__DIR__ . '/views', $module->getViewsPath());
    }
    
    #[Test]
    public function it_returns_correct_namespace(): void
    {
        $module = $this->createModule($this->testModuleClass);
        $this->assertEquals('Tests\Unit\Modules', $module->getNamespace());
    }
}
