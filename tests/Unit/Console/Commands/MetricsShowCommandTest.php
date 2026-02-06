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

use App\Core\Console\Commands\MetricsShowCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetricsShowCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_metrics_show(): void
    {
        $command = new MetricsShowCommand();
        
        $this->assertSame('metrics:show', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new MetricsShowCommand();
        
        $this->assertStringContainsString('metrics', $command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_metrics(): void
    {
        $command = new MetricsShowCommand();
        
        $this->assertContains('metrics', $command->getAliases());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new MetricsShowCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_outputs_metrics_header(): void
    {
        $command = new MetricsShowCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Application Metrics', $output);
    }

    #[Test]
    public function handle_shows_overview(): void
    {
        $command = new MetricsShowCommand();
        
        ob_start();
        $command->handle();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Overview', $output);
    }

    #[Test]
    public function handle_with_hourly_flag(): void
    {
        $command = new MetricsShowCommand();
        
        ob_start();
        $result = $command->handle(['--hourly']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }
}
