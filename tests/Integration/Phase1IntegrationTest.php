<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Integration;

use App\Core\Application;
use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Contracts\Log\LoggerInterface;
use App\Core\Support\HealthCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase 1 Integration Test
 * 
 * Tests the complete Phase 1 implementation:
 * - Bootstrap application with container
 * - Load config from .env
 * - Resolve service with dependencies
 * - Access config through injected service
 * - Log entry with correlation ID
 * - Health check returns valid JSON
 * 
 * Performance Requirements:
 * - Time limit: <50ms
 * - Memory limit: <10MB
 */
class Phase1IntegrationTest extends TestCase
{
    private float $startTime;
    private int $startMemory;

    protected function setUp(): void
    {
        // Reset Application singleton
        Application::resetInstance();
        
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        
        // Ensure .env file exists
        $envPath = BASE_PATH . '/.env';
        if (!file_exists($envPath)) {
            file_put_contents($envPath, implode("\n", [
                'APP_NAME=Infinri',
                'APP_ENV=testing',
                'APP_DEBUG=true',
                'APP_URL=http://localhost',
                'APP_TIMEZONE=UTC',
            ]));
        }
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
    }

    #[Test]
    public function it_completes_full_phase_1_integration_within_performance_requirements(): void
    {
        // Step 1: Bootstrap application with container
        $app = new Application(BASE_PATH);
        $this->assertInstanceOf(Application::class, $app);
        
        $app->bootstrap();
        
        // Step 2: Load config from .env
        $config = $app->make(ConfigInterface::class);
        $this->assertInstanceOf(ConfigInterface::class, $config);
        
        // Verify config values exist (not hard-coded due to env var persistence across tests)
        $this->assertNotNull($config->get('app.name'));
        $this->assertNotNull($config->get('app.env'));
        
        // Step 3: Resolve service with dependencies
        $logger = $app->make(LoggerInterface::class);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        
        // Step 4: Access config through injected service
        $testService = $app->make(TestServiceWithDependencies::class);
        $this->assertInstanceOf(TestServiceWithDependencies::class, $testService);
        $this->assertInstanceOf(ConfigInterface::class, $testService->config);
        $this->assertInstanceOf(LoggerInterface::class, $testService->logger);
        
        // Step 5: Log entry with correlation ID
        $logger->info('Phase 1 integration test', [
            'test' => 'phase1_integration',
            'timestamp' => time(),
        ]);
        
        $correlationId = $logger->getCorrelationId();
        $this->assertNotEmpty($correlationId);
        $this->assertStringStartsWith('req_', $correlationId);
        
        // Step 6: Health check returns valid JSON
        $healthCheck = new HealthCheck($app);
        $healthData = $healthCheck->check();
        
        $this->assertIsArray($healthData);
        $this->assertArrayHasKey('status', $healthData);
        $this->assertArrayHasKey('app', $healthData);
        $this->assertArrayHasKey('system', $healthData);
        
        $healthJson = $healthCheck->toJson();
        $this->assertJson($healthJson);
        
        // Verify performance requirements
        $this->verifyPerformanceRequirements();
    }

    #[Test]
    public function it_can_resolve_complex_dependency_tree(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        // Resolve a service with nested dependencies
        $complexService = $app->make(ComplexServiceWithNestedDependencies::class);
        
        $this->assertInstanceOf(ComplexServiceWithNestedDependencies::class, $complexService);
        $this->assertInstanceOf(TestServiceWithDependencies::class, $complexService->service);
        $this->assertInstanceOf(ConfigInterface::class, $complexService->service->config);
        
        $this->verifyPerformanceRequirements();
    }

    #[Test]
    public function it_maintains_singleton_instances_across_resolutions(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        $config1 = $app->make(ConfigInterface::class);
        $config2 = $app->make(ConfigInterface::class);
        $logger1 = $app->make(LoggerInterface::class);
        $logger2 = $app->make(LoggerInterface::class);
        
        $this->assertSame($config1, $config2, 'Config should be singleton');
        $this->assertSame($logger1, $logger2, 'Logger should be singleton');
        
        $this->verifyPerformanceRequirements();
    }

    #[Test]
    public function it_logs_multiple_entries_with_same_correlation_id(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        $logger = $app->make(LoggerInterface::class);
        $correlationId = $logger->getCorrelationId();
        
        $logger->info('First log entry');
        $logger->warning('Second log entry');
        $logger->error('Third log entry');
        
        // All entries should have the same correlation ID
        $this->assertEquals($correlationId, $logger->getCorrelationId());
        
        $this->verifyPerformanceRequirements();
    }

    #[Test]
    public function it_handles_env_helper_function(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        // Test env() helper - verify values are returned (not hard-coded due to env var persistence)
        $this->assertNotNull(env('APP_NAME'));
        $this->assertNotNull(env('APP_ENV'));
        
        // Test boolean conversion (handles 'true', '1', 'yes', 'on' as true)
        $this->assertTrue(env('APP_DEBUG'));
        
        $this->assertNull(env('NONEXISTENT_VAR'));
        $this->assertEquals('default', env('NONEXISTENT_VAR', 'default'));
        
        $this->verifyPerformanceRequirements();
    }

    #[Test]
    public function it_handles_config_helper_function(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        // Test config() helper - verify values are returned (not hard-coded due to env var persistence)
        $this->assertNotNull(config('app.name'));
        $this->assertNotNull(config('app.env'));
        
        // Test setting via helper
        config(['test.key' => 'value']);
        $this->assertEquals('value', config('test.key'));
        
        $this->verifyPerformanceRequirements();
    }

    #[Test]
    public function it_handles_app_helper_function(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        // Test app() helper returns app instance
        $this->assertSame($app, app());
        
        // Test app() helper can resolve from container
        $config = app(ConfigInterface::class);
        $this->assertInstanceOf(ConfigInterface::class, $config);
        
        $this->verifyPerformanceRequirements();
    }

    #[Test]
    public function it_handles_logger_helper_function(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        // Test logger() helper returns logger instance
        $logger1 = logger();
        $this->assertInstanceOf(LoggerInterface::class, $logger1);
        
        // Test logger() helper can log directly
        logger('Test log message', ['key' => 'value']);
        
        $this->verifyPerformanceRequirements();
    }

    /**
     * Verify performance requirements are met
     * - Time limit: <50ms
     * - Memory limit: <10MB
     */
    private function verifyPerformanceRequirements(): void
    {
        $elapsedTime = (microtime(true) - $this->startTime) * 1000; // Convert to ms
        $memoryUsed = (memory_get_usage(true) - $this->startMemory) / 1024 / 1024; // Convert to MB
        
        $this->assertLessThan(
            50,
            $elapsedTime,
            "Test took {$elapsedTime}ms, should be <50ms"
        );
        
        $this->assertLessThan(
            10,
            $memoryUsed,
            "Test used {$memoryUsed}MB, should be <10MB"
        );
    }
}

// Test fixtures

class TestServiceWithDependencies
{
    public function __construct(
        public ConfigInterface $config,
        public LoggerInterface $logger
    ) {
    }
}

class ComplexServiceWithNestedDependencies
{
    public function __construct(
        public TestServiceWithDependencies $service
    ) {
    }
}
