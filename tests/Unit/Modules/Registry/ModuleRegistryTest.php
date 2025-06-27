<?php declare(strict_types=1);

namespace Tests\Unit\Modules\Registry;

use App\Modules\ModuleInterface;
use App\Modules\Registry\ModuleRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModuleRegistryTest extends TestCase
{
    private ModuleRegistry $registry;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ModuleRegistry();
    }
    
    private array $moduleCounter = [];
    
    private function createTestModule(string $id): ModuleInterface
    {
        // Track how many times we've created a module with this ID
        $this->moduleCounter[$id] = ($this->moduleCounter[$id] ?? 0) + 1;
        $counter = $this->moduleCounter[$id];
        
        // Create a unique class name for each module instance
        $className = 'TestModule_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $id) . '_' . $counter;
        
        // Create a new anonymous class with a unique name
        $code = "class {$className} implements \\App\\Modules\\ModuleInterface {
            private string \$id;
            private string \$name;
            
            public function __construct() {
                \$this->id = '{$id}_' . '{$counter}';
                \$this->name = 'Test Module {$id} {$counter}';
            }
            
            public function getId(): string { return \$this->id; }
            public function getName(): string { return \$this->name; }
            public function getVersion(): string { return '1.0.0'; }
            public function getDescription(): string { return 'Test module'; }
            public function getAuthor(): array { 
                return [
                    'name' => 'Test Author',
                    'email' => 'author@example.com',
                    'url' => 'https://example.com/author'
                ];
            }
            public function register(): void {}
            public function boot(): void {}
            public function getBasePath(): string { return '/path/to/module'; }
            public function getViewsPath(): string { return '/path/to/module/views'; }
            public function getNamespace(): string { return 'App\\\\Modules\\\\Test'; }
            public function getDependencies(): array { return []; }
            public function getOptionalDependencies(): array { return []; }
            public function getConflicts(): array { return []; }
            public function isCompatible(): bool { return true; }
            public function getRequirements(): array { return []; }
        }";
        
        eval($code);
        $fullClassName = '\\' . $className;
        return new $fullClassName();
    }

    public function testRegisterModule(): void
    {
        $module = $this->createTestModule('test-module');
        $this->registry->register($module);
        $this->assertTrue($this->registry->has($module::class));
    }

    public function testRegisterSameModuleTwiceThrowsException(): void
    {
        $module = $this->createTestModule('test-module');
        $this->expectException(\RuntimeException::class);
        $this->registry->register($module);
        $this->registry->register($module);
    }

    public function testGetModule(): void
    {
        $module = $this->createTestModule('test-module');
        $this->registry->register($module);
        $retrieved = $this->registry->get($module::class);
        $this->assertSame($module, $retrieved);
    }

    public function testGetNonExistentModuleThrowsException(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->registry->get('NonExistent\Module\Class');
    }

    public function testHasModule(): void
    {
        $module = $this->createTestModule('test-module');
        $this->assertFalse($this->registry->has($module::class));
        $this->registry->register($module);
        $this->assertTrue($this->registry->has($module::class));
    }

    public function testGetAllModules(): void
    {
        $module1 = $this->createTestModule('test-module-1');
        $module2 = $this->createTestModule('test-module-2');
        
        $this->registry->register($module1);
        $this->registry->register($module2);
        
        $allModules = $this->registry->all();
        
        $this->assertCount(2, $allModules);
        
        // Convert to array of IDs for easier comparison
        $moduleIds = array_map(fn($m) => $m->getId(), $allModules);
        $this->assertContains($module1->getId(), $moduleIds);
        $this->assertContains($module2->getId(), $moduleIds);
    }

    public function testGetByTag(): void
    {
        $module1 = $this->createTestModule('test-module-1');
        $module2 = $this->createTestModule('test-module-2');
        
        $this->registry->register($module1);
        $this->registry->register($module2);
        
        // Add tags
        $this->registry->tagModule(get_class($module1), 'tag1');
        $this->registry->tagModule(get_class($module1), 'tag2');
        $this->registry->tagModule(get_class($module2), 'tag2');
        
        $tag1Modules = $this->registry->getByTag('tag1');
        $tag2Modules = $this->registry->getByTag('tag2');
        $tag3Modules = $this->registry->getByTag('non-existent-tag');
        
        $this->assertCount(1, $tag1Modules);
        $this->assertArrayHasKey(get_class($module1), $tag1Modules);
        
        $this->assertCount(2, $tag2Modules);
        $this->assertArrayHasKey(get_class($module1), $tag2Modules);
        $this->assertArrayHasKey(get_class($module2), $tag2Modules);
        
        $this->assertCount(0, $tag3Modules);
    }

    public function testGetByInterface(): void
    {
        // Create a test interface and implementation with valid names
        $testInterface = 'TestInterface_' . bin2hex(random_bytes(8));
        $testClass = 'TestClass_' . bin2hex(random_bytes(8));
        
        $code = "
            namespace Test;
            
            interface {$testInterface} {}
            class {$testClass} implements \\App\\Modules\\ModuleInterface, \\Test\\{$testInterface} {
                private string \$id = 'test-id';
                private string \$name = 'Test Module';
                
                public function getId(): string { return \$this->id; }
                public function getName(): string { return \$this->name; }
                public function getVersion(): string { return '1.0.0'; }
                public function getDescription(): string { return 'Test module description'; }
                public function getAuthor(): array { 
                    return [
                        'name' => 'Test Author',
                        'email' => 'author@example.com',
                        'url' => 'https://example.com/author'
                    ];
                }
                public function register(): void {}
                public function boot(): void {}
                public function getBasePath(): string { return '/path/to/module'; }
                public function getViewsPath(): string { return '/path/to/module/views'; }
                public function getNamespace(): string { return 'App\\\\Modules\\\\Test'; }
                public function getDependencies(): array { return []; }
                public function getOptionalDependencies(): array { return []; }
                public function getConflicts(): array { return []; }
                public function isCompatible(): bool { return true; }
                public function getRequirements(): array { return []; }
            }
        ";
        
        eval($code);
        
        $testClassFQN = '\\Test\\' . $testClass;
        $testInterfaceFQN = '\\Test\\' . $testInterface;
        $testInstance = new $testClassFQN();
        
        $this->registry->register($testInstance);
        
        $results = $this->registry->getByInterface($testInterfaceFQN);
        
        // Debug output to help diagnose the issue
        $actualKeys = array_keys($results);
        $this->assertCount(1, $results, sprintf(
            'Expected exactly 1 result, got %d. Results: %s', 
            count($results), 
            json_encode($actualKeys, JSON_PRETTY_PRINT)
        ));
        
        // Check if any key in the results matches our expected class
        $found = false;
        foreach ($results as $key => $value) {
            if ($value === $testInstance) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, sprintf(
            'Expected instance of %s not found in results. Results: %s', 
            $testClassFQN, 
            json_encode($results, JSON_PRETTY_PRINT)
        ));
        
        // Check if the key exists (case-insensitive comparison and handle leading backslash)
        $keyExists = false;
        $normalizedExpected = ltrim($testClassFQN, '\\');
        
        foreach (array_keys($results) as $key) {
            if (strtolower(ltrim($key, '\\')) === strtolower($normalizedExpected)) {
                $keyExists = true;
                break;
            }
        }
        
        $this->assertTrue($keyExists, sprintf(
            'Expected key "%s" not found in results. Actual keys: %s', 
            $normalizedExpected, 
            json_encode($actualKeys, JSON_PRETTY_PRINT)
        ));
    }

    public function testGetByNonExistentInterfaceThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->registry->getByInterface('NonExistent\Interface\Name');
    }

    public function testTagModule(): void
    {
        $module = $this->createTestModule('test-module');
        $this->registry->register($module);
        
        // Test adding a tag
        $this->registry->tagModule($module::class, 'test-tag');
        
        $taggedModules = $this->registry->getByTag('test-tag');
        $this->assertCount(1, $taggedModules);
        $this->assertArrayHasKey($module::class, $taggedModules);
        
        // Test adding the same tag twice (should be idempotent)
        $this->registry->tagModule($module::class, 'test-tag');
        $taggedModules = $this->registry->getByTag('test-tag');
        $this->assertCount(1, $taggedModules);
    }

    public function testTagNonExistentModule(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $nonExistentClass = 'NonExistent\\Module\\Class' . uniqid('', true);
        $this->expectExceptionMessage(sprintf('Module %s is not registered', $nonExistentClass));
        $this->registry->tagModule($nonExistentClass, 'test-tag');
    }
}
