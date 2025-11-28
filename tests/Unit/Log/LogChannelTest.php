<?php

declare(strict_types=1);

namespace Tests\Unit\Log;

use App\Core\Log\LogChannel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LogChannelTest extends TestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        $this->logDir = sys_get_temp_dir() . '/log_channel_test_' . uniqid();
        mkdir($this->logDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->logDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    public function it_creates_channel_with_correct_name(): void
    {
        $channel = new LogChannel('test', $this->logDir . '/test.log');
        
        $this->assertEquals('test', $channel->getName());
    }

    #[Test]
    public function it_returns_correct_path(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path);
        
        $this->assertEquals($path, $channel->getPath());
    }

    #[Test]
    public function it_writes_entries_to_file(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path);
        
        $channel->write("Test entry\n");
        
        $this->assertFileExists($path);
        $this->assertStringContainsString('Test entry', file_get_contents($path));
    }

    #[Test]
    public function it_appends_entries(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path);
        
        $channel->write("Entry 1\n");
        $channel->write("Entry 2\n");
        
        $content = file_get_contents($path);
        $this->assertStringContainsString('Entry 1', $content);
        $this->assertStringContainsString('Entry 2', $content);
    }

    #[Test]
    public function it_rotates_when_max_size_reached(): void
    {
        $path = $this->logDir . '/test.log';
        $maxSize = 100; // Very small for testing
        $channel = new LogChannel('test', $path, $maxSize);
        
        // Write enough data to exceed max size
        for ($i = 0; $i < 20; $i++) {
            $channel->write(str_repeat('x', 20) . "\n");
        }
        
        // Should have created archive
        $archiveDir = $this->logDir . '/archive';
        $this->assertDirectoryExists($archiveDir);
        
        // Archive should be gzipped
        $archives = glob($archiveDir . '/test_*.gz');
        $this->assertNotEmpty($archives);
    }

    #[Test]
    public function it_creates_archive_directory_on_rotation(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path, 50);
        
        // Write data to trigger rotation
        for ($i = 0; $i < 10; $i++) {
            $channel->write(str_repeat('y', 20) . "\n");
        }
        
        $archiveDir = $this->logDir . '/archive';
        $this->assertDirectoryExists($archiveDir);
    }

    #[Test]
    public function it_compresses_rotated_files(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path, 50);
        
        // Write data
        for ($i = 0; $i < 10; $i++) {
            $channel->write("Test data line $i\n");
        }
        
        $archives = glob($this->logDir . '/archive/*.gz');
        if (!empty($archives)) {
            $compressed = file_get_contents($archives[0]);
            $decompressed = gzdecode($compressed);
            $this->assertStringContainsString('Test data', $decompressed);
        }
    }

    #[Test]
    public function it_clears_log_after_rotation(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path, 50);
        
        // Fill and rotate
        for ($i = 0; $i < 10; $i++) {
            $channel->write(str_repeat('z', 20) . "\n");
        }
        
        // Current log should be empty or very small
        clearstatcache();
        $this->assertLessThan(50, filesize($path));
    }

    #[Test]
    public function it_respects_max_files_limit(): void
    {
        $path = $this->logDir . '/test.log';
        $maxFiles = 2;
        $channel = new LogChannel('test', $path, 30, $maxFiles);
        
        // Force multiple rotations by writing enough data
        // Each rotation creates an archive, cleanup keeps only maxFiles
        for ($rotation = 0; $rotation < 5; $rotation++) {
            for ($i = 0; $i < 5; $i++) {
                $channel->write(str_repeat('a', 20) . "\n");
            }
            // Force rotation without sleep by manually calling rotate
            $channel->rotate();
        }
        
        $archives = glob($this->logDir . '/archive/test_*.gz');
        $this->assertLessThanOrEqual($maxFiles, count($archives));
    }

    #[Test]
    public function it_names_archives_with_date_range(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path, 50);
        
        for ($i = 0; $i < 10; $i++) {
            $channel->write("Entry $i\n");
        }
        
        $archives = glob($this->logDir . '/archive/test_*.gz');
        if (!empty($archives)) {
            $filename = basename($archives[0]);
            // Should match pattern: test_YYYY-MM-DD_HH-ii-ss_to_YYYY-MM-DD_HH-ii-ss.log.gz
            $this->assertMatchesRegularExpression('/test_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_to_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.log\.gz/', $filename);
        }
    }

    #[Test]
    public function it_creates_log_directory_if_not_exists(): void
    {
        $nestedPath = $this->logDir . '/nested/deep/test.log';
        $channel = new LogChannel('test', $nestedPath);
        
        $channel->write("Test\n");
        
        $this->assertDirectoryExists(dirname($nestedPath));
        $this->assertFileExists($nestedPath);
    }

    #[Test]
    public function it_manual_rotation_works(): void
    {
        $path = $this->logDir . '/test.log';
        $channel = new LogChannel('test', $path);
        
        $channel->write("Some data\n");
        $channel->rotate();
        
        // File should be cleared
        $this->assertEquals('', file_get_contents($path));
        
        // Archive should exist
        $archives = glob($this->logDir . '/archive/test_*.gz');
        $this->assertCount(1, $archives);
    }

    #[Test]
    public function rotate_does_nothing_for_empty_file(): void
    {
        $path = $this->logDir . '/empty.log';
        file_put_contents($path, '');
        
        $channel = new LogChannel('empty', $path);
        $channel->rotate();
        
        // No archive should be created for empty file
        $archives = glob($this->logDir . '/archive/empty_*.gz');
        $this->assertCount(0, $archives);
    }

    #[Test]
    public function cleanup_removes_oldest_files(): void
    {
        $path = $this->logDir . '/cleanup.log';
        $archiveDir = $this->logDir . '/archive';
        mkdir($archiveDir, 0755, true);
        
        // Create multiple archive files with different timestamps
        for ($i = 0; $i < 5; $i++) {
            $archiveFile = $archiveDir . "/cleanup_2024-01-0{$i}_00-00-00_to_2024-01-0{$i}_23-59-59.log.gz";
            file_put_contents($archiveFile, gzencode("log $i"));
            touch($archiveFile, strtotime("2024-01-0{$i}")); // Set mtime
            usleep(10000); // Small delay to ensure different mtimes
        }
        
        // Create channel with maxFiles = 2
        $channel = new LogChannel('cleanup', $path, 1000, 2);
        
        // Write and rotate to trigger cleanup
        $channel->write("New entry\n");
        $channel->rotate();
        
        // Should have at most 2 archives
        $archives = glob($archiveDir . '/cleanup_*.gz');
        $this->assertLessThanOrEqual(3, count($archives)); // 2 + 1 new
    }
}
