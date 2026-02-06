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

use App\Core\Console\Commands\MakeSeederCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MakeSeederCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_make_seeder(): void
    {
        $command = new MakeSeederCommand();
        
        $this->assertSame('make:seeder', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new MakeSeederCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_without_name_shows_error(): void
    {
        $command = new MakeSeederCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
    }

    #[Test]
    public function handle_creates_seeder_file(): void
    {
        $command = new MakeSeederCommand();
        $name = 'Test' . uniqid() . 'Seeder';
        
        ob_start();
        $result = $command->handle([$name]);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Created seeder', $output);
        
        // Clean up
        $rootDir = dirname(__DIR__, 4);
        $filepath = $rootDir . '/database/seeders/' . $name . '.php';
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    #[Test]
    public function handle_appends_seeder_suffix(): void
    {
        $command = new MakeSeederCommand();
        $baseName = 'TestAppend' . uniqid();
        
        ob_start();
        $result = $command->handle([$baseName]);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString($baseName . 'Seeder', $output);
        
        // Clean up
        $rootDir = dirname(__DIR__, 4);
        $filepath = $rootDir . '/database/seeders/' . $baseName . 'Seeder.php';
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    #[Test]
    public function handle_creates_seeders_directory(): void
    {
        $command = new MakeSeederCommand();
        $name = 'TestDir' . uniqid() . 'Seeder';
        
        ob_start();
        $result = $command->handle([$name]);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        
        // Clean up
        $rootDir = dirname(__DIR__, 4);
        $filepath = $rootDir . '/database/seeders/' . $name . '.php';
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    #[Test]
    public function handle_generates_valid_seeder_content(): void
    {
        $command = new MakeSeederCommand();
        $name = 'TestContent' . uniqid() . 'Seeder';
        
        ob_start();
        $result = $command->handle([$name]);
        ob_get_clean();
        
        $rootDir = dirname(__DIR__, 4);
        $filepath = $rootDir . '/database/seeders/' . $name . '.php';
        
        $this->assertFileExists($filepath);
        $content = file_get_contents($filepath);
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('extends Seeder', $content);
        $this->assertStringContainsString('function run()', $content);
        
        // Clean up
        unlink($filepath);
    }
}
