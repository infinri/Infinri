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
namespace Tests\Unit\Support;

use App\Core\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    #[Test]
    public function it_gets_value_with_dot_notation(): void
    {
        $array = [
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => ['host' => 'localhost']
                ]
            ]
        ];
        
        $this->assertEquals('mysql', Arr::get($array, 'database.default'));
        $this->assertEquals('localhost', Arr::get($array, 'database.connections.mysql.host'));
        $this->assertNull(Arr::get($array, 'nonexistent'));
        $this->assertEquals('default', Arr::get($array, 'nonexistent', 'default'));
    }

    #[Test]
    public function it_gets_top_level_value(): void
    {
        $array = ['key' => 'value'];
        
        $this->assertEquals('value', Arr::get($array, 'key'));
    }

    #[Test]
    public function it_sets_value_with_dot_notation(): void
    {
        $array = [];
        
        Arr::set($array, 'database.default', 'mysql');
        
        $this->assertEquals(['database' => ['default' => 'mysql']], $array);
    }

    #[Test]
    public function it_sets_top_level_value(): void
    {
        $array = [];
        
        Arr::set($array, 'key', 'value');
        
        $this->assertEquals(['key' => 'value'], $array);
    }

    #[Test]
    public function it_checks_key_exists_with_dot_notation(): void
    {
        $array = [
            'database' => [
                'default' => 'mysql'
            ]
        ];
        
        $this->assertTrue(Arr::has($array, 'database'));
        $this->assertTrue(Arr::has($array, 'database.default'));
        $this->assertFalse(Arr::has($array, 'database.connections'));
        $this->assertFalse(Arr::has($array, 'nonexistent'));
    }

    #[Test]
    public function it_flattens_array(): void
    {
        $array = [1, [2, 3], [4, [5, 6]]];
        
        $this->assertEquals([1, 2, 3, 4, 5, 6], Arr::flatten($array));
        $this->assertEquals([1, 2, 3, 4, [5, 6]], Arr::flatten($array, 1));
    }

    #[Test]
    public function it_checks_if_array_is_associative(): void
    {
        $this->assertTrue(Arr::isAssoc(['a' => 1, 'b' => 2]));
        $this->assertFalse(Arr::isAssoc([1, 2, 3]));
        $this->assertFalse(Arr::isAssoc([]));
    }

    #[Test]
    public function it_merges_arrays_recursively(): void
    {
        $array1 = ['a' => ['b' => 1, 'c' => 2]];
        $array2 = ['a' => ['b' => 3, 'd' => 4]];
        
        $expected = ['a' => ['b' => 3, 'c' => 2, 'd' => 4]];
        
        $this->assertEquals($expected, Arr::mergeRecursive($array1, $array2));
    }
}
