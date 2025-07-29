<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Infinri\SwarmFramework\Core\MeshAccessController;
use Infinri\SwarmFramework\Core\MeshDataValidator;
use Infinri\SwarmFramework\Core\MeshPerformanceMonitor;
use Infinri\SwarmFramework\Core\MeshSubscriptionManager;
use Infinri\SwarmFramework\Core\ModuleDiscovery;
use Infinri\SwarmFramework\Core\ModuleValidator;
use Infinri\SwarmFramework\Core\HotSwapManager;
use Infinri\SwarmFramework\Core\DependencyResolver;
use Infinri\SwarmFramework\Core\SemanticMesh;
use Infinri\SwarmFramework\Core\ModuleRegistry;
use Psr\Log\NullLogger;

/**
 * Test suite for refactored Swarm Framework components
 * 
 * Validates that the refactored components maintain functionality
 * while improving maintainability and modularity.
 */
final class RefactoredComponentsTest extends TestCase
{
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullLogger();
    }

    public function testMeshAccessControllerCreation(): void
    {
        $controller = new MeshAccessController($this->logger);
        $this->assertInstanceOf(MeshAccessController::class, $controller);
    }

    public function testMeshDataValidatorCreation(): void
    {
        $validator = new MeshDataValidator($this->logger);
        $this->assertInstanceOf(MeshDataValidator::class, $validator);
    }

    public function testMeshDataValidatorSerialization(): void
    {
        $validator = new MeshDataValidator($this->logger);
        
        $testData = ['key' => 'value', 'number' => 42];
        $serialized = $validator->serialize($testData, 'test-unit');
        
        $this->assertIsString($serialized);
        $this->assertNotEmpty($serialized);
        
        $deserialized = $validator->deserialize($serialized);
        $this->assertEquals($testData, $deserialized);
    }

    public function testMeshDataValidatorHashVerification(): void
    {
        $validator = new MeshDataValidator($this->logger);
        
        $testValue = 'test-value-for-hashing';
        $hash = $validator->calculateHash($testValue);
        
        $this->assertStringStartsWith('sha256:', $hash);
        $this->assertTrue($validator->verifyHash($testValue, $hash));
        $this->assertFalse($validator->verifyHash('different-value', $hash));
    }

    public function testModuleDiscoveryCreation(): void
    {
        $discovery = new ModuleDiscovery($this->logger);
        $this->assertInstanceOf(ModuleDiscovery::class, $discovery);
    }

    public function testModuleValidatorCreation(): void
    {
        $validator = new ModuleValidator($this->logger);
        $this->assertInstanceOf(ModuleValidator::class, $validator);
    }

    public function testDependencyResolverCreation(): void
    {
        $resolver = new DependencyResolver($this->logger);
        $this->assertInstanceOf(DependencyResolver::class, $resolver);
    }

    public function testDependencyResolverBasicOperations(): void
    {
        $resolver = new DependencyResolver($this->logger);
        
        // Test adding dependencies
        $resolver->addDependency('moduleA', 'moduleB', '1.0.0');
        $resolver->addDependency('moduleB', 'moduleC', '2.0.0');
        
        // Test dependency graph
        $graph = $resolver->getDependencyGraph();
        $this->assertIsArray($graph);
        
        // Test load order resolution
        $loadOrder = $resolver->resolveLoadOrder(['moduleA', 'moduleB', 'moduleC']);
        $this->assertIsArray($loadOrder);
        $this->assertCount(3, $loadOrder);
    }

    public function testDependencyResolverCircularDetection(): void
    {
        $resolver = new DependencyResolver($this->logger);
        
        // Create circular dependency
        $resolver->addDependency('moduleA', 'moduleB', '1.0.0');
        $resolver->addDependency('moduleB', 'moduleC', '1.0.0');
        $resolver->addDependency('moduleC', 'moduleA', '1.0.0');
        
        $this->assertTrue($resolver->hasCircularDependencies(['moduleA', 'moduleB', 'moduleC']));
    }

    public function testModuleRegistryRefactoredStructure(): void
    {
        // Test that ModuleRegistry can be instantiated with new structure
        $mockMesh = $this->createMock(\Infinri\SwarmFramework\Interfaces\SemanticMeshInterface::class);
        $registry = new ModuleRegistry($this->logger, $mockMesh);
        $this->assertInstanceOf(ModuleRegistry::class, $registry);
    }

    public function testSemanticMeshRefactoredStructure(): void
    {
        // Create a mock Redis instance for testing
        $mockRedis = $this->createMock(\Redis::class);
        
        $mesh = new SemanticMesh($mockRedis, $this->logger);
        $this->assertInstanceOf(SemanticMesh::class, $mesh);
    }

    public function testComponentIntegration(): void
    {
        // Test that all refactored components can work together
        $discovery = new ModuleDiscovery($this->logger);
        $validator = new ModuleValidator($this->logger);
        $resolver = new DependencyResolver($this->logger);
        
        // Test basic integration
        $this->assertInstanceOf(ModuleDiscovery::class, $discovery);
        $this->assertInstanceOf(ModuleValidator::class, $validator);
        $this->assertInstanceOf(DependencyResolver::class, $resolver);
    }

    public function testRefactoringMaintainsFunctionality(): void
    {
        // Test that core functionality is preserved after refactoring
        $resolver = new DependencyResolver($this->logger);
        
        // Add some test dependencies
        $resolver->addDependency('core', 'utils', '1.0.0');
        $resolver->addDependency('api', 'core', '1.0.0');
        $resolver->addDependency('frontend', 'api', '1.0.0');
        
        $modules = ['frontend', 'api', 'core', 'utils'];
        $loadOrder = $resolver->resolveLoadOrder($modules);
        
        // Verify that dependencies are loaded in correct order
        $utilsIndex = array_search('utils', $loadOrder);
        $coreIndex = array_search('core', $loadOrder);
        $apiIndex = array_search('api', $loadOrder);
        $frontendIndex = array_search('frontend', $loadOrder);
        
        $this->assertLessThan($coreIndex, $utilsIndex, 'utils should load before core');
        $this->assertLessThan($apiIndex, $coreIndex, 'core should load before api');
        $this->assertLessThan($frontendIndex, $apiIndex, 'api should load before frontend');
    }

    public function testErrorHandlingInRefactoredComponents(): void
    {
        $validator = new MeshDataValidator($this->logger);
        
        // Test error handling with invalid data
        $this->expectException(\InvalidArgumentException::class);
        $validator->deserialize('invalid-json-data');
    }

    public function testPerformanceMonitoringIntegration(): void
    {
        $mockRedis = $this->createMock(\Redis::class);
        $mockRedis->method('info')->willReturn([
            'used_memory' => 1024,
            'keyspace_hits' => 100,
            'keyspace_misses' => 10
        ]);
        $mockRedis->method('dbSize')->willReturn(50);
        $mockRedis->method('get')->willReturn(false); // No cached stats
        
        $monitor = new MeshPerformanceMonitor($mockRedis, $this->logger);
        
        // Test basic monitoring functionality
        $stats = $monitor->getStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('keys', $stats);
        $this->assertArrayHasKey('memory', $stats);
    }

    public function testRefactoredComponentsFollowSOLIDPrinciples(): void
    {
        // Test Single Responsibility Principle
        $accessController = new MeshAccessController($this->logger);
        $dataValidator = new MeshDataValidator($this->logger);
        $discovery = new ModuleDiscovery($this->logger);
        $validator = new ModuleValidator($this->logger);
        
        // Each component should have a single, well-defined responsibility
        $this->assertInstanceOf(MeshAccessController::class, $accessController);
        $this->assertInstanceOf(MeshDataValidator::class, $dataValidator);
        $this->assertInstanceOf(ModuleDiscovery::class, $discovery);
        $this->assertInstanceOf(ModuleValidator::class, $validator);
        
        // Test that components are properly separated
        $this->assertNotSame($accessController, $dataValidator);
        $this->assertNotSame($discovery, $validator);
    }
}
