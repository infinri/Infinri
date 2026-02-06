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

use App\Core\Http\Request;
use App\Core\Http\ParameterBag;
use App\Core\Http\HeaderBag;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_arrays(): void
    {
        $request = new Request(
            ['foo' => 'bar'],
            ['baz' => 'qux'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test'],
            ['cookie' => 'value']
        );
        
        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function it_can_be_created_from_uri(): void
    {
        $request = Request::create('/users/123?filter=active', 'GET');
        
        $this->assertEquals('/users/123', $request->path());
        $this->assertEquals('GET', $request->method());
        $this->assertEquals('active', $request->query('filter'));
    }

    #[Test]
    public function it_returns_correct_method(): void
    {
        $request = Request::create('/test', 'POST');
        
        $this->assertEquals('POST', $request->method());
        $this->assertTrue($request->isMethod('POST'));
        $this->assertTrue($request->isMethod('post'));
        $this->assertFalse($request->isMethod('GET'));
    }

    #[Test]
    public function it_supports_method_override(): void
    {
        $request = new Request(
            [],
            ['_method' => 'PUT'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']
        );
        
        $this->assertEquals('PUT', $request->method());
    }

    #[Test]
    public function it_returns_path_without_query_string(): void
    {
        $request = Request::create('/users/123?foo=bar', 'GET');
        
        $this->assertEquals('/users/123', $request->path());
    }

    #[Test]
    public function it_returns_full_url(): void
    {
        $request = Request::create('/users?filter=active', 'GET', [], [], [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com'
        ]);
        
        $this->assertEquals('https://example.com/users', $request->url());
        $this->assertEquals('https://example.com/users?filter=active', $request->fullUrl());
    }

    #[Test]
    public function it_gets_query_parameters(): void
    {
        $request = Request::create('/test?foo=bar&baz=qux', 'GET');
        
        $this->assertEquals('bar', $request->query('foo'));
        $this->assertEquals('qux', $request->query('baz'));
        $this->assertNull($request->query('missing'));
        $this->assertEquals('default', $request->query('missing', 'default'));
    }

    #[Test]
    public function it_gets_post_parameters(): void
    {
        $request = Request::create('/test', 'POST', ['name' => 'John', 'email' => 'john@example.com']);
        
        $this->assertEquals('John', $request->post('name'));
        $this->assertEquals('john@example.com', $request->post('email'));
    }

    #[Test]
    public function it_gets_input_from_query_or_post(): void
    {
        $request = new Request(
            ['query_param' => 'from_query'],
            ['post_param' => 'from_post'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']
        );
        
        $this->assertEquals('from_query', $request->input('query_param'));
        $this->assertEquals('from_post', $request->input('post_param'));
        $this->assertEquals('default', $request->input('missing', 'default'));
    }

    #[Test]
    public function it_gets_all_input(): void
    {
        $request = new Request(
            ['foo' => 'bar'],
            ['baz' => 'qux'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']
        );
        
        $all = $request->all();
        
        $this->assertEquals('bar', $all['foo']);
        $this->assertEquals('qux', $all['baz']);
    }

    #[Test]
    public function it_gets_only_specified_keys(): void
    {
        $request = new Request(
            ['foo' => 'bar', 'baz' => 'qux', 'extra' => 'value'],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test']
        );
        
        $only = $request->only(['foo', 'baz']);
        
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $only);
        $this->assertArrayNotHasKey('extra', $only);
    }

    #[Test]
    public function it_gets_except_specified_keys(): void
    {
        $request = new Request(
            ['foo' => 'bar', 'baz' => 'qux', 'extra' => 'value'],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test']
        );
        
        $except = $request->except(['extra']);
        
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $except);
    }

    #[Test]
    public function it_checks_if_input_has_key(): void
    {
        $request = new Request(
            ['foo' => 'bar'],
            ['baz' => 'qux'],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test']
        );
        
        $this->assertTrue($request->has('foo'));
        $this->assertTrue($request->has('baz'));
        $this->assertFalse($request->has('missing'));
    }

    #[Test]
    public function it_checks_if_input_is_filled(): void
    {
        $request = new Request(
            ['filled' => 'value', 'empty' => '', 'null' => null],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test']
        );
        
        $this->assertTrue($request->filled('filled'));
        $this->assertFalse($request->filled('empty'));
        $this->assertFalse($request->filled('null'));
        $this->assertFalse($request->filled('missing'));
    }

    #[Test]
    public function it_gets_headers(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_CUSTOM' => 'custom-value'
            ]
        );
        
        $this->assertEquals('application/json', $request->header('accept'));
        $this->assertEquals('custom-value', $request->header('x-custom'));
        $this->assertNull($request->header('missing'));
    }

    #[Test]
    public function it_gets_bearer_token(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTP_AUTHORIZATION' => 'Bearer abc123token'
            ]
        );
        
        $this->assertEquals('abc123token', $request->bearerToken());
    }

    #[Test]
    public function it_returns_null_when_no_bearer_token(): void
    {
        $request = Request::create('/test', 'GET');
        
        $this->assertNull($request->bearerToken());
    }

    #[Test]
    public function it_gets_client_ip(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'REMOTE_ADDR' => '192.168.1.1'
            ]
        );
        
        $this->assertEquals('192.168.1.1', $request->ip());
    }

    #[Test]
    public function it_gets_forwarded_ip(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'REMOTE_ADDR' => '10.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.195, 70.41.3.18'
            ]
        );
        
        $this->assertEquals('203.0.113.195', $request->ip());
    }

    #[Test]
    public function it_gets_user_agent(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTP_USER_AGENT' => 'Mozilla/5.0'
            ]
        );
        
        $this->assertEquals('Mozilla/5.0', $request->userAgent());
    }

    #[Test]
    public function it_checks_if_expects_json(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $this->assertTrue($request->expectsJson());
    }

    #[Test]
    public function it_checks_if_ajax(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        );
        
        $this->assertTrue($request->ajax());
    }

    #[Test]
    public function it_checks_if_secure(): void
    {
        $secureRequest = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTPS' => 'on'
            ]
        );
        
        $insecureRequest = Request::create('/test', 'GET');
        
        $this->assertTrue($secureRequest->secure());
        $this->assertFalse($insecureRequest->secure());
    }

    #[Test]
    public function it_handles_route_parameters(): void
    {
        $request = Request::create('/users/123', 'GET');
        $request->setRouteParameters(['id' => '123']);
        
        $this->assertEquals('123', $request->route('id'));
        $this->assertNull($request->route('missing'));
        $this->assertEquals('default', $request->route('missing', 'default'));
        $this->assertEquals(['id' => '123'], $request->route());
    }

    #[Test]
    public function it_parses_json_content(): void
    {
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/test',
                'CONTENT_TYPE' => 'application/json'
            ],
            [],
            '{"name": "John", "age": 30}'
        );
        
        $this->assertEquals('John', $request->input('name'));
        $this->assertEquals(30, $request->input('age'));
    }

    #[Test]
    public function it_checks_if_content_is_json(): void
    {
        $jsonRequest = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/test',
                'CONTENT_TYPE' => 'application/json'
            ]
        );
        
        $htmlRequest = Request::create('/test', 'GET');
        
        $this->assertTrue($jsonRequest->isJson());
        $this->assertFalse($htmlRequest->isJson());
    }

    #[Test]
    public function it_has_parameter_bag_properties(): void
    {
        $request = Request::create('/test?foo=bar', 'POST', ['baz' => 'qux']);
        
        $this->assertInstanceOf(ParameterBag::class, $request->query);
        $this->assertInstanceOf(ParameterBag::class, $request->request);
        $this->assertInstanceOf(ParameterBag::class, $request->server);
        $this->assertInstanceOf(ParameterBag::class, $request->cookies);
        $this->assertInstanceOf(HeaderBag::class, $request->headers);
    }

    #[Test]
    public function it_normalizes_path(): void
    {
        $request1 = Request::create('', 'GET');
        $request2 = Request::create('/', 'GET');
        $request3 = Request::create('/users/', 'GET');
        
        $this->assertEquals('/', $request1->path());
        $this->assertEquals('/', $request2->path());
        $this->assertEquals('/users/', $request3->path());
    }

    #[Test]
    public function it_checks_accepts_html(): void
    {
        $html = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
        ]);
        $json = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertTrue($html->acceptsHtml());
        $this->assertFalse($json->acceptsHtml());
    }

    #[Test]
    public function it_checks_accepts_any(): void
    {
        $any = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT' => '*/*',
        ]);
        $specific = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertTrue($any->acceptsAny());
        $this->assertFalse($specific->acceptsAny());
    }

    #[Test]
    public function it_gets_preferred_content_type(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT' => 'text/html, application/json',
        ]);
        
        $this->assertEquals('application/json', $request->prefers(['application/xml', 'application/json']));
        $this->assertEquals('text/html', $request->prefers(['text/html', 'application/json']));
        $this->assertNull($request->prefers(['application/xml']));
    }

    #[Test]
    public function it_checks_pjax(): void
    {
        $pjax = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_X_PJAX' => 'true',
        ]);
        $normal = Request::create('/test', 'GET');
        
        $this->assertTrue($pjax->pjax());
        $this->assertFalse($normal->pjax());
    }

    #[Test]
    public function it_checks_wants_json(): void
    {
        $expectsJson = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $sendsJson = new Request([], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/test',
            'CONTENT_TYPE' => 'application/json',
        ]);
        $html = Request::create('/test', 'GET');
        
        $this->assertTrue($expectsJson->wantsJson());
        $this->assertTrue($sendsJson->wantsJson());
        $this->assertFalse($html->wantsJson());
    }

    #[Test]
    public function it_gets_boolean_input(): void
    {
        $request = Request::create('/test', 'GET', [
            'active' => 'true',
            'disabled' => 'false',
            'enabled' => '1',
        ]);
        
        $this->assertTrue($request->boolean('active'));
        $this->assertFalse($request->boolean('disabled'));
        $this->assertTrue($request->boolean('enabled'));
        $this->assertFalse($request->boolean('missing'));
    }

    #[Test]
    public function it_gets_integer_input(): void
    {
        $request = Request::create('/test', 'GET', ['count' => '42', 'invalid' => 'abc']);
        
        $this->assertEquals(42, $request->integer('count'));
        $this->assertEquals(0, $request->integer('invalid'));
        $this->assertEquals(10, $request->integer('missing', 10));
    }

    #[Test]
    public function it_gets_string_input(): void
    {
        $request = Request::create('/test', 'GET', ['name' => 'John', 'count' => 42]);
        
        $this->assertEquals('John', $request->string('name'));
        $this->assertEquals('42', $request->string('count'));
        $this->assertEquals('default', $request->string('missing', 'default'));
    }

    #[Test]
    public function it_returns_raw_content(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/test',
        ], [], 'raw body content');
        
        $this->assertEquals('raw body content', $request->getContent());
    }

    #[Test]
    public function it_generates_url(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/users/123',
            'HTTP_HOST' => 'example.com',
            'HTTPS' => 'on',
        ]);
        
        $this->assertEquals('https://example.com/users/123', $request->url());
    }

    #[Test]
    public function it_generates_full_url(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/search?q=test',
            'QUERY_STRING' => 'q=test',
            'HTTP_HOST' => 'example.com',
        ]);
        
        $this->assertStringContainsString('q=test', $request->fullUrl());
    }

    #[Test]
    public function it_gets_ip_from_forwarded_for(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_X_FORWARDED_FOR' => '192.168.1.1, 10.0.0.1',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        
        $this->assertEquals('192.168.1.1', $request->ip());
    }

    #[Test]
    public function it_gets_ip_from_remote_addr(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'REMOTE_ADDR' => '10.20.30.40',
        ]);
        
        $this->assertEquals('10.20.30.40', $request->ip());
    }

    #[Test]
    public function it_gets_all_headers(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CUSTOM' => 'value',
        ]);
        
        $headers = $request->headers();
        
        $this->assertArrayHasKey('accept', $headers);
        $this->assertArrayHasKey('x-custom', $headers);
    }

    #[Test]
    public function it_checks_secure_connection(): void
    {
        $secure = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTPS' => 'on',
        ]);
        
        $insecure = Request::create('/test', 'GET');
        
        $this->assertTrue($secure->secure());
        $this->assertFalse($insecure->secure());
    }

}
