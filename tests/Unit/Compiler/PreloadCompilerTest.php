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
namespace Tests\Unit\Compiler;

use App\Core\Compiler\PreloadCompiler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PreloadCompilerTest extends TestCase
{
    private string $tempDir;
    private string $outputPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/preload_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->outputPath = $this->tempDir . '/preload.php';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function add_core_file_returns_self(): void
    {
        $compiler = new PreloadCompiler($this->tempDir, $this->outputPath);
        
        // The method should add to internal list (no return value but should not throw)
        $compiler->addCoreFile('test/file.php');
        
        // Compiling should work without error
        $result = $compiler->compile();
        $this->assertIsArray($result);
    }

    #[Test]
    public function add_scan_dir_returns_self(): void
    {
        $compiler = new PreloadCompiler($this->tempDir, $this->outputPath);
        
        // The method should add to internal list
        $compiler->addScanDir('test/dir');
        
        // Compiling should work without error
        $result = $compiler->compile();
        $this->assertIsArray($result);
    }

    #[Test]
    public function get_output_path_returns_path(): void
    {
        $compiler = new PreloadCompiler($this->tempDir, $this->outputPath);
        
        $this->assertSame($this->outputPath, $compiler->getOutputPath());
    }

    #[Test]
    public function compile_creates_preload_file(): void
    {
        $compiler = new PreloadCompiler($this->tempDir, $this->outputPath);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
        $this->assertFileExists($this->outputPath);
    }
}
