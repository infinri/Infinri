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

use App\Core\Console\Commands\HelpCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HelpCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_help(): void
    {
        $command = new HelpCommand();
        
        $this->assertSame('help', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new HelpCommand();
        
        $this->assertSame('Show available commands', $command->getDescription());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new HelpCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_outputs_usage(): void
    {
        $command = new HelpCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('USAGE:', $output);
        $this->assertStringContainsString('php bin/console', $output);
    }

    #[Test]
    public function handle_shows_static_commands_without_app(): void
    {
        $command = new HelpCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('COMMANDS:', $output);
    }
}
