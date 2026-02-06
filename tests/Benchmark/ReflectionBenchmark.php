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

use App\Core\Application;
use App\Core\Container\Container;

/**
 * Reflection Costs Audit Benchmark
 * 
 * Tests:
 * - Reflection on first boot
 * - Repeated resolution
 * - Constructor injection cost
 * - Property injection cost (if used)
 * - Method call injection cost
 * - Metadata caching impact
 * 
 * Run with: php tests/Benchmark/ReflectionBenchmark.php
 */
final class ReflectionBenchmark
{
    private array $results = [];
    private const ITERATIONS = 1000;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Reflection Costs Audit\n";
        echo "========================================\n\n";

        $this->benchmarkReflectionClassCreation();
        $this->benchmarkConstructorAnalysis();
        $this->benchmarkParameterAnalysis();
        $this->benchmarkMethodAnalysis();
        $this->benchmarkPropertyAnalysis();
        $this->benchmarkContainerResolution();
        $this->benchmarkCachedVsUncached();
        $this->benchmarkAutowiring();
        $this->printResults();
    }

    /**
     * Benchmark ReflectionClass creation
     */
    private function benchmarkReflectionClassCreation(): void
    {
        echo "Benchmarking ReflectionClass Creation... ";

        $classes = [
            \stdClass::class,
            Container::class,
            Application::class,
        ];

        foreach ($classes as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            
            // Cold (first time)
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                new \ReflectionClass($class);
                $times[] = hrtime(true) - $start;
            }

            $this->results["ReflectionClass: {$shortName}"] = [
                'avg_ns' => array_sum($times) / count($times),
                'min_ns' => min($times),
                'max_ns' => max($times),
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark constructor analysis
     */
    private function benchmarkConstructorAnalysis(): void
    {
        echo "Benchmarking Constructor Analysis... ";

        // Simple class
        $simpleClass = new class {};
        $simpleReflection = new \ReflectionClass($simpleClass);

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $constructor = $simpleReflection->getConstructor();
            $times[] = hrtime(true) - $start;
        }

        $this->results['Constructor (None)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Class with constructor
        $complexClass = new class(new \stdClass(), 'test', 123) {
            public function __construct(
                public \stdClass $obj,
                public string $str,
                public int $num
            ) {}
        };
        $complexReflection = new \ReflectionClass($complexClass);

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $constructor = $complexReflection->getConstructor();
            $params = $constructor?->getParameters() ?? [];
            $times[] = hrtime(true) - $start;
        }

        $this->results['Constructor (3 params)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark parameter analysis
     */
    private function benchmarkParameterAnalysis(): void
    {
        echo "Benchmarking Parameter Analysis... ";

        $class = new class(new \stdClass(), 'test', 123, ['a', 'b'], true) {
            public function __construct(
                public \stdClass $obj,
                public string $str,
                public int $num,
                public array $arr,
                public bool $flag
            ) {}
        };

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();

        // Type analysis
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            foreach ($params as $param) {
                $type = $param->getType();
                $typeName = $type?->getName();
                $allowsNull = $type?->allowsNull();
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Parameter Type Analysis (5 params)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'per_param_ns' => (array_sum($times) / count($times)) / count($params),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Default value analysis
        $classWithDefaults = new class('required', 'default', 123) {
            public function __construct(
                public string $required,
                public string $optional = 'default',
                public int $number = 0
            ) {}
        };

        $defaultReflection = new \ReflectionClass($classWithDefaults);
        $defaultParams = $defaultReflection->getConstructor()->getParameters();

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            foreach ($defaultParams as $param) {
                $hasDefault = $param->isDefaultValueAvailable();
                if ($hasDefault) {
                    $default = $param->getDefaultValue();
                }
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Default Value Analysis (3 params)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark method analysis
     */
    private function benchmarkMethodAnalysis(): void
    {
        echo "Benchmarking Method Analysis... ";

        $reflection = new \ReflectionClass(Container::class);

        // Get all methods
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $methods = $reflection->getMethods();
            $times[] = hrtime(true) - $start;
        }

        $methodCount = count($reflection->getMethods());
        $this->results["getMethods ({$methodCount} methods)"] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Get single method
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            try {
                $method = $reflection->getMethod('make');
            } catch (\ReflectionException) {}
            $times[] = hrtime(true) - $start;
        }

        $this->results['getMethod (single)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark property analysis
     */
    private function benchmarkPropertyAnalysis(): void
    {
        echo "Benchmarking Property Analysis... ";

        $class = new class {
            public string $public = 'public';
            protected int $protected = 123;
            private array $private = [];
            public readonly string $readonly;
            
            public function __construct() {
                $this->readonly = 'readonly';
            }
        };

        $reflection = new \ReflectionClass($class);

        // Get all properties
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $properties = $reflection->getProperties();
            $times[] = hrtime(true) - $start;
        }

        $propCount = count($reflection->getProperties());
        $this->results["getProperties ({$propCount} props)"] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Property attribute analysis
        $times = [];
        $properties = $reflection->getProperties();
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            foreach ($properties as $prop) {
                $type = $prop->getType();
                $name = $prop->getName();
                $modifiers = $prop->getModifiers();
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Property Attribute Analysis'] = [
            'avg_ns' => array_sum($times) / count($times),
            'per_prop_ns' => (array_sum($times) / count($times)) / count($properties),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark container resolution
     */
    private function benchmarkContainerResolution(): void
    {
        echo "Benchmarking Container Resolution... ";

        $container = new Container();

        // Register services
        $container->singleton('simple', fn() => new \stdClass());
        $container->bind('factory', fn() => new \stdClass());

        // Simple service
        $simpleService = new class {
            public function __construct() {}
        };
        $container->bind('no_deps', fn() => new $simpleService());

        // Service with dependencies
        $depService = new class(new \stdClass()) {
            public function __construct(public \stdClass $dep) {}
        };
        $container->bind(\stdClass::class, fn() => new \stdClass());

        // Singleton resolution
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $container->make('simple');
            $times[] = hrtime(true) - $start;
        }

        $this->results['Container: Singleton'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Factory resolution
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $container->make('factory');
            $times[] = hrtime(true) - $start;
        }

        $this->results['Container: Factory'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark cached vs uncached reflection
     */
    private function benchmarkCachedVsUncached(): void
    {
        echo "Benchmarking Cached vs Uncached... ";

        $class = Container::class;

        // Uncached (create new each time)
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            $params = $constructor?->getParameters() ?? [];
            foreach ($params as $param) {
                $type = $param->getType();
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Uncached Full Analysis'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Cached (reuse reflection)
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $params = $constructor?->getParameters() ?? [];
        
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            foreach ($params as $param) {
                $type = $param->getType();
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cached (Reused Reflection)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Calculate speedup
        $uncachedAvg = $this->results['Uncached Full Analysis']['avg_ns'];
        $cachedAvg = $this->results['Cached (Reused Reflection)']['avg_ns'];
        $this->results['Cache Speedup'] = [
            'speedup_factor' => $uncachedAvg / max($cachedAvg, 1),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark autowiring
     */
    private function benchmarkAutowiring(): void
    {
        echo "Benchmarking Autowiring... ";

        $container = new Container();
        $container->bind(\stdClass::class, fn() => new \stdClass());

        // Class with autowired dependencies
        $autoWiredClass = new class(new \stdClass()) {
            public function __construct(public \stdClass $dep) {}
        };

        // Manual resolution
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $dep = $container->make(\stdClass::class);
            $instance = new $autoWiredClass($dep);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Manual Resolution'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Full autowiring simulation
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            // Simulate autowiring
            $reflection = new \ReflectionClass($autoWiredClass);
            $constructor = $reflection->getConstructor();
            $params = $constructor?->getParameters() ?? [];
            
            $args = [];
            foreach ($params as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $args[] = $container->make($type->getName());
                }
            }
            
            $instance = $reflection->newInstanceArgs($args);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Full Autowiring (Reflection)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
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
                    if (str_contains($key, '_ns')) {
                        printf("  %s: %.0f ns\n", $label, $value);
                    } elseif (str_contains($key, '_us')) {
                        printf("  %s: %.2f µs\n", $label, $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", $label, number_format($value, 0));
                    } elseif (str_contains($key, 'factor')) {
                        printf("  %s: %.2fx\n", $label, $value);
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
        
        if (isset($this->results['Cache Speedup'])) {
            $speedup = $this->results['Cache Speedup']['speedup_factor'];
            if ($speedup > 5) {
                printf("  ✅ Reflection caching provides %.1fx speedup\n", $speedup);
                echo "     Consider caching reflection metadata for hot paths\n";
            }
        }

        echo "\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__, 2));
    }
    
    (new ReflectionBenchmark())->run();
}
