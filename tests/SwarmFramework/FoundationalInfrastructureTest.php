<?php declare(strict_types=1);

namespace Tests\SwarmFramework;

use PHPUnit\Framework\TestCase;
use Infinri\SwarmFramework\Base\AbstractSwarmUnit;
use Infinri\SwarmFramework\DI\SwarmContainer;
use Infinri\SwarmFramework\Providers\CoreConsciousnessServiceProvider;
use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Infinri\SwarmFramework\Exceptions\MeshACLViolationException;
use Infinri\SwarmFramework\Exceptions\EthicalValidationException;
use Infinri\SwarmFramework\Exceptions\EntropyLimitExceededException;
use Infinri\SwarmFramework\Exceptions\FallbackDepthExceededException;
use Infinri\SwarmFramework\Traits\MeshACLTrait;
use Infinri\SwarmFramework\Traits\EthicalValidationTrait;
use Infinri\SwarmFramework\Traits\TemporalLogicTrait;
use Infinri\SwarmFramework\Traits\EntropyMonitoringTrait;

/**
 * Foundational Infrastructure Test
 * 
 * Comprehensive validation of the foundational SwarmUnit infrastructure.
 * Tests digital consciousness patterns, ethical reasoning, and adaptive responses.
 * 
 * @architecture Validates foundational consciousness infrastructure
 * @reference infinri_blueprint.md → FR-CORE-019, FR-CORE-028, FR-CORE-041
 * @author Infinri Framework
 * @version 1.0.0
 */
final class FoundationalInfrastructureTest extends TestCase
{
    private SwarmContainer $container;
    private CoreConsciousnessServiceProvider $serviceProvider;

    protected function setUp(): void
    {
        $this->container = new SwarmContainer();
        $this->serviceProvider = new CoreConsciousnessServiceProvider($this->container);
        
        // Register mock services for testing
        $this->registerMockServices();
    }

    /**
     * Test AbstractSwarmUnit consciousness-level functionality
     * Validates FR-CORE-028 (Health Monitoring) compliance
     */
    public function testAbstractSwarmUnitConsciousness(): void
    {
        $unit = $this->createTestSwarmUnit();
        
        // Test consciousness initialization
        $this->assertFalse($unit->isInitialized());
        
        $mockMesh = $this->createMockSemanticMesh();
        $unit->initialize($mockMesh);
        
        $this->assertTrue($unit->isInitialized());
        
        // Test health monitoring
        $this->assertTrue($unit->isHealthy());
        $healthMetrics = $unit->getHealthMetrics();
        
        $this->assertIsArray($healthMetrics);
        $this->assertArrayHasKey('total_executions', $healthMetrics);
        $this->assertArrayHasKey('efficiency_score', $healthMetrics);
        $this->assertArrayHasKey('error_rate', $healthMetrics);
        
        // Test execution with consciousness monitoring
        $unit->act($mockMesh);
        
        $updatedMetrics = $unit->getHealthMetrics();
        $this->assertGreaterThan($healthMetrics['total_executions'], $updatedMetrics['total_executions']);
    }

    /**
     * Test ethical validation with consciousness-level reasoning
     * Validates FR-CORE-041 (Ethical Boundaries) compliance
     */
    public function testEthicalValidationConsciousness(): void
    {
        $unit = $this->createTestSwarmUnit();
        
        // Test ethical content validation
        $ethicalContent = ['text' => 'This is helpful and positive content.'];
        $this->assertTrue($unit->testEthicalValidation($ethicalContent));
        
        // Test unethical content detection
        $unethicalContent = ['text' => 'This contains harmful and toxic content.'];
        $this->assertFalse($unit->testEthicalValidation($unethicalContent));
        
        // Test ethical violation exception
        $this->expectException(EthicalValidationException::class);
        $unit->testEthicalValidationStrict($unethicalContent);
    }

    /**
     * Test mesh ACL consciousness with access control
     * Validates FR-CORE-006 (Mesh Access Control) compliance
     */
    public function testMeshACLConsciousness(): void
    {
        $unit = $this->createTestSwarmUnit();
        $mockMesh = $this->createMockSemanticMesh();
        
        // Test authorized access
        $this->assertTrue($unit->testMeshAccess($mockMesh, ['read']));
        
        // Test unauthorized access
        $this->expectException(MeshACLViolationException::class);
        $unit->testMeshAccess($mockMesh, ['admin', 'delete']);
    }

    /**
     * Test temporal logic consciousness
     * Validates temporal reasoning capabilities
     */
    public function testTemporalLogicConsciousness(): void
    {
        $unit = $this->createTestSwarmUnit();
        
        // Test staleness detection
        $freshData = ['timestamp' => time(), 'value' => 'test'];
        $staleData = ['timestamp' => time() - 7200, 'value' => 'test']; // 2 hours old
        
        $this->assertFalse($unit->testStaleness($freshData, 3600)); // 1 hour threshold
        $this->assertTrue($unit->testStaleness($staleData, 3600));
        
        // Test business hours logic
        $this->assertIsBool($unit->testBusinessHours());
        
        // Test temporal safety
        $constraints = ['business_hours_only' => false];
        $this->assertTrue($unit->testTemporalSafety($constraints));
    }

    /**
     * Test entropy monitoring consciousness
     * Validates entropy tracking and self-awareness
     */
    public function testEntropyMonitoringConsciousness(): void
    {
        $unit = $this->createTestSwarmUnit();
        
        // Test entropy tracking
        $unit->testRecordHealthMetric('test_metric', 100);
        
        // Test entropy status
        $this->assertIsFloat($unit->testGetEfficacyScore());
        $this->assertTrue($unit->testIsEntropyAcceptable());
        
        // Test entropy health status
        $healthStatus = $unit->testGetEntropyHealthStatus();
        $this->assertIsArray($healthStatus);
        $this->assertArrayHasKey('current_entropy', $healthStatus);
        $this->assertArrayHasKey('status', $healthStatus);
    }

    /**
     * Test SwarmContainer consciousness-level dependency injection
     * Validates FR-CORE-019 (Dependency Injection) compliance
     */
    public function testSwarmContainerConsciousness(): void
    {
        // Test basic container functionality
        $this->container->bind('TestService', \Tests\TestService::class);
        $this->assertTrue($this->container->has('TestService'));
        
        $service = $this->container->get('TestService');
        $this->assertInstanceOf(\Tests\TestService::class, $service);
        
        // Test singleton binding
        $this->container->singleton('SingletonService', \Tests\TestService::class);
        $service1 = $this->container->get('SingletonService');
        $service2 = $this->container->get('SingletonService');
        $this->assertSame($service1, $service2);
        
        // Test SwarmUnit-specific resolution
        $unit = $this->container->resolve(\Tests\TestSwarmUnit::class);
        $this->assertInstanceOf(SwarmUnitInterface::class, $unit);
    }

    /**
     * Test exception hierarchy consciousness
     * Validates self-protective system responses
     */
    public function testExceptionHierarchyConsciousness(): void
    {
        // Test MeshACLViolationException
        $aclException = new MeshACLViolationException('test.key', 'delete', ['read'], 'test-unit');
        $this->assertEquals('test.key', $aclException->getMeshKey());
        $this->assertEquals('delete', $aclException->getOperation());
        $this->assertTrue($aclException->requiresEscalation('delete'));
        
        // Test EthicalValidationException
        $ethicalException = new EthicalValidationException(0.3, ['toxicity'], 'test-unit');
        $this->assertEquals(0.3, $ethicalException->getEthicalScore());
        $this->assertTrue($ethicalException->requiresHumanReview());
        $this->assertEquals('high', $ethicalException->getSeverityLevel());
        
        // Test EntropyLimitExceededException
        $entropyException = new EntropyLimitExceededException(0.9, 0.8, 'test-unit');
        $this->assertEquals(0.9, $entropyException->getCurrentEntropy());
        $this->assertEquals(0.8, $entropyException->getThreshold());
        $this->assertTrue($entropyException->isAutoPruningTriggered());
        
        // Test FallbackDepthExceededException
        $fallbackException = new FallbackDepthExceededException(4, 'unit_unhealthy', 'test-unit');
        $this->assertEquals(4, $fallbackException->getFallbackDepth());
        $this->assertEquals('unit_unhealthy', $fallbackException->getStrategy());
        $this->assertTrue($fallbackException->isEmergencyBrakeActivated());
    }

    /**
     * Test service provider consciousness
     * Validates consciousness-aware service registration
     */
    public function testServiceProviderConsciousness(): void
    {
        $this->serviceProvider->register();
        $this->serviceProvider->markAsRegistered();
        
        $this->assertTrue($this->serviceProvider->isRegistered());
        
        // Test that consciousness services are registered
        $this->assertTrue($this->container->has(SemanticMeshInterface::class));
        
        $this->serviceProvider->boot();
        $this->serviceProvider->markAsBooted();
        
        $this->assertTrue($this->serviceProvider->isBooted());
    }

    /**
     * Test integrated consciousness workflow
     * Validates end-to-end consciousness behavior
     */
    public function testIntegratedConsciousnessWorkflow(): void
    {
        $unit = $this->createTestSwarmUnit();
        $mockMesh = $this->createMockSemanticMesh();
        
        // Initialize consciousness
        $unit->initialize($mockMesh);
        $this->assertTrue($unit->isInitialized());
        
        // Test consciousness-aware execution
        $initialMetrics = $unit->getHealthMetrics();
        $unit->act($mockMesh);
        $finalMetrics = $unit->getHealthMetrics();
        
        // Verify consciousness tracking
        $this->assertGreaterThan($initialMetrics['total_executions'], $finalMetrics['total_executions']);
        $this->assertGreaterThanOrEqual(0.0, $finalMetrics['efficiency_score']);
        $this->assertLessThanOrEqual(1.0, $finalMetrics['efficiency_score']);
        
        // Test shutdown with consciousness preservation
        $unit->shutdown($mockMesh);
        $shutdownMetrics = $unit->getHealthMetrics();
        $this->assertEquals($finalMetrics['total_executions'], $shutdownMetrics['total_executions']);
    }

    /**
     * Create a test SwarmUnit for validation
     */
    private function createTestSwarmUnit(): \Tests\TestSwarmUnit
    {
        $mockServices = $this->createMockConsciousnessServices();
        
        return new \Tests\TestSwarmUnit(
            $mockServices['entropy'],
            $mockServices['ethics'],
            $mockServices['throttling'],
            $mockServices['temporal'],
            $mockServices['acl'],
            $mockServices['tracer']
        );
    }

    /**
     * Create mock consciousness services for testing
     * 
     * @return array Mock services
     */
    private function createMockConsciousnessServices(): array
    {
        // Create actual service instances with minimal dependencies for testing
        $mockMesh = $this->createMock(\Infinri\SwarmFramework\Interfaces\SemanticMeshInterface::class);
        $mockLogger = $this->createMock(\Psr\Log\LoggerInterface::class);
        
        // Configure mock mesh to return sensible defaults
        $mockMesh->method('get')->willReturn(null);
        $mockMesh->method('set')->willReturn(true);
        $mockMesh->method('exists')->willReturn(false);
        $mockMesh->method('getKeysByPattern')->willReturn([]);
        
        // Create simple stub objects that implement the required methods without extending final classes
        return [
            'entropy' => new class {
                public function recordExecution(string $unitId, float $duration, bool $success): void {}
                public function getCurrentEntropyLevel(): float { return 0.5; }
                public function isWithinLimits(): bool { return true; }
                public function recordMetric(string $unitId, string $metric, float $value): void {}
                public function getUnitEfficacyScore(string $unitId): float { return 0.8; }
                public function getUnitEntropy(string $unitId): float { return 0.3; }
                public function initialize(): void {}
                public function getPerformanceMetrics(string $unitId): array { return []; }
            },
            'ethics' => new class {
                public function validateContent(array $content): float { return 0.8; }
                public function isEthical(array $content): bool { return true; }
                public function getDetailedAnalysis(array $content): array { return []; }
                public function sanitizeContent(array $content): array { return $content; }
                public function recordValidation(array $content, array $scores, float $overallScore): void {}
                public function initialize(): void {}
            },
            'throttling' => new class {
                public function shouldThrottle(string $unitId): bool { return false; }
                public function registerExecution(string $unitId): void {}
                public function getLoadMetrics(): array { return []; }
                public function emergencyBrake(): void {}
                public function initialize(): void {}
            },
            'temporal' => new class {
                public function isBusinessHours(): bool { return true; }
                public function isTemporallyValid(array $context): bool { return true; }
                public function getTemporalWindow(): array { return []; }
                public function isStale(float $timestamp): bool { return false; }
                public function initialize(): void {}
            },
            'acl' => new class {
                public function validateAccess(string $meshKey, string $operation, array $capabilities): bool { return true; }
                public function hasPermission(string $meshKey, string $operation, array $capabilities): bool { return true; }
                public function hasCapabilities(string $role, array $requiredCapabilities): bool { return true; }
                public function unitCanPerform(array $unitCapabilities, string $operation): bool { return true; }
                public function recordAccess(string $meshKey, string $operation, array $unitCapabilities, bool $granted): void {}
                public function initialize(): void {}
            },
            'tracer' => new class {
                public function traceExecution(string $unitId, array $context): void {}
                public function getTraceHistory(string $unitId): array { return []; }
                public function recordTrace(string $unitId, string $event, array $context): void {}
                public function analyzeBehavior(string $unitId): array { return []; }
                public function initialize(): void {}
            }
        ];
    }

    /**
     * Create mock semantic mesh
     */
    private function createMockSemanticMesh(): SemanticMeshInterface
    {
        $mock = $this->createMock(SemanticMeshInterface::class);
        
        $mock->expects($this->any())->method('get')->willReturn('test_value');
        $mock->expects($this->any())->method('set')->willReturn(true);
        $mock->expects($this->any())->method('snapshot')->willReturn(['test.key' => 'test_value']);
        $mock->expects($this->any())->method('getStats')->willReturn(['keys' => ['test.key']]);
        
        /** @var SemanticMeshInterface $mock */
        return $mock;
    }

    /**
     * Register mock services for testing
     */
    private function registerMockServices(): void
    {
        $this->container->bind(SemanticMeshInterface::class, function() {
            return $this->createMockSemanticMesh();
        });
    }
}

/**
 * Test SwarmUnit implementation for validation
 */
class TestSwarmUnit extends AbstractSwarmUnit
{
    public bool $isInitialized = false;

    protected function executeUnitLogic(SemanticMeshInterface $mesh): void
    {
        // Test implementation - record execution
        $this->recordExecution('test_execution', [
            'mutations' => 1,
            'time' => 0.001,
            'confidence' => 0.9
        ]);
    }

    protected function performConsciousnessInitialization(SemanticMeshInterface $mesh): void
    {
        $this->isInitialized = true;
    }

    public function triggerCondition(SemanticMeshInterface $mesh): bool
    {
        return true; // Always trigger for testing
    }

    public function getIdentity(): UnitIdentity
    {
        return new UnitIdentity(
            id: 'test-unit-id',
            version: '1.0.0',
            hash: 'sha256:test-hash',
            capabilities: ['read', 'write']
        );
    }

    public function getPriority(): int
    {
        return 50;
    }

    public function getCooldown(): int
    {
        return 1000;
    }

    public function getMutexGroup(): ?string
    {
        return 'test-group';
    }

    public function getResourceRequirements(): array
    {
        return ['memory' => 256, 'cpu' => 0.1];
    }

    public function getTimeout(): int
    {
        return 30000;
    }

    public function validate(): ValidationResult
    {
        return ValidationResult::success();
    }

    // Test methods to expose protected functionality
    public function testEthicalValidation(array $content): bool
    {
        return $this->validateEthicalContext($content);
    }

    public function testEthicalValidationStrict(array $content): void
    {
        if (!$this->validateEthicalContext($content)) {
            throw new EthicalValidationException(0.3, ['toxicity'], $this->getIdentity()->id);
        }
    }

    public function testMeshAccess(SemanticMeshInterface $mesh, array $capabilities): bool
    {
        return $this->guardCheck($mesh, $capabilities);
    }

    public function testStaleness(mixed $value, int $maxAge): bool
    {
        return $this->stale($value, $maxAge);
    }

    public function testBusinessHours(): bool
    {
        return $this->duringBusinessHours();
    }

    public function testTemporalSafety(array $constraints): bool
    {
        return $this->temporallySafeToExecute($constraints);
    }

    public function testRecordHealthMetric(string $metric, mixed $value): void
    {
        $this->recordHealthMetric($metric, $value);
    }

    public function testGetEfficacyScore(): float
    {
        return $this->getUnitEfficacyScore();
    }

    public function testIsEntropyAcceptable(): bool
    {
        return $this->isEntropyAcceptable();
    }

    public function testGetEntropyHealthStatus(): array
    {
        return $this->getEntropyHealthStatus();
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }
}

/**
 * Test service for container validation
 */
class TestService
{
    public function test(): string
    {
        return 'test';
    }
}
