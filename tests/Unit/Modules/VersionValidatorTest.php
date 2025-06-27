<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Validation\VersionValidator;
use App\Modules\ModuleVersion;
use PHPUnit\Framework\TestCase;

class VersionValidatorTest extends TestCase
{
    private VersionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new VersionValidator();
    }

    public function testValidatePhpVersion(): void
    {
        // Test with current PHP version
        $this->validator->validatePhpVersion('>=' . PHP_VERSION);
        
        // Test with valid constraint
        $this->validator->validatePhpVersion('^8.0');
        
        // Should not throw an exception for valid versions
        $this->assertTrue(true);
    }

    public function testValidatePhpVersionThrowsOnInvalidVersion(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PHP version requirement not met');
        
        // This should fail as we're requiring a future PHP version
        $this->validator->validatePhpVersion('>=999.0.0');
    }

    public function testValidateExtensions(): void
    {
        // Get a list of loaded extensions
        $loadedExtensions = get_loaded_extensions();
        
        if (empty($loadedExtensions)) {
            $this->markTestSkipped('No extensions loaded to test with');
        }
        
        // Test with a loaded extension
        $extension = $loadedExtensions[0];
        $this->validator->validateExtensions(["ext-$extension" => '*']);
        
        // Should not throw an exception for valid extensions
        $this->assertTrue(true);
    }

    public function testValidateExtensionsThrowsOnMissingExtension(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Required extension is missing: non_existent_extension');
        
        $this->validator->validateExtensions(['ext-non_existent_extension' => '*']);
    }

    public function testValidateExtensionsWithVersionConstraints(): void
    {
        // Get a loaded extension and its version
        $loadedExtensions = get_loaded_extensions();
        
        if (empty($loadedExtensions)) {
            $this->markTestSkipped('No extensions loaded to test with');
        }
        
        $extension = $loadedExtensions[0];
        $version = phpversion($extension);
        
        if ($version === false) {
            $this->markTestSkipped("Could not get version for extension $extension");
        }
        
        // Test with a version constraint that should pass
        $this->validator->validateExtensions(["ext-$extension" => ">=0.1"]);
        
        // Should not throw an exception for valid version constraints
        $this->assertTrue(true);
    }

    public function testValidateExtensionsThrowsOnVersionMismatch(): void
    {
        // Get a loaded extension and its version
        $loadedExtensions = get_loaded_extensions();
        
        if (empty($loadedExtensions)) {
            $this->markTestSkipped('No extensions loaded to test with');
        }
        
        $extension = $loadedExtensions[0];
        $version = phpversion($extension);
        
        if ($version === false) {
            $this->markTestSkipped("Could not get version for extension $extension");
        }
        
        // Bump the version to a future version that shouldn't exist yet
        $futureVersion = $this->incrementVersion($version);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Extension $extension version requirement not met");
        
        $this->validator->validateExtensions(["ext-$extension" => ">$futureVersion"]);
    }

    public function testValidateVersionConstraint(): void
    {
        $this->assertTrue($this->validator->satisfies('1.0.0', '^1.0'));
        $this->assertTrue($this->validator->satisfies('2.5.0', '^2.0'));
        $this->assertTrue($this->validator->satisfies('1.2.3', '~1.2'));
        $this->assertTrue($this->validator->satisfies('1.2.3', '>=1.0 <2.0'));
        
        $this->assertFalse($this->validator->satisfies('1.0.0', '^2.0'));
        $this->assertFalse($this->validator->satisfies('2.0.0', '~1.2'));
    }

    private function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);
        $last = (int) array_pop($parts);
        $parts[] = (string) ($last + 1);
        return implode('.', $parts);
    }
}
