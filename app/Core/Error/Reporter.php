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
namespace App\Core\Error;

use App\Core\Contracts\Log\LoggerInterface;
use Throwable;

/**
 * Exception Reporter
 *
 * Reports exceptions to various channels (log, external services).
 * Extensible via custom reporters.
 */
class Reporter
{
    /**
     * Logger instance
     */
    protected ?LoggerInterface $logger;

    /**
     * Custom reporters
     */
    protected array $reporters = [];

    /**
     * Context to include with all reports
     */
    protected array $globalContext = [];

    /**
     * Whether to suppress fallback error_log output (useful in tests)
     */
    protected bool $suppressFallback = false;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Report an exception
     */
    public function report(Throwable $e, array $context = []): void
    {
        $context = array_merge($this->globalContext, $context);

        // Run custom reporters first
        foreach ($this->reporters as $reporter) {
            try {
                if ($reporter($e, $context) === false) {
                    return; // Reporter handled it, stop
                }
            } catch (Throwable $reporterError) {
                // Don't let reporter errors break the chain
                $this->fallbackLog($reporterError, 'Reporter failed');
            }
        }

        // Log to default logger
        $this->logException($e, $context);
    }

    /**
     * Log exception to the logger
     */
    protected function logException(Throwable $e, array $context = []): void
    {
        if ($this->logger === null) {
            $this->fallbackLog($e);

            return;
        }

        try {
            $this->logger->error($e->getMessage(), array_merge([
                'exception' => get_class($e),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->formatTrace($e),
            ], $context));
        } catch (Throwable $logError) {
            $this->fallbackLog($e);
            $this->fallbackLog($logError, 'Logger failed');
        }
    }

    /**
     * Fallback logging to error_log
     */
    protected function fallbackLog(Throwable $e, string $prefix = ''): void
    {
        if ($this->suppressFallback) {
            return;
        }

        $message = sprintf(
            '%s[%s] %s in %s:%d',
            $prefix !== '' ? "[$prefix] " : '',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        error_log($message);
    }

    /**
     * Format stack trace for logging
     */
    protected function formatTrace(Throwable $e): array
    {
        return Handler::formatTraceArray($e);
    }

    /**
     * Add a custom reporter
     *
     * Reporter receives (Throwable $e, array $context)
     * Return false to stop further reporting
     */
    public function addReporter(callable $reporter): static
    {
        $this->reporters[] = $reporter;

        return $this;
    }

    /**
     * Add global context to all reports
     */
    public function addContext(string $key, mixed $value): static
    {
        $this->globalContext[$key] = $value;

        return $this;
    }

    /**
     * Set the logger instance
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get the logger instance
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Clear all custom reporters
     */
    public function clearReporters(): static
    {
        $this->reporters = [];

        return $this;
    }

    /**
     * Suppress fallback error_log output (useful in tests)
     */
    public function suppressFallback(bool $suppress = true): static
    {
        $this->suppressFallback = $suppress;

        return $this;
    }
}
