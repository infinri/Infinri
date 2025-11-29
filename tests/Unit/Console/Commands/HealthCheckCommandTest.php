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
namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\HealthCheckCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HealthCheckCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_health_check(): void
    {
        $command = new HealthCheckCommand();
        
        $this->assertSame('health:check', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new HealthCheckCommand();
        
        $this->assertStringContainsString('health', $command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_doctor(): void
    {
        $command = new HealthCheckCommand();
        
        $this->assertContains('doctor', $command->getAliases());
    }

    #[Test]
    public function handle_outputs_health_check_header(): void
    {
        $command = new HealthCheckCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('System Health Check', $output);
    }

    #[Test]
    public function handle_checks_environment(): void
    {
        $command = new HealthCheckCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Environment', $output);
    }

    #[Test]
    public function handle_checks_directories(): void
    {
        $command = new HealthCheckCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Directories', $output);
    }

    #[Test]
    public function handle_with_verbose_flag(): void
    {
        $command = new HealthCheckCommand();
        
        ob_start();
        $result = $command->handle(['--verbose']);
        ob_end_clean();
        
        $this->assertIsInt($result);
    }
}
