<?php declare(strict_types=1);


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

use App\Core\Cache\ArrayStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArrayStoreTest extends TestCase
{
    #[Test]
    public function get_returns_default_for_missing_key(): void
    {
        $store = new ArrayStore();
        
        $this->assertNull($store->get('missing'));
        $this->assertSame('default', $store->get('missing', 'default'));
    }

    #[Test]
    public function put_stores_value(): void
    {
        $store = new ArrayStore();
        
        $result = $store->put('key', 'value');
        
        $this->assertTrue($result);
        $this->assertSame('value', $store->get('key'));
    }

    #[Test]
    public function put_with_ttl(): void
    {
        $store = new ArrayStore();
        
        $store->put('key', 'value', 3600);
        
        $this->assertSame('value', $store->get('key'));
    }

    #[Test]
    public function add_only_adds_if_not_exists(): void
    {
        $store = new ArrayStore();
        
        $this->assertTrue($store->add('key', 'first'));
        $this->assertFalse($store->add('key', 'second'));
        $this->assertSame('first', $store->get('key'));
    }

    #[Test]
    public function forever_stores_without_expiration(): void
    {
        $store = new ArrayStore();
        
        $result = $store->forever('key', 'value');
        
        $this->assertTrue($result);
        $this->assertSame('value', $store->get('key'));
    }

    #[Test]
    public function remember_returns_cached_value(): void
    {
        $store = new ArrayStore();
        $store->put('key', 'cached');
        
        $result = $store->remember('key', 3600, fn() => 'new');
        
        $this->assertSame('cached', $result);
    }

    #[Test]
    public function remember_stores_callback_result(): void
    {
        $store = new ArrayStore();
        
        $result = $store->remember('key', 3600, fn() => 'computed');
        
        $this->assertSame('computed', $result);
        $this->assertSame('computed', $store->get('key'));
    }

    #[Test]
    public function remember_forever_stores_without_ttl(): void
    {
        $store = new ArrayStore();
        
        $result = $store->rememberForever('key', fn() => 'forever');
        
        $this->assertSame('forever', $result);
    }

    #[Test]
    public function forget_removes_value(): void
    {
        $store = new ArrayStore();
        $store->put('key', 'value');
        
        $result = $store->forget('key');
        
        $this->assertTrue($result);
        $this->assertFalse($store->has('key'));
    }

    #[Test]
    public function has_returns_true_for_existing(): void
    {
        $store = new ArrayStore();
        $store->put('key', 'value');
        
        $this->assertTrue($store->has('key'));
    }

    #[Test]
    public function has_returns_false_for_missing(): void
    {
        $store = new ArrayStore();
        
        $this->assertFalse($store->has('missing'));
    }

    #[Test]
    public function increment_increases_value(): void
    {
        $store = new ArrayStore();
        $store->put('counter', 5);
        
        $result = $store->increment('counter');
        
        $this->assertSame(6, $result);
    }

    #[Test]
    public function increment_by_amount(): void
    {
        $store = new ArrayStore();
        $store->put('counter', 10);
        
        $result = $store->increment('counter', 5);
        
        $this->assertSame(15, $result);
    }

    #[Test]
    public function increment_returns_false_for_non_numeric(): void
    {
        $store = new ArrayStore();
        $store->put('key', 'string');
        
        $result = $store->increment('key');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function decrement_decreases_value(): void
    {
        $store = new ArrayStore();
        $store->put('counter', 10);
        
        $result = $store->decrement('counter');
        
        $this->assertSame(9, $result);
    }

    #[Test]
    public function flush_clears_all(): void
    {
        $store = new ArrayStore();
        $store->put('key1', 'value1');
        $store->put('key2', 'value2');
        
        $result = $store->flush();
        
        $this->assertTrue($result);
        $this->assertFalse($store->has('key1'));
        $this->assertFalse($store->has('key2'));
    }

    #[Test]
    public function many_returns_multiple_values(): void
    {
        $store = new ArrayStore();
        $store->put('key1', 'value1');
        $store->put('key2', 'value2');
        
        $result = $store->many(['key1', 'key2', 'key3']);
        
        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
        $this->assertNull($result['key3']);
    }

    #[Test]
    public function put_many_stores_multiple(): void
    {
        $store = new ArrayStore();
        
        $result = $store->putMany(['key1' => 'value1', 'key2' => 'value2']);
        
        $this->assertTrue($result);
        $this->assertSame('value1', $store->get('key1'));
        $this->assertSame('value2', $store->get('key2'));
    }

    #[Test]
    public function has_returns_false_for_expired_item(): void
    {
        $store = new ArrayStore();
        
        // Use reflection to directly set an expired item
        $reflection = new \ReflectionClass($store);
        $property = $reflection->getProperty('storage');
        $property->setAccessible(true);
        $property->setValue($store, [
            'expired_key' => [
                'value' => 'test',
                'expiration' => time() - 100, // Already expired
            ]
        ]);
        
        $this->assertFalse($store->has('expired_key'));
    }
}
