<?php

declare(strict_types=1);

namespace Tests\Benchmark;

/**
 * Serialization / Deserialization Benchmark
 * 
 * Benchmarks:
 * - JSON encode/decode
 * - Object serialization
 * - Array conversion
 * - Cache payload decode time
 * - Config array parse speed
 * 
 * Goal: Avoid overhead when caching config or modules.
 * 
 * Run with: php tests/Benchmark/SerializationBenchmark.php
 */
final class SerializationBenchmark
{
    private array $results = [];
    private const ITERATIONS = 1000;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Serialization Benchmark\n";
        echo "========================================\n\n";

        $this->benchmarkJsonEncode();
        $this->benchmarkJsonDecode();
        $this->benchmarkSerialize();
        $this->benchmarkUnserialize();
        $this->benchmarkVarExport();
        $this->benchmarkArrayConversion();
        $this->benchmarkCachePayloads();
        $this->benchmarkConfigParsing();
        $this->benchmarkLargePayloads();
        $this->printResults();
    }

    /**
     * Benchmark JSON encoding
     */
    private function benchmarkJsonEncode(): void
    {
        echo "Benchmarking JSON Encode... ";

        $payloads = [
            'Small Array' => ['id' => 1, 'name' => 'test', 'active' => true],
            'Medium Array' => array_fill(0, 100, ['id' => 1, 'name' => 'test', 'value' => 123.45]),
            'Nested Array' => $this->generateNestedArray(5, 3),
            'String Heavy' => array_fill(0, 50, str_repeat('Lorem ipsum dolor sit amet. ', 10)),
        ];

        foreach ($payloads as $name => $payload) {
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                json_encode($payload);
                $times[] = hrtime(true) - $start;
            }

            $encoded = json_encode($payload);
            $this->results["JSON Encode: {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
                'output_bytes' => strlen($encoded),
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark JSON decoding
     */
    private function benchmarkJsonDecode(): void
    {
        echo "Benchmarking JSON Decode... ";

        $payloads = [
            'Small' => json_encode(['id' => 1, 'name' => 'test', 'active' => true]),
            'Medium' => json_encode(array_fill(0, 100, ['id' => 1, 'name' => 'test'])),
            'Large' => json_encode(array_fill(0, 1000, ['id' => 1, 'data' => str_repeat('x', 100)])),
        ];

        foreach ($payloads as $name => $json) {
            // Decode to array
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                json_decode($json, true);
                $times[] = hrtime(true) - $start;
            }

            $this->results["JSON Decode (Array): {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
                'input_bytes' => strlen($json),
            ];

            // Decode to object
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                json_decode($json, false);
                $times[] = hrtime(true) - $start;
            }

            $this->results["JSON Decode (Object): {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark PHP serialize
     */
    private function benchmarkSerialize(): void
    {
        echo "Benchmarking PHP Serialize... ";

        $objects = [
            'stdClass' => (object)['id' => 1, 'name' => 'test'],
            'Array' => ['id' => 1, 'name' => 'test', 'nested' => ['a' => 1, 'b' => 2]],
            'Complex Object' => $this->createComplexObject(),
        ];

        foreach ($objects as $name => $object) {
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                serialize($object);
                $times[] = hrtime(true) - $start;
            }

            $serialized = serialize($object);
            $this->results["Serialize: {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
                'output_bytes' => strlen($serialized),
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark PHP unserialize
     */
    private function benchmarkUnserialize(): void
    {
        echo "Benchmarking PHP Unserialize... ";

        $serialized = [
            'Small' => serialize(['id' => 1, 'name' => 'test']),
            'Medium' => serialize(array_fill(0, 100, ['key' => 'value'])),
            'Object' => serialize($this->createComplexObject()),
        ];

        foreach ($serialized as $name => $data) {
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                unserialize($data);
                $times[] = hrtime(true) - $start;
            }

            $this->results["Unserialize: {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
                'input_bytes' => strlen($data),
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark var_export/include pattern
     */
    private function benchmarkVarExport(): void
    {
        echo "Benchmarking var_export... ";

        $config = [
            'app' => [
                'name' => 'Infinri',
                'env' => 'production',
                'debug' => false,
            ],
            'database' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
            ],
            'cache' => [
                'driver' => 'redis',
                'prefix' => 'app_',
            ],
        ];

        // var_export
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            var_export($config, true);
            $times[] = hrtime(true) - $start;
        }

        $exported = var_export($config, true);
        $this->results['var_export (Config)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'output_bytes' => strlen($exported),
        ];

        // Write and include
        $tempFile = sys_get_temp_dir() . '/bench_config_' . uniqid() . '.php';
        file_put_contents($tempFile, '<?php return ' . var_export($config, true) . ';');

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            include $tempFile;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Include (Cached Config)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        unlink($tempFile);

        echo "Done\n";
    }

    /**
     * Benchmark array conversion
     */
    private function benchmarkArrayConversion(): void
    {
        echo "Benchmarking Array Conversion... ";

        $object = new class {
            public int $id = 1;
            public string $name = 'test';
            public array $tags = ['php', 'benchmark'];
            public bool $active = true;
        };

        // Object to array (cast)
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            (array)$object;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Object to Array (Cast)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Object to array (get_object_vars)
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            get_object_vars($object);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Object to Array (get_object_vars)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Array to object
        $array = ['id' => 1, 'name' => 'test', 'active' => true];
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            (object)$array;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Array to Object (Cast)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark cache payloads
     */
    private function benchmarkCachePayloads(): void
    {
        echo "Benchmarking Cache Payloads... ";

        // Typical cache payload
        $cacheData = [
            'data' => array_fill(0, 50, [
                'id' => 123,
                'title' => 'Sample Post Title',
                'content' => str_repeat('Lorem ipsum dolor sit amet. ', 20),
                'created_at' => '2024-01-01 12:00:00',
                'tags' => ['php', 'cache', 'performance'],
            ]),
            'meta' => [
                'total' => 1000,
                'page' => 1,
                'per_page' => 50,
            ],
            'cached_at' => time(),
        ];

        // JSON round-trip
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $encoded = json_encode($cacheData);
            $decoded = json_decode($encoded, true);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cache: JSON Round-trip'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'payload_bytes' => strlen(json_encode($cacheData)),
        ];

        // Serialize round-trip
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $serialized = serialize($cacheData);
            $unserialized = unserialize($serialized);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cache: Serialize Round-trip'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'payload_bytes' => strlen(serialize($cacheData)),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark config parsing
     */
    private function benchmarkConfigParsing(): void
    {
        echo "Benchmarking Config Parsing... ";

        // Create temp config files
        $tempDir = sys_get_temp_dir() . '/config_bench_' . uniqid();
        mkdir($tempDir, 0755, true);

        // PHP array config
        $phpConfig = '<?php return ' . var_export([
            'database' => ['host' => 'localhost', 'port' => 3306],
            'cache' => ['driver' => 'redis', 'prefix' => 'app_'],
            'queue' => ['default' => 'sync'],
        ], true) . ';';
        file_put_contents($tempDir . '/config.php', $phpConfig);

        // JSON config
        $jsonConfig = json_encode([
            'database' => ['host' => 'localhost', 'port' => 3306],
            'cache' => ['driver' => 'redis', 'prefix' => 'app_'],
            'queue' => ['default' => 'sync'],
        ], JSON_PRETTY_PRINT);
        file_put_contents($tempDir . '/config.json', $jsonConfig);

        // PHP include
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            include $tempDir . '/config.php';
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config: PHP Include'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // JSON file_get_contents + decode
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            json_decode(file_get_contents($tempDir . '/config.json'), true);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Config: JSON File'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Cleanup
        unlink($tempDir . '/config.php');
        unlink($tempDir . '/config.json');
        rmdir($tempDir);

        echo "Done\n";
    }

    /**
     * Benchmark large payload handling
     */
    private function benchmarkLargePayloads(): void
    {
        echo "Benchmarking Large Payloads... ";

        $sizes = [
            '10KB' => 10 * 1024,
            '100KB' => 100 * 1024,
            '1MB' => 1024 * 1024,
        ];

        foreach ($sizes as $name => $targetSize) {
            // Generate payload of approximate size
            $itemCount = (int)($targetSize / 100);
            $payload = array_fill(0, $itemCount, [
                'id' => 12345,
                'data' => str_repeat('x', 50),
            ]);

            // JSON encode
            $times = [];
            for ($i = 0; $i < 100; $i++) {
                $start = hrtime(true);
                json_encode($payload);
                $times[] = hrtime(true) - $start;
            }

            $encoded = json_encode($payload);
            $this->results["Large JSON Encode: {$name}"] = [
                'avg_ms' => (array_sum($times) / count($times)) / 1e6,
                'actual_bytes' => strlen($encoded),
                'throughput_mb_sec' => (strlen($encoded) / 1024 / 1024) / ((array_sum($times) / count($times)) / 1e9),
            ];

            // JSON decode
            $times = [];
            for ($i = 0; $i < 100; $i++) {
                $start = hrtime(true);
                json_decode($encoded, true);
                $times[] = hrtime(true) - $start;
            }

            $this->results["Large JSON Decode: {$name}"] = [
                'avg_ms' => (array_sum($times) / count($times)) / 1e6,
                'throughput_mb_sec' => (strlen($encoded) / 1024 / 1024) / ((array_sum($times) / count($times)) / 1e9),
            ];
        }

        echo "Done\n";
    }

    /**
     * Generate nested array
     */
    private function generateNestedArray(int $depth, int $width): array
    {
        if ($depth === 0) {
            return ['value' => 'leaf'];
        }

        $result = [];
        for ($i = 0; $i < $width; $i++) {
            $result["level_{$depth}_{$i}"] = $this->generateNestedArray($depth - 1, $width);
        }
        return $result;
    }

    /**
     * Create complex object for testing
     */
    private function createComplexObject(): object
    {
        $obj = new \stdClass();
        $obj->id = 1;
        $obj->name = 'Complex Object';
        $obj->metadata = [
            'created' => '2024-01-01',
            'updated' => '2024-01-02',
            'tags' => ['a', 'b', 'c'],
        ];
        $obj->parent = (object)['id' => 0, 'name' => 'Parent'];
        return $obj;
    }

    /**
     * Print results
     */
    private function printResults(): void
    {
        echo "\n========================================\n";
        echo "  Results\n";
        echo "========================================\n\n";

        $categories = [
            'JSON Encode' => [],
            'JSON Decode' => [],
            'Serialize' => [],
            'Unserialize' => [],
            'var_export' => [],
            'Object' => [],
            'Cache' => [],
            'Config' => [],
            'Large' => [],
        ];

        foreach ($this->results as $name => $data) {
            foreach ($categories as $category => &$items) {
                if (str_starts_with($name, $category) || str_contains($name, $category)) {
                    $items[$name] = $data;
                    break;
                }
            }
        }

        foreach ($categories as $category => $items) {
            if (empty($items)) continue;
            
            echo "{$category}:\n";
            echo str_repeat('-', 60) . "\n";
            
            foreach ($items as $name => $data) {
                $shortName = str_replace(["{$category}: ", "{$category} "], '', $name);
                echo "  {$shortName}:\n";
                
                foreach ($data as $key => $value) {
                    $label = str_replace('_', ' ', ucfirst($key));
                    
                    if (is_float($value)) {
                        if (str_contains($key, '_us')) {
                            printf("    %s: %.2f µs\n", $label, $value);
                        } elseif (str_contains($key, '_ns')) {
                            printf("    %s: %.0f ns\n", $label, $value);
                        } elseif (str_contains($key, '_ms')) {
                            printf("    %s: %.3f ms\n", $label, $value);
                        } elseif (str_contains($key, 'per_sec')) {
                            printf("    %s: %s\n", $label, number_format($value, 0));
                        } elseif (str_contains($key, 'mb_sec')) {
                            printf("    %s: %.2f MB/s\n", $label, $value);
                        } else {
                            printf("    %s: %.2f\n", $label, $value);
                        }
                    } else {
                        if (str_contains($key, 'bytes')) {
                            printf("    %s: %s\n", $label, number_format($value));
                        } else {
                            printf("    %s: %s\n", $label, $value);
                        }
                    }
                }
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    (new SerializationBenchmark())->run();
}
