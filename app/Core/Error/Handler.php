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
namespace App\Core\Error;

use Throwable;

/**
 * Error Handler
 * 
 * Central exception handling for the application.
 * Context-agnostic - works for HTTP, CLI, Queue, Events.
 * 
 * SEI Pattern: Isolated fault handling module.
 */
class Handler
{
    /**
     * Exception reporter
     */
    protected Reporter $reporter;

    /**
     * Whether debug mode is enabled
     */
    protected bool $debug;

    /**
     * Exceptions that should not be reported
     */
    protected array $dontReport = [];

    /**
     * Custom exception handlers
     */
    protected array $handlers = [];

    public function __construct(?Reporter $reporter = null, bool $debug = false)
    {
        $this->reporter = $reporter ?? new Reporter();
        $this->debug = $debug;
    }

    /**
     * Handle an exception
     */
    public function handle(Throwable $e, array $context = []): void
    {
        // Report the exception
        $this->report($e, $context);

        // Run custom handlers
        foreach ($this->handlers as $type => $handler) {
            if ($e instanceof $type) {
                $handler($e, $context);
                return;
            }
        }
    }

    /**
     * Report an exception
     */
    public function report(Throwable $e, array $context = []): void
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        $this->reporter->report($e, $context);
    }

    /**
     * Determine if the exception should be reported
     */
    public function shouldReport(Throwable $e): bool
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register exception types that should not be reported
     */
    public function dontReport(array $types): static
    {
        $this->dontReport = array_merge($this->dontReport, $types);
        return $this;
    }

    /**
     * Register a custom handler for an exception type
     */
    public function register(string $exceptionType, callable $handler): static
    {
        $this->handlers[$exceptionType] = $handler;
        return $this;
    }

    /**
     * Get the reporter instance
     */
    public function getReporter(): Reporter
    {
        return $this->reporter;
    }

    /**
     * Set debug mode
     */
    public function setDebug(bool $debug): static
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Check if in debug mode
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Convert exception to array (for logging/JSON)
     */
    public function toArray(Throwable $e, bool $includeTrace = false): array
    {
        $data = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        if ($includeTrace) {
            $data['trace'] = $this->formatTrace($e);
        }

        if ($e->getPrevious()) {
            $data['previous'] = $this->toArray($e->getPrevious(), $includeTrace);
        }

        return $data;
    }

    /**
     * Format stack trace
     */
    protected function formatTrace(Throwable $e): array
    {
        $trace = [];
        foreach ($e->getTrace() as $i => $frame) {
            $trace[] = sprintf(
                '#%d %s:%d %s%s%s()',
                $i,
                $frame['file'] ?? 'unknown',
                $frame['line'] ?? 0,
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                $frame['function'] ?? 'unknown'
            );
        }
        return $trace;
    }
}
