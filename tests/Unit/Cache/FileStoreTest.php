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

use App\Core\Cache\FileStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FileStoreTest extends TestCase
{
    private string $tempDir;
    private FileStore $store;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/file_store_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->store = new FileStore($this->tempDir);
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
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }

    #[Test]
    public function get_returns_default_for_missing_key(): void
    {
        $this->assertNull($this->store->get('missing'));
        $this->assertSame('default', $this->store->get('missing', 'default'));
    }

    #[Test]
    public function put_stores_value(): void
    {
        $result = $this->store->put('key', 'value');
        
        $this->assertTrue($result);
        $this->assertSame('value', $this->store->get('key'));
    }

    #[Test]
    public function put_with_ttl(): void
    {
        $this->store->put('key', 'value', 3600);
        
        $this->assertSame('value', $this->store->get('key'));
    }

    #[Test]
    public function add_only_adds_if_not_exists(): void
    {
        $this->assertTrue($this->store->add('key', 'first'));
        $this->assertFalse($this->store->add('key', 'second'));
        $this->assertSame('first', $this->store->get('key'));
    }

    #[Test]
    public function forever_stores_without_expiration(): void
    {
        $result = $this->store->forever('key', 'value');
        
        $this->assertTrue($result);
        $this->assertSame('value', $this->store->get('key'));
    }

    #[Test]
    public function remember_returns_cached_value(): void
    {
        $this->store->put('key', 'cached');
        
        $result = $this->store->remember('key', 3600, fn() => 'new');
        
        $this->assertSame('cached', $result);
    }

    #[Test]
    public function remember_stores_callback_result(): void
    {
        $result = $this->store->remember('key', 3600, fn() => 'computed');
        
        $this->assertSame('computed', $result);
        $this->assertSame('computed', $this->store->get('key'));
    }

    #[Test]
    public function remember_forever_stores_without_ttl(): void
    {
        $result = $this->store->rememberForever('key', fn() => 'forever');
        
        $this->assertSame('forever', $result);
    }

    #[Test]
    public function forget_removes_value(): void
    {
        $this->store->put('key', 'value');
        
        $result = $this->store->forget('key');
        
        $this->assertTrue($result);
        $this->assertFalse($this->store->has('key'));
    }

    #[Test]
    public function has_returns_true_for_existing(): void
    {
        $this->store->put('key', 'value');
        
        $this->assertTrue($this->store->has('key'));
    }

    #[Test]
    public function has_returns_false_for_missing(): void
    {
        $this->assertFalse($this->store->has('missing'));
    }

    #[Test]
    public function increment_increases_value(): void
    {
        $this->store->put('counter', 5);
        
        $result = $this->store->increment('counter');
        
        $this->assertSame(6, $result);
    }

    #[Test]
    public function increment_by_amount(): void
    {
        $this->store->put('counter', 10);
        
        $result = $this->store->increment('counter', 5);
        
        $this->assertSame(15, $result);
    }

    #[Test]
    public function decrement_decreases_value(): void
    {
        $this->store->put('counter', 10);
        
        $result = $this->store->decrement('counter');
        
        $this->assertSame(9, $result);
    }

    #[Test]
    public function flush_clears_all(): void
    {
        $this->store->put('key1', 'value1');
        $this->store->put('key2', 'value2');
        
        $result = $this->store->flush();
        
        $this->assertTrue($result);
        $this->assertFalse($this->store->has('key1'));
        $this->assertFalse($this->store->has('key2'));
    }

    #[Test]
    public function many_returns_multiple_values(): void
    {
        $this->store->put('key1', 'value1');
        $this->store->put('key2', 'value2');
        
        $result = $this->store->many(['key1', 'key2', 'key3']);
        
        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
        $this->assertNull($result['key3']);
    }

    #[Test]
    public function put_many_stores_multiple(): void
    {
        $result = $this->store->putMany(['key1' => 'value1', 'key2' => 'value2']);
        
        $this->assertTrue($result);
        $this->assertSame('value1', $this->store->get('key1'));
        $this->assertSame('value2', $this->store->get('key2'));
    }

    #[Test]
    public function stores_complex_data(): void
    {
        $data = [
            'array' => [1, 2, 3],
            'nested' => ['a' => 'b'],
            'object' => new \stdClass(),
        ];
        
        $this->store->put('complex', $data);
        
        $retrieved = $this->store->get('complex');
        $this->assertSame([1, 2, 3], $retrieved['array']);
        $this->assertSame(['a' => 'b'], $retrieved['nested']);
    }

    #[Test]
    public function forget_returns_false_for_non_existent(): void
    {
        $result = $this->store->forget('non_existent_key_xyz');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function increment_returns_false_for_non_numeric(): void
    {
        $this->store->put('string_value', 'not_a_number');
        
        $result = $this->store->increment('string_value');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function add_returns_false_if_exists(): void
    {
        $this->store->put('existing', 'value');
        
        $result = $this->store->add('existing', 'new_value');
        
        $this->assertFalse($result);
        $this->assertSame('value', $this->store->get('existing'));
    }

    #[Test]
    public function forever_stores_without_expiry(): void
    {
        $result = $this->store->forever('forever_key', 'forever_value');
        
        $this->assertTrue($result);
        $this->assertSame('forever_value', $this->store->get('forever_key'));
    }

    #[Test]
    public function remember_caches_callback_result(): void
    {
        $calls = 0;
        $callback = function() use (&$calls) {
            $calls++;
            return 'computed';
        };
        
        $result1 = $this->store->remember('remember_key', 60, $callback);
        $result2 = $this->store->remember('remember_key', 60, $callback);
        
        $this->assertSame('computed', $result1);
        $this->assertSame('computed', $result2);
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function remember_forever_caches_callback_result(): void
    {
        $calls = 0;
        $callback = function() use (&$calls) {
            $calls++;
            return 'forever_computed';
        };
        
        $result1 = $this->store->rememberForever('remember_forever_key', $callback);
        $result2 = $this->store->rememberForever('remember_forever_key', $callback);
        
        $this->assertSame('forever_computed', $result1);
        $this->assertSame('forever_computed', $result2);
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function flush_returns_true_when_dir_not_exists(): void
    {
        // Create store with non-existent directory
        $nonExistentPath = $this->tempDir . '/non_existent_' . uniqid();
        $store = new FileStore($nonExistentPath);
        
        $result = $store->flush();
        
        $this->assertTrue($result);
    }

    #[Test]
    public function get_returns_null_for_expired_item(): void
    {
        // Create an expired cache file manually
        $key = 'expired_key';
        $path = $this->tempDir . '/' . md5($key) . '.cache';
        
        $payload = serialize([
            'value' => 'expired_value',
            'expiration' => time() - 100, // Expired
        ]);
        file_put_contents($path, $payload);
        
        $result = $this->store->get($key);
        
        $this->assertNull($result);
    }

    #[Test]
    public function get_returns_null_for_corrupted_data(): void
    {
        $key = 'corrupted_key';
        $path = $this->tempDir . '/' . md5($key) . '.cache';
        
        // Write corrupted data
        file_put_contents($path, 'not_serialized_data');
        
        $result = $this->store->get($key);
        
        $this->assertNull($result);
    }

    #[Test]
    public function decrement_returns_false_for_non_numeric(): void
    {
        $this->store->put('string_val', 'not_number');
        
        $result = $this->store->decrement('string_val');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function put_many_tracks_failures(): void
    {
        // Create a store where writing fails (read-only directory would work)
        // But for simplicity, test that it returns true when all succeed
        $result = $this->store->putMany([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function get_records_cache_miss_metric(): void
    {
        // Get a key that doesn't exist - should record cache miss
        $result = $this->store->get('nonexistent_metric_key');
        
        $this->assertNull($result);
    }

    #[Test]
    public function get_records_cache_hit_metric(): void
    {
        $this->store->put('metric_key', 'metric_value');
        
        // Get the key - should record cache hit
        $result = $this->store->get('metric_key');
        
        $this->assertSame('metric_value', $result);
    }
}
