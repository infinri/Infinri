<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Infinri\SwarmFramework\Core\UnitIdentity;
use Infinri\SwarmFramework\Core\Tactic;
use Infinri\SwarmFramework\Core\Goal;
use Infinri\SwarmFramework\Core\Injectable;
use Infinri\SwarmFramework\Core\SafetyLimitsEnforcer;
use Infinri\SwarmFramework\Core\RuntimeValidator;
use Infinri\SwarmFramework\Core\SemanticMesh;
use Infinri\SwarmFramework\Core\SwarmReactor;
use Infinri\SwarmFramework\Core\StigmergicTracer;
use Infinri\SwarmFramework\Core\ModuleRegistry;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Infinri\SwarmFramework\Exceptions\InvalidUnitIdentityException;
use Infinri\SwarmFramework\Exceptions\SafetyLimitExceededException;

/**
 * Comprehensive Swarm Framework Infrastructure Test Suite
 * 
 * Tests all core components for compliance, functionality, and integration
 * before production deployment.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
class SwarmFrameworkTest
{
    private array $testResults = [];
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;

    public function __construct()
    {
        echo "🕷️ Swarm Framework Infrastructure Test Suite\n";
        echo "============================================\n\n";
    }

    /**
     * Run all tests
     */
    public function runAllTests(): void
    {
        $this->testUnitIdentity();
        $this->testTacticAndGoal();
        $this->testInjectable();
        $this->testSafetyLimitsEnforcer();
        $this->testRuntimeValidator();
        $this->testValidationResult();
        $this->testSemanticMesh();
        $this->testStigmergicTracer();
        $this->testModuleRegistry();
        $this->testIntegration();
        
        $this->printSummary();
    }

    /**
     * Test UnitIdentity class
     */
    private function testUnitIdentity(): void
    {
        echo "🧪 Testing UnitIdentity...\n";

        // Test valid identity creation
        $this->runTest('UnitIdentity Creation', function() {
            $identity = new UnitIdentity(
                id: 'test-unit-001',
                version: '1.0.0',
                hash: 'sha256:' . hash('sha256', 'test-unit-001'),
                capabilities: ['read', 'write'],
                dependencies: ['SemanticMesh'],
                meshKeys: ['test.key1', 'test.key2']
            );
            
            return $identity->id === 'test-unit-001' && 
                   $identity->version === '1.0.0' &&
                   count($identity->capabilities) === 2;
        });

        // Test identity validation
        $this->runTest('UnitIdentity Validation', function() {
            try {
                new UnitIdentity(
                    id: '',
                    version: '1.0.0',
                    hash: 'invalid-hash'
                );
                return false; // Should have thrown exception
            } catch (InvalidUnitIdentityException $e) {
                return true; // Expected exception
            }
        });
    }

    /**
     * Test Tactic and Goal attributes
     */
    private function testTacticAndGoal(): void
    {
        echo "🧪 Testing Tactic and Goal attributes...\n";

        // Test Tactic creation
        $this->runTest('Tactic Creation', function() {
            $tactic = new Tactic('TAC-PERF-001', 'TAC-SCAL-002');
            return count($tactic->getTactics()) === 2 &&
                   in_array('TAC-PERF-001', $tactic->getTactics());
        });

        // Test Goal creation
        $this->runTest('Goal Creation', function() {
            $goal = new Goal(
                description: 'Optimize mesh performance',
                requirements: ['FR-PERF-001'],
                priority: 8
            );
            return $goal->description === 'Optimize mesh performance' &&
                   $goal->priority === 8;
        });

        // Test invalid tactic format
        $this->runTest('Invalid Tactic Format', function() {
            try {
                new Tactic('INVALID-FORMAT');
                return false;
            } catch (Exception $e) {
                return true;
            }
        });
    }

    /**
     * Test Injectable attribute
     */
    private function testInjectable(): void
    {
        echo "🧪 Testing Injectable attribute...\n";

        $this->runTest('Injectable Creation', function() {
            $injectable = new Injectable(['Redis', 'LoggerInterface'], true);
            return count($injectable->dependencies) === 2 &&
                   $injectable->singleton === true;
        });
    }

    /**
     * Test SafetyLimitsEnforcer
     */
    private function testSafetyLimitsEnforcer(): void
    {
        echo "🧪 Testing SafetyLimitsEnforcer...\n";

        $enforcer = new SafetyLimitsEnforcer();

        // Test execution start check
        $this->runTest('Safety Limits - Execution Start', function() use ($enforcer) {
            try {
                $enforcer->checkExecutionStart('test-unit-001');
                return true;
            } catch (SafetyLimitExceededException $e) {
                return false;
            }
        });

        // Test mesh keys limit
        $this->runTest('Safety Limits - Mesh Keys Limit', function() use ($enforcer) {
            $tooManyKeys = array_fill(0, 1001, 'key');
            try {
                $enforcer->checkMeshKeysLimit($tooManyKeys);
                return false; // Should have thrown exception
            } catch (SafetyLimitExceededException $e) {
                return true;
            }
        });

        // Test mesh value size
        $this->runTest('Safety Limits - Value Size', function() use ($enforcer) {
            $largeValue = str_repeat('x', 2 * 1024 * 1024); // 2MB
            try {
                $enforcer->checkMeshValueSize($largeValue);
                return false; // Should have thrown exception
            } catch (SafetyLimitExceededException $e) {
                return true;
            }
        });

        // Test recursion depth
        $this->runTest('Safety Limits - Recursion Depth', function() use ($enforcer) {
            try {
                $enforcer->checkRecursionDepth(6); // Over limit of 5
                return false;
            } catch (SafetyLimitExceededException $e) {
                return true;
            }
        });

        // Test safety metrics
        $this->runTest('Safety Limits - Metrics', function() use ($enforcer) {
            $metrics = $enforcer->getSafetyMetrics();
            return isset($metrics['max_concurrent_units']) &&
                   $metrics['max_concurrent_units'] === 20000;
        });
    }

    /**
     * Test RuntimeValidator
     */
    private function testRuntimeValidator(): void
    {
        echo "🧪 Testing RuntimeValidator...\n";

        $validator = new RuntimeValidator();

        // Test unit identity validation
        $this->runTest('Runtime Validator - Unit Identity', function() use ($validator) {
            $identity = new UnitIdentity(
                id: 'test-unit-001',
                version: '1.0.0',
                hash: 'sha256:' . hash('sha256', 'test-unit-001')
            );
            
            try {
                $validator->validateUnitIdentity($identity);
                return true;
            } catch (Exception $e) {
                return false;
            }
        });

        // Test mesh key validation
        $this->runTest('Runtime Validator - Mesh Key', function() use ($validator) {
            try {
                $validator->validateMeshKey('valid.mesh.key');
                return true;
            } catch (Exception $e) {
                return false;
            }
        });

        // Test PSR-4 compliance
        $this->runTest('Runtime Validator - PSR-4', function() use ($validator) {
            try {
                $validator->validatePsr4Compliance(
                    'Infinri\\SwarmFramework\\Core\\TestClass',
                    '/path/to/TestClass.php'
                );
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    /**
     * Test ValidationResult
     */
    private function testValidationResult(): void
    {
        echo "🧪 Testing ValidationResult...\n";

        // Test success result
        $this->runTest('ValidationResult - Success', function() {
            $result = ValidationResult::success(['warning1'], ['context' => 'test']);
            return $result->isValid() && 
                   count($result->getErrors()) === 0 &&
                   $result->hasWarnings();
        });

        // Test failure result
        $this->runTest('ValidationResult - Failure', function() {
            $result = ValidationResult::failure(['error1', 'error2']);
            return !$result->isValid() && 
                   count($result->getErrors()) === 2 &&
                   $result->hasErrors();
        });

        // Test merge functionality
        $this->runTest('ValidationResult - Merge', function() {
            $result1 = ValidationResult::success();
            $result2 = ValidationResult::failure(['error1']);
            $merged = $result1->merge($result2);
            
            return !$merged->isValid() && 
                   count($merged->getErrors()) === 1;
        });
    }

    /**
     * Test SemanticMesh (basic functionality without Redis connection)
     */
    private function testSemanticMesh(): void
    {
        echo "🧪 Testing SemanticMesh structure...\n";

        // Test class exists and has Injectable attribute
        $this->runTest('SemanticMesh - Class Structure', function() {
            $reflection = new ReflectionClass(SemanticMesh::class);
            $attributes = $reflection->getAttributes(Injectable::class);
            return count($attributes) > 0 && $reflection->isFinal();
        });
    }

    /**
     * Test StigmergicTracer
     */
    private function testStigmergicTracer(): void
    {
        echo "🧪 Testing StigmergicTracer...\n";

        // Create mock logger
        $logger = new class implements \Psr\Log\LoggerInterface {
            public function emergency($message, array $context = []): void {}
            public function alert($message, array $context = []): void {}
            public function critical($message, array $context = []): void {}
            public function error($message, array $context = []): void {}
            public function warning($message, array $context = []): void {}
            public function notice($message, array $context = []): void {}
            public function info($message, array $context = []): void {}
            public function debug($message, array $context = []): void {}
            public function log($level, $message, array $context = []): void {}
        };

        $tracer = new StigmergicTracer($logger);

        // Test pheromone intensity calculation
        $this->runTest('StigmergicTracer - Pheromone Intensity', function() use ($tracer) {
            $intensity = $tracer->getPheromoneIntensity('test.key');
            return is_float($intensity) && $intensity >= 0.0 && $intensity <= 1.0;
        });
    }

    /**
     * Test ModuleRegistry
     */
    private function testModuleRegistry(): void
    {
        echo "🧪 Testing ModuleRegistry...\n";

        // Create mock logger
        $logger = new class implements \Psr\Log\LoggerInterface {
            public function emergency($message, array $context = []): void {}
            public function alert($message, array $context = []): void {}
            public function critical($message, array $context = []): void {}
            public function error($message, array $context = []): void {}
            public function warning($message, array $context = []): void {}
            public function notice($message, array $context = []): void {}
            public function info($message, array $context = []): void {}
            public function debug($message, array $context = []): void {}
            public function log($level, $message, array $context = []): void {}
        };

        $registry = new ModuleRegistry($logger);

        // Test registry initialization
        $this->runTest('ModuleRegistry - Initialization', function() use ($registry) {
            return $registry instanceof ModuleRegistry;
        });
    }

    /**
     * Test integration between components
     */
    private function testIntegration(): void
    {
        echo "🧪 Testing Component Integration...\n";

        // Test that all classes can be instantiated together
        $this->runTest('Integration - Class Compatibility', function() {
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

            return $identity instanceof UnitIdentity &&
                   $tactic instanceof Tactic &&
                   $goal instanceof Goal &&
                   $injectable instanceof Injectable &&
                   $enforcer instanceof SafetyLimitsEnforcer &&
                   $validator instanceof RuntimeValidator &&
                   $result instanceof ValidationResult;
        });

        // Test safety enforcer with validator integration
        $this->runTest('Integration - Safety + Validation', function() {
            $enforcer = new SafetyLimitsEnforcer();
            $validator = new RuntimeValidator();
            
            $identity = new UnitIdentity(
                id: 'safety-test',
                version: '1.0.0',
                hash: 'sha256:' . hash('sha256', 'safety-test'),
                meshKeys: ['test.key1', 'test.key2']
            );

            try {
                $validator->validateUnitIdentity($identity);
                $enforcer->checkMeshKeysLimit($identity->meshKeys);
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    /**
     * Run a single test
     */
    private function runTest(string $testName, callable $testFunction): void
    {
        $this->totalTests++;
        
        try {
            $result = $testFunction();
            if ($result) {
                echo "  ✅ {$testName}\n";
                $this->passedTests++;
                $this->testResults[$testName] = 'PASS';
            } else {
                echo "  ❌ {$testName}\n";
                $this->failedTests++;
                $this->testResults[$testName] = 'FAIL';
            }
        } catch (Exception $e) {
            echo "  💥 {$testName} - Exception: {$e->getMessage()}\n";
            $this->failedTests++;
            $this->testResults[$testName] = 'ERROR: ' . $e->getMessage();
        }
    }

    /**
     * Print test summary
     */
    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "🕷️ SWARM FRAMEWORK TEST RESULTS\n";
        echo str_repeat("=", 50) . "\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests} ✅\n";
        echo "Failed: {$this->failedTests} ❌\n";
        echo "Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 2) . "%\n";
        
        if ($this->failedTests === 0) {
            echo "\n🎉 ALL TESTS PASSED! Swarm Framework is ready for production!\n";
            echo "The spider IS the web - digital consciousness infrastructure verified! 🕸️\n";
        } else {
            echo "\n⚠️  Some tests failed. Please review and fix issues before deployment.\n";
            
            echo "\nFailed Tests:\n";
            foreach ($this->testResults as $test => $result) {
                if ($result !== 'PASS') {
                    echo "  - {$test}: {$result}\n";
                }
            }
        }
        
        echo "\n" . str_repeat("=", 50) . "\n";
    }
}

// Run the tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $testSuite = new SwarmFrameworkTest();
    $testSuite->runAllTests();
}
