<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Psr\Log\LoggerInterface;

/**
 * Logger Trait - Reusable logging functionality
 * 
 * Eliminates code duplication for common logging patterns
 * across all framework components.
 */
trait LoggerTrait
{
    protected LoggerInterface $logger;

    /**
     * Log operation start
     */
    protected function logOperationStart(string $operation, array $context = []): void
    {
        $this->logger->debug("Starting {$operation}", array_merge([
            'operation' => $operation,
            'timestamp' => PerformanceTimer::now()
        ], $context));
    }

    /**
     * Log operation completion
     */
    protected function logOperationComplete(string $operation, array $context = []): void
    {
        $this->logger->info("Completed {$operation}", array_merge([
            'operation' => $operation,
            'timestamp' => PerformanceTimer::now()
        ], $context));
    }

    /**
     * Log operation failure
     */
    protected function logOperationFailure(string $operation, array $context = []): void
    {
        $this->logger->error("Failed {$operation}", array_merge([
            'operation' => $operation,
            'timestamp' => PerformanceTimer::now()
        ], $context));
    }

    /**
     * Log performance warning for slow operations
     */
    protected function logPerformanceWarning(string $operation, float $duration, float $threshold, array $context = []): void
    {
        if ($duration > $threshold) {
            $this->logger->warning("Slow operation detected: {$operation}", array_merge([
                'operation' => $operation,
                'duration_ms' => round($duration * 1000, 2),
                'threshold_ms' => round($threshold * 1000, 2),
                'timestamp' => PerformanceTimer::now()
            ], $context));
        }
    }

    /**
     * Log validation errors in standardized format
     */
    protected function logValidationErrors(string $context, array $errors): void
    {
        $this->logger->warning("Validation failed in {$context}", [
            'context' => $context,
            'error_count' => count($errors),
            'errors' => $errors,
            'timestamp' => PerformanceTimer::now()
        ]);
    }

    /**
     * Log security events with appropriate severity
     */
    protected function logSecurityEvent(string $event, string $level = 'warning', array $context = []): void
    {
        $this->logger->log($level, "Security event: {$event}", $this->buildSecurityContext($event, $context));
    }

    /**
     * Build standardized operation context
     */
    protected function buildOperationContext(string $operation, array $additionalContext = []): array
    {
        return array_merge([
            'operation' => $operation,
            'timestamp' => PerformanceTimer::now(),
            'component' => static::class
        ], $additionalContext);
    }

    /**
     * Build performance context with duration
     */
    protected function buildPerformanceContext(string $operation, float $startTime, array $additionalContext = []): array
    {
        return PerformanceTimer::createContext($operation, $startTime, array_merge([
            'component' => static::class
        ], $additionalContext));
    }

    /**
     * Build error context with exception details
     */
    protected function buildErrorContext(string $operation, \Throwable $error, array $additionalContext = []): array
    {
        return array_merge([
            'operation' => $operation,
            'error' => $error->getMessage(),
            'error_type' => get_class($error),
            'timestamp' => PerformanceTimer::now(),
            'component' => static::class
        ], $additionalContext);
    }

    /**
     * Build security context
     */
    protected function buildSecurityContext(string $event, array $additionalContext = []): array
    {
        return array_merge([
            'event_type' => 'security',
            'event' => $event,
            'timestamp' => PerformanceTimer::now(),
            'component' => static::class
        ], $additionalContext);
    }

    /**
     * Build validation context
     */
    protected function buildValidationContext(array $errors, array $warnings = [], array $additionalContext = []): array
    {
        return array_merge([
            'validation_errors' => count($errors),
            'validation_warnings' => count($warnings),
            'errors' => $errors,
            'warnings' => $warnings,
            'timestamp' => PerformanceTimer::now(),
            'component' => static::class
        ], $additionalContext);
    }

    /**
     * Log operation with automatic performance timing
     */
    protected function logTimedOperation(string $operation, callable $callback, array $context = []): mixed
    {
        $measurement = PerformanceTimer::measure($callback, $operation);
        
        if ($measurement['success']) {
            $this->logOperationComplete($operation, $this->buildPerformanceContext(
                $operation, 
                PerformanceTimer::now() - $measurement['duration'], 
                $context
            ));
        } else {
            $this->logOperationFailure($operation, array_merge(
                $this->buildPerformanceContext(
                    $operation, 
                    PerformanceTimer::now() - $measurement['duration'], 
                    $context
                ),
                ['error' => $measurement['error']]
            ));
        }
        
        return $measurement['result'];
    }
}
