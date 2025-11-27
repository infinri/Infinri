<?php

declare(strict_types=1);

namespace App\Core\Container;

use Exception;

/**
 * Binding Resolution Exception
 * 
 * Thrown when the container cannot resolve a dependency.
 * Automatically logs detailed error information.
 */
class BindingResolutionException extends Exception
{
    protected string $abstract;
    protected ?string $reason;

    public function __construct(string $message, string $abstract = '', ?string $reason = null)
    {
        parent::__construct($message);
        $this->abstract = $abstract;
        $this->reason = $reason;
        
        $this->logException();
    }

    /**
     * Create exception for unresolvable binding
     */
    public static function unresolvable(string $abstract, ?string $message = null): static
    {
        $reason = $message;
        $message = $message ?? "Unable to resolve binding for [{$abstract}]";
        
        return new static($message, $abstract, $reason);
    }

    /**
     * Create exception for circular dependency
     */
    public static function circularDependency(string $abstract): static
    {
        return new static(
            "Circular dependency detected while resolving [{$abstract}]",
            $abstract,
            'circular_dependency'
        );
    }

    /**
     * Create exception for uninstantiable type
     */
    public static function uninstantiable(string $abstract, ?string $reason = null): static
    {
        $message = "Target [{$abstract}] is not instantiable";
        
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        return new static($message, $abstract, $reason);
    }

    /**
     * Get the abstract type that failed to resolve
     */
    public function getAbstract(): string
    {
        return $this->abstract;
    }

    /**
     * Get the reason for failure
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Log the exception with full details
     */
    protected function logException(): void
    {
        if (!function_exists('logger')) {
            return;
        }

        try {
            logger()->error('Container binding resolution failed', [
                'abstract' => $this->abstract,
                'reason' => $this->reason,
                'message' => $this->getMessage(),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->formatTrace(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail if logging fails
        }
    }

    /**
     * Format stack trace for logging
     */
    protected function formatTrace(): array
    {
        $trace = [];
        foreach ($this->getTrace() as $i => $frame) {
            if ($i > 10) break; // Limit trace depth
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
