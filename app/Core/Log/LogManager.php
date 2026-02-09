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
namespace App\Core\Log;

use App\Core\Contracts\Log\LoggerInterface;
use App\Core\Support\Str;
use Throwable;

/**
 * Log Manager
 *
 * Manages multiple log channels and routes logs appropriately.
 * Channels: exception, error, debug, info, system, security
 */
class LogManager implements LoggerInterface
{
    /** @var array<string, LogChannel> */
    protected array $channels = [];

    protected string $logDirectory;
    protected ?string $correlationId = null;
    protected array $globalContext = [];

    /** Map log levels to channels */
    protected const LEVEL_CHANNELS = [
        'emergency' => 'exception',
        'alert' => 'exception',
        'critical' => 'exception',
        'error' => 'error',
        'warning' => 'error',
        'notice' => 'info',
        'info' => 'info',
        'debug' => 'debug',
    ];

    /** Available channels */
    protected const CHANNELS = [
        'exception',  // Critical errors, exceptions, crashes
        'error',      // Errors and warnings
        'debug',      // Debug information, development logs
        'info',       // Informational messages, request logs
        'system',     // System events, startup, shutdown
        'security',   // Authentication, authorization, security events
        'query',      // Database queries (optional)
    ];

    public function __construct(string $logDirectory)
    {
        $this->logDirectory = rtrim($logDirectory, '/');
        $this->initializeChannels();
    }

    /**
     * Initialize all log channels
     */
    protected function initializeChannels(): void
    {
        foreach (self::CHANNELS as $channelName) {
            $this->channels[$channelName] = new LogChannel(
                $channelName,
                $this->logDirectory . '/' . $channelName . '.log'
            );
        }
    }

    /**
     * Get a specific channel
     */
    public function channel(string $name): LogChannel
    {
        if (! isset($this->channels[$name])) {
            // Create on demand for custom channels
            $this->channels[$name] = new LogChannel(
                $name,
                $this->logDirectory . '/' . $name . '.log'
            );
        }

        return $this->channels[$name];
    }

    // ==================== PSR-3 Log Levels ====================

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $channelName = self::LEVEL_CHANNELS[$level] ?? 'info';
        $this->writeToChannel($channelName, $level, $message, $context);
    }

    // ==================== Specialized Logging ====================

    /**
     * Log an exception with full details
     */
    public function exception(Throwable $e, array $context = []): void
    {
        $exceptionContext = $this->formatException($e);
        $context = array_merge($exceptionContext, $context);

        $this->writeToChannel('exception', 'error', $e->getMessage(), $context);

        // Also log to error channel for visibility
        $this->writeToChannel('error', 'error', 'Exception: ' . $e->getMessage(), [
            'exception_class' => get_class($e),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ]);
    }

    /**
     * Log a security event
     */
    public function security(string $event, array $context = []): void
    {
        $context['security_event'] = $event;
        $this->writeToChannel('security', 'warning', $event, $context);
    }

    /**
     * Log a system event
     */
    public function system(string $message, array $context = []): void
    {
        $this->writeToChannel('system', 'info', $message, $context);
    }

    /**
     * Log a database query
     */
    public function query(string $sql, array $bindings = [], float $time = 0): void
    {
        $this->writeToChannel('query', 'debug', $sql, [
            'bindings' => $bindings,
            'time_ms' => round($time * 1000, 2),
        ]);
    }

    // ==================== Core Logging ====================

    /**
     * Write to a specific channel
     */
    protected function writeToChannel(string $channelName, string $level, string $message, array $context): void
    {
        $channel = $this->channel($channelName);
        $entry = $this->formatEntry($level, $message, $context);
        $channel->write($entry);
    }

    /**
     * Format a log entry as JSON
     */
    protected function formatEntry(string $level, string $message, array $context): string
    {
        $entry = [
            'timestamp' => date('Y-m-d\TH:i:s.uP'),
            'level' => strtoupper($level),
            'correlation_id' => $this->getCorrelationId(),
            'message' => $message,
            'context' => array_merge($this->globalContext, $context),
            'memory_bytes' => memory_get_usage(true),
            'pid' => getmypid(),
        ];

        return json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    }

    /**
     * Format exception with full details for investigation
     */
    protected function formatException(Throwable $e): array
    {
        $trace = [];
        foreach ($e->getTrace() as $i => $frame) {
            $trace[] = [
                'index' => $i,
                'file' => ($frame['file'] ?? 'unknown') . ':' . ($frame['line'] ?? 0),
                'call' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? 'unknown'),
                'args_count' => count($frame['args'] ?? []),
            ];
        }

        $context = [
            'exception' => [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $trace,
            ],
        ];

        // Include previous exception if exists
        if ($previous = $e->getPrevious()) {
            $context['previous_exception'] = [
                'class' => get_class($previous),
                'message' => $previous->getMessage(),
                'file' => $previous->getFile(),
                'line' => $previous->getLine(),
            ];
        }

        return $context;
    }

    // ==================== Context Management ====================

    public function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
    }

    public function getCorrelationId(): string
    {
        if ($this->correlationId === null) {
            $this->correlationId = 'req_' . Str::randomHex(6);
        }

        return $this->correlationId;
    }

    public function setGlobalContext(array $context): void
    {
        $this->globalContext = $context;
    }

    public function addGlobalContext(string $key, mixed $value): void
    {
        $this->globalContext[$key] = $value;
    }

    // ==================== Maintenance ====================

    /**
     * Rotate all channels
     */
    public function rotateAll(): void
    {
        foreach ($this->channels as $channel) {
            $channel->rotate();
        }
    }

    /**
     * Rotate a specific channel
     */
    public function rotate(string $channelName): void
    {
        $this->channel($channelName)->rotate();
    }
}
