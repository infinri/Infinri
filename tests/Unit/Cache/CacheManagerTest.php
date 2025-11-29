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
namespace Tests\Unit\Cache;

use App\Core\Cache\CacheManager;
use App\Core\Cache\FileStore;
use App\Core\Cache\ArrayStore;
use App\Core\Contracts\Cache\CacheInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cache_manager_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/cache', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \FilesystemIterator($dir);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($dir);
    }

    #[Test]
    public function constructor_sets_default_store(): void
    {
        $manager = new CacheManager(['default' => 'array'], $this->tempDir);
        
        $this->assertInstanceOf(CacheManager::class, $manager);
    }

    #[Test]
    public function get_pool_names_returns_pools(): void
    {
        $manager = new CacheManager([], $this->tempDir);
        
        $pools = $manager->getPoolNames();
        
        $this->assertContains('runtime', $pools);
        $this->assertContains('views', $pools);
        $this->assertContains('data', $pools);
    }

    #[Test]
    public function pool_returns_cache_interface(): void
    {
        $manager = new CacheManager([], $this->tempDir);
        
        $pool = $manager->pool('runtime');
        
        $this->assertInstanceOf(CacheInterface::class, $pool);
    }

    #[Test]
    public function pool_throws_for_invalid_name(): void
    {
        $manager = new CacheManager([], $this->tempDir);
        
        $this->expectException(\InvalidArgumentException::class);
        
        $manager->pool('invalid');
    }

    #[Test]
    public function pool_returns_same_instance(): void
    {
        $manager = new CacheManager([], $this->tempDir);
        
        $pool1 = $manager->pool('runtime');
        $pool2 = $manager->pool('runtime');
        
        $this->assertSame($pool1, $pool2);
    }

    #[Test]
    public function store_returns_default_when_null(): void
    {
        $manager = new CacheManager(['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]], $this->tempDir);
        
        $store = $manager->store();
        
        $this->assertInstanceOf(CacheInterface::class, $store);
    }

    #[Test]
    public function store_returns_named_store(): void
    {
        $manager = new CacheManager([
            'default' => 'file',
            'stores' => [
                'array' => ['driver' => 'array'],
            ],
        ], $this->tempDir);
        
        $store = $manager->store('array');
        
        $this->assertInstanceOf(ArrayStore::class, $store);
    }

    #[Test]
    public function get_returns_value(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $manager->put('key', 'value');
        
        $this->assertSame('value', $manager->get('key'));
    }

    #[Test]
    public function get_returns_default_for_missing(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $this->assertSame('default', $manager->get('missing', 'default'));
    }

    #[Test]
    public function has_returns_true_when_exists(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $manager->put('key', 'value');
        
        $this->assertTrue($manager->has('key'));
    }

    #[Test]
    public function has_returns_false_when_missing(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $this->assertFalse($manager->has('missing'));
    }

    #[Test]
    public function forget_removes_value(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $manager->put('key', 'value');
        $manager->forget('key');
        
        $this->assertFalse($manager->has('key'));
    }

    #[Test]
    public function flush_clears_all(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $manager->put('key1', 'value1');
        $manager->put('key2', 'value2');
        $manager->flush();
        
        $this->assertFalse($manager->has('key1'));
        $this->assertFalse($manager->has('key2'));
    }

    #[Test]
    public function clear_pool_clears_specific_pool(): void
    {
        $manager = new CacheManager([], $this->tempDir);
        
        // Create a pool directory
        $poolDir = $this->tempDir . '/var/cache/runtime';
        mkdir($poolDir, 0755, true);
        file_put_contents($poolDir . '/test.cache', 'test');
        
        $result = $manager->clearPool('runtime');
        
        $this->assertTrue($result);
    }

    #[Test]
    public function clear_all_pools_clears_all(): void
    {
        $manager = new CacheManager([], $this->tempDir);
        
        // Create pool directories
        foreach (['runtime', 'views'] as $pool) {
            $poolDir = $this->tempDir . '/var/cache/' . $pool;
            @mkdir($poolDir, 0755, true);
        }
        
        $results = $manager->clearAllPools();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('runtime', $results);
        $this->assertArrayHasKey('views', $results);
    }

    #[Test]
    public function increment_increments_value(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $manager->put('counter', 5);
        $result = $manager->increment('counter');
        
        $this->assertSame(6, $result);
    }

    #[Test]
    public function decrement_decrements_value(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $manager->put('counter', 5);
        $result = $manager->decrement('counter');
        
        $this->assertSame(4, $result);
    }

    #[Test]
    public function many_returns_multiple_values(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $manager->put('key1', 'value1');
        $manager->put('key2', 'value2');
        
        $result = $manager->many(['key1', 'key2', 'key3']);
        
        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
        $this->assertNull($result['key3']);
    }

    #[Test]
    public function put_many_stores_multiple(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $result = $manager->putMany(['key1' => 'value1', 'key2' => 'value2']);
        
        $this->assertTrue($result);
        $this->assertSame('value1', $manager->get('key1'));
        $this->assertSame('value2', $manager->get('key2'));
    }

    #[Test]
    public function add_stores_only_if_not_exists(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $result1 = $manager->add('add_key', 'value1');
        $result2 = $manager->add('add_key', 'value2');
        
        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertSame('value1', $manager->get('add_key'));
    }

    #[Test]
    public function forever_stores_without_expiry(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $result = $manager->forever('forever_key', 'forever_value');
        
        $this->assertTrue($result);
        $this->assertSame('forever_value', $manager->get('forever_key'));
    }

    #[Test]
    public function remember_returns_cached_or_computes(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $calls = 0;
        $callback = function() use (&$calls) {
            $calls++;
            return 'computed';
        };
        
        $result1 = $manager->remember('remember_key', 60, $callback);
        $result2 = $manager->remember('remember_key', 60, $callback);
        
        $this->assertSame('computed', $result1);
        $this->assertSame('computed', $result2);
        $this->assertSame(1, $calls); // Should only be called once
    }

    #[Test]
    public function remember_forever_returns_cached_or_computes(): void
    {
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => ['array' => ['driver' => 'array']],
        ], $this->tempDir);
        
        $calls = 0;
        $callback = function() use (&$calls) {
            $calls++;
            return 'computed_forever';
        };
        
        $result1 = $manager->rememberForever('remember_forever_key', $callback);
        $result2 = $manager->rememberForever('remember_forever_key', $callback);
        
        $this->assertSame('computed_forever', $result1);
        $this->assertSame('computed_forever', $result2);
        $this->assertSame(1, $calls);
    }
}
