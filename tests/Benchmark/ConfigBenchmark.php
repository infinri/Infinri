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
namespace Tests\Benchmark;

use App\Core\Config\Config;

/**
 * Configuration Parser Speed Benchmark
 * 
 * Benchmarks:
 * - Config file load
 * - Merged config arrays
 * - Environment overrides
 * - Boot-time config expansion
 * 
 * Goal: < 150 µs for config boot.
 * 
 * Run with: php tests/Benchmark/ConfigBenchmark.php
 */
final class ConfigBenchmark
{
    private array $results = [];
    private const ITERATIONS = 1000;
    private string $tempDir;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Configuration Parser Benchmark\n";
        echo "========================================\n\n";

        $this->setupTempDir();

        try {
            $this->benchmarkEnvParsing();
            $this->benchmarkConfigLoad();
            $this->benchmarkConfigMerge();
            $this->benchmarkDotNotation();
            $this->benchmarkEnvOverrides();
            $this->benchmarkConfigCaching();
            $this->benchmarkLargeConfig();
            $this->analyzeBootTime();
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
        $this->tempDir = sys_get_temp_dir() . '/config_bench_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/config', 0755, true);

        // Create .env file
        $env = <<<ENV
APP_NAME=Infinri
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_KEY=base64:randomkeyhere1234567890123456789012

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=infinri
DB_USERNAME=root
DB_PASSWORD=secret

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
ENV;
        file_put_contents($this->tempDir . '/.env', $env);

        // Create config files
        $this->createConfigFiles();
    }

    /**
     * Create config files
     */
    private function createConfigFiles(): void
    {
        $configs = [
            'app' => [
                'name' => "env('APP_NAME', 'Infinri')",
                'env' => "env('APP_ENV', 'production')",
                'debug' => "env('APP_DEBUG', false)",
                'url' => "env('APP_URL', 'http://localhost')",
                'timezone' => 'UTC',
                'locale' => 'en',
                'fallback_locale' => 'en',
                'key' => "env('APP_KEY')",
                'cipher' => 'AES-256-CBC',
            ],
            'database' => [
                'default' => "env('DB_CONNECTION', 'mysql')",
                'connections' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => "env('DB_HOST', '127.0.0.1')",
                        'port' => "env('DB_PORT', 3306)",
                        'database' => "env('DB_DATABASE', 'forge')",
                        'username' => "env('DB_USERNAME', 'forge')",
                        'password' => "env('DB_PASSWORD', '')",
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                    ],
                ],
            ],
            'cache' => [
                'default' => "env('CACHE_DRIVER', 'file')",
                'stores' => [
                    'file' => ['driver' => 'file', 'path' => '/tmp/cache'],
                    'redis' => ['driver' => 'redis', 'connection' => 'cache'],
                ],
                'prefix' => 'infinri_cache_',
            ],
        ];

        foreach ($configs as $name => $config) {
            $content = '<?php return ' . var_export($config, true) . ';';
            file_put_contents($this->tempDir . "/config/{$name}.php", $content);
        }
    }

    /**
     * Benchmark .env parsing
     */
    private function benchmarkEnvParsing(): void
    {
        echo "Benchmarking .env Parsing... ";

        $envFile = $this->tempDir . '/.env';

        // Line-by-line parsing
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $env = [];
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['.env Parse (Line-by-line)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'lines' => count(file($envFile)),
        ];

        // Regex parsing
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $content = file_get_contents($envFile);
            preg_match_all('/^([A-Z_]+)=(.*)$/m', $content, $matches, PREG_SET_ORDER);
            $env = [];
            foreach ($matches as $match) {
                $env[$match[1]] = $match[2];
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['.env Parse (Regex)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark config file loading
     */
    private function benchmarkConfigLoad(): void
    {
        echo "Benchmarking Config Load... ";

        $configFile = $this->tempDir . '/config/app.php';

        // Single file load
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            include $configFile;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config Load (Single File)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Multiple file load
        $configFiles = glob($this->tempDir . '/config/*.php');
        
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $configs = [];
            foreach ($configFiles as $file) {
                $name = basename($file, '.php');
                $configs[$name] = include $file;
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config Load (All Files)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'files' => count($configFiles),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark config merging
     */
    private function benchmarkConfigMerge(): void
    {
        echo "Benchmarking Config Merge... ";

        $baseConfig = [
            'app' => ['name' => 'Base', 'debug' => false],
            'database' => ['driver' => 'mysql', 'host' => 'localhost'],
            'cache' => ['driver' => 'file'],
        ];

        $overrideConfig = [
            'app' => ['debug' => true, 'env' => 'testing'],
            'database' => ['host' => '127.0.0.1', 'database' => 'test'],
            'session' => ['driver' => 'array'],
        ];

        // array_merge
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            array_merge($baseConfig, $overrideConfig);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config Merge (array_merge)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // array_replace_recursive (deep merge)
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            array_replace_recursive($baseConfig, $overrideConfig);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config Merge (Deep)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Spread operator
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            [...$baseConfig, ...$overrideConfig];
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config Merge (Spread)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark dot notation access
     */
    private function benchmarkDotNotation(): void
    {
        echo "Benchmarking Dot Notation... ";

        $config = [
            'app' => [
                'name' => 'Infinri',
                'providers' => [
                    'database' => ['class' => 'DatabaseProvider'],
                    'cache' => ['class' => 'CacheProvider'],
                ],
            ],
            'database' => [
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'port' => 3306,
                    ],
                ],
            ],
        ];

        // Direct array access
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $value = $config['database']['connections']['mysql']['host'];
            $times[] = hrtime(true) - $start;
        }

        $this->results['Access: Direct Array'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Dot notation (split and traverse)
        $getDot = function(array $array, string $key, $default = null) {
            $keys = explode('.', $key);
            foreach ($keys as $k) {
                if (!isset($array[$k])) {
                    return $default;
                }
                $array = $array[$k];
            }
            return $array;
        };

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $getDot($config, 'database.connections.mysql.host');
            $times[] = hrtime(true) - $start;
        }

        $this->results['Access: Dot Notation'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Cached flat array
        $flatConfig = [];
        $flatten = function(array $array, string $prefix = '') use (&$flatten, &$flatConfig) {
            foreach ($array as $key => $value) {
                $newKey = $prefix ? "{$prefix}.{$key}" : $key;
                if (is_array($value)) {
                    $flatten($value, $newKey);
                } else {
                    $flatConfig[$newKey] = $value;
                }
            }
        };
        $flatten($config);

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $value = $flatConfig['database.connections.mysql.host'] ?? null;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Access: Flattened Cache'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark environment variable overrides
     */
    private function benchmarkEnvOverrides(): void
    {
        echo "Benchmarking Env Overrides... ";

        // Set env variables
        $_ENV['BENCH_VAR1'] = 'value1';
        $_ENV['BENCH_VAR2'] = 'value2';
        putenv('BENCH_VAR3=value3');

        // getenv()
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            getenv('BENCH_VAR3');
            $times[] = hrtime(true) - $start;
        }

        $this->results['Env: getenv()'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // $_ENV
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $_ENV['BENCH_VAR1'] ?? null;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Env: $_ENV'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Combined lookup
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $_ENV['BENCH_VAR1'] ?? getenv('BENCH_VAR1') ?: null;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Env: Combined Lookup'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark config caching
     */
    private function benchmarkConfigCaching(): void
    {
        echo "Benchmarking Config Caching... ";

        $config = [
            'app' => include $this->tempDir . '/config/app.php',
            'database' => include $this->tempDir . '/config/database.php',
            'cache' => include $this->tempDir . '/config/cache.php',
        ];

        $cacheFile = $this->tempDir . '/config.cache.php';

        // Write cache
        $times = [];
        for ($i = 0; $i < 100; $i++) {
            $start = hrtime(true);
            $export = '<?php return ' . var_export($config, true) . ';';
            file_put_contents($cacheFile, $export);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config Cache: Write'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'file_size' => filesize($cacheFile),
        ];

        // Read cache (cold)
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($cacheFile, true);
        }

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            include $cacheFile;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config Cache: Read'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark large config handling
     */
    private function benchmarkLargeConfig(): void
    {
        echo "Benchmarking Large Config... ";

        // Generate large config
        $largeConfig = [];
        for ($i = 0; $i < 100; $i++) {
            $largeConfig["section_{$i}"] = [
                'key1' => 'value1',
                'key2' => 123,
                'key3' => ['nested' => true, 'data' => str_repeat('x', 100)],
                'key4' => array_fill(0, 10, 'item'),
            ];
        }

        $largeFile = $this->tempDir . '/config/large.php';
        file_put_contents($largeFile, '<?php return ' . var_export($largeConfig, true) . ';');

        // Load large config
        $times = [];
        for ($i = 0; $i < 100; $i++) {
            $start = hrtime(true);
            include $largeFile;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Large Config (100 sections)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'file_size_kb' => filesize($largeFile) / 1024,
        ];

        echo "Done\n";
    }

    /**
     * Analyze total boot time
     */
    private function analyzeBootTime(): void
    {
        echo "Analyzing Boot Time... ";

        // Simulate full config boot
        $times = [];
        for ($i = 0; $i < 100; $i++) {
            $start = hrtime(true);

            // 1. Parse .env
            $envFile = $this->tempDir . '/.env';
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $env = [];
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }

            // 2. Load config files
            $configFiles = glob($this->tempDir . '/config/*.php');
            $configs = [];
            foreach ($configFiles as $file) {
                $name = basename($file, '.php');
                $configs[$name] = include $file;
            }

            // 3. Merge defaults
            $defaults = ['app' => ['debug' => false]];
            $configs = array_replace_recursive($defaults, $configs);

            $times[] = hrtime(true) - $start;
        }

        $avgUs = (array_sum($times) / count($times)) / 1e3;
        $meetsGoal = $avgUs < 150;

        $this->results['Full Config Boot'] = [
            'avg_us' => $avgUs,
            'meets_goal' => $meetsGoal ? 'YES (< 150µs)' : 'NO (> 150µs)',
            'rating' => match (true) {
                $avgUs < 50 => 'Excellent',
                $avgUs < 100 => 'Good',
                $avgUs < 150 => 'Acceptable',
                $avgUs < 300 => 'Needs Improvement',
                default => 'Poor',
            },
        ];

        echo "Done\n";
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
                    } elseif (str_contains($key, '_ns')) {
                        printf("  %s: %.0f ns\n", $label, $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", $label, number_format($value, 0));
                    } elseif (str_contains($key, '_kb')) {
                        printf("  %s: %.2f KB\n", $label, $value);
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
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        printf("  Goal: < 150 µs for config boot\n");
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
    (new ConfigBenchmark())->run();
}
