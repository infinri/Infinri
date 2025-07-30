<?php

declare(strict_types=1);

namespace Tests;

use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Infinri\SwarmFramework\Interfaces\ValidationResult;

/**
 * Test SwarmUnit implementation for unit testing
 */
final class TestSwarmUnit implements SwarmUnitInterface
{
    private bool $initialized = false;
    private UnitIdentity $identity;
    private array $mockServices;
    private int $executionCount = 0;
    private int $totalExecutions = 5;
    
    public function __construct(
        $entropy = null,
        $ethics = null,
        $throttling = null,
        $temporal = null,
        $acl = null,
        $tracer = null
    ) {
        $this->identity = new UnitIdentity(
            'test-unit',
            '1.0.0',
            'sha256:a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', // SHA-256 hash of 'test'
            ['test', 'unit']
        );
        
        $this->mockServices = [
            'entropy' => $entropy,
            'ethics' => $ethics,
            'throttling' => $throttling,
            'temporal' => $temporal,
            'acl' => $acl,
            'tracer' => $tracer
        ];
    }
    
    public function triggerCondition(SemanticMeshInterface $mesh): bool
    {
        return true; // Always trigger for testing
    }
    
    public function act(SemanticMeshInterface $mesh): void
    {
        // Test action logic
        $this->executionCount++;
        $this->totalExecutions++;
        $mesh->set('test.unit.executed', true);
        $mesh->set('test.unit.execution_count', $this->executionCount);
    }
    
    public function getIdentity(): UnitIdentity
    {
        return $this->identity;
    }
    
    public function getPriority(): int
    {
        return 5; // Medium priority
    }
    
    public function getCooldown(): int
    {
        return 1000; // 1 second cooldown
    }
    
    public function getMutexGroup(): ?string
    {
        return 'test-group';
    }
    
    public function getResourceRequirements(): array
    {
        return ['memory' => '64MB', 'cpu' => '0.1'];
    }
    
    public function getTimeout(): int
    {
        return 30000; // 30 seconds
    }
    
    public function isHealthy(): bool
    {
        return true; // Always healthy for testing
    }
    
    public function getHealthMetrics(): array
    {
        return [
            'status' => 'healthy',
            'last_execution' => microtime(true),
            'execution_count' => $this->executionCount,
            'total_executions' => $this->totalExecutions,
            'efficiency_score' => 0.85,
            'error_rate' => 0.02
        ];
    }
    
    public function initialize(SemanticMeshInterface $mesh): void
    {
        $mesh->set('test.unit.initialized', true);
        $this->initialized = true;
    }
    
    public function shutdown(SemanticMeshInterface $mesh): void
    {
        $mesh->set('test.unit.shutdown', true);
    }
    
    public function validate(): ValidationResult
    {
        return ValidationResult::success('Test unit validation passed');
    }

    /**
     * Test method for initialization status
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Test method for ethical validation
     */
    public function testEthicalValidation($content = null): bool
    {
        if ($content && isset($content['text']) && strpos($content['text'], 'harmful') !== false) {
            return false; // Simulate unethical content detection
        }
        return true; // Simulate successful ethical validation
    }

    /**
     * Test method for strict ethical validation
     */
    public function testEthicalValidationStrict($content = null): bool
    {
        if ($content && isset($content['text']) && strpos($content['text'], 'harmful') !== false) {
            throw new \Infinri\SwarmFramework\Exceptions\EthicalValidationException(
                0.2, // ethical score
                ['harmful_content' => true, 'content' => $content], // violations
                'test-unit' // unit ID
            );
        }
        return true;
    }

    /**
     * Test method for mesh access
     */
    public function testMeshAccess($mesh = null, $permissions = null): bool
    {
        if ($permissions && (in_array('admin', $permissions) || in_array('delete', $permissions))) {
            throw new \Infinri\SwarmFramework\Exceptions\MeshACLViolationException(
                'test.mesh.key', // mesh key
                'admin', // operation
                ['admin' => false, 'delete' => false], // unit capabilities
                'test-unit' // unit ID
            );
        }
        return true; // Simulate successful mesh access
    }

    /**
     * Test method for staleness check
     */
    public function testStaleness($data = null, $threshold = null): bool
    {
        if ($data && isset($data['timestamp']) && $threshold) {
            $age = time() - $data['timestamp'];
            return $age > $threshold; // Return true if data is stale (older than threshold)
        }
        return false; // Simulate not stale by default
    }

    /**
     * Test method for business hours check
     */
    public function testBusinessHours(): bool
    {
        return true; // Simulate business hours
    }

    /**
     * Test method for temporal safety check
     */
    public function testTemporalSafety(): bool
    {
        return true; // Simulate temporal safety compliance
    }

    /**
     * Test method for recording health metrics
     */
    public function testRecordHealthMetric(): bool
    {
        return true; // Simulate successful health metric recording
    }

    /**
     * Test method for getting efficacy score
     */
    public function testGetEfficacyScore(): float
    {
        return 0.85; // Simulate good efficacy score
    }

    /**
     * Test method for entropy acceptability check
     */
    public function testIsEntropyAcceptable(): bool
    {
        return true; // Simulate acceptable entropy
    }

    /**
     * Test method for entropy health status
     */
    public function testGetEntropyHealthStatus(): array
    {
        return [
            'status' => 'healthy', 
            'entropy_level' => 0.3,
            'current_entropy' => 0.25
        ]; // Simulate healthy entropy status
    }
    
    // Helper method to access mock services for testing
    public function getMockService(string $name)
    {
        return $this->mockServices[$name] ?? null;
    }
}
