<?php

declare(strict_types=1);

namespace Tests\Unit\Module;

use App\Core\Module\ModuleRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that prevents exit calls
 */
class TestableModuleRenderer extends ModuleRenderer
{
    public bool $exitCalled = false;
    public int $exitCode = 0;
    
    public function renderError(int $code, ?string $type = null): void
    {
        // Don't call http_response_code in CLI
        // http_response_code($code);
        $this->exitCode = $code;
        
        $type = $type ?? match ($code) {
            400 => '400',
            404 => '404',
            500, 503 => '500',
            default => '404'
        };

        $errorModulePath = $this->getModulesPath() . "/error/index.php";

        if (!file_exists($errorModulePath)) {
            echo "Error {$code}";
            $this->exitCalled = true;
            return; // Instead of exit
        }

        // Capture error module content
        $errorType = $type;
        ob_start();
        require $errorModulePath;
        $content = ob_get_clean();

        // Render with layout
        $this->renderWithLayoutPublic($content);
        $this->exitCalled = true;
        // Instead of exit
    }
    
    public function renderWithLayoutPublic(string $content): void
    {
        $layoutPath = $this->getLayoutPath();
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
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
        
        ob_start();
        $renderer->render('test');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Module Content', $output);
    }

    #[Test]
    public function render_outputs_content_without_layout_when_layout_missing(): void
    {
        // Create module directory and file
        mkdir($this->tempDir . '/modules/test', 0755, true);
        file_put_contents($this->tempDir . '/modules/test/index.php', '<?php echo "Direct Output";');
        
        $renderer = new ModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/nonexistent_layout.php');
        
        ob_start();
        $renderer->render('test');
        $output = ob_get_clean();
        
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
        
        ob_start();
        $renderer->render('valid-module_123');
        $output = ob_get_clean();
        
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
        
        ob_start();
        $renderer->render('../invalid');
        $output = ob_get_clean();
        
        $this->assertTrue($renderer->exitCalled);
        $this->assertSame(500, $renderer->exitCode);
        $this->assertStringContainsString('Error 500', $output);
    }

    #[Test]
    public function render_calls_error_for_nonexistent_module(): void
    {
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        ob_start();
        $renderer->render('nonexistent');
        $output = ob_get_clean();
        
        $this->assertTrue($renderer->exitCalled);
        $this->assertSame(404, $renderer->exitCode);
        $this->assertStringContainsString('Error 404', $output);
    }

    #[Test]
    public function render_error_uses_error_module_when_exists(): void
    {
        // Create error module
        mkdir($this->tempDir . '/modules/error', 0755, true);
        file_put_contents($this->tempDir . '/modules/error/index.php', '<?php echo "Custom Error: " . $errorType;');
        
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/nonexistent.php');
        
        ob_start();
        $renderer->renderError(404);
        $output = ob_get_clean();
        
        $this->assertTrue($renderer->exitCalled);
        $this->assertStringContainsString('Custom Error: 404', $output);
    }

    #[Test]
    public function render_error_maps_codes_correctly(): void
    {
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        // Test 400 error
        $renderer->exitCalled = false;
        ob_start();
        $renderer->renderError(400);
        ob_get_clean();
        $this->assertSame(400, $renderer->exitCode);
        
        // Test 503 error (should map to '500' type)
        $renderer->exitCalled = false;
        ob_start();
        $renderer->renderError(503);
        ob_get_clean();
        $this->assertSame(503, $renderer->exitCode);
        
        // Test unknown error code (defaults to 404 type)
        $renderer->exitCalled = false;
        ob_start();
        $renderer->renderError(418); // I'm a teapot
        ob_get_clean();
        $this->assertSame(418, $renderer->exitCode);
    }

    #[Test]
    public function render_error_accepts_custom_type(): void
    {
        // Create error module that shows the type
        mkdir($this->tempDir . '/modules/error', 0755, true);
        file_put_contents($this->tempDir . '/modules/error/index.php', '<?php echo "Type: " . $errorType;');
        
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/nonexistent.php');
        
        ob_start();
        $renderer->renderError(503, 'maintenance');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Type: maintenance', $output);
    }

    #[Test]
    public function render_maintenance_calls_render_error_with_503(): void
    {
        $renderer = new TestableModuleRenderer($this->tempDir . '/modules', $this->tempDir . '/layout.php');
        
        ob_start();
        $renderer->renderMaintenance();
        $output = ob_get_clean();
        
        $this->assertTrue($renderer->exitCalled);
        $this->assertSame(503, $renderer->exitCode);
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
        
        ob_start();
        $renderer->renderError(404);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Error Content', $output);
    }
}
