<?php declare(strict_types=1);

use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Infinri\SwarmFramework\Core\Attributes\Tactic;
use Infinri\SwarmFramework\Core\Attributes\Goal;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Safety\SafetyLimitsEnforcer;
use Infinri\SwarmFramework\Core\Safety\RuntimeValidator;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Infinri\SwarmFramework\Exceptions\InvalidUnitIdentityException;
use Infinri\SwarmFramework\Exceptions\SafetyLimitExceededException;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive Swarm Framework Infrastructure Test Suite
 * 
 * Tests all core components for compliance, functionality, and integration
 * before production deployment.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
class SwarmFrameworkTest extends TestCase
{
    /**
     * Test UnitIdentity class creation and validation
     */
    public function testUnitIdentityCreation(): void
    {
        // Test valid identity creation
        $identity = new UnitIdentity(
            id: 'test-unit-001',
            version: '1.0.0',
            hash: 'sha256:' . hash('sha256', 'test-unit-001'),
            capabilities: ['read', 'write'],
            dependencies: ['SemanticMesh'],
            meshKeys: ['test.key1', 'test.key2']
        );
        
        $this->assertEquals('test-unit-001', $identity->id);
        $this->assertEquals('1.0.0', $identity->version);
        $this->assertCount(2, $identity->capabilities);
        $this->assertContains('read', $identity->capabilities);
    }

    /**
     * Test UnitIdentity validation with invalid data
     */
    public function testUnitIdentityValidation(): void
    {
        $this->expectException(InvalidUnitIdentityException::class);
        
        new UnitIdentity(
            id: '',
            version: '1.0.0',
            hash: 'invalid-hash'
        );
    }

    /**
     * Test Tactic attribute creation
     */
    public function testTacticCreation(): void
    {
        $tactic = new Tactic('TAC-PERF-001', 'TAC-SCAL-002');
        
        $this->assertCount(2, $tactic->getTactics());
        $this->assertContains('TAC-PERF-001', $tactic->getTactics());
        $this->assertContains('TAC-SCAL-002', $tactic->getTactics());
    }

    /**
     * Test Goal attribute creation
     */
    public function testGoalCreation(): void
    {
        $goal = new Goal(
            description: 'Optimize mesh performance',
            requirements: ['FR-PERF-001'],
            priority: 8
        );
        
        $this->assertEquals('Optimize mesh performance', $goal->description);
        $this->assertEquals(8, $goal->priority);
        $this->assertContains('FR-PERF-001', $goal->requirements);
    }

    /**
     * Test Injectable attribute creation
     */
    public function testInjectableCreation(): void
    {
        $injectable = new Injectable(['TestDep', 'AnotherDep']);
        
        $this->assertCount(2, $injectable->dependencies);
        $this->assertContains('TestDep', $injectable->dependencies);
    }

    /**
     * Test SafetyLimitsEnforcer functionality
     */
    public function testSafetyLimitsEnforcer(): void
    {
        $enforcer = new SafetyLimitsEnforcer();
        
        // Test mesh keys limit check
        $validKeys = ['key1', 'key2', 'key3'];
        $enforcer->checkMeshKeysLimit($validKeys);
        
        // This should not throw an exception
        $this->assertTrue(true);
    }

    /**
     * Test RuntimeValidator functionality
     */
    public function testRuntimeValidator(): void
    {
        $validator = new RuntimeValidator();
        
        $identity = new UnitIdentity(
            id: 'test-validator',
            version: '1.0.0',
            hash: 'sha256:' . hash('sha256', 'test-validator')
        );
        
        // RuntimeValidator::validateUnitIdentity returns void and throws on failure
        // If no exception is thrown, validation passed
        $validator->validateUnitIdentity($identity);
        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * Test ValidationResult creation
     */
    public function testValidationResult(): void
    {
        // Test success result
        $successResult = ValidationResult::success();
        $this->assertTrue($successResult->isValid());
        $this->assertEmpty($successResult->getErrors());
        
        // Test failure result
        $failureResult = ValidationResult::failure(['Test error']);
        $this->assertFalse($failureResult->isValid());
        $this->assertContains('Test error', $failureResult->getErrors());
    }

    /**
     * Test component integration
     */
    public function testComponentIntegration(): void
    {
        // Test that all classes can be instantiated together
        $identity = new UnitIdentity(
            id: 'integration-test',
            version: '1.0.0',
            hash: 'sha256:' . hash('sha256', 'integration-test')
        );

        $tactic = new Tactic('TAC-TEST-001');
        $goal = new Goal('Integration test goal');
        $injectable = new Injectable(['TestDep']);
        $enforcer = new SafetyLimitsEnforcer();
        $validator = new RuntimeValidator();
        $result = ValidationResult::success();

        $this->assertInstanceOf(UnitIdentity::class, $identity);
        $this->assertInstanceOf(Tactic::class, $tactic);
        $this->assertInstanceOf(Goal::class, $goal);
        $this->assertInstanceOf(Injectable::class, $injectable);
        $this->assertInstanceOf(SafetyLimitsEnforcer::class, $enforcer);
        $this->assertInstanceOf(RuntimeValidator::class, $validator);
        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    /**
     * Test safety enforcer with validator integration
     */
    public function testSafetyValidatorIntegration(): void
    {
        $enforcer = new SafetyLimitsEnforcer();
        $validator = new RuntimeValidator();
        
        $identity = new UnitIdentity(
            id: 'safety-test',
            version: '1.0.0',
            hash: 'sha256:' . hash('sha256', 'safety-test'),
            meshKeys: ['test.key1', 'test.key2']
        );

        // Validate identity (throws on failure, void return)
        $validator->validateUnitIdentity($identity);
        
        // Check safety limits (throws on failure, void return)
        $enforcer->checkMeshKeysLimit($identity->meshKeys);
        
        // If we get here without exceptions, the integration works
        $this->assertTrue(true);
    }
}
