<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Core\Support\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    private string $testEnvPath;
    private string $testEnvFile;

    protected function setUp(): void
    {
        $this->testEnvPath = BASE_PATH . '/var/tmp';
        $this->testEnvFile = $this->testEnvPath . '/.env.test';
        
        // Clean up any previous test files
        if (file_exists($this->testEnvFile)) {
            unlink($this->testEnvFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testEnvFile)) {
            unlink($this->testEnvFile);
        }
        
        // Clean up environment variables
        putenv('TEST_VAR');
        unset($_ENV['TEST_VAR'], $_SERVER['TEST_VAR']);
    }

    /** @test */
    public function it_loads_environment_variables_from_file(): void
    {
        file_put_contents($this->testEnvFile, "TEST_VAR=test_value\n");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('test_value', getenv('TEST_VAR'));
        $this->assertEquals('test_value', $_ENV['TEST_VAR']);
        $this->assertEquals('test_value', $_SERVER['TEST_VAR']);
    }

    /** @test */
    public function it_strips_quotes_from_values(): void
    {
        file_put_contents($this->testEnvFile, 'TEST_VAR="quoted value"');
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('quoted value', getenv('TEST_VAR'));
    }

    /** @test */
    public function it_strips_single_quotes(): void
    {
        file_put_contents($this->testEnvFile, "TEST_VAR='single quoted'");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('single quoted', getenv('TEST_VAR'));
    }

    /** @test */
    public function it_ignores_comments(): void
    {
        file_put_contents($this->testEnvFile, "# This is a comment\nTEST_VAR=value");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('value', getenv('TEST_VAR'));
    }

    /** @test */
    public function it_ignores_empty_lines(): void
    {
        file_put_contents($this->testEnvFile, "\nTEST_VAR=value\n\n");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('value', getenv('TEST_VAR'));
    }

    /** @test */
    public function it_does_not_override_existing_environment_variables(): void
    {
        putenv('TEST_VAR=existing');
        file_put_contents($this->testEnvFile, 'TEST_VAR=new_value');
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('existing', getenv('TEST_VAR'));
    }

    /** @test */
    public function it_throws_exception_if_file_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment file not found');
        
        $env = new Environment($this->testEnvPath, '.env.nonexistent');
        $env->load();
    }

    /** @test */
    public function it_provides_file_path(): void
    {
        $env = new Environment($this->testEnvPath, '.env.test');
        
        $expectedPath = $this->testEnvPath . DIRECTORY_SEPARATOR . '.env.test';
        $this->assertEquals($expectedPath, $env->getFilePath());
    }
}
