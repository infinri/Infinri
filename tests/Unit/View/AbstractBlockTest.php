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
use App\Core\View\Block\AbstractBlock;

// Create concrete implementation for testing
class TestBlock extends AbstractBlock
{
    private array $testData = [];
    
    public function __construct(array $data = [])
    {
        $this->testData = $data;
    }
    
    public function getData(): array
    {
        return $this->testData;
    }
    
    public function setTestData(array $data): void
    {
        $this->testData = $data;
    }
}

class CacheableTestBlock extends TestBlock
{
    private string $cacheKey;
    
    public function __construct(string $cacheKey, array $data = [])
    {
        parent::__construct($data);
        $this->cacheKey = $cacheKey;
    }
    
    public function getCacheKey(): ?string
    {
        return $this->cacheKey;
    }
    
    public function getCacheTtl(): int
    {
        return 7200;
    }
    
    public function getCacheTags(): array
    {
        return ['block', 'test'];
    }
}

beforeEach(function () {
    $this->block = new TestBlock(['title' => 'Test', 'count' => 42]);
});

// Template tests

test('set template returns self', function () {
    $result = $this->block->setTemplate('Module::path/to/template');
    
    expect($result)->toBe($this->block);
});

test('get template returns set template', function () {
    $this->block->setTemplate('Home::templates/hero');
    
    expect($this->block->getTemplate())->toBe('Home::templates/hero');
});

test('get template returns empty string by default', function () {
    $block = new TestBlock();
    
    expect($block->getTemplate())->toBe('');
});

// Name in layout tests

test('set name in layout returns self', function () {
    $result = $this->block->setNameInLayout('hero.block');
    
    expect($result)->toBe($this->block);
});

test('get name in layout returns set name', function () {
    $this->block->setNameInLayout('main.content');
    
    expect($this->block->getNameInLayout())->toBe('main.content');
});

test('get name in layout returns empty string by default', function () {
    expect($this->block->getNameInLayout())->toBe('');
});

// getData tests

test('get data returns block data', function () {
    $data = $this->block->getData();
    
    expect($data)->toBe(['title' => 'Test', 'count' => 42]);
});

// Cache tests

test('get cache key returns null by default', function () {
    expect($this->block->getCacheKey())->toBeNull();
});

test('get cache ttl returns 3600 by default', function () {
    expect($this->block->getCacheTtl())->toBe(3600);
});

test('get cache tags returns empty array by default', function () {
    expect($this->block->getCacheTags())->toBe([]);
});

test('is cacheable returns false by default', function () {
    expect($this->block->isCacheable())->toBeFalse();
});

test('cacheable block returns true for is cacheable', function () {
    $block = new CacheableTestBlock('my-cache-key', []);
    
    expect($block->isCacheable())->toBeTrue();
});

test('cacheable block returns custom cache key', function () {
    $block = new CacheableTestBlock('my-cache-key', []);
    
    expect($block->getCacheKey())->toBe('my-cache-key');
});

test('cacheable block returns custom ttl', function () {
    $block = new CacheableTestBlock('key', []);
    
    expect($block->getCacheTtl())->toBe(7200);
});

test('cacheable block returns custom tags', function () {
    $block = new CacheableTestBlock('key', []);
    
    expect($block->getCacheTags())->toBe(['block', 'test']);
});

// getPreparedData tests

test('get prepared data returns data from get data', function () {
    $data = $this->block->getPreparedData();
    
    expect($data)->toBe(['title' => 'Test', 'count' => 42]);
});

test('get prepared data caches result', function () {
    // First call
    $data1 = $this->block->getPreparedData();
    
    // Change underlying data
    $this->block->setTestData(['changed' => true]);
    
    // Second call should return cached data
    $data2 = $this->block->getPreparedData();
    
    expect($data2)->toBe($data1);
    expect($data2)->not->toBe(['changed' => true]);
});

// resetData tests

test('reset data returns self', function () {
    $result = $this->block->resetData();
    
    expect($result)->toBe($this->block);
});

test('reset data clears cached data', function () {
    // First call caches
    $this->block->getPreparedData();
    
    // Change underlying data
    $this->block->setTestData(['new' => 'data']);
    
    // Reset and get again
    $this->block->resetData();
    $data = $this->block->getPreparedData();
    
    expect($data)->toBe(['new' => 'data']);
});

// toArray tests

test('to array returns block info', function () {
    $this->block->setNameInLayout('test.block');
    $this->block->setTemplate('Test::template');
    
    $array = $this->block->toArray();
    
    expect($array['name'])->toBe('test.block');
    expect($array['template'])->toBe('Test::template');
    expect($array['class'])->toBe(TestBlock::class);
    expect($array['cacheable'])->toBeFalse();
    expect($array['cache_key'])->toBeNull();
    expect($array['cache_ttl'])->toBe(3600);
});

test('to array shows cacheable info for cacheable block', function () {
    $block = new CacheableTestBlock('my-key', []);
    $block->setNameInLayout('cached.block');
    
    $array = $block->toArray();
    
    expect($array['cacheable'])->toBeTrue();
    expect($array['cache_key'])->toBe('my-key');
    expect($array['cache_ttl'])->toBe(7200);
});
