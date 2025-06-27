<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Validation\DependencyValidator;
use App\Modules\ModuleMetadata;
use PHPUnit\Framework\TestCase;

class DependencyValidatorTest extends TestCase
{
    private DependencyValidator $validator;
    private array $moduleA;
    private array $moduleB;
    private array $moduleC;

    protected function setUp(): void
    {
        $this->validator = new DependencyValidator();
        
        // Create test modules
        $this->moduleA = [
            'id' => 'vendor/module-a',
            'name' => 'Module A',
            'version' => '1.0.0',
            'dependencies' => [],
            'optionalDependencies' => [],
            'conflicts' => [],
        ];
        
        $this->moduleB = [
            'id' => 'vendor/module-b',
            'name' => 'Module B',
            'version' => '2.0.0',
            'dependencies' => ['vendor/module-a' => '^1.0'],
            'optionalDependencies' => [],
            'conflicts' => [],
        ];
        
        $this->moduleC = [
            'id' => 'vendor/module-c',
            'name' => 'Module C',
            'version' => '3.0.0',
            'dependencies' => ['vendor/module-a' => '^1.0', 'vendor/module-b' => '^2.0'],
            'optionalDependencies' => [],
            'conflicts' => [],
        ];
    }
    
    public function testValidateNoConflictsWithNoModules(): void
    {
        $module = new ModuleMetadata($this->moduleA);
        $this->validator->validateNoConflicts($module, []);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
    
    public function testValidateNoConflictsWithNoConflicts(): void
    {
        $moduleA = new ModuleMetadata($this->moduleA);
        $moduleB = new ModuleMetadata($this->moduleB);
        
        $this->validator->validateNoConflicts($moduleA, ['vendor/module-b' => $moduleB]);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
    
    public function testValidateNoConflictsWithConflict(): void
    {
        $moduleA = new ModuleMetadata($this->moduleA);
        
        // Module B conflicts with Module A
        $moduleB = new ModuleMetadata(array_merge($this->moduleB, [
            'conflicts' => ['vendor/module-a' => '*'],
        ]));
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Module vendor/module-b conflicts with vendor/module-a');
        
        $this->validator->validateNoConflicts($moduleA, ['vendor/module-b' => $moduleB]);
    }
    
    public function testValidateDependenciesWithNoDependencies(): void
    {
        $module = new ModuleMetadata($this->moduleA);
        $this->validator->validateDependencies($module, []);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
    
    public function testValidateDependenciesWithSatisfiedDependencies(): void
    {
        $moduleB = new ModuleMetadata($this->moduleB);
        $moduleA = new ModuleMetadata($this->moduleA);
        
        $this->validator->validateDependencies($moduleB, ['vendor/module-a' => $moduleA]);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
    
    public function testValidateDependenciesWithMissingDependency(): void
    {
        $moduleB = new ModuleMetadata($this->moduleB);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required dependency: vendor/module-a');
        
        $this->validator->validateDependencies($moduleB, []);
    }
    
    public function testValidateDependenciesWithVersionMismatch(): void
    {
        $moduleB = new ModuleMetadata($this->moduleB);
        
        // Module A with an incompatible version
        $moduleA = new ModuleMetadata(array_merge($this->moduleA, ['version' => '2.0.0']));
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dependency version mismatch: vendor/module-a');
        
        $this->validator->validateDependencies($moduleB, ['vendor/module-a' => $moduleA]);
    }
    
    public function testValidateOptionalDependencies(): void
    {
        $module = new ModuleMetadata([
            'id' => 'test/module',
            'name' => 'Test Module',
            'version' => '1.0.0',
            'optionalDependencies' => [
                'vendor/module-a' => '^1.0',
                'vendor/module-b' => '^2.0',
            ],
        ]);
        
        // Only module A is available
        $moduleA = new ModuleMetadata($this->moduleA);
        $registered = ['vendor/module-a' => $moduleA];
        
        $missing = $this->validator->validateOptionalDependencies($module, $registered);
        
        $this->assertArrayHasKey('vendor/module-b', $missing);
        $this->assertArrayNotHasKey('vendor/module-a', $missing);
        $this->assertSame('^2.0', $missing['vendor/module-b']);
    }
    
    public function testValidateDependencyGraph(): void
    {
        $moduleA = new ModuleMetadata($this->moduleA);
        $moduleB = new ModuleMetadata($this->moduleB);
        $moduleC = new ModuleMetadata($this->moduleC);
        
        // This should pass as all dependencies are satisfied
        $this->validator->validateDependencies($moduleC, [
            'vendor/module-a' => $moduleA,
            'vendor/module-b' => $moduleB,
        ]);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
    
    public function testValidateDependencyGraphWithMissingDependency(): void
    {
        $moduleA = new ModuleMetadata($this->moduleA);
        $moduleC = new ModuleMetadata($this->moduleC);
        
        // Module C requires Module B, which is missing
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required dependency: vendor/module-b');
        
        $this->validator->validateDependencies($moduleC, [
            'vendor/module-a' => $moduleA,
        ]);
    }
}
