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
namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for filesystem helper functions
 */
class FileHelpersTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/file_helpers_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \FilesystemIterator($dir);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }

    // ensure_directory tests

    #[Test]
    public function ensure_directory_creates_directory(): void
    {
        $path = $this->tempDir . '/new_dir';
        
        $result = ensure_directory($path);
        
        $this->assertTrue($result);
        $this->assertDirectoryExists($path);
    }

    #[Test]
    public function ensure_directory_creates_nested_directories(): void
    {
        $path = $this->tempDir . '/level1/level2/level3';
        
        $result = ensure_directory($path);
        
        $this->assertTrue($result);
        $this->assertDirectoryExists($path);
    }

    #[Test]
    public function ensure_directory_returns_true_for_existing_directory(): void
    {
        mkdir($this->tempDir, 0755, true);
        
        $result = ensure_directory($this->tempDir);
        
        $this->assertTrue($result);
    }

    // save_php_array tests

    #[Test]
    public function save_php_array_creates_file(): void
    {
        ensure_directory($this->tempDir);
        $path = $this->tempDir . '/test.php';
        $data = ['key' => 'value'];
        
        $result = save_php_array($path, $data);
        
        $this->assertTrue($result);
        $this->assertFileExists($path);
    }

    #[Test]
    public function save_php_array_creates_valid_php(): void
    {
        ensure_directory($this->tempDir);
        $path = $this->tempDir . '/test.php';
        $data = ['name' => 'test', 'count' => 42];
        
        save_php_array($path, $data);
        
        $loaded = require $path;
        $this->assertSame($data, $loaded);
    }

    #[Test]
    public function save_php_array_includes_header(): void
    {
        ensure_directory($this->tempDir);
        $path = $this->tempDir . '/test.php';
        
        save_php_array($path, [], 'My Custom Header');
        
        $content = file_get_contents($path);
        $this->assertStringContainsString('My Custom Header', $content);
    }

    #[Test]
    public function save_php_array_creates_parent_directory(): void
    {
        $path = $this->tempDir . '/subdir/test.php';
        
        save_php_array($path, ['test' => true]);
        
        $this->assertFileExists($path);
    }

    // clear_directory tests

    #[Test]
    public function clear_directory_removes_files(): void
    {
        mkdir($this->tempDir, 0755, true);
        file_put_contents($this->tempDir . '/file1.txt', 'test');
        file_put_contents($this->tempDir . '/file2.txt', 'test');
        
        $result = clear_directory($this->tempDir, true);
        
        $this->assertTrue($result);
        $this->assertDirectoryExists($this->tempDir); // Preserved
        $this->assertFileDoesNotExist($this->tempDir . '/file1.txt');
        $this->assertFileDoesNotExist($this->tempDir . '/file2.txt');
    }

    #[Test]
    public function clear_directory_removes_subdirectories(): void
    {
        mkdir($this->tempDir . '/subdir', 0755, true);
        file_put_contents($this->tempDir . '/subdir/file.txt', 'test');
        
        clear_directory($this->tempDir, true);
        
        $this->assertDirectoryDoesNotExist($this->tempDir . '/subdir');
    }

    #[Test]
    public function clear_directory_removes_directory_when_not_preserved(): void
    {
        mkdir($this->tempDir, 0755, true);
        file_put_contents($this->tempDir . '/file.txt', 'test');
        
        clear_directory($this->tempDir, false);
        
        $this->assertDirectoryDoesNotExist($this->tempDir);
    }

    #[Test]
    public function clear_directory_returns_false_for_nonexistent(): void
    {
        $result = clear_directory('/nonexistent/path');
        
        $this->assertFalse($result);
    }

    // e() helper tests

    #[Test]
    public function e_escapes_html(): void
    {
        $result = e('<script>alert("xss")</script>');
        
        $this->assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);
    }

    #[Test]
    public function e_escapes_ampersand(): void
    {
        $this->assertSame('&amp;', e('&'));
    }

    #[Test]
    public function e_escapes_quotes(): void
    {
        $result = e('"test"');
        
        $this->assertSame('&quot;test&quot;', $result);
    }
}
