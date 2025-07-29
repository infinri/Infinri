<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Tracing;

use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;

/**
 * Trace Span - Individual Execution Tracking Unit
 * 
 * Represents a single unit execution span with timing, mutations,
 * and causality information for detailed behavioral analysis.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
final class TraceSpan
{
    private string $spanId;
    private UnitIdentity $identity;
    private float $startTime;
    private ?float $endTime = null;
    private array $meshMutations = [];
    private array $causalityChain = [];
    private array $metadata = [];

    public function __construct(string $spanId, UnitIdentity $identity, float $startTime)
    {
        $this->spanId = $spanId;
        $this->identity = $identity;
        $this->startTime = $startTime;
    }

    public function finish(): void
    {
        $this->endTime = PerformanceTimer::now();
    }

    public function addMeshMutations(array $mutations): void
    {
        $this->meshMutations = array_merge($this->meshMutations, $mutations);
    }

    public function setCausalityChain(array $chain): void
    {
        $this->causalityChain = $chain;
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getSpanId(): string
    {
        return $this->spanId;
    }

    public function getIdentity(): UnitIdentity
    {
        return $this->identity;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getEndTime(): ?float
    {
        return $this->endTime;
    }

    public function getDuration(): ?float
    {
        return $this->endTime ? ($this->endTime - $this->startTime) : null;
    }

    public function getMeshMutations(): array
    {
        return $this->meshMutations;
    }

    public function getCausalityChain(): array
    {
        return $this->causalityChain;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isFinished(): bool
    {
        return $this->endTime !== null;
    }

    public function toArray(): array
    {
        return [
            'span_id' => $this->spanId,
            'unit_id' => $this->identity->id,
            'unit_version' => $this->identity->version,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'duration' => $this->getDuration(),
            'mesh_mutations' => $this->meshMutations,
            'causality_chain' => $this->causalityChain,
            'metadata' => $this->metadata
        ];
    }
}
