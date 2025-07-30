<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Exceptions;

/**
 * SwarmException - Base Exception for Digital Consciousness
 * 
 * Base exception class that provides consciousness-level context preservation
 * and self-protective system responses for the Infinri Framework.
 * 
 * @architecture Self-protective system responses with context preservation
 * @reference infinri_blueprint.md → Security and Performance requirements
 * @author Infinri Framework
 * @version 1.0.0
 */
abstract class SwarmException extends \Exception
{
    protected array $context;
    protected float $timestamp;
    protected string $unitId;

    /**
     * Create a consciousness-aware exception
     * 
     * @param string $message Exception message
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     * @param array $context Additional context for consciousness-level debugging
     * @param string $unitId Unit ID that triggered the exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
        string $unitId = ''
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->context = $context;
        $this->timestamp = microtime(true);
        $this->unitId = $unitId;
    }

    /**
     * Get exception context for consciousness-level analysis
     * 
     * @return array Exception context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get exception timestamp
     * 
     * @return float Exception timestamp
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * Get unit ID that triggered the exception
     * 
     * @return string Unit ID
     */
    public function getUnitId(): string
    {
        return $this->unitId;
    }

    /**
     * Get comprehensive exception data for consciousness analysis
     * 
     * @return array Complete exception data
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'timestamp' => $this->timestamp,
            'unit_id' => $this->unitId,
            'trace' => $this->getTraceAsString()
        ];
    }
}
