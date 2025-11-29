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
namespace Tests\Unit\Security;

use App\Core\Security\Sanitizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    #[Test]
    public function html_escapes_special_characters(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', Sanitizer::html('<script>alert(1)</script>'));
        $this->assertSame('&quot;test&quot;', Sanitizer::html('"test"'));
        $this->assertStringContainsString('test', Sanitizer::html("'test'"));
        $this->assertSame('&amp;', Sanitizer::html('&'));
    }

    #[Test]
    public function attr_escapes_for_html_attributes(): void
    {
        $this->assertSame('&quot;onclick=alert(1)&quot;', Sanitizer::attr('"onclick=alert(1)"'));
        $result = Sanitizer::attr("'test'");
        $this->assertTrue(str_contains($result, 'test'));
    }

    #[Test]
    public function js_escapes_for_javascript(): void
    {
        $result = Sanitizer::js('<script>');
        $this->assertStringContainsString('\\u003C', $result);
        
        $result = Sanitizer::js("test'value");
        $this->assertIsString($result);
    }

    #[Test]
    public function url_sanitizes_url(): void
    {
        $this->assertSame('https://example.com/path', Sanitizer::url('https://example.com/path'));
        // FILTER_SANITIZE_URL removes invalid chars but keeps the scheme
        $result = Sanitizer::url("javascript:alert('xss')");
        $this->assertIsString($result);
    }

    #[Test]
    public function email_sanitizes_email(): void
    {
        $this->assertSame('test@example.com', Sanitizer::email('test@example.com'));
        // FILTER_SANITIZE_EMAIL removes invalid email chars
        $result = Sanitizer::email('test@example.com<script>');
        $this->assertStringContainsString('test@example.com', $result);
    }

    #[Test]
    public function strip_removes_html_tags(): void
    {
        $this->assertSame('Hello World', Sanitizer::strip('<p>Hello <b>World</b></p>'));
        $this->assertSame('alert(1)', Sanitizer::strip('<script>alert(1)</script>'));
    }

    #[Test]
    public function int_sanitizes_to_integer(): void
    {
        $this->assertSame(123, Sanitizer::int('123'));
        $this->assertSame(123, Sanitizer::int('123abc'));
        $this->assertSame(-45, Sanitizer::int('-45'));
        $this->assertSame(0, Sanitizer::int('abc'));
    }

    #[Test]
    public function float_sanitizes_to_float(): void
    {
        $this->assertSame(123.45, Sanitizer::float('123.45'));
        $this->assertSame(123.45, Sanitizer::float('123.45abc'));
        $this->assertSame(-45.67, Sanitizer::float('-45.67'));
    }

    #[Test]
    public function alphanum_removes_non_alphanumeric(): void
    {
        $this->assertSame('abc123', Sanitizer::alphanum('abc123'));
        $this->assertSame('abc123test', Sanitizer::alphanum('abc-123_test!@#'));
        $this->assertSame('', Sanitizer::alphanum('!@#$%'));
    }

    #[Test]
    public function slug_creates_url_friendly_string(): void
    {
        $this->assertSame('hello-world', Sanitizer::slug('Hello World'));
        $this->assertSame('test-123', Sanitizer::slug('Test 123'));
    }

    #[Test]
    public function null_bytes_removes_null_characters(): void
    {
        $this->assertSame('test', Sanitizer::nullBytes("te\0st"));
        $this->assertSame('abc', Sanitizer::nullBytes("a\0b\0c"));
    }

    #[Test]
    public function path_prevents_directory_traversal(): void
    {
        $this->assertSame('path/to/file', Sanitizer::path('path/to/file'));
        $this->assertSame('path/file', Sanitizer::path('../path/file'));
        $this->assertSame('path/file', Sanitizer::path('..\\path\\file'));
        $this->assertSame('path/file', Sanitizer::path('path/../file'));
    }

    #[Test]
    public function path_normalizes_slashes(): void
    {
        $this->assertSame('path/to/file', Sanitizer::path('path\\to\\file'));
        $this->assertSame('path/to/file', Sanitizer::path('path//to//file'));
    }

    #[Test]
    public function filename_sanitizes_filename(): void
    {
        $this->assertSame('test.txt', Sanitizer::filename('test.txt'));
        $this->assertSame('test.txt', Sanitizer::filename('/path/to/test.txt'));
        // < and > are removed, leaving 'script'
        $result = Sanitizer::filename('test<script>.txt');
        $this->assertStringNotContainsString('<', $result);
        $this->assertSame('test_file.txt', Sanitizer::filename('test_file.txt'));
    }

    #[Test]
    public function array_sanitizes_array_values(): void
    {
        $input = ['<script>alert(1)</script>', 'normal'];
        $result = Sanitizer::array($input);
        
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $result[0]);
        $this->assertSame('normal', $result[1]);
    }

    #[Test]
    public function array_sanitizes_nested_arrays(): void
    {
        $input = [
            'level1' => '<b>test</b>',
            'nested' => [
                'level2' => '<script>alert(1)</script>',
            ],
        ];
        
        $result = Sanitizer::array($input);
        
        $this->assertSame('&lt;b&gt;test&lt;/b&gt;', $result['level1']);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $result['nested']['level2']);
    }

    #[Test]
    public function array_preserves_non_string_values(): void
    {
        $input = ['string' => '<b>test</b>', 'int' => 123, 'bool' => true];
        $result = Sanitizer::array($input);
        
        $this->assertSame(123, $result['int']);
        $this->assertTrue($result['bool']);
    }

    #[Test]
    public function array_uses_specified_method(): void
    {
        $input = ['<p>Hello</p>'];
        $result = Sanitizer::array($input, 'strip');
        
        $this->assertSame('Hello', $result[0]);
    }
}
