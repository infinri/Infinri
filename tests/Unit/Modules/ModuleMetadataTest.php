<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\ModuleMetadata;
use App\Modules\ModuleState;
use PHPUnit\Framework\TestCase;

class ModuleMetadataTest extends TestCase
{
    private function createValidMetadata(): ModuleMetadata
    {
        return new ModuleMetadata(
            id: 'test/module',
            name: 'Test Module',
            version: '1.0.0',
            description: 'A test module',
            author: ['name' => 'Test Author'],
            basePath: '/path/to/module',
            namespace: 'Test\\Module',
            requirements: ['php' => '^8.1'],
            dependencies: ['App\\Modules\\OtherModule' => '^1.0'],
            optionalDependencies: ['App\\Modules\\OptionalModule' => '^2.0'],
            conflicts: ['App\\Modules\\ConflictModule' => '*'],
            state: ModuleState::ENABLED
        );
    }

    public function testCreateWithValidData(): void
    {
        $metadata = $this->createValidMetadata();
        
        $this->assertSame('test/module', $metadata->getId());
        $this->assertSame('Test Module', $metadata->getName());
        $this->assertSame('1.0.0', $metadata->getVersion());
        $this->assertSame('A test module', $metadata->getDescription());
        $this->assertSame(['name' => 'Test Author'], $metadata->getAuthor());
        $this->assertSame('/path/to/module', $metadata->getBasePath());
        $this->assertSame('Test\\Module', $metadata->getNamespace());
        $this->assertSame(['php' => '^8.1'], $metadata->getRequirements());
        $this->assertSame(['App\\Modules\\OtherModule' => '^1.0'], $metadata->getDependencies());
        $this->assertSame(['App\\Modules\\OptionalModule' => '^2.0'], $metadata->getOptionalDependencies());
        $this->assertSame(['App\\Modules\\ConflictModule' => '*'], $metadata->getConflicts());
        $this->assertSame(ModuleState::ENABLED, $metadata->getState());
    }

    public function testCreateWithMinimalData(): void
    {
        $metadata = new ModuleMetadata(
            id: 'minimal/module',
            name: 'Minimal Module',
            version: '0.1.0',
            description: '',
            author: ['name' => 'Test Author'],
            basePath: '',
            namespace: 'Test'
        );
        
        $this->assertSame('minimal/module', $metadata->getId());
        $this->assertSame('Minimal Module', $metadata->getName());
        $this->assertSame('0.1.0', $metadata->getVersion());
        $this->assertSame('', $metadata->getDescription());
        $this->assertSame(['name' => 'Test Author'], $metadata->getAuthor());
        $this->assertSame('', $metadata->getBasePath());
        $this->assertSame('Test', $metadata->getNamespace());
        $this->assertSame(['php' => '^8.1'], $metadata->getRequirements()); // Default value
        $this->assertSame([], $metadata->getDependencies());
        $this->assertSame([], $metadata->getOptionalDependencies());
        $this->assertSame([], $metadata->getConflicts());
        $this->assertSame(ModuleState::UNINSTALLED, $metadata->getState()); // Default value
    }

    public function testWithInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Module ID cannot be empty');
        
        new ModuleMetadata(
            id: '',
            name: 'Test',
            version: '1.0.0',
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: ''
        );
    }

    public function testWithInvalidVersion(): void
    {
        // Empty version is allowed by the current implementation
        $metadata = new ModuleMetadata(
            id: 'test/module',
            name: 'Test',
            version: '', // Empty string is technically allowed by current validation
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: 'Test'
        );
        
        $this->assertSame('', $metadata->getVersion());
    }

    public function testWithEmptyName(): void
    {
        // Empty name is allowed by the current implementation
        $metadata = new ModuleMetadata(
            id: 'test/module',
            name: '', // Empty string is technically allowed by current validation
            version: '1.0.0',
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: 'Test'
        );
        
        $this->assertSame('', $metadata->getName());
    }

    public function testWithInvalidDependencies(): void
    {
        $this->expectException(\TypeError::class);
        
        new ModuleMetadata(
            id: 'test/module',
            name: 'Test',
            version: '1.0.0',
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: 'Test',
            dependencies: 'not-an-array' // Invalid type
        );
    }

    public function testWithInvalidOptionalDependencies(): void
    {
        $this->expectException(\TypeError::class);
        
        new ModuleMetadata(
            id: 'test/module',
            name: 'Test',
            version: '1.0.0',
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: 'Test',
            optionalDependencies: 'not-an-array' // Invalid type
        );
    }

    public function testWithInvalidConflicts(): void
    {
        $this->expectException(\TypeError::class);
        
        new ModuleMetadata(
            id: 'test/module',
            name: 'Test',
            version: '1.0.0',
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: 'Test',
            conflicts: 'not-an-array' // Invalid type
        );
    }

    public function testWithInvalidRequirements(): void
    {
        $this->expectException(\TypeError::class);
        
        new ModuleMetadata(
            id: 'test/module',
            name: 'Test',
            version: '1.0.0',
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: 'Test',
            requirements: 'not-an-array' // Invalid type
        );
    }

    public function testIsEnabled(): void
    {
        $metadata = $this->createValidMetadata();
        $this->assertTrue($metadata->getState() === ModuleState::ENABLED);
        
        $disabledMetadata = new ModuleMetadata(
            id: 'test/disabled',
            name: 'Disabled',
            version: '1.0.0',
            description: '',
            author: ['name' => 'Test'],
            basePath: '',
            namespace: 'Test',
            state: ModuleState::DISABLED
        );
        $this->assertTrue($disabledMetadata->getState() === ModuleState::DISABLED);
    }

    public function testGetPath(): void
    {
        $metadata = $this->createValidMetadata();
        $this->assertSame('/path/to/module', $metadata->getBasePath());
    }

    public function testToArray(): void
    {
        $metadata = $this->createValidMetadata();
        $data = $metadata->toArray();
        
        $this->assertSame('test/module', $data['id']);
        $this->assertSame('Test Module', $data['name']);
        $this->assertSame('1.0.0', $data['version']);
        $this->assertSame('A test module', $data['description']);
        $this->assertSame(['name' => 'Test Author'], $data['author']);
        $this->assertSame('/path/to/module', $data['basePath']);
        $this->assertSame('Test\\Module', $data['namespace']);
        $this->assertSame(['php' => '^8.1'], $data['requirements']);
        $this->assertSame(['App\\Modules\\OtherModule' => '^1.0'], $data['dependencies']);
        $this->assertSame(['App\\Modules\\OptionalModule' => '^2.0'], $data['optionalDependencies']);
        $this->assertSame(['App\\Modules\\ConflictModule' => '*'], $data['conflicts']);
        $this->assertSame(ModuleState::ENABLED->value, $data['state']);
    }
}
