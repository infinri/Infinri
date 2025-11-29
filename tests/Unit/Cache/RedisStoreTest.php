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
use App\Core\Cache\RedisStore;
use App\Core\Redis\RedisManager;

describe('RedisStore', function () {
    
    it('can be instantiated', function () {
        $manager = new RedisManager();
        $store = new RedisStore($manager, 'cache', 3600, 'test:');
        
        expect($store)->toBeInstanceOf(RedisStore::class);
    });
    
    it('has correct interface methods', function () {
        $manager = new RedisManager();
        $store = new RedisStore($manager);
        
        expect(method_exists($store, 'get'))->toBeTrue();
        expect(method_exists($store, 'put'))->toBeTrue();
        expect(method_exists($store, 'forget'))->toBeTrue();
        expect(method_exists($store, 'has'))->toBeTrue();
        expect(method_exists($store, 'flush'))->toBeTrue();
        expect(method_exists($store, 'increment'))->toBeTrue();
        expect(method_exists($store, 'decrement'))->toBeTrue();
        expect(method_exists($store, 'many'))->toBeTrue();
        expect(method_exists($store, 'putMany'))->toBeTrue();
        expect(method_exists($store, 'remember'))->toBeTrue();
        expect(method_exists($store, 'lock'))->toBeTrue();
        expect(method_exists($store, 'unlock'))->toBeTrue();
    });
    
    it('has enterprise features', function () {
        $manager = new RedisManager();
        $store = new RedisStore($manager);
        
        // Lock support
        expect(method_exists($store, 'lock'))->toBeTrue();
        expect(method_exists($store, 'unlock'))->toBeTrue();
        
        // TTL inspection
        expect(method_exists($store, 'ttl'))->toBeTrue();
        
        // Pattern clearing
        expect(method_exists($store, 'clearByPattern'))->toBeTrue();
        
        // Statistics
        expect(method_exists($store, 'stats'))->toBeTrue();
    });
});
