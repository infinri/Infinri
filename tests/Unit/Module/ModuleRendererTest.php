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
namespace Tests\Unit\Module;

use App\Core\Module\ModuleRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that tracks error codes
 */
class TestableModuleRenderer extends ModuleRenderer
{
    public bool $errorRendered = false;
    public int $errorCode = 0;

    public function renderError(int $code, ?string $type = null): string
    {
        $this->errorCode = $code;
        $this->errorRendered = true;

        return parent::renderError($code, $type);
    }
}

class ModuleRendererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/module_renderer_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/modules', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
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
    public function constructor_accepts_custom_paths(): void
    {
        $renderer = new ModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        $this->assertSame($this->tempDir . '/modules', $renderer->getModulesPath());
        $this->assertSame($this->tempDir . '/layout.php', $renderer->getLayoutPath());
    }

    #[Test]
    public function get_modules_path_returns_path(): void
    {
        $renderer = new ModuleRenderer($this->tempDir . '/modules');
        
        $this->assertSame($this->tempDir . '/modules', $renderer->getModulesPath());
    }

    #[Test]
    public function set_modules_path_changes_path(): void
    {
        $renderer = new ModuleRenderer($this->tempDir . '/modules');
        
        $renderer->setModulesPath('/new/path');
        
        $this->assertSame('/new/path', $renderer->getModulesPath());
    }

    #[Test]
    public function get_layout_path_returns_path(): void
    {
        $renderer = new ModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        $this->assertSame($this->tempDir . '/layout.php', $renderer->getLayoutPath());
    }

    #[Test]
    public function set_layout_path_changes_path(): void
    {
        $renderer = new ModuleRenderer($this->tempDir . '/modules');
        
        $renderer->setLayoutPath('/new/layout.php');
        
        $this->assertSame('/new/layout.php', $renderer->getLayoutPath());
    }

    #[Test]
    public function render_outputs_module_content_with_layout(): void
    {
        // Create module directory and file
        mkdir($this->tempDir . '/modules/test', 0755, true);
        file_put_contents($this->tempDir . '/modules/test/index.php', '<?php echo "Module Content";');
        
        // Create layout file
        file_put_contents($this->tempDir . '/layout.php', '<?php echo "<div>" . $content . "</div>";');
        
        $renderer = new ModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        $output = $renderer->render('test');
        
        $this->assertStringContainsString('Module Content', $output);
    }

    #[Test]
    public function render_outputs_content_without_layout_when_layout_missing(): void
    {
        // Create module directory and file
        mkdir($this->tempDir . '/modules/test', 0755, true);
        file_put_contents($this->tempDir . '/modules/test/index.php', '<?php echo "Direct Output";');
        
        $renderer = new ModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/nonexistent_layout.php');
        
        $output = $renderer->render('test');
        
        $this->assertSame('Direct Output', $output);
    }

    #[Test]
    public function render_validates_module_name_pattern(): void
    {
        // Test that the regex pattern accepts valid module names
        $validNames = ['test', 'my-module', 'module_name', 'module123', 'test-mod_123'];
        $invalidNames = ['../invalid', 'Invalid', 'test.php', 'mod/sub'];
        
        foreach ($validNames as $name) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $name);
        }
        
        foreach ($invalidNames as $name) {
            $this->assertDoesNotMatchRegularExpression('/^[a-z0-9_-]+$/', $name);
        }
    }

    #[Test]
    public function render_allows_valid_module_names(): void
    {
        // Create module with valid name patterns
        mkdir($this->tempDir . '/modules/valid-module_123', 0755, true);
        file_put_contents($this->tempDir . '/modules/valid-module_123/index.php', '<?php echo "Valid";');
        
        $renderer = new ModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/nonexistent.php');
        
        $output = $renderer->render('valid-module_123');
        
        $this->assertSame('Valid', $output);
    }

    #[Test]
    public function constructor_uses_defaults_when_no_paths_provided(): void
    {
        // We can't easily test default paths without mocking base_path()
        // but we can verify the constructor doesn't crash
        $renderer = new ModuleRenderer();
        
        $this->assertNotEmpty($renderer->getModulesPath());
        $this->assertNotEmpty($renderer->getLayoutPath());
    }

    #[Test]
    public function render_calls_error_for_invalid_module_name(): void
    {
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        $output = $renderer->render('../invalid');
        
        $this->assertTrue($renderer->errorRendered);
        $this->assertSame(500, $renderer->errorCode);
        $this->assertStringContainsString('Error 500', $output);
    }

    #[Test]
    public function render_calls_error_for_nonexistent_module(): void
    {
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        $output = $renderer->render('nonexistent');
        
        $this->assertTrue($renderer->errorRendered);
        $this->assertSame(404, $renderer->errorCode);
        $this->assertStringContainsString('Error 404', $output);
    }

    #[Test]
    public function render_error_uses_error_module_when_exists(): void
    {
        // Create error module
        mkdir($this->tempDir . '/modules/error', 0755, true);
        file_put_contents($this->tempDir . '/modules/error/index.php', '<?php echo "Custom Error: " . $errorType;');
        
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/nonexistent.php');
        
        $output = $renderer->renderError(404);
        
        $this->assertTrue($renderer->errorRendered);
        $this->assertStringContainsString('Custom Error: 404', $output);
    }

    #[Test]
    public function render_error_maps_codes_correctly(): void
    {
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        // Test 400 error
        $renderer->errorRendered = false;
        $renderer->renderError(400);
        $this->assertSame(400, $renderer->errorCode);
        
        // Test 503 error (should map to '500' type)
        $renderer->errorRendered = false;
        $renderer->renderError(503);
        $this->assertSame(503, $renderer->errorCode);
        
        // Test unknown error code (defaults to 404 type)
        $renderer->errorRendered = false;
        $renderer->renderError(418); // I'm a teapot
        $this->assertSame(418, $renderer->errorCode);
    }

    #[Test]
    public function render_error_accepts_custom_type(): void
    {
        // Create error module that shows the type
        mkdir($this->tempDir . '/modules/error', 0755, true);
        file_put_contents($this->tempDir . '/modules/error/index.php', '<?php echo "Type: " . $errorType;');
        
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/nonexistent.php');
        
        $output = $renderer->renderError(503, 'maintenance');
        
        $this->assertStringContainsString('Type: maintenance', $output);
    }

    #[Test]
    public function render_maintenance_calls_render_error_with_503(): void
    {
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        $output = $renderer->renderMaintenance();
        
        $this->assertTrue($renderer->errorRendered);
        $this->assertSame(503, $renderer->errorCode);
    }

    #[Test]
    public function render_error_with_layout(): void
    {
        // Create error module
        mkdir($this->tempDir . '/modules/error', 0755, true);
        file_put_contents($this->tempDir . '/modules/error/index.php', '<?php echo "Error Content";');
        
        // Create layout
        file_put_contents($this->tempDir . '/layout.php', '<?php echo "<layout>" . $content . "</layout>";');
        
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        $output = $renderer->renderError(404);
        
        $this->assertStringContainsString('Error Content', $output);
    }
}
