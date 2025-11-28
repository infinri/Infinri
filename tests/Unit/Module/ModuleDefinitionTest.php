<?php

declare(strict_types=1);

namespace Tests\Unit\Module;

use App\Core\Module\ModuleDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleDefinitionTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir() . '/module_test_' . uniqid();
        mkdir($this->tempPath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempPath);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \FilesystemIterator($dir);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($dir);
    }

    #[Test]
    public function constructor_sets_properties_from_data(): void
    {
        $definition = new ModuleDefinition([
            'name' => 'test',
            'version' => '2.0.0',
            'description' => 'Test module',
        ], $this->tempPath);
        
        $this->assertSame('test', $definition->name);
        $this->assertSame('2.0.0', $definition->version);
        $this->assertSame('Test module', $definition->description);
    }

    #[Test]
    public function constructor_uses_defaults(): void
    {
        $definition = new ModuleDefinition([], $this->tempPath);
        
        $this->assertSame(basename($this->tempPath), $definition->name);
        $this->assertSame('1.0.0', $definition->version);
        $this->assertSame('', $definition->description);
        $this->assertTrue($definition->enabled);
    }

    #[Test]
    public function constructor_sets_optional_properties(): void
    {
        $definition = new ModuleDefinition([
            'dependencies' => ['core'],
            'providers' => ['TestProvider'],
            'commands' => ['TestCommand'],
            'events' => 'events.php',
            'config' => 'config.php',
            'routes' => 'routes.php',
            'enabled' => false,
        ], $this->tempPath);
        
        $this->assertSame(['core'], $definition->dependencies);
        $this->assertSame(['TestProvider'], $definition->providers);
        $this->assertSame(['TestCommand'], $definition->commands);
        $this->assertSame('events.php', $definition->eventsFile);
        $this->assertSame('config.php', $definition->configFile);
        $this->assertSame('routes.php', $definition->routesFile);
        $this->assertFalse($definition->enabled);
    }

    #[Test]
    public function get_file_path_returns_full_path(): void
    {
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertSame($this->tempPath . '/view/template.php', $definition->getFilePath('view/template.php'));
    }

    #[Test]
    public function get_file_path_strips_leading_slash(): void
    {
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertSame($this->tempPath . '/view/template.php', $definition->getFilePath('/view/template.php'));
    }

    #[Test]
    public function has_file_returns_true_for_existing_file(): void
    {
        file_put_contents($this->tempPath . '/test.php', '<?php');
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertTrue($definition->hasFile('test.php'));
    }

    #[Test]
    public function has_file_returns_false_for_missing_file(): void
    {
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertFalse($definition->hasFile('nonexistent.php'));
    }

    #[Test]
    public function get_class_file_returns_module_class_path(): void
    {
        $definition = new ModuleDefinition(['name' => 'home'], $this->tempPath);
        
        $this->assertSame($this->tempPath . '/HomeModule.php', $definition->getClassFile());
    }

    #[Test]
    public function get_class_name_returns_fqcn(): void
    {
        $definition = new ModuleDefinition(['name' => 'home'], $this->tempPath);
        
        $this->assertSame('\\App\\Modules\\Home\\HomeModule', $definition->getClassName());
    }

    #[Test]
    public function has_assets_returns_true_when_directory_exists(): void
    {
        mkdir($this->tempPath . '/view/frontend', 0755, true);
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertTrue($definition->hasAssets('frontend'));
    }

    #[Test]
    public function has_assets_returns_false_when_directory_missing(): void
    {
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertFalse($definition->hasAssets('frontend'));
    }

    #[Test]
    public function load_events_returns_empty_when_no_events_file(): void
    {
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertSame([], $definition->loadEvents());
    }

    #[Test]
    public function load_events_returns_empty_when_file_missing(): void
    {
        $definition = new ModuleDefinition([
            'name' => 'test',
            'events' => 'events.php',
        ], $this->tempPath);
        
        $this->assertSame([], $definition->loadEvents());
    }

    #[Test]
    public function load_config_returns_empty_when_no_config_file(): void
    {
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertSame([], $definition->loadConfig());
    }

    #[Test]
    public function load_routes_returns_empty_when_no_routes_file(): void
    {
        $definition = new ModuleDefinition(['name' => 'test'], $this->tempPath);
        
        $this->assertSame([], $definition->loadRoutes());
    }

    #[Test]
    public function to_array_returns_all_properties(): void
    {
        $definition = new ModuleDefinition([
            'name' => 'test',
            'version' => '1.0.0',
            'description' => 'Test',
        ], $this->tempPath);
        
        $array = $definition->toArray();
        
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('version', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('path', $array);
        $this->assertArrayHasKey('enabled', $array);
    }

    #[Test]
    public function from_array_creates_definition(): void
    {
        $data = [
            'name' => 'test',
            'version' => '2.0.0',
            'path' => $this->tempPath,
        ];
        
        $definition = ModuleDefinition::fromArray($data);
        
        $this->assertSame('test', $definition->name);
        $this->assertSame('2.0.0', $definition->version);
    }
}
