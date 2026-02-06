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

use App\Core\Console\Commands\CacheClearCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CacheClearCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_cache_clear(): void
    {
        $command = new CacheClearCommand();
        
        $this->assertSame('cache:clear', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new CacheClearCommand();
        
        $this->assertStringContainsString('Clear cache', $command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_cc(): void
    {
        $command = new CacheClearCommand();
        
        $this->assertContains('cc', $command->getAliases());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new CacheClearCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_outputs_clearing_message(): void
    {
        $command = new CacheClearCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Clearing caches', $output);
    }

    #[Test]
    public function handle_with_pool_arg(): void
    {
        $command = new CacheClearCommand();
        
        ob_start();
        $result = $command->handle(['--pool=runtime']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_compiled_flag(): void
    {
        $command = new CacheClearCommand();
        
        ob_start();
        $result = $command->handle(['--compiled']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_all_flag(): void
    {
        $command = new CacheClearCommand();
        
        ob_start();
        $result = $command->handle(['--all']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_unknown_pool(): void
    {
        $command = new CacheClearCommand();
        
        ob_start();
        $result = $command->handle(['--pool=unknown_pool_xyz']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Unknown pool', $output);
    }

    #[Test]
    public function handle_clears_runtime_caches(): void
    {
        $command = new CacheClearCommand();
        
        // Create a cache directory to clear
        $cacheDir = dirname(__DIR__, 4) . '/var/cache/runtime';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
    }
}
