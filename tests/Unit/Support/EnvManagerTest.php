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

use App\Core\Support\EnvManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnvManagerTest extends TestCase
{
    private string $tempEnvFile;

    protected function setUp(): void
    {
        $this->tempEnvFile = sys_get_temp_dir() . '/.env_test_' . uniqid();
        file_put_contents($this->tempEnvFile, "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_DEBUG=true\n");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvFile)) {
            unlink($this->tempEnvFile);
        }
    }

    #[Test]
    public function get_returns_value_from_env_file(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertSame('TestApp', $env->get('APP_NAME'));
        $this->assertSame('testing', $env->get('APP_ENV'));
    }

    #[Test]
    public function get_returns_default_when_key_not_found(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertSame('default', $env->get('NONEXISTENT', 'default'));
    }

    #[Test]
    public function has_returns_true_for_existing_key(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertTrue($env->has('APP_NAME'));
    }

    #[Test]
    public function has_returns_false_for_missing_key(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertFalse($env->has('NONEXISTENT'));
    }

    #[Test]
    public function set_updates_existing_value(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $env->set('APP_NAME', 'NewName');
        
        $this->assertSame('NewName', $env->get('APP_NAME'));
    }

    #[Test]
    public function set_adds_new_value(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $env->set('NEW_KEY', 'new_value');
        
        $this->assertSame('new_value', $env->get('NEW_KEY'));
    }

    #[Test]
    public function persist_saves_to_file(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $env->persist('APP_NAME', 'UpdatedApp');
        
        // Re-read the file
        $env2 = new EnvManager($this->tempEnvFile);
        $this->assertSame('UpdatedApp', $env2->get('APP_NAME'));
    }

    #[Test]
    public function persist_adds_new_key(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $env->persist('NEW_KEY', 'new_value');
        
        $env2 = new EnvManager($this->tempEnvFile);
        $this->assertSame('new_value', $env2->get('NEW_KEY'));
    }

    #[Test]
    public function all_returns_all_variables(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $all = $env->all();
        
        $this->assertArrayHasKey('APP_NAME', $all);
        $this->assertArrayHasKey('APP_ENV', $all);
        $this->assertArrayHasKey('APP_DEBUG', $all);
    }

    #[Test]
    public function exists_returns_true_for_existing_file(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $this->assertTrue($env->exists());
    }

    #[Test]
    public function exists_returns_false_for_missing_file(): void
    {
        $env = new EnvManager('/nonexistent/.env');
        $this->assertFalse($env->exists());
    }

    #[Test]
    public function get_missing_returns_missing_keys(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $missing = $env->getMissing(['APP_NAME', 'NONEXISTENT']);
        
        $this->assertContains('NONEXISTENT', $missing);
        $this->assertNotContains('APP_NAME', $missing);
    }

    #[Test]
    public function reload_refreshes_from_file(): void
    {
        $env = new EnvManager($this->tempEnvFile);
        $env->get('APP_NAME'); // Load
        
        // Modify file directly
        file_put_contents($this->tempEnvFile, "APP_NAME=Changed\n");
        
        $env->reload();
        $this->assertSame('Changed', $env->get('APP_NAME'));
    }

    #[Test]
    public function handles_quoted_values(): void
    {
        file_put_contents($this->tempEnvFile, 'APP_NAME="Quoted Value"' . "\n");
        
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertSame('Quoted Value', $env->get('APP_NAME'));
    }

    #[Test]
    public function handles_single_quoted_values(): void
    {
        file_put_contents($this->tempEnvFile, "APP_NAME='Single Quoted'\n");
        
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertSame('Single Quoted', $env->get('APP_NAME'));
    }

    #[Test]
    public function ignores_comments(): void
    {
        file_put_contents($this->tempEnvFile, "# This is a comment\nAPP_NAME=Test\n");
        
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertSame('Test', $env->get('APP_NAME'));
        $this->assertFalse($env->has('# This is a comment'));
    }

    #[Test]
    public function handles_empty_values(): void
    {
        file_put_contents($this->tempEnvFile, "EMPTY_VAR=\nAPP_NAME=Test\n");
        
        $env = new EnvManager($this->tempEnvFile);
        
        $this->assertSame('', $env->get('EMPTY_VAR'));
    }

    #[Test]
    public function persist_returns_false_for_missing_file(): void
    {
        $env = new EnvManager('/nonexistent/path/.env');
        
        $result = $env->persist('KEY', 'value');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function load_handles_missing_file(): void
    {
        $env = new EnvManager('/nonexistent/path/.env');
        
        // Should not throw, just return empty
        $all = $env->all();
        
        $this->assertIsArray($all);
    }

    #[Test]
    public function uses_default_env_file_when_null(): void
    {
        // EnvManager with null uses getDefaultEnvFile
        $env = new EnvManager(null);
        
        // Should not throw
        $this->assertInstanceOf(EnvManager::class, $env);
    }
}
