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

use App\Core\Console\Commands\PermissionsCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PermissionsCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_permissions(): void
    {
        $command = new PermissionsCommand();
        
        $this->assertSame('setup:permissions', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new PermissionsCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_s_p(): void
    {
        $command = new PermissionsCommand();
        
        $this->assertContains('s:p', $command->getAliases());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new PermissionsCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_creates_missing_directories(): void
    {
        $tempDir = sys_get_temp_dir() . '/perm_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        // Create a subclass that uses our temp dir
        $command = new class($tempDir) extends PermissionsCommand {
            public function __construct(private string $testDir) {
                parent::__construct();
            }
            protected function getRootDir(): string {
                return $this->testDir;
            }
        };
        
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Created', $output);
        
        // Cleanup
        $this->recursiveDelete($tempDir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
