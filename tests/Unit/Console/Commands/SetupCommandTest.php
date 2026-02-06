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

use App\Core\Console\Commands\SetupCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SetupCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_setup_update(): void
    {
        $command = new SetupCommand();
        
        $this->assertSame('setup:update', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new SetupCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_s_up(): void
    {
        $command = new SetupCommand();
        
        $this->assertContains('s:up', $command->getAliases());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new SetupCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }
}
