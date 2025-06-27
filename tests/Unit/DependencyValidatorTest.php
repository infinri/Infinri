<?php declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Validation\DependencyValidator;
use App\Modules\ValueObject\ModuleMetadata;
use App\Modules\ValueObject\VersionConstraint;
use App\Modules\ModuleState;
use PHPUnit\Framework\TestCase;

final class DependencyValidatorTest extends TestCase
{
    private function createMetadata(
        string $id,
        string $version = '1.0.0',
        array $dependencies = [],
        array $optional = [],
        array $conflicts = []
    ): ModuleMetadata {
        return new ModuleMetadata(
            id: $id,
            name: ucfirst($id),
            version: $version,
            description: '',
            author: ['name' => 'Tester'],
            basePath: __DIR__,
            namespace: 'Tests\\Stub',
            requirements: [],
            dependencies: $dependencies,
            optionalDependencies: $optional,
            conflicts: $conflicts,
            state: ModuleState::INSTALLED
        );
    }

    public function testRequiredDependencySatisfied(): void
    {
        $validator = new DependencyValidator();

        $depClass = 'Vendor\\B\\Module';
        $modB = $this->createMetadata('vendor/b', '1.2.0');
        $modA = $this->createMetadata('vendor/a', '1.0.0', [$depClass => new VersionConstraint('^1.0')]);

        $available = [$depClass => $modB];

        // should not throw
        $validator->validateDependencies($modA, $available);
        $this->assertTrue(true);
    }

    public function testRequiredDependencyVersionMismatch(): void
    {
        $validator = new DependencyValidator();

        $depClass = 'Vendor\\B\\Module';
        $modB = $this->createMetadata('vendor/b', '2.0.0');
        $modA = $this->createMetadata('vendor/a', '1.0.0', [$depClass => new VersionConstraint('^1.0')]);

        $available = [$depClass => $modB];

        $this->expectException(\RuntimeException::class);
        $validator->validateDependencies($modA, $available);
    }

    public function testConflictDetected(): void
    {
        $validator = new DependencyValidator();

        $conflictClass = 'Vendor\\B\\Module';
        $modB = $this->createMetadata('vendor/b', '2.1.0');
        $modA = $this->createMetadata('vendor/a', '1.0.0', [], [], [$conflictClass => new VersionConstraint('^2.0')]);

        $installed = [$conflictClass => $modB];

        $this->expectException(\RuntimeException::class);
        $validator->validateNoConflicts($modA, $installed);
    }

    public function testConflictNotTriggeredWhenOutsideRange(): void
    {
        $validator = new DependencyValidator();

        $conflictClass = 'Vendor\\B\\Module';
        $modB = $this->createMetadata('vendor/b', '3.0.0');
        $modA = $this->createMetadata('vendor/a', '1.0.0', [], [], [$conflictClass => new VersionConstraint('^2.0')]);

        $installed = [$conflictClass => $modB];

        // should not throw
        $validator->validateNoConflicts($modA, $installed);
        $this->assertTrue(true);
    }
}
