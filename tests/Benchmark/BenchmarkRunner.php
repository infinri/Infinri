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
 * Unified Benchmark Suite Runner
 * 
 * Runs all benchmarks and generates comprehensive reports.
 * 
 * Usage:
 *   php tests/Benchmark/BenchmarkRunner.php [options]
 * 
 * Options:
 *   --all           Run all benchmarks
 *   --quick         Run quick benchmarks only (reduced iterations)
 *   --suite=NAME    Run specific suite (autoloader, routing, etc.)
 *   --report=FORMAT Output format: text, json, html
 *   --output=FILE   Save report to file
 * 
 * Examples:
 *   php tests/Benchmark/BenchmarkRunner.php --all
 *   php tests/Benchmark/BenchmarkRunner.php --suite=routing --report=json
 *   php tests/Benchmark/BenchmarkRunner.php --quick --output=benchmark-results.json
 */
final class BenchmarkRunner
{
    private array $results = [];
    private array $options = [];
    private float $startTime;
    private int $startMemory;
    private string $reportFile = '';
    private string $timestamp = '';

    private const SUITES = [
        'autoloader' => AutoloaderBenchmark::class,
        'module' => ModuleScalabilityBenchmark::class,
        'middleware' => MiddlewareBenchmark::class,
        'routing' => RoutingBenchmark::class,
        'database' => DatabaseBenchmark::class,
        'reflection' => ReflectionBenchmark::class,
        'serialization' => SerializationBenchmark::class,
        'config' => ConfigBenchmark::class,
        'error' => ErrorHandlingBenchmark::class,
        'io' => IOStressBenchmark::class,
        'longrunning' => LongRunningBenchmark::class,
        'security' => SecurityBenchmark::class,
        'framework' => FrameworkBenchmark::class,
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'all' => false,
            'quick' => false,
            'suite' => null,
            'report' => 'text',
            'output' => null,
        ], $options);
    }

    /**
     * Run benchmarks
     */
    public function run(): int
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->timestamp = date('Y-m-d_H-i-s');
        
        // Setup report file
        $this->setupReportFile();

        $this->printHeader();

        $suitesToRun = $this->determineSuites();

        if (empty($suitesToRun)) {
            echo "No benchmarks selected. Use --all or --suite=NAME\n";
            return 1;
        }

        echo "Running " . count($suitesToRun) . " benchmark suite(s)...\n";
        echo "Report: {$this->reportFile}\n\n";

        foreach ($suitesToRun as $name => $class) {
            $this->runSuite($name, $class);
        }

        $this->generateReport();

        return 0;
    }

    /**
     * Determine which suites to run
     */
    private function determineSuites(): array
    {
        if ($this->options['all']) {
            return self::SUITES;
        }

        if ($this->options['suite']) {
            $suite = strtolower($this->options['suite']);
            if (isset(self::SUITES[$suite])) {
                return [$suite => self::SUITES[$suite]];
            }
            echo "Unknown suite: {$suite}\n";
            echo "Available suites: " . implode(', ', array_keys(self::SUITES)) . "\n";
            return [];
        }

        // Default: run quick benchmarks
        return [
            'framework' => FrameworkBenchmark::class,
        ];
    }

    /**
     * Setup the report file with header
     */
    private function setupReportFile(): void
    {
        $resultsDir = dirname(__DIR__) . '/results';
        if (!is_dir($resultsDir)) {
            mkdir($resultsDir, 0755, true);
        }
        
        $this->reportFile = $resultsDir . '/benchmark-report-' . $this->timestamp . '.md';
        
        $header = <<<MD
# Infinri Framework Benchmark Report

**Generated:** {$this->timestamp}  
**PHP Version:** %s  
**Quick Mode:** %s  

---

MD;
        
        file_put_contents($this->reportFile, sprintf(
            $header,
            PHP_VERSION,
            $this->options['quick'] ? 'Yes' : 'No'
        ));
    }

    /**
     * Write a suite result to the report file
     */
    private function appendToReport(string $name, array $result): void
    {
        $content = "\n## {$name}\n\n";
        $content .= "**Status:** {$result['status']}  \n";
        
        if (isset($result['duration_sec'])) {
            $content .= sprintf("**Duration:** %.2f seconds  \n", $result['duration_sec']);
        }
        if (isset($result['memory_mb'])) {
            $content .= sprintf("**Memory:** %.2f MB  \n", $result['memory_mb']);
        }
        
        if ($result['status'] === 'completed' && !empty($result['output'])) {
            $content .= "\n### Detailed Results\n\n";
            $content .= "```\n";
            $content .= $result['output'];
            $content .= "```\n";
        } elseif ($result['status'] === 'failed') {
            $content .= "\n### Error\n\n";
            $content .= "```\n";
            $content .= $result['error'] ?? 'Unknown error';
            $content .= "\n```\n";
        } elseif ($result['status'] === 'skipped') {
            $content .= "**Reason:** " . ($result['reason'] ?? 'Unknown') . "  \n";
        }
        
        $content .= "\n---\n";
        
        file_put_contents($this->reportFile, $content, FILE_APPEND);
    }

    /**
     * Run a benchmark suite
     */
    private function runSuite(string $name, string $class): void
    {
        echo str_repeat('=', 50) . "\n";
        echo "  Running: {$name}\n";
        echo str_repeat('=', 50) . "\n";

        if (!class_exists($class)) {
            echo "  Skipped: Class not found\n\n";
            $this->results[$name] = ['status' => 'skipped', 'reason' => 'Class not found'];
            $this->appendToReport($name, $this->results[$name]);
            return;
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            // Capture output
            ob_start();
            
            $benchmark = new $class();
            
            // Handle different method signatures
            if (method_exists($benchmark, 'run')) {
                if ($name === 'longrunning' && $this->options['quick']) {
                    $benchmark->run(1000); // Reduced iterations for quick mode
                } else {
                    $benchmark->run();
                }
            }
            
            $output = ob_get_clean();

            $this->results[$name] = [
                'status' => 'completed',
                'duration_sec' => microtime(true) - $startTime,
                'memory_mb' => (memory_get_usage(true) - $startMemory) / 1024 / 1024,
                'output' => $output,
            ];

            echo "  Completed in " . number_format(microtime(true) - $startTime, 2) . "s\n\n";
            
            // Write to report file immediately
            $this->appendToReport($name, $this->results[$name]);

        } catch (\Throwable $e) {
            ob_end_clean();
            
            $this->results[$name] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];

            echo "  Failed: " . $e->getMessage() . "\n\n";
            
            // Write failure to report file immediately
            $this->appendToReport($name, $this->results[$name]);
        }
    }

    /**
     * Generate report
     */
    private function generateReport(): void
    {
        $totalDuration = microtime(true) - $this->startTime;
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

        $report = [
            'meta' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'total_duration_sec' => $totalDuration,
                'peak_memory_mb' => $peakMemory,
                'suites_run' => count($this->results),
            ],
            'results' => $this->results,
        ];

        // Append summary to markdown report file
        $this->appendSummaryToReport($report);

        switch ($this->options['report']) {
            case 'json':
                $output = json_encode($report, JSON_PRETTY_PRINT);
                break;

            case 'html':
                $output = $this->generateHtmlReport($report);
                break;

            default:
                $output = $this->generateTextReport($report);
        }

        if ($this->options['output']) {
            file_put_contents($this->options['output'], $output);
            echo "Report saved to: " . $this->options['output'] . "\n";
        } else {
            echo $output;
        }
        
        echo "\nDetailed report saved to: {$this->reportFile}\n";
    }

    /**
     * Append final summary to the markdown report
     */
    private function appendSummaryToReport(array $report): void
    {
        $completed = count(array_filter($report['results'], fn($r) => $r['status'] === 'completed'));
        $failed = count(array_filter($report['results'], fn($r) => $r['status'] === 'failed'));
        $skipped = count(array_filter($report['results'], fn($r) => $r['status'] === 'skipped'));

        $summary = <<<MD

# Summary

| Metric | Value |
|--------|-------|
| **Total Duration** | {$this->formatDuration($report['meta']['total_duration_sec'])} |
| **Peak Memory** | {$this->formatMemory($report['meta']['peak_memory_mb'])} |
| **Suites Run** | {$report['meta']['suites_run']} |
| **Completed** | {$completed} |
| **Failed** | {$failed} |
| **Skipped** | {$skipped} |

## Results Overview

| Suite | Status | Duration | Memory |
|-------|--------|----------|--------|
MD;

        foreach ($report['results'] as $name => $result) {
            $status = $result['status'];
            $statusEmoji = match($status) {
                'completed' => '✅',
                'failed' => '❌',
                'skipped' => '⏭️',
                default => '❓',
            };
            $duration = isset($result['duration_sec']) ? $this->formatDuration($result['duration_sec']) : 'N/A';
            $memory = isset($result['memory_mb']) ? $this->formatMemory($result['memory_mb']) : 'N/A';
            
            $summary .= "\n| **{$name}** | {$statusEmoji} {$status} | {$duration} | {$memory} |";
        }

        $summary .= "\n\n---\n\n";
        $summary .= "_Report generated by Infinri Benchmark Suite_\n";

        file_put_contents($this->reportFile, $summary, FILE_APPEND);
    }

    /**
     * Generate text report
     */
    private function generateTextReport(array $report): string
    {
        $output = "\n";
        $output .= str_repeat('=', 60) . "\n";
        $output .= "  BENCHMARK SUMMARY\n";
        $output .= str_repeat('=', 60) . "\n\n";

        $output .= sprintf("  Timestamp: %s\n", $report['meta']['timestamp']);
        $output .= sprintf("  PHP Version: %s\n", $report['meta']['php_version']);
        $output .= sprintf("  Total Duration: %.2f seconds\n", $report['meta']['total_duration_sec']);
        $output .= sprintf("  Peak Memory: %.2f MB\n", $report['meta']['peak_memory_mb']);
        $output .= sprintf("  Suites Run: %d\n\n", $report['meta']['suites_run']);

        $output .= str_repeat('-', 60) . "\n";
        $output .= sprintf("  %-20s %-12s %-12s %-12s\n", "Suite", "Status", "Duration", "Memory");
        $output .= str_repeat('-', 60) . "\n";

        foreach ($report['results'] as $name => $result) {
            $status = $result['status'];
            $duration = isset($result['duration_sec']) ? sprintf("%.2fs", $result['duration_sec']) : 'N/A';
            $memory = isset($result['memory_mb']) ? sprintf("%.2fMB", $result['memory_mb']) : 'N/A';
            
            $output .= sprintf("  %-20s %-12s %-12s %-12s\n", $name, $status, $duration, $memory);
        }

        $output .= str_repeat('-', 60) . "\n\n";

        // Summary statistics
        $completed = count(array_filter($report['results'], fn($r) => $r['status'] === 'completed'));
        $failed = count(array_filter($report['results'], fn($r) => $r['status'] === 'failed'));
        $skipped = count(array_filter($report['results'], fn($r) => $r['status'] === 'skipped'));

        $output .= "  Results: {$completed} completed, {$failed} failed, {$skipped} skipped\n\n";

        return $output;
    }

    /**
     * Generate HTML report
     */
    private function generateHtmlReport(array $report): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infinri Benchmark Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        h1 { color: #2c3e50; margin-bottom: 2rem; }
        .meta { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .meta-item { padding: 0.5rem; }
        .meta-label { font-size: 0.875rem; color: #666; }
        .meta-value { font-size: 1.25rem; font-weight: 600; color: #2c3e50; }
        table { width: 100%; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th { background: #2c3e50; color: white; padding: 1rem; text-align: left; }
        td { padding: 1rem; border-bottom: 1px solid #eee; }
        tr:last-child td { border-bottom: none; }
        .status-completed { color: #27ae60; font-weight: 600; }
        .status-failed { color: #e74c3c; font-weight: 600; }
        .status-skipped { color: #f39c12; font-weight: 600; }
        .output { margin-top: 2rem; }
        .output-title { cursor: pointer; background: #ecf0f1; padding: 1rem; border-radius: 4px; margin-bottom: 0.5rem; }
        .output-content { background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; font-family: monospace; font-size: 0.875rem; white-space: pre-wrap; display: none; }
        .output-content.show { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Infinri Benchmark Report</h1>
        
        <div class="meta">
            <div class="meta-grid">
                <div class="meta-item">
                    <div class="meta-label">Timestamp</div>
                    <div class="meta-value">{$report['meta']['timestamp']}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">PHP Version</div>
                    <div class="meta-value">{$report['meta']['php_version']}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Total Duration</div>
                    <div class="meta-value">{$this->formatDuration($report['meta']['total_duration_sec'])}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Peak Memory</div>
                    <div class="meta-value">{$this->formatMemory($report['meta']['peak_memory_mb'])}</div>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Suite</th>
                    <th>Status</th>
                    <th>Duration</th>
                    <th>Memory</th>
                </tr>
            </thead>
            <tbody>
HTML;

        foreach ($report['results'] as $name => $result) {
            $status = $result['status'];
            $statusClass = "status-{$status}";
            $duration = isset($result['duration_sec']) ? $this->formatDuration($result['duration_sec']) : 'N/A';
            $memory = isset($result['memory_mb']) ? $this->formatMemory($result['memory_mb']) : 'N/A';

            $html .= <<<HTML
                <tr>
                    <td>{$name}</td>
                    <td class="{$statusClass}">{$status}</td>
                    <td>{$duration}</td>
                    <td>{$memory}</td>
                </tr>
HTML;
        }

        $html .= <<<HTML
            </tbody>
        </table>

        <div class="output">
            <h2 style="margin: 2rem 0 1rem;">Detailed Output</h2>
HTML;

        foreach ($report['results'] as $name => $result) {
            if (isset($result['output']) && !empty($result['output'])) {
                $escapedOutput = htmlspecialchars($result['output']);
                $html .= <<<HTML
            <div class="output-title" onclick="this.nextElementSibling.classList.toggle('show')">
                {$name} (click to expand)
            </div>
            <div class="output-content">{$escapedOutput}</div>
HTML;
            }
        }

        $html .= <<<HTML
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Format duration
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return number_format($seconds * 1000, 0) . 'ms';
        }
        return number_format($seconds, 2) . 's';
    }

    /**
     * Format memory
     */
    private function formatMemory(float $mb): string
    {
        return number_format($mb, 2) . ' MB';
    }

    /**
     * Print header
     */
    private function printHeader(): void
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║         Infinri Framework Benchmark Suite              ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Quick Mode: " . ($this->options['quick'] ? 'Yes' : 'No') . "\n";
        echo "\n";
    }

    /**
     * Parse command line options
     */
    public static function parseOptions(array $argv): array
    {
        $options = [
            'all' => false,
            'quick' => false,
            'suite' => null,
            'report' => 'text',
            'output' => null,
        ];

        foreach ($argv as $arg) {
            if ($arg === '--all') {
                $options['all'] = true;
            } elseif ($arg === '--quick') {
                $options['quick'] = true;
            } elseif (str_starts_with($arg, '--suite=')) {
                $options['suite'] = substr($arg, 8);
            } elseif (str_starts_with($arg, '--report=')) {
                $options['report'] = substr($arg, 9);
            } elseif (str_starts_with($arg, '--output=')) {
                $options['output'] = substr($arg, 9);
            } elseif ($arg === '--help' || $arg === '-h') {
                self::printHelp();
                exit(0);
            }
        }

        return $options;
    }

    /**
     * Print help
     */
    public static function printHelp(): void
    {
        echo <<<HELP

Infinri Framework Benchmark Suite

Usage:
  php tests/Benchmark/BenchmarkRunner.php [options]

Options:
  --all             Run all benchmark suites
  --quick           Run with reduced iterations (faster)
  --suite=NAME      Run specific suite only
  --report=FORMAT   Output format: text, json, html
  --output=FILE     Save report to file
  --help, -h        Show this help message

Available Suites:
  autoloader        Autoloader performance
  module            Module scalability
  middleware        Middleware pipeline
  routing           Routing precision
  database          Database & ORM
  reflection        Reflection costs
  serialization     Serialization speed
  config            Configuration parsing
  error             Error handling
  io                I/O stress
  longrunning       Memory fragmentation
  security          Serialization safety
  framework         Core framework (default)

Examples:
  php tests/Benchmark/BenchmarkRunner.php --all
  php tests/Benchmark/BenchmarkRunner.php --suite=routing
  php tests/Benchmark/BenchmarkRunner.php --all --quick --report=html --output=report.html

HELP;
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__, 2));
    }
    
    $options = BenchmarkRunner::parseOptions($argv);
    $runner = new BenchmarkRunner($options);
    exit($runner->run());
}
