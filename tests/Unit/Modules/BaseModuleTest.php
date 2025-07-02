<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Module;
use App\Modules\ValueObject\ModuleMetadata;
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
            eval(<<<'PHP'

                namespace Tests\Unit\Modules;
                
                use App\Modules\Module;
                use App\Modules\ValueObject\ModuleMetadata;
                
                class TestModule extends Module {
                    // id inherited
                    // name inherited
                    // version inherited
                    // description inherited
                    
                    public function register(): void {}
                    public function boot(): void {}
                    public function getBasePath(): string { return __DIR__; }
                    public function getViewsPath(): string { return __DIR__ . '/views'; }
                    public function getNamespace(): string { return __NAMESPACE__; }
                    
                    public function getId(): string
                     {
                         return 'test/module';
                     }

                     public function getName(): string
                     {
                         return 'Test Module';
                     }

                     public function getVersion(): string
                     {
                         return '1.0.0';
                     }

                     public function getDescription(): string
                     {
                         return 'A test module';
                     }

                     public function getAuthor(): array
                     {
                         return [
                             'name' => 'Tester',
                             'email' => null,
                             'url' => null,
                         ];
                     }
                    
                    protected function generateDefaultId(): string
                     {
                         return 'test/module';
                     }

                     protected function generateDefaultName(): string
                     {
                         return 'Test Module';
                     }

                     public function isCompatible(): bool
                     {
                         return true;
                     }
                }
                
                class TestModuleWithDeps extends TestModule {
                    // id inherited
                    // dependencies overridden in getMetadata
                    
                    public function getId(): string
                     {
                         return 'test/module-with-deps';
                     }

                     protected function generateDefaultId(): string
                      {
                          return 'test/module-with-deps';
                      }

                      public function getDependencies(): array
                      {
                          return ['test/dependency' => '^1.0'];
                      }
                }
                
                class TestModuleWithConflicts extends TestModule {
                    // id inherited
                    // conflicts overridden in getMetadata
                    
                    public function getId(): string
                     {
                         return 'test/module-with-conflicts';
                     }

                     protected function generateDefaultId(): string
                      {
                          return 'test/module-with-conflicts';
                      }

                      public function getConflicts(): array
                      {
                          return ['test/conflict' => '*'];
                      }
                }
            PHP
            );
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
