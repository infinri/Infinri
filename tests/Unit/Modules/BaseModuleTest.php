<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Module;
use App\Modules\ModuleMetadata;
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
                
                use App\Modules\BaseModule;
                use App\Modules\ModuleMetadata;
                
                class TestModule extends BaseModule {
                    protected static string $id = 'test/module';
                    protected static string $name = 'Test Module';
                    protected static string $version = '1.0.0';
                    protected static string $description = 'A test module';
                    
                    public function register(): void {}
                    public function boot(): void {}
                    public function getBasePath(): string { return __DIR__; }
                    public function getViewsPath(): string { return __DIR__ . '/views'; }
                    public function getNamespace(): string { return __NAMESPACE__; }
                    
                    public static function getMetadata(): ModuleMetadata
                    {
                        return new ModuleMetadata([
                            'id' => static::$id,
                            'name' => static::$name,
                            'version' => static::$version,
                            'description' => static::$description,
                        ]);
                    }
                    
                    public function isCompatible(): bool
                    {
                        return true;
                    }
                }
            
                namespace Tests\Unit\Modules;
                
                class TestModuleWithDeps extends TestModule {
                    protected static string $id = 'test/module-with-deps';
                    protected static array $dependencies = ['test/dependency' => '^1.0'];
                    
                    public static function getMetadata(): ModuleMetadata
                    {
                        $metadata = parent::getMetadata();
                        $metadata->setDependencies(static::$dependencies);
                        return $metadata;
                    }
                }
                
                class TestModuleWithConflicts extends TestModule {
                    protected static string $id = 'test/module-with-conflicts';
                    protected static array $conflicts = ['test/conflict' => '*'];
                    
                    public static function getMetadata(): ModuleMetadata
                    {
                        $metadata = parent::getMetadata();
                        $metadata->setConflicts(static::$conflicts);
                        return $metadata;
                    }
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
    
    #[Test]
    public function it_returns_metadata(): void
    {
        $module = $this->createModule($this->testModuleClass);
        $metadata = $module->getMetadata();
        
        $this->assertInstanceOf(ModuleMetadata::class, $metadata);
        $this->assertEquals('test/module', $metadata->getId());
        $this->assertEquals('Test Module', $metadata->getName());
        $this->assertEquals('1.0.0', $metadata->getVersion());
        $this->assertEquals('A test module', $metadata->getDescription());
    }
    
    #[Test]
    public function it_handles_dependencies(): void
    {
        $module = $this->createModule('Tests\Unit\Modules\TestModuleWithDeps');
        $metadata = $module->getMetadata();
        
        $this->assertEquals(['test/dependency' => '^1.0'], $metadata->getDependencies());
        $this->assertEquals(['test/dependency' => '^1.0'], $module->getDependencies());
    }
    
    #[Test]
    public function it_handles_conflicts(): void
    {
        $module = $this->createModule('Tests\Unit\Modules\TestModuleWithConflicts');
        $metadata = $module->getMetadata();
        
        $this->assertEquals(['test/conflict' => '*'], $metadata->getConflicts());
        $this->assertEquals(['test/conflict' => '*'], $module->getConflicts());
    }
    
    #[Test]
    public function it_handles_optional_dependencies(): void
    {
        $module = $this->createModule($this->testModuleClass);
        $this->assertEquals([], $module->getOptionalDependencies());
    }
    
    #[Test]
    public function it_returns_requirements(): void
    {
        $module = $this->createModule($this->testModuleClass);
        $this->assertIsArray($module->getRequirements());
    }
    
    #[Test]
    public function it_checks_compatibility(): void
    {
        $module = $this->createModule($this->testModuleClass);
        $this->assertTrue($module->isCompatible());
    }
}
