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

use App\Core\Console\Commands\CodeStatsCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CodeStatsCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_code_stats(): void
    {
        $command = new CodeStatsCommand();
        
        $this->assertSame('code:stats', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new CodeStatsCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new CodeStatsCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_verbose_shows_largest_files(): void
    {
        $command = new CodeStatsCommand();
        
        ob_start();
        $result = $command->handle(['--verbose']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        // Verbose output should include largest files info
        $this->assertStringContainsString('Largest files', $output);
    }

    #[Test]
    public function handle_with_short_verbose_flag(): void
    {
        $command = new CodeStatsCommand();
        
        ob_start();
        $result = $command->handle(['-v']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Largest files', $output);
    }
}
