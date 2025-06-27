<?php declare(strict_types=1);

namespace Tests\Unit\Modules\Discovery;

use App\Modules\Discovery\ModuleDiscovery;
use App\Modules\ModuleInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ModuleDiscoveryTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $modulesPath;
    private string $cacheFile;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up a virtual filesystem for testing
        $this->root = vfsStream::setup('root');
        $this->modulesPath = vfsStream::url('root/modules');
        $this->cacheFile = vfsStream::url('root/cache/modules.cache');
        
        // Create modules directory
        mkdir($this->modulesPath, 0777, true);
        mkdir(dirname($this->cacheFile), 0777, true);
    }
    
    /**
     * Create a test module directory structure
     */
    private function createTestModule(
        string $moduleName, 
        bool $valid = true,
        bool $psr4 = true,
        ?string $className = null
    ): string {
        $moduleDir = $this->modulesPath . '/' . $moduleName;
        mkdir($moduleDir, 0777, true);
        
        if ($valid) {
            $className = $className ?? $moduleName . 'Module';
            $namespace = 'Test\\Modules\\' . $moduleName;
            
            if ($psr4) {
                mkdir($moduleDir . '/src', 0777, true);
                $filePath = $moduleDir . '/src/' . $className . '.php';
            } else {
                $filePath = $moduleDir . '/' . $className . '.php';
            }
            
            $code = <<<PHP
<?php

namespace {$namespace};

use App\\Modules\\ModuleInterface;

class {$className} implements ModuleInterface
{
    public function getId(): string { return 'test-id'; }
    public function getName(): string { return 'Test Module'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDescription(): string { return 'Test module'; }
    public function getAuthor(): array { 
        return [
            'name' => 'Test',
            'email' => 'test@example.com',
            'url' => 'https://example.com'
        ];
    }
    public function register(): void {}
    public function boot(): void {}
    public function getBasePath(): string { return __DIR__; }
    public function getViewsPath(): string { return __DIR__ . '/views'; }
    public function getNamespace(): string { return __NAMESPACE__; }
    public function getDependencies(): array { return []; }
    public function getOptionalDependencies(): array { return []; }
    public function getConflicts(): array { return []; }
    public function isCompatible(): bool { return true; }
    public function getRequirements(): array { return []; }
}
PHP;
            
            file_put_contents($filePath, $code);
        }
        
        return $moduleDir;
    }
    
    public function testDiscoverFindsPsr4Module(): void
    {
        // Debug: Check if modules directory exists before creating test module
        $this->assertDirectoryExists($this->modulesPath, 'Modules directory should exist');
        
        // Create test module and verify it was created
        $moduleDir = $this->createTestModule('TestModule1');
        $this->assertDirectoryExists($moduleDir, 'Test module directory should exist');
        
        // Verify the module file was created
        $moduleFile = $moduleDir . '/src/TestModule1Module.php';
        $this->assertFileExists($moduleFile, 'Module file should exist');
        
        // Manually include the module file since it's in a virtual filesystem
        require_once $moduleFile;
        
        // Verify the module class exists and implements ModuleInterface
        $className = 'Test\\Modules\\TestModule1\\TestModule1Module';
        $this->assertTrue(
            class_exists($className, false), 
            'Module class should exist: ' . $className
        );
        
        $this->assertTrue(
            is_subclass_of($className, 'App\\Modules\\ModuleInterface'),
            'Module class should implement ModuleInterface: ' . $className
        );
        
        // Run discovery
        $discovery = new ModuleDiscovery($this->modulesPath, false);
        $modules = $discovery->discover();
        
        // Verify discovery results
        $this->assertCount(1, $modules, 'Should find exactly one module');
        $this->assertSame($className, $modules[0], 'Discovered module class should match expected');
    }
    
    public function testDiscoverFindsLegacyModule(): void
    {
        $moduleDir = $this->createTestModule('LegacyModule', true, false);
        $this->assertDirectoryExists($moduleDir, 'Legacy module directory should exist');
        
        // Manually include the module file
        $moduleFile = $moduleDir . '/LegacyModuleModule.php';
        $this->assertFileExists($moduleFile, 'Legacy module file should exist');
        require_once $moduleFile;
        
        // Verify the module class exists and implements ModuleInterface
        $className = 'Test\\Modules\\LegacyModule\\LegacyModuleModule';
        $this->assertTrue(
            class_exists($className, false), 
            'Legacy module class should exist: ' . $className
        );
        $this->assertTrue(
            is_subclass_of($className, 'App\\Modules\\ModuleInterface'),
            'Legacy module class should implement ModuleInterface: ' . $className
        );
        
        // Run discovery
        $discovery = new ModuleDiscovery($this->modulesPath, false);
        $modules = $discovery->discover();
        
        // Verify discovery results
        $this->assertCount(1, $modules, 'Should find exactly one module');
        $this->assertSame($className, $modules[0], 'Discovered module class should match expected');
    }
    
    public function testDiscoverSkipsInvalidModules(): void
    {
        // Create a valid module
        $moduleDir = $this->createTestModule('ValidModule');
        $this->assertDirectoryExists($moduleDir, 'Valid module directory should exist');
        
        // Manually include the valid module file
        $moduleFile = $moduleDir . '/src/ValidModuleModule.php';
        $this->assertFileExists($moduleFile, 'Valid module file should exist');
        require_once $moduleFile;
        
        // Verify the valid module class exists
        $validClassName = 'Test\\Modules\\ValidModule\\ValidModuleModule';
        $this->assertTrue(
            class_exists($validClassName, false),
            'Valid module class should exist: ' . $validClassName
        );
        
        // Create an invalid module (no class file)
        $invalidModuleDir = $this->modulesPath . '/InvalidModule';
        mkdir($invalidModuleDir, 0777, true);
        $this->assertDirectoryExists($invalidModuleDir, 'Invalid module directory should exist');
        
        // Run discovery
        $discovery = new ModuleDiscovery($this->modulesPath, false);
        $modules = $discovery->discover();
        
        // Verify only the valid module is discovered
        $this->assertCount(1, $modules, 'Should only find the valid module');
        $this->assertSame($validClassName, $modules[0], 'Should only find the valid module class');
    }
    
    public function testDiscoverWithCache(): void
    {
        // Create a module
        $moduleDir = $this->createTestModule('CachedModule');
        $this->assertDirectoryExists($moduleDir, 'Cached module directory should exist');
        
        // Manually include the module file
        $moduleFile = $moduleDir . '/src/CachedModuleModule.php';
        $this->assertFileExists($moduleFile, 'Cached module file should exist');
        require_once $moduleFile;
        
        // Verify the module class exists
        $cachedClassName = 'Test\\Modules\\CachedModule\\CachedModuleModule';
        $this->assertTrue(
            class_exists($cachedClassName, false),
            'Cached module class should exist: ' . $cachedClassName
        );
        
        // First discovery (should populate cache)
        $discovery = new ModuleDiscovery($this->modulesPath, true, $this->cacheFile);
        $modules = $discovery->discover();
        
        // Verify discovery and cache file
        $this->assertCount(1, $modules, 'Should find one module');
        $this->assertFileExists($this->cacheFile, 'Cache file should be created');
        $this->assertSame($cachedClassName, $modules[0], 'Should find the cached module class');
        
        // Create a new module that shouldn't be discovered due to caching
        $newModuleDir = $this->createTestModule('NewModule');
        $this->assertDirectoryExists($newModuleDir, 'New module directory should exist');
        
        // Manually include the new module file
        $newModuleFile = $newModuleDir . '/src/NewModuleModule.php';
        $this->assertFileExists($newModuleFile, 'New module file should exist');
        require_once $newModuleFile;
        
        // Verify the new module class exists
        $newClassName = 'Test\\Modules\\NewModule\\NewModuleModule';
        $this->assertTrue(
            class_exists($newClassName, false),
            'New module class should exist: ' . $newClassName
        );
        
        // Second discovery (should use cache and not find the new module)
        $discovery = new ModuleDiscovery($this->modulesPath, true, $this->cacheFile);
        $cachedModules = $discovery->discover();
        
        // Verify cache is used (should not find the new module)
        $this->assertCount(1, $cachedModules, 'Should still only find one module due to caching');
        $this->assertSame($cachedClassName, $cachedModules[0], 'Should still return the cached module');
    }
    
    public function testDiscoverWithoutCache(): void
    {
        // Create first module
        $firstModuleDir = $this->createTestModule('NoCacheModule');
        $this->assertDirectoryExists($firstModuleDir, 'First module directory should exist');
        
        // Manually include the first module file
        $firstModuleFile = $firstModuleDir . '/src/NoCacheModuleModule.php';
        $this->assertFileExists($firstModuleFile, 'First module file should exist');
        require_once $firstModuleFile;
        
        // Verify the first module class exists
        $firstClassName = 'Test\\Modules\\NoCacheModule\\NoCacheModuleModule';
        $this->assertTrue(
            class_exists($firstClassName, false),
            'First module class should exist: ' . $firstClassName
        );
        
        // First discovery with cache disabled
        $discovery = new ModuleDiscovery($this->modulesPath, false, $this->cacheFile);
        $modules = $discovery->discover();
        
        // Verify first discovery found the module
        $this->assertCount(1, $modules, 'Should find the first module');
        $this->assertSame($firstClassName, $modules[0], 'Should find the first module class');
        
        // Create second module
        $secondModuleDir = $this->createTestModule('NewModule');
        $this->assertDirectoryExists($secondModuleDir, 'Second module directory should exist');
        
        // Manually include the second module file
        $secondModuleFile = $secondModuleDir . '/src/NewModuleModule.php';
        $this->assertFileExists($secondModuleFile, 'Second module file should exist');
        require_once $secondModuleFile;
        
        // Verify the second module class exists
        $secondClassName = 'Test\\Modules\\NewModule\\NewModuleModule';
        $this->assertTrue(
            class_exists($secondClassName, false),
            'Second module class should exist: ' . $secondClassName
        );
        
        // Second discovery with cache still disabled - should find both modules
        $discovery = new ModuleDiscovery($this->modulesPath, false, $this->cacheFile);
        $newModules = $discovery->discover();
        
        // Verify second discovery finds both modules
        $this->assertCount(2, $newModules, 'Should find both modules without cache');
        $this->assertContains($firstClassName, $newModules, 'Should still find the first module');
        $this->assertContains($secondClassName, $newModules, 'Should find the second module');
    }
    
    public function testDiscoverWithInvalidModulesDirectory(): void
    {
        $invalidPath = '/path/to/nonexistent/directory';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Modules directory does not exist: {$invalidPath}");
        
        $discovery = new ModuleDiscovery($invalidPath, false);
        $discovery->discover();
    }
    
    public function testDiscoverWithMultipleModules(): void
    {
        // Create and verify first module
        $moduleADir = $this->createTestModule('ModuleA');
        $this->assertDirectoryExists($moduleADir, 'ModuleA directory should exist');
        $moduleAFile = $moduleADir . '/src/ModuleAModule.php';
        $this->assertFileExists($moduleAFile, 'ModuleA file should exist');
        require_once $moduleAFile;
        $classNameA = 'Test\\Modules\\ModuleA\\ModuleAModule';
        $this->assertTrue(
            class_exists($classNameA, false),
            'ModuleA class should exist: ' . $classNameA
        );
        
        // Create and verify second module
        $moduleBDir = $this->createTestModule('ModuleB');
        $this->assertDirectoryExists($moduleBDir, 'ModuleB directory should exist');
        $moduleBFile = $moduleBDir . '/src/ModuleBModule.php';
        $this->assertFileExists($moduleBFile, 'ModuleB file should exist');
        require_once $moduleBFile;
        $classNameB = 'Test\\Modules\\ModuleB\\ModuleBModule';
        $this->assertTrue(
            class_exists($classNameB, false),
            'ModuleB class should exist: ' . $classNameB
        );
        
        // Create and verify third module
        $moduleCDir = $this->createTestModule('ModuleC');
        $this->assertDirectoryExists($moduleCDir, 'ModuleC directory should exist');
        $moduleCFile = $moduleCDir . '/src/ModuleCModule.php';
        $this->assertFileExists($moduleCFile, 'ModuleC file should exist');
        require_once $moduleCFile;
        $classNameC = 'Test\\Modules\\ModuleC\\ModuleCModule';
        $this->assertTrue(
            class_exists($classNameC, false),
            'ModuleC class should exist: ' . $classNameC
        );
        
        // Run discovery
        $discovery = new ModuleDiscovery($this->modulesPath, false);
        $modules = $discovery->discover();
        
        // Verify all modules are discovered
        $this->assertCount(3, $modules, 'Should find all three modules');
        $this->assertContains($classNameA, $modules, 'Should find ModuleA');
        $this->assertContains($classNameB, $modules, 'Should find ModuleB');
        $this->assertContains($classNameC, $modules, 'Should find ModuleC');
    }
}
