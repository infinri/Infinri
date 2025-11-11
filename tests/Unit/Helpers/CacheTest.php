<?php declare(strict_types=1);

use App\Helpers\Cache;

describe('Cache Helper', function () {
    beforeEach(function () {
        // Clear cache before each test
        Cache::clear();
    });
    
    describe('remember()', function () {
        it('caches closure results', function () {
            $callCount = 0;
            
            $result1 = Cache::remember('test', function () use (&$callCount) {
                $callCount++;
                return 'expensive-result';
            });
            
            $result2 = Cache::remember('test', function () use (&$callCount) {
                $callCount++;
                return 'expensive-result';
            });
            
            expect($result1)->toBe('expensive-result');
            expect($result2)->toBe('expensive-result');
            expect($callCount)->toBe(1); // Only called once
        });
        
        it('returns different values for different keys', function () {
            $value1 = Cache::remember('key1', fn() => 'value1');
            $value2 = Cache::remember('key2', fn() => 'value2');
            
            expect($value1)->toBe('value1');
            expect($value2)->toBe('value2');
        });
    });
    
    describe('clearPrefix()', function () {
        it('clears keys with prefix', function () {
            Cache::remember('user.1', fn() => 'John');
            Cache::remember('user.2', fn() => 'Jane');
            Cache::remember('product.1', fn() => 'Widget');
            
            Cache::clearPrefix('user.');
            
            // Product should still be cached
            $productCalls = 0;
            Cache::remember('product.1', function () use (&$productCalls) {
                $productCalls++;
                return 'Widget';
            });
            
            expect($productCalls)->toBe(0); // Should be from cache
        });
    });
    
    describe('clear()', function () {
        it('clears all cache', function () {
            Cache::remember('key1', fn() => 'value1');
            Cache::remember('key2', fn() => 'value2');
            
            Cache::clear();
            
            $callCount = 0;
            Cache::remember('key1', function () use (&$callCount) {
                $callCount++;
                return 'new-value';
            });
            
            expect($callCount)->toBe(1); // Should recalculate
        });
    });
    
    describe('stats()', function () {
        it('returns cache statistics', function () {
            Cache::remember('key1', fn() => 'value1');
            Cache::remember('key2', fn() => 'value2');
            
            $stats = Cache::stats();
            
            expect($stats)
                ->toBeArray()
                ->toHaveKey('count')
                ->toHaveKey('keys')
                ->toHaveKey('size');
            
            expect($stats['count'])->toBe(2);
            expect($stats['keys'])->toContain('key1', 'key2');
        });
    });
});
