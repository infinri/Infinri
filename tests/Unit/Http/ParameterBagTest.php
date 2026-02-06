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
namespace Tests\Unit\Http;

use App\Core\Http\ParameterBag;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParameterBagTest extends TestCase
{
    #[Test]
    public function it_creates_empty_bag(): void
    {
        $bag = new ParameterBag();
        $this->assertEquals([], $bag->all());
        $this->assertEquals(0, $bag->count());
    }

    #[Test]
    public function it_creates_bag_with_parameters(): void
    {
        $bag = new ParameterBag(['foo' => 'bar', 'baz' => 123]);
        $this->assertEquals(['foo' => 'bar', 'baz' => 123], $bag->all());
    }

    #[Test]
    public function it_gets_parameter(): void
    {
        $bag = new ParameterBag(['foo' => 'bar']);
        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertNull($bag->get('missing'));
        $this->assertEquals('default', $bag->get('missing', 'default'));
    }

    #[Test]
    public function it_sets_parameter(): void
    {
        $bag = new ParameterBag();
        $bag->set('foo', 'bar');
        $this->assertEquals('bar', $bag->get('foo'));
    }

    #[Test]
    public function it_checks_has_parameter(): void
    {
        $bag = new ParameterBag(['foo' => 'bar', 'null' => null]);
        $this->assertTrue($bag->has('foo'));
        $this->assertTrue($bag->has('null'));
        $this->assertFalse($bag->has('missing'));
    }

    #[Test]
    public function it_removes_parameter(): void
    {
        $bag = new ParameterBag(['foo' => 'bar']);
        $bag->remove('foo');
        $this->assertFalse($bag->has('foo'));
    }

    #[Test]
    public function it_returns_keys(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2]);
        $this->assertEquals(['a', 'b'], $bag->keys());
    }

    #[Test]
    public function it_replaces_parameters(): void
    {
        $bag = new ParameterBag(['foo' => 'bar']);
        $bag->replace(['baz' => 'qux']);
        $this->assertEquals(['baz' => 'qux'], $bag->all());
    }

    #[Test]
    public function it_adds_parameters(): void
    {
        $bag = new ParameterBag(['foo' => 'bar']);
        $bag->add(['baz' => 'qux']);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $bag->all());
    }

    #[Test]
    public function it_gets_string(): void
    {
        $bag = new ParameterBag(['str' => 'hello', 'num' => 123, 'arr' => []]);
        $this->assertEquals('hello', $bag->getString('str'));
        $this->assertEquals('123', $bag->getString('num'));
        $this->assertEquals('', $bag->getString('arr'));
        $this->assertEquals('default', $bag->getString('missing', 'default'));
    }

    #[Test]
    public function it_gets_int(): void
    {
        $bag = new ParameterBag(['num' => '42', 'str' => 'hello']);
        $this->assertEquals(42, $bag->getInt('num'));
        $this->assertEquals(0, $bag->getInt('str'));
        $this->assertEquals(10, $bag->getInt('missing', 10));
    }

    #[Test]
    public function it_gets_boolean(): void
    {
        $bag = new ParameterBag([
            'true_str' => 'true',
            'false_str' => 'false',
            'one' => '1',
            'zero' => '0',
            'yes' => 'yes',
            'invalid' => 'invalid',
        ]);
        $this->assertTrue($bag->getBoolean('true_str'));
        $this->assertFalse($bag->getBoolean('false_str'));
        $this->assertTrue($bag->getBoolean('one'));
        $this->assertFalse($bag->getBoolean('zero'));
        $this->assertTrue($bag->getBoolean('yes'));
        $this->assertFalse($bag->getBoolean('invalid'));
        $this->assertTrue($bag->getBoolean('missing', true));
    }

    #[Test]
    public function it_is_countable(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertEquals(3, count($bag));
    }

    #[Test]
    public function it_is_iterable(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2]);
        $result = [];
        foreach ($bag as $key => $value) {
            $result[$key] = $value;
        }
        $this->assertEquals(['a' => 1, 'b' => 2], $result);
    }
}
