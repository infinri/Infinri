<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Core\Http\HeaderBag;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HeaderBagTest extends TestCase
{
    #[Test]
    public function it_creates_empty_bag(): void
    {
        $bag = new HeaderBag();
        $this->assertEquals([], $bag->all());
        $this->assertEquals(0, $bag->count());
    }

    #[Test]
    public function it_creates_bag_with_headers(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'application/json']);
        $this->assertEquals('application/json', $bag->get('content-type'));
    }

    #[Test]
    public function it_normalizes_header_keys(): void
    {
        $bag = new HeaderBag(['CONTENT_TYPE' => 'text/html']);
        $this->assertTrue($bag->has('content-type'));
        $this->assertTrue($bag->has('Content-Type'));
        $this->assertTrue($bag->has('CONTENT_TYPE'));
    }

    #[Test]
    public function it_gets_header(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $this->assertEquals('text/html', $bag->get('accept'));
        $this->assertNull($bag->get('missing'));
        $this->assertEquals('default', $bag->get('missing', 'default'));
    }

    #[Test]
    public function it_sets_header(): void
    {
        $bag = new HeaderBag();
        $bag->set('Content-Type', 'application/json');
        $this->assertEquals('application/json', $bag->get('content-type'));
    }

    #[Test]
    public function it_sets_multiple_values(): void
    {
        $bag = new HeaderBag();
        $bag->set('Accept', ['text/html', 'application/json']);
        $this->assertEquals('text/html', $bag->get('accept'));
        $this->assertEquals([['text/html', 'application/json']], [$bag->all()['accept']]);
    }

    #[Test]
    public function it_appends_values_when_not_replacing(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $bag->set('Accept', 'application/json', false);
        $this->assertEquals(['text/html', 'application/json'], $bag->all()['accept']);
    }

    #[Test]
    public function it_replaces_values_by_default(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $bag->set('Accept', 'application/json');
        $this->assertEquals(['application/json'], $bag->all()['accept']);
    }

    #[Test]
    public function it_removes_header(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $bag->remove('accept');
        $this->assertFalse($bag->has('accept'));
    }

    #[Test]
    public function it_checks_has_header(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $this->assertTrue($bag->has('accept'));
        $this->assertFalse($bag->has('missing'));
    }

    #[Test]
    public function it_returns_keys(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html', 'Content-Type' => 'application/json']);
        $this->assertContains('accept', $bag->keys());
        $this->assertContains('content-type', $bag->keys());
    }

    #[Test]
    public function it_replaces_all_headers(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $bag->replace(['Content-Type' => 'application/json']);
        $this->assertFalse($bag->has('accept'));
        $this->assertTrue($bag->has('content-type'));
    }

    #[Test]
    public function it_adds_headers(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $bag->add(['Content-Type' => 'application/json']);
        $this->assertTrue($bag->has('accept'));
        $this->assertTrue($bag->has('content-type'));
    }

    #[Test]
    public function it_gets_content_type(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'application/json']);
        $this->assertEquals('application/json', $bag->getContentType());
    }

    #[Test]
    public function it_gets_content_length(): void
    {
        $bag = new HeaderBag(['Content-Length' => '1234']);
        $this->assertEquals(1234, $bag->getContentLength());
        
        $bag2 = new HeaderBag();
        $this->assertNull($bag2->getContentLength());
    }

    #[Test]
    public function it_is_countable(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html', 'Content-Type' => 'application/json']);
        $this->assertEquals(2, count($bag));
    }

    #[Test]
    public function it_is_iterable(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html']);
        $result = [];
        foreach ($bag as $key => $values) {
            $result[$key] = $values;
        }
        $this->assertArrayHasKey('accept', $result);
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $bag = new HeaderBag(['Accept' => 'text/html', 'Content-Type' => 'application/json']);
        $string = (string) $bag;
        $this->assertStringContainsString('Accept: text/html', $string);
        $this->assertStringContainsString('Content-Type: application/json', $string);
    }
}
