<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Unit\Support;

use App\Core\Support\Environment;
use PHPUnit\Framework\Attributes\Test;
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
        putenv('BOOL_VAR');
        putenv('YES_VAR');
        putenv('NO_VAR');
        unset(
            $_ENV['TEST_VAR'], $_SERVER['TEST_VAR'],
            $_ENV['BOOL_VAR'], $_SERVER['BOOL_VAR'],
            $_ENV['YES_VAR'], $_SERVER['YES_VAR'],
            $_ENV['NO_VAR'], $_SERVER['NO_VAR']
        );
    }

    #[Test]
    public function it_loads_environment_variables_from_file(): void
    {
        file_put_contents($this->testEnvFile, "TEST_VAR=test_value\n");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('test_value', getenv('TEST_VAR'));
        $this->assertEquals('test_value', $_ENV['TEST_VAR']);
        $this->assertEquals('test_value', $_SERVER['TEST_VAR']);
    }

    #[Test]
    public function it_strips_quotes_from_values(): void
    {
        file_put_contents($this->testEnvFile, 'TEST_VAR="quoted value"');
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('quoted value', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_strips_single_quotes(): void
    {
        file_put_contents($this->testEnvFile, "TEST_VAR='single quoted'");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('single quoted', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_ignores_comments(): void
    {
        file_put_contents($this->testEnvFile, "# This is a comment\nTEST_VAR=value");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('value', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_ignores_empty_lines(): void
    {
        file_put_contents($this->testEnvFile, "\nTEST_VAR=value\n\n");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('value', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_does_not_override_existing_environment_variables(): void
    {
        putenv('TEST_VAR=existing');
        file_put_contents($this->testEnvFile, 'TEST_VAR=new_value');
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('existing', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_throws_exception_if_file_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment file not found');
        
        $env = new Environment($this->testEnvPath, '.env.nonexistent');
        $env->load();
    }

    #[Test]
    public function it_provides_file_path(): void
    {
        $env = new Environment($this->testEnvPath, '.env.test');
        
        $expectedPath = $this->testEnvPath . DIRECTORY_SEPARATOR . '.env.test';
        $this->assertEquals($expectedPath, $env->getFilePath());
    }

    #[Test]
    public function env_helper_converts_numeric_true(): void
    {
        file_put_contents($this->testEnvFile, 'BOOL_VAR=1');
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertTrue(env('BOOL_VAR'));
    }

    #[Test]
    public function env_helper_converts_numeric_false(): void
    {
        file_put_contents($this->testEnvFile, 'BOOL_VAR=0');
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertFalse(env('BOOL_VAR'));
    }

    #[Test]
    public function env_helper_converts_yes_and_no(): void
    {
        file_put_contents($this->testEnvFile, "YES_VAR=yes\nNO_VAR=no");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertTrue(env('YES_VAR'));
        $this->assertFalse(env('NO_VAR'));
    }

    #[Test]
    public function it_ignores_lines_without_equals_sign(): void
    {
        file_put_contents($this->testEnvFile, "INVALID_LINE\nTEST_VAR=value");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        // Invalid line is skipped, valid line is processed
        $this->assertEquals('value', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_handles_values_with_equals_sign(): void
    {
        file_put_contents($this->testEnvFile, "TEST_VAR=value=with=equals");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('value=with=equals', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_handles_empty_value(): void
    {
        file_put_contents($this->testEnvFile, "TEST_VAR=");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('', getenv('TEST_VAR'));
    }

    #[Test]
    public function it_trims_whitespace_from_name_and_value(): void
    {
        file_put_contents($this->testEnvFile, "  TEST_VAR  =  trimmed value  ");
        
        $env = new Environment($this->testEnvPath, '.env.test');
        $env->load();
        
        $this->assertEquals('trimmed value', getenv('TEST_VAR'));
    }

    #[Test]
    public function load_throws_for_unreadable_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not readable');
        
        // Create file and make it unreadable
        file_put_contents($this->testEnvFile, 'TEST=value');
        chmod($this->testEnvFile, 0000);
        
        try {
            $env = new Environment($this->testEnvPath, '.env.test');
            $env->load();
        } finally {
            chmod($this->testEnvFile, 0644); // Restore permissions for cleanup
        }
    }
}
