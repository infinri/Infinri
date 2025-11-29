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
namespace Tests\Unit\Config;

use App\Core\Config\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config([
            'app' => [
                'name' => 'Infinri',
                'env' => 'testing',
                'debug' => true,
            ],
            'database' => [
                'default' => 'pgsql',
                'connections' => [
                    'pgsql' => [
                        'host' => 'localhost',
                        'port' => 5432,
                    ],
                ],
            ],
            'simple' => 'value',
        ]);
    }

    #[Test]
    public function it_can_get_simple_value(): void
    {
        $this->assertEquals('value', $this->config->get('simple'));
    }

    #[Test]
    public function it_can_get_nested_array(): void
    {
        $app = $this->config->get('app');
        
        $this->assertIsArray($app);
        $this->assertEquals('Infinri', $app['name']);
    }

    #[Test]
    public function it_can_get_value_using_dot_notation(): void
    {
        $this->assertEquals('Infinri', $this->config->get('app.name'));
        $this->assertEquals('testing', $this->config->get('app.env'));
    }

    #[Test]
    public function it_can_get_deeply_nested_value(): void
    {
        $this->assertEquals('localhost', $this->config->get('database.connections.pgsql.host'));
        $this->assertEquals(5432, $this->config->get('database.connections.pgsql.port'));
    }

    #[Test]
    public function it_returns_default_for_missing_key(): void
    {
        $this->assertNull($this->config->get('nonexistent'));
        $this->assertEquals('default', $this->config->get('nonexistent', 'default'));
    }

    #[Test]
    public function it_returns_default_for_missing_nested_key(): void
    {
        $this->assertNull($this->config->get('app.nonexistent'));
        $this->assertEquals('default', $this->config->get('app.nonexistent', 'default'));
    }

    #[Test]
    public function it_can_check_if_key_exists(): void
    {
        $this->assertTrue($this->config->has('app'));
        $this->assertTrue($this->config->has('app.name'));
        $this->assertFalse($this->config->has('nonexistent'));
    }

    #[Test]
    public function it_can_set_simple_value(): void
    {
        $this->config->set('new', 'value');
        
        $this->assertEquals('value', $this->config->get('new'));
    }

    #[Test]
    public function it_can_set_nested_value_using_dot_notation(): void
    {
        $this->config->set('app.timezone', 'UTC');
        
        $this->assertEquals('UTC', $this->config->get('app.timezone'));
    }

    #[Test]
    public function it_can_set_deeply_nested_value(): void
    {
        $this->config->set('database.connections.pgsql.database', 'infinri');
        
        $this->assertEquals('infinri', $this->config->get('database.connections.pgsql.database'));
    }

    #[Test]
    public function it_can_set_multiple_values_at_once(): void
    {
        $this->config->set([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        
        $this->assertEquals('value1', $this->config->get('key1'));
        $this->assertEquals('value2', $this->config->get('key2'));
    }

    #[Test]
    public function it_creates_nested_arrays_when_setting_dot_notation(): void
    {
        $this->config->set('new.nested.key', 'value');
        
        $this->assertEquals('value', $this->config->get('new.nested.key'));
        $this->assertIsArray($this->config->get('new'));
        $this->assertIsArray($this->config->get('new.nested'));
    }

    #[Test]
    public function it_can_get_all_configuration(): void
    {
        $all = $this->config->all();
        
        $this->assertIsArray($all);
        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('database', $all);
        $this->assertArrayHasKey('simple', $all);
    }

    #[Test]
    public function it_can_prepend_value_to_array(): void
    {
        $this->config->set('list', ['b', 'c']);
        
        $this->config->prepend('list', 'a');
        
        $this->assertEquals(['a', 'b', 'c'], $this->config->get('list'));
    }

    #[Test]
    public function it_can_push_value_to_array(): void
    {
        $this->config->set('list', ['a', 'b']);
        
        $this->config->push('list', 'c');
        
        $this->assertEquals(['a', 'b', 'c'], $this->config->get('list'));
    }

    #[Test]
    public function it_creates_array_when_prepending_to_nonexistent_key(): void
    {
        $this->config->prepend('newlist', 'first');
        
        $this->assertEquals(['first'], $this->config->get('newlist'));
    }

    #[Test]
    public function it_creates_array_when_pushing_to_nonexistent_key(): void
    {
        $this->config->push('newlist', 'first');
        
        $this->assertEquals(['first'], $this->config->get('newlist'));
    }
}
