<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\ModuleListCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleListCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_module_list(): void
    {
        $command = new ModuleListCommand();
        
        $this->assertSame('module:list', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new ModuleListCommand();
        
        $this->assertStringContainsString('modules', $command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_m_l(): void
    {
        $command = new ModuleListCommand();
        
        $this->assertContains('m:l', $command->getAliases());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new ModuleListCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_rebuild_flag(): void
    {
        $command = new ModuleListCommand();
        
        ob_start();
        $result = $command->handle(['--rebuild']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Rebuilding', $output);
    }

    #[Test]
    public function handle_with_short_rebuild_flag(): void
    {
        $command = new ModuleListCommand();
        
        ob_start();
        $result = $command->handle(['-r']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Rebuilding', $output);
    }
}
