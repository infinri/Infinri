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

/**
 * Error Handling & Recovery Benchmark
 * 
 * Tests:
 * - Exception throw → response time
 * - Deep exception chains
 * - Error handler overhead
 * - Logging slowdown under load
 * - Shutdown function behavior
 * 
 * Why it matters: Production apps throw exceptions. They must not tank performance.
 * 
 * Run with: php tests/Benchmark/ErrorHandlingBenchmark.php
 */
final class ErrorHandlingBenchmark
{
    private array $results = [];
    private const ITERATIONS = 1000;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Error Handling & Recovery Benchmark\n";
        echo "========================================\n\n";

        $this->benchmarkExceptionCreation();
        $this->benchmarkExceptionThrowCatch();
        $this->benchmarkExceptionChains();
        $this->benchmarkStackTraceGeneration();
        $this->benchmarkErrorHandlerOverhead();
        $this->benchmarkLoggingUnderLoad();
        $this->benchmarkRecoveryPatterns();
        $this->printResults();
    }

    /**
     * Benchmark exception creation
     */
    private function benchmarkExceptionCreation(): void
    {
        echo "Benchmarking Exception Creation... ";

        // Standard Exception
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            new \Exception('Test exception message');
            $times[] = hrtime(true) - $start;
        }

        $this->results['Exception Creation (Standard)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // RuntimeException
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            new \RuntimeException('Runtime error', 500);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Exception Creation (Runtime)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // With previous exception
        $previous = new \Exception('Previous');
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            new \Exception('Current', 0, $previous);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Exception Creation (With Previous)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark exception throw/catch
     */
    private function benchmarkExceptionThrowCatch(): void
    {
        echo "Benchmarking Exception Throw/Catch... ";

        // Simple throw/catch
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            try {
                throw new \Exception('Test');
            } catch (\Exception $e) {
                // Caught
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Throw/Catch (Simple)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Throw through function call
        $thrower = function() {
            throw new \Exception('From function');
        };

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            try {
                $thrower();
            } catch (\Exception $e) {
                // Caught
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Throw/Catch (Function)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Deep call stack
        $deepThrower = function($depth) use (&$deepThrower) {
            if ($depth <= 0) {
                throw new \Exception('From deep');
            }
            return $deepThrower($depth - 1);
        };

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            try {
                $deepThrower(10);
            } catch (\Exception $e) {
                // Caught
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Throw/Catch (10 Deep)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark exception chains
     */
    private function benchmarkExceptionChains(): void
    {
        echo "Benchmarking Exception Chains... ";

        $chainLengths = [1, 3, 5, 10];

        foreach ($chainLengths as $length) {
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                
                $exception = new \Exception("Level 0");
                for ($j = 1; $j < $length; $j++) {
                    $exception = new \Exception("Level {$j}", 0, $exception);
                }
                
                $times[] = hrtime(true) - $start;
            }

            $this->results["Exception Chain ({$length} deep)"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            ];
        }

        // Traversing chain
        $chain = new \Exception("Level 0");
        for ($j = 1; $j < 10; $j++) {
            $chain = new \Exception("Level {$j}", 0, $chain);
        }

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $current = $chain;
            $messages = [];
            while ($current) {
                $messages[] = $current->getMessage();
                $current = $current->getPrevious();
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Chain Traversal (10 deep)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark stack trace generation
     */
    private function benchmarkStackTraceGeneration(): void
    {
        echo "Benchmarking Stack Trace... ";

        // getTrace()
        $exception = new \Exception('Test');
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $exception->getTrace();
            $times[] = hrtime(true) - $start;
        }

        $this->results['getTrace()'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // getTraceAsString()
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $exception->getTraceAsString();
            $times[] = hrtime(true) - $start;
        }

        $this->results['getTraceAsString()'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // debug_backtrace()
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            debug_backtrace();
            $times[] = hrtime(true) - $start;
        }

        $this->results['debug_backtrace()'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // debug_backtrace with limits
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $times[] = hrtime(true) - $start;
        }

        $this->results['debug_backtrace (limited)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark error handler overhead
     */
    private function benchmarkErrorHandlerOverhead(): void
    {
        echo "Benchmarking Error Handler Overhead... ";

        // Without custom handler
        $oldHandler = set_error_handler(null);
        restore_error_handler();

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            @trigger_error('Test warning', E_USER_WARNING);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Error (No Handler)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // With simple handler
        set_error_handler(function($errno, $errstr) {
            return true;
        });

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            trigger_error('Test warning', E_USER_WARNING);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Error (Simple Handler)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // With logging handler
        $logBuffer = [];
        set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$logBuffer) {
            $logBuffer[] = [
                'level' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
                'time' => microtime(true),
            ];
            return true;
        });

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            trigger_error('Test warning', E_USER_WARNING);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Error (Logging Handler)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        restore_error_handler();

        echo "Done\n";
    }

    /**
     * Benchmark logging under load
     */
    private function benchmarkLoggingUnderLoad(): void
    {
        echo "Benchmarking Logging Under Load... ";

        $tempLog = sys_get_temp_dir() . '/bench_log_' . uniqid() . '.log';

        // File logging
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $message = date('Y-m-d H:i:s') . " [ERROR] Test error message {$i}\n";
            file_put_contents($tempLog, $message, FILE_APPEND);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Log Write (Append)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Buffered logging
        $buffer = [];
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $buffer[] = date('Y-m-d H:i:s') . " [ERROR] Test error message {$i}";
            if (count($buffer) >= 100) {
                file_put_contents($tempLog, implode("\n", $buffer) . "\n", FILE_APPEND);
                $buffer = [];
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Log Write (Buffered)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // error_log
        $times = [];
        for ($i = 0; $i < 100; $i++) { // Reduced iterations
            $start = hrtime(true);
            error_log("Test error message {$i}", 3, $tempLog);
            $times[] = hrtime(true) - $start;
        }

        $this->results['error_log()'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => 100 / (array_sum($times) / 1e9),
        ];

        unlink($tempLog);

        echo "Done\n";
    }

    /**
     * Benchmark recovery patterns
     */
    private function benchmarkRecoveryPatterns(): void
    {
        echo "Benchmarking Recovery Patterns... ";

        // Try-catch-finally
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $resource = null;
            try {
                $resource = 'acquired';
                if ($i % 2 === 0) {
                    throw new \Exception('Even iteration');
                }
            } catch (\Exception $e) {
                // Handle
            } finally {
                $resource = null; // Cleanup
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Try-Catch-Finally'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Multiple catch blocks
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            try {
                match ($i % 3) {
                    0 => throw new \InvalidArgumentException('Invalid'),
                    1 => throw new \RuntimeException('Runtime'),
                    2 => throw new \LogicException('Logic'),
                };
            } catch (\InvalidArgumentException $e) {
                // Handle invalid
            } catch (\RuntimeException $e) {
                // Handle runtime
            } catch (\LogicException $e) {
                // Handle logic
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Multiple Catch Blocks'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Retry pattern
        $times = [];
        $attempts = 3;
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            $attempt = 0;
            $success = false;
            while ($attempt < $attempts && !$success) {
                try {
                    if ($attempt < 2) {
                        throw new \Exception('Retry needed');
                    }
                    $success = true;
                } catch (\Exception $e) {
                    $attempt++;
                }
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Retry Pattern (3 attempts)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
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
                    if (str_contains($key, '_us')) {
                        printf("  %s: %.2f µs\n", $label, $value);
                    } elseif (str_contains($key, '_ns')) {
                        printf("  %s: %.0f ns\n", $label, $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", $label, number_format($value, 0));
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
        echo "  - Use limited debug_backtrace() in production\n";
        echo "  - Buffer log writes when possible\n";
        echo "  - Keep exception chains shallow\n";
        echo "  - Use simple error handlers\n";
        echo "\n";

        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    (new ErrorHandlingBenchmark())->run();
}
