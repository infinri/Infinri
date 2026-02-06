<?php declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\KeyGenerateCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class KeyGenerateCommandTest extends TestCase
{
    private string $testEnvFile;
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a unique directory for each test to avoid collisions
        $this->testDir = sys_get_temp_dir() . '/key_gen_test_' . uniqid() . '_' . mt_rand();
        mkdir($this->testDir, 0755, true);
        $this->testEnvFile = $this->testDir . '/.env';
        
        // Clear any global APP_KEY that might be set from other tests
        unset($_ENV['APP_KEY']);
        putenv('APP_KEY');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testEnvFile)) {
            unlink($this->testEnvFile);
        }
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_name(): void
    {
        $command = new KeyGenerateCommand();
        
        $this->assertEquals('key:generate', $command->getName());
    }

    #[Test]
    public function it_has_aliases(): void
    {
        $command = new KeyGenerateCommand();
        
        $this->assertContains('key:gen', $command->getAliases());
    }

    #[Test]
    public function it_has_description(): void
    {
        $command = new KeyGenerateCommand();
        
        $this->assertEquals('Generate a new application encryption key', $command->getDescription());
    }

    #[Test]
    public function show_flag_outputs_key_without_saving(): void
    {
        $command = new KeyGenerateCommand();
        
        ob_start();
        $result = $command->handle(['--show']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Generated Key:', $output);
        $this->assertStringContainsString('Add this to your .env file', $output);
    }

    #[Test]
    public function it_returns_error_if_env_file_not_found(): void
    {
        $command = new TestableKeyGenerateCommand('/non/existent/path');
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('.env file not found', $output);
    }

    #[Test]
    public function it_returns_error_if_key_exists_without_force(): void
    {
        // Create temp .env with existing key
        file_put_contents($this->testEnvFile, "APP_KEY=existing_key_value_here_12345\n");
        
        $command = new TestableKeyGenerateCommand($this->testDir);
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('APP_KEY already exists', $output);
        $this->assertStringContainsString('--force', $output);
    }

    #[Test]
    public function it_generates_key_when_env_file_exists_without_key(): void
    {
        // Create temp .env without APP_KEY
        file_put_contents($this->testEnvFile, "APP_NAME=Test\n");
        
        $command = new TestableKeyGenerateCommand($this->testDir);
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Application key generated', $output);
        
        // Verify key was saved
        $envContent = file_get_contents($this->testEnvFile);
        $this->assertStringContainsString('APP_KEY=', $envContent);
    }

    #[Test]
    public function it_overwrites_key_with_force_flag(): void
    {
        // Create temp .env with existing key
        $oldKey = 'old_key_value_here_1234567890123456';
        file_put_contents($this->testEnvFile, "APP_KEY={$oldKey}\n");
        
        $command = new TestableKeyGenerateCommand($this->testDir);
        
        ob_start();
        $result = $command->handle(['--force']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Application key generated', $output);
        $this->assertStringContainsString('Previous key was overwritten', $output);
        
        // Verify new key is different
        $envContent = file_get_contents($this->testEnvFile);
        $this->assertStringNotContainsString($oldKey, $envContent);
    }
}

/**
 * Testable version that overrides getRootDir for testing
 */
class TestableKeyGenerateCommand extends KeyGenerateCommand
{
    private string $testRootDir;

    public function __construct(string $testRootDir)
    {
        $this->testRootDir = $testRootDir;
    }

    protected function getRootDir(): string
    {
        return $this->testRootDir;
    }
}
