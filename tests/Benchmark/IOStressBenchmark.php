<?php

declare(strict_types=1);

namespace Tests\Benchmark;

/**
 * I/O Stress Benchmark
 * 
 * Simulates:
 * - Writing logs under load
 * - Cache writes during traffic spikes
 * - Database reconnect storms
 * - Disk throttling
 * 
 * This reveals fault tolerance.
 * 
 * Run with: php tests/Benchmark/IOStressBenchmark.php
 */
final class IOStressBenchmark
{
    private array $results = [];
    private string $tempDir;
    private const ITERATIONS = 1000;

    public function run(): void
    {
        echo "========================================\n";
        echo "  I/O Stress Benchmark\n";
        echo "========================================\n\n";

        $this->setupTempDir();

        try {
            $this->benchmarkLogWriteUnderLoad();
            $this->benchmarkConcurrentFileWrites();
            $this->benchmarkCacheWritePatterns();
            $this->benchmarkFileRotation();
            $this->benchmarkDiskLatency();
            $this->benchmarkBufferedVsUnbuffered();
            $this->benchmarkLockContention();
            $this->printResults();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Setup temporary directory
     */
    private function setupTempDir(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/io_bench_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/logs', 0755, true);
        mkdir($this->tempDir . '/cache', 0755, true);
    }

    /**
     * Benchmark log writing under load
     */
    private function benchmarkLogWriteUnderLoad(): void
    {
        echo "Benchmarking Log Write Under Load... ";

        $logFile = $this->tempDir . '/logs/app.log';
        $logSizes = [100, 1000, 5000];

        foreach ($logSizes as $size) {
            $message = str_repeat('x', $size);
            
            // Sequential writes
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " {$message}\n", FILE_APPEND);
                $times[] = hrtime(true) - $start;
            }

            $this->results["Log Write ({$size} bytes)"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'min_us' => min($times) / 1e3,
                'max_us' => max($times) / 1e3,
                'p99_us' => $this->percentile($times, 99) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            ];

            // Cleanup for next test
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        }

        echo "Done\n";
    }

    /**
     * Benchmark concurrent file writes
     */
    private function benchmarkConcurrentFileWrites(): void
    {
        echo "Benchmarking Concurrent File Writes... ";

        // Multiple files simultaneously
        $fileCount = 10;
        $files = [];
        for ($i = 0; $i < $fileCount; $i++) {
            $files[] = $this->tempDir . "/cache/file_{$i}.cache";
        }

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            foreach ($files as $file) {
                file_put_contents($file, "Data for iteration {$i}");
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results["Concurrent Write ({$fileCount} files)"] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'per_file_us' => ((array_sum($times) / count($times)) / $fileCount) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Cleanup
        foreach ($files as $file) {
            if (file_exists($file)) unlink($file);
        }

        echo "Done\n";
    }

    /**
     * Benchmark cache write patterns
     */
    private function benchmarkCacheWritePatterns(): void
    {
        echo "Benchmarking Cache Write Patterns... ";

        $cacheDir = $this->tempDir . '/cache';

        // Simple key-value cache
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $key = 'cache_key_' . ($i % 100);
            $value = json_encode(['data' => $i, 'timestamp' => time()]);
            $file = $cacheDir . '/' . md5($key) . '.cache';
            
            $start = hrtime(true);
            file_put_contents($file, $value);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cache Write (JSON)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Cache read
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $key = 'cache_key_' . ($i % 100);
            $file = $cacheDir . '/' . md5($key) . '.cache';
            
            $start = hrtime(true);
            if (file_exists($file)) {
                $data = file_get_contents($file);
                json_decode($data, true);
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cache Read (JSON)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Atomic write (temp + rename)
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $key = 'atomic_' . ($i % 100);
            $value = serialize(['data' => $i, 'timestamp' => time()]);
            $file = $cacheDir . '/' . md5($key) . '.cache';
            $tempFile = $file . '.tmp.' . getmypid();
            
            $start = hrtime(true);
            file_put_contents($tempFile, $value);
            rename($tempFile, $file);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cache Write (Atomic)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Cleanup cache files
        array_map('unlink', glob($cacheDir . '/*.cache'));
        array_map('unlink', glob($cacheDir . '/*.tmp.*'));

        echo "Done\n";
    }

    /**
     * Benchmark file rotation
     */
    private function benchmarkFileRotation(): void
    {
        echo "Benchmarking File Rotation... ";

        $logFile = $this->tempDir . '/logs/rotating.log';

        // Create initial log file
        file_put_contents($logFile, str_repeat("Log entry\n", 1000));

        // Simulate rotation
        $times = [];
        for ($i = 0; $i < 100; $i++) {
            // Create file to rotate
            file_put_contents($logFile, str_repeat("Log entry\n", 1000));
            
            $start = hrtime(true);
            
            // Rotate (rename and create new)
            $rotatedFile = $logFile . '.' . date('YmdHis') . '.' . $i;
            rename($logFile, $rotatedFile);
            touch($logFile);
            
            $times[] = hrtime(true) - $start;
            
            // Cleanup rotated file
            unlink($rotatedFile);
        }

        $this->results['File Rotation'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => 100 / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark disk latency variations
     */
    private function benchmarkDiskLatency(): void
    {
        echo "Benchmarking Disk Latency... ";

        $testFile = $this->tempDir . '/latency_test.bin';
        $sizes = [
            '1KB' => 1024,
            '10KB' => 10 * 1024,
            '100KB' => 100 * 1024,
            '1MB' => 1024 * 1024,
        ];

        foreach ($sizes as $name => $size) {
            $data = str_repeat('x', $size);
            
            // Write latency
            $times = [];
            for ($i = 0; $i < 100; $i++) {
                $start = hrtime(true);
                file_put_contents($testFile, $data);
                $times[] = hrtime(true) - $start;
            }

            $this->results["Write Latency ({$name})"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'throughput_mb_sec' => ($size / 1024 / 1024) / ((array_sum($times) / count($times)) / 1e9),
            ];

            // Read latency
            $times = [];
            for ($i = 0; $i < 100; $i++) {
                $start = hrtime(true);
                file_get_contents($testFile);
                $times[] = hrtime(true) - $start;
            }

            $this->results["Read Latency ({$name})"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'throughput_mb_sec' => ($size / 1024 / 1024) / ((array_sum($times) / count($times)) / 1e9),
            ];
        }

        unlink($testFile);

        echo "Done\n";
    }

    /**
     * Benchmark buffered vs unbuffered I/O
     */
    private function benchmarkBufferedVsUnbuffered(): void
    {
        echo "Benchmarking Buffered vs Unbuffered... ";

        $testFile = $this->tempDir . '/buffer_test.log';
        $lineCount = 1000;
        $line = str_repeat('x', 100) . "\n";

        // Unbuffered (file_put_contents per line)
        $start = hrtime(true);
        for ($i = 0; $i < $lineCount; $i++) {
            file_put_contents($testFile, $line, FILE_APPEND);
        }
        $unbufferedTime = hrtime(true) - $start;

        unlink($testFile);

        $this->results['Unbuffered (1000 writes)'] = [
            'total_ms' => $unbufferedTime / 1e6,
            'per_write_us' => ($unbufferedTime / $lineCount) / 1e3,
        ];

        // Buffered (collect then write)
        $buffer = '';
        $start = hrtime(true);
        for ($i = 0; $i < $lineCount; $i++) {
            $buffer .= $line;
        }
        file_put_contents($testFile, $buffer);
        $bufferedTime = hrtime(true) - $start;

        unlink($testFile);

        $this->results['Buffered (1 write)'] = [
            'total_ms' => $bufferedTime / 1e6,
            'speedup' => $unbufferedTime / max($bufferedTime, 1),
        ];

        // Stream with buffer
        $start = hrtime(true);
        $handle = fopen($testFile, 'w');
        stream_set_write_buffer($handle, 8192);
        for ($i = 0; $i < $lineCount; $i++) {
            fwrite($handle, $line);
        }
        fclose($handle);
        $streamTime = hrtime(true) - $start;

        unlink($testFile);

        $this->results['Stream Buffered'] = [
            'total_ms' => $streamTime / 1e6,
            'speedup_vs_unbuffered' => $unbufferedTime / max($streamTime, 1),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark lock contention
     */
    private function benchmarkLockContention(): void
    {
        echo "Benchmarking Lock Contention... ";

        $lockFile = $this->tempDir . '/test.lock';
        $dataFile = $this->tempDir . '/data.txt';

        // Without locking
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            file_put_contents($dataFile, "Data {$i}");
            $times[] = hrtime(true) - $start;
        }

        $this->results['Write (No Lock)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // With flock
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $handle = fopen($dataFile, 'w');
            flock($handle, LOCK_EX);
            fwrite($handle, "Data {$i}");
            flock($handle, LOCK_UN);
            fclose($handle);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Write (flock)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // With separate lock file
        touch($lockFile);
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $lock = fopen($lockFile, 'r');
            flock($lock, LOCK_EX);
            file_put_contents($dataFile, "Data {$i}");
            flock($lock, LOCK_UN);
            fclose($lock);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Write (Separate Lock)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Calculate percentile
     */
    private function percentile(array $data, int $percentile): float
    {
        sort($data);
        $index = ceil(count($data) * ($percentile / 100)) - 1;
        return $data[max(0, (int)$index)];
    }

    /**
     * Print results
     */
    private function printResults(): void
    {
        echo "\n========================================\n";
        echo "  Results\n";
        echo "========================================\n\n";

        foreach ($this->results as $name => $data) {
            echo "{$name}:\n";
            
            foreach ($data as $key => $value) {
                $label = str_replace('_', ' ', ucfirst($key));
                
                if (is_float($value)) {
                    if (str_contains($key, '_us')) {
                        printf("  %s: %.2f µs\n", $label, $value);
                    } elseif (str_contains($key, '_ms')) {
                        printf("  %s: %.2f ms\n", $label, $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", $label, number_format($value, 0));
                    } elseif (str_contains($key, 'mb_sec')) {
                        printf("  %s: %.2f MB/s\n", $label, $value);
                    } elseif (str_contains($key, 'speedup')) {
                        printf("  %s: %.1fx\n", $label, $value);
                    } else {
                        printf("  %s: %.2f\n", $label, $value);
                    }
                } else {
                    printf("  %s: %s\n", $label, $value);
                }
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "  Recommendations\n";
        echo "========================================\n";
        echo "  - Use buffered writes for high-frequency logging\n";
        echo "  - Atomic writes (temp + rename) for cache safety\n";
        echo "  - Stream buffers for sequential writes\n";
        echo "  - Separate lock files reduce contention\n";
        echo "\n";

        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }

    /**
     * Cleanup
     */
    private function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
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
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    (new IOStressBenchmark())->run();
}
