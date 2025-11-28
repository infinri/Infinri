<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\PreloadGenerateCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PreloadGenerateCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_preload_generate(): void
    {
        $command = new PreloadGenerateCommand();
        
        $this->assertSame('preload:generate', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new PreloadGenerateCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new PreloadGenerateCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }
}
