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

use App\Core\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    #[Test]
    public function it_normalizes_uri(): void
    {
        $this->assertEquals('/', Str::normalizeUri(''));
        $this->assertEquals('/', Str::normalizeUri('/'));
        $this->assertEquals('/users', Str::normalizeUri('users'));
        $this->assertEquals('/users', Str::normalizeUri('/users'));
        $this->assertEquals('/users', Str::normalizeUri('/users/'));
        $this->assertEquals('/users/123', Str::normalizeUri('users/123/'));
    }

    #[Test]
    public function it_joins_uri_segments(): void
    {
        $this->assertEquals('/api/users', Str::joinUri('api', 'users'));
        $this->assertEquals('/api/v1/users', Str::joinUri('/api/', '/v1/', '/users/'));
        $this->assertEquals('/users', Str::joinUri('', 'users'));
        $this->assertEquals('/api', Str::joinUri('api', ''));
    }

    #[Test]
    public function it_converts_header_to_http_format(): void
    {
        $this->assertEquals('Content-Type', Str::headerToHttpFormat('content-type'));
        $this->assertEquals('X-Custom-Header', Str::headerToHttpFormat('x-custom-header'));
        $this->assertEquals('Accept', Str::headerToHttpFormat('ACCEPT'));
    }

    #[Test]
    public function it_extracts_header_from_server_key(): void
    {
        $this->assertEquals('ACCEPT', Str::serverKeyToHeader('HTTP_ACCEPT'));
        $this->assertEquals('CONTENT-TYPE', Str::serverKeyToHeader('CONTENT_TYPE'));
        $this->assertEquals('X-CUSTOM-HEADER', Str::serverKeyToHeader('HTTP_X_CUSTOM_HEADER'));
    }

    #[Test]
    public function it_checks_contains_any(): void
    {
        $this->assertTrue(Str::containsAny('application/json', ['/json', '+json']));
        $this->assertTrue(Str::containsAny('application/xml+json', ['/json', '+json']));
        $this->assertFalse(Str::containsAny('text/html', ['/json', '+json']));
        $this->assertFalse(Str::containsAny('text/html', []));
    }

    #[Test]
    public function it_stringifies_values(): void
    {
        $this->assertEquals('hello', Str::stringify('hello'));
        $this->assertEquals('123', Str::stringify(123));
        $this->assertEquals('1', Str::stringify(true));
        $this->assertEquals('', Str::stringify(null));
        $this->assertEquals('["a","b"]', Str::stringify(['a', 'b']));
        
        // Test object with __toString
        $obj = new class {
            public function __toString(): string { return 'object-string'; }
        };
        $this->assertEquals('object-string', Str::stringify($obj));
        
        // Test object without __toString
        $plainObj = new \stdClass();
        $this->assertEquals('', Str::stringify($plainObj));
    }

    #[Test]
    public function it_converts_to_boolean(): void
    {
        $this->assertTrue(Str::toBoolean('true'));
        $this->assertTrue(Str::toBoolean('1'));
        $this->assertTrue(Str::toBoolean('yes'));
        $this->assertTrue(Str::toBoolean('on'));
        $this->assertTrue(Str::toBoolean(true));
        
        $this->assertFalse(Str::toBoolean('false'));
        $this->assertFalse(Str::toBoolean('0'));
        $this->assertFalse(Str::toBoolean('no'));
        $this->assertFalse(Str::toBoolean(''));
        $this->assertFalse(Str::toBoolean(false));
        
        // Non-string, non-bool values
        $this->assertTrue(Str::toBoolean(1));
        $this->assertFalse(Str::toBoolean(0));
    }

    #[Test]
    public function it_converts_to_studly_case(): void
    {
        $this->assertEquals('FooBar', Str::studly('foo_bar'));
        $this->assertEquals('FooBar', Str::studly('foo-bar'));
        $this->assertEquals('FooBar', Str::studly('foo bar'));
        $this->assertEquals('Foobar', Str::studly('foobar'));
    }

    #[Test]
    public function it_converts_to_camel_case(): void
    {
        $this->assertEquals('fooBar', Str::camel('foo_bar'));
        $this->assertEquals('fooBar', Str::camel('foo-bar'));
        $this->assertEquals('fooBar', Str::camel('FooBar'));
    }

    #[Test]
    public function it_converts_to_snake_case(): void
    {
        $this->assertEquals('foo_bar', Str::snake('FooBar'));
        $this->assertEquals('foo_bar', Str::snake('fooBar'));
        $this->assertEquals('foo_bar_baz', Str::snake('FooBarBaz'));
    }

    #[Test]
    public function it_converts_to_kebab_case(): void
    {
        $this->assertEquals('foo-bar', Str::kebab('FooBar'));
        $this->assertEquals('foo-bar', Str::kebab('fooBar'));
    }

    #[Test]
    public function it_gets_class_basename(): void
    {
        $this->assertEquals('StrTest', Str::classBasename(self::class));
        $this->assertEquals('StrTest', Str::classBasename($this));
        $this->assertEquals('Str', Str::classBasename(Str::class));
    }

    #[Test]
    public function it_generates_slug(): void
    {
        $this->assertEquals('hello-world', Str::slug('Hello World'));
        $this->assertEquals('foo-bar', Str::slug('Foo Bar!'));
        $this->assertEquals('test-123', Str::slug('test 123'));
    }

    #[Test]
    public function it_limits_string_length(): void
    {
        $this->assertEquals('Hello...', Str::limit('Hello World', 5));
        $this->assertEquals('Hello World', Str::limit('Hello World', 20));
        $this->assertEquals('Hello--', Str::limit('Hello World', 5, '--'));
    }

    #[Test]
    public function it_generates_random_string(): void
    {
        $random = Str::random(16);
        $this->assertEquals(16, strlen($random));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $random);
        
        // Different calls should produce different results
        $this->assertNotEquals(Str::random(16), Str::random(16));
    }

    #[Test]
    public function it_checks_starts_with_any(): void
    {
        $this->assertTrue(Str::startsWithAny('/api/users', ['/api', '/web']));
        $this->assertTrue(Str::startsWithAny('/web/home', ['/api', '/web']));
        $this->assertFalse(Str::startsWithAny('/admin', ['/api', '/web']));
    }

    #[Test]
    public function it_checks_ends_with_any(): void
    {
        $this->assertTrue(Str::endsWithAny('file.php', ['.php', '.html']));
        $this->assertTrue(Str::endsWithAny('page.html', ['.php', '.html']));
        $this->assertFalse(Str::endsWithAny('file.js', ['.php', '.html']));
    }
}
