<?php declare(strict_types=1);

use App\Core\Support\CircuitBreaker;
use App\Core\Support\CircuitBreakerOpenException;
use App\Core\Cache\ArrayStore;

describe('CircuitBreaker', function () {
    
    it('can be instantiated', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        expect($breaker)->toBeInstanceOf(CircuitBreaker::class);
    });
    
    it('starts in closed state', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        expect($breaker->getState('test_service'))->toBe(CircuitBreaker::STATE_CLOSED);
    });
    
    it('executes operations in closed state', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $result = $breaker->call('test_service', fn() => 'success');
        
        expect($result)->toBe('success');
    });
    
    it('reports service as available when closed', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        expect($breaker->isAvailable('test_service'))->toBeTrue();
    });
    
    it('can be force opened', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $breaker->forceOpen('test_service');
        
        expect($breaker->getState('test_service'))->toBe(CircuitBreaker::STATE_OPEN);
    });
    
    it('can be force closed', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $breaker->forceOpen('test_service');
        $breaker->forceClose('test_service');
        
        expect($breaker->getState('test_service'))->toBe(CircuitBreaker::STATE_CLOSED);
    });
    
    it('uses fallback when circuit is open', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $breaker->forceOpen('test_service');
        
        $result = $breaker->call(
            'test_service',
            fn() => 'main',
            fn() => 'fallback'
        );
        
        expect($result)->toBe('fallback');
    });
    
    it('throws exception when open and no fallback', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $breaker->forceOpen('test_service');
        
        expect(fn() => $breaker->call('test_service', fn() => 'test'))
            ->toThrow(CircuitBreakerOpenException::class);
    });
    
    it('provides statistics', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $stats = $breaker->getStats('test_service');
        
        expect($stats)->toBeArray();
        expect($stats)->toHaveKey('state');
        expect($stats)->toHaveKey('failures');
        expect($stats)->toHaveKey('options');
    });
    
    it('accepts per-service configuration', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'api_service' => [
                'failure_threshold' => 10,
                'timeout' => 120,
            ],
        ]);
        
        $stats = $breaker->getStats('api_service');
        
        expect($stats['options']['failure_threshold'])->toBe(10);
        expect($stats['options']['timeout'])->toBe(120);
    });
    
    it('uses fallback on operation failure', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $result = $breaker->call(
            'failing_service',
            fn() => throw new \RuntimeException('Service error'),
            fn() => 'fallback_value'
        );
        
        expect($result)->toBe('fallback_value');
    });
    
    it('opens circuit after reaching failure threshold', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => ['failure_threshold' => 3],
        ]);
        
        // Fail 3 times
        for ($i = 0; $i < 3; $i++) {
            try {
                $breaker->call('test_service', fn() => throw new \RuntimeException('Error'));
            } catch (\RuntimeException) {
                // Expected
            }
        }
        
        expect($breaker->getState('test_service'))->toBe(CircuitBreaker::STATE_OPEN);
    });
    
    it('resets failure count on success', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => ['failure_threshold' => 3],
        ]);
        
        // Fail twice
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->call('test_service', fn() => throw new \RuntimeException('Error'));
            } catch (\RuntimeException) {
                // Expected
            }
        }
        
        // Success should reset counter
        $breaker->call('test_service', fn() => 'success');
        
        $stats = $breaker->getStats('test_service');
        expect($stats['failures'])->toBe(0);
    });
    
    it('transitions to half-open after timeout', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => ['timeout' => 0], // Immediate timeout for test
        ]);
        
        $breaker->forceOpen('test_service');
        
        // Transition should happen on next call
        expect($breaker->isAvailable('test_service'))->toBeTrue();
    });
    
    it('returns to closed state after successes in half-open', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => [
                'timeout' => 0,
                'success_threshold' => 2,
            ],
        ]);
        
        $breaker->forceOpen('test_service');
        
        // First success puts in half-open
        $breaker->call('test_service', fn() => 'success');
        $breaker->call('test_service', fn() => 'success');
        
        expect($breaker->getState('test_service'))->toBe(CircuitBreaker::STATE_CLOSED);
    });
    
    it('returns to open state on failure in half-open', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => ['timeout' => 0],
        ]);
        
        $breaker->forceOpen('test_service');
        
        // Trigger half-open then fail
        try {
            $breaker->call('test_service', fn() => throw new \RuntimeException('Error'));
        } catch (\RuntimeException) {
            // Expected
        }
        
        expect($breaker->getState('test_service'))->toBe(CircuitBreaker::STATE_OPEN);
    });
    
    it('reports unavailable when open and timeout not expired', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => ['timeout' => 3600], // Long timeout
        ]);
        
        $breaker->forceOpen('test_service');
        
        expect($breaker->isAvailable('test_service'))->toBeFalse();
    });
    
    it('reports available in half-open state', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => ['timeout' => 0],
        ]);
        
        $breaker->forceOpen('test_service');
        
        // Trigger transition to half-open
        $breaker->call('test_service', fn() => 'test');
        
        expect($breaker->isAvailable('test_service'))->toBeTrue();
    });
    
    it('rethrows original exception when no fallback', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        expect(fn() => $breaker->call('test_service', fn() => throw new \InvalidArgumentException('Specific error')))
            ->toThrow(\InvalidArgumentException::class, 'Specific error');
    });
    
    it('uses fallback when fallback returns value on exception', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $exception = new \RuntimeException('Test error');
        
        $result = $breaker->call(
            'test_service',
            fn() => throw $exception,
            fn($e) => 'Handled: ' . $e->getMessage()
        );
        
        expect($result)->toBe('Handled: Test error');
    });
    
    it('rethrows original exception when fallback also fails', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        expect(fn() => $breaker->call(
            'test_service',
            fn() => throw new \RuntimeException('Main error'),
            fn() => throw new \Exception('Fallback error')
        ))->toThrow(\RuntimeException::class, 'Main error');
    });
    
    it('uses default options for unknown service', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'known_service' => ['failure_threshold' => 10],
        ]);
        
        $stats = $breaker->getStats('unknown_service');
        
        expect($stats['options']['failure_threshold'])->toBe(5); // Default
    });
    
    it('tracks half-open successes', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => [
                'timeout' => 0,
                'success_threshold' => 3,
            ],
        ]);
        
        $breaker->forceOpen('test_service');
        
        // First success in half-open
        $breaker->call('test_service', fn() => 'success');
        
        $stats = $breaker->getStats('test_service');
        expect($stats['half_open_successes'])->toBe(1);
    });
    
    it('has open time after opening', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $breaker->forceOpen('test_service');
        
        $stats = $breaker->getStats('test_service');
        expect($stats['open_time'])->not->toBeNull();
    });
    
    it('clears open time on force close', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache);
        
        $breaker->forceOpen('test_service');
        $breaker->forceClose('test_service');
        
        $stats = $breaker->getStats('test_service');
        expect($stats['open_time'])->toBeNull();
    });
    
    it('reports available when open but should attempt reset', function () {
        $cache = new ArrayStore();
        $breaker = new CircuitBreaker($cache, [
            'test_service' => ['timeout' => 0],
        ]);
        
        $breaker->forceOpen('test_service');
        
        // Should be available because timeout expired immediately
        expect($breaker->isAvailable('test_service'))->toBeTrue();
    });
});
