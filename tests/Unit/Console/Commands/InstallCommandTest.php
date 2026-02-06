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

use App\Core\Console\Commands\InstallCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InstallCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_setup_install(): void
    {
        $command = new InstallCommand();
        
        $this->assertSame('setup:install', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new InstallCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_s_i(): void
    {
        $command = new InstallCommand();
        
        $this->assertContains('s:i', $command->getAliases());
    }
}
