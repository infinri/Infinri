<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Core\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    #[Test]
    public function it_can_be_created(): void
    {
        $response = new Response('Hello World');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Hello World', $response->getContent());
    }

    #[Test]
    public function it_can_be_created_with_status(): void
    {
        $response = new Response('Not Found', 404);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getStatusText());
    }

    #[Test]
    public function it_can_be_created_with_headers(): void
    {
        $response = new Response('', 200, ['X-Custom' => 'value']);
        
        $this->assertEquals('value', $response->getHeader('x-custom'));
    }

    #[Test]
    public function it_can_set_content(): void
    {
        $response = new Response();
        $response->setContent('New content');
        
        $this->assertEquals('New content', $response->getContent());
    }

    #[Test]
    public function it_can_set_status_code(): void
    {
        $response = new Response();
        $response->setStatusCode(201, 'Created');
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Created', $response->getStatusText());
    }

    #[Test]
    public function it_uses_default_status_text(): void
    {
        $response = new Response();
        $response->setStatusCode(404);
        
        $this->assertEquals('Not Found', $response->getStatusText());
    }

    #[Test]
    public function it_checks_if_successful(): void
    {
        $ok = new Response('', 200);
        $created = new Response('', 201);
        $notFound = new Response('', 404);
        
        $this->assertTrue($ok->isSuccessful());
        $this->assertTrue($created->isSuccessful());
        $this->assertFalse($notFound->isSuccessful());
    }

    #[Test]
    public function it_checks_if_redirect(): void
    {
        $redirect = new Response('', 302);
        $permanent = new Response('', 301);
        $ok = new Response('', 200);
        
        $this->assertTrue($redirect->isRedirect());
        $this->assertTrue($permanent->isRedirect());
        $this->assertFalse($ok->isRedirect());
    }

    #[Test]
    public function it_checks_if_client_error(): void
    {
        $notFound = new Response('', 404);
        $badRequest = new Response('', 400);
        $ok = new Response('', 200);
        
        $this->assertTrue($notFound->isClientError());
        $this->assertTrue($badRequest->isClientError());
        $this->assertFalse($ok->isClientError());
    }

    #[Test]
    public function it_checks_if_server_error(): void
    {
        $internal = new Response('', 500);
        $gateway = new Response('', 502);
        $notFound = new Response('', 404);
        
        $this->assertTrue($internal->isServerError());
        $this->assertTrue($gateway->isServerError());
        $this->assertFalse($notFound->isServerError());
    }

    #[Test]
    public function it_checks_if_ok(): void
    {
        $ok = new Response('', 200);
        $created = new Response('', 201);
        
        $this->assertTrue($ok->isOk());
        $this->assertFalse($created->isOk());
    }

    #[Test]
    public function it_checks_if_not_found(): void
    {
        $notFound = new Response('', 404);
        $ok = new Response('', 200);
        
        $this->assertTrue($notFound->isNotFound());
        $this->assertFalse($ok->isNotFound());
    }

    #[Test]
    public function it_can_set_headers(): void
    {
        $response = new Response();
        $response->header('X-Custom', 'value');
        $response->header('X-Another', 'another');
        
        $this->assertEquals('value', $response->getHeader('x-custom'));
        $this->assertEquals('another', $response->getHeader('x-another'));
    }

    #[Test]
    public function it_can_set_multiple_headers(): void
    {
        $response = new Response();
        $response->withHeaders([
            'X-First' => 'first',
            'X-Second' => 'second'
        ]);
        
        $this->assertEquals('first', $response->getHeader('x-first'));
        $this->assertEquals('second', $response->getHeader('x-second'));
    }

    #[Test]
    public function it_can_get_all_headers(): void
    {
        $response = new Response('', 200, ['X-Custom' => 'value']);
        $response->header('X-Another', 'another');
        
        $headers = $response->getHeaders();
        
        $this->assertArrayHasKey('x-custom', $headers);
        $this->assertArrayHasKey('x-another', $headers);
    }

    #[Test]
    public function it_sets_html_content_type(): void
    {
        $response = new Response();
        $response->asHtml();
        
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeader('content-type'));
    }

    #[Test]
    public function it_sets_text_content_type(): void
    {
        $response = new Response();
        $response->asText();
        
        $this->assertEquals('text/plain; charset=UTF-8', $response->getHeader('content-type'));
    }

    #[Test]
    public function it_sets_cache_headers(): void
    {
        $response = new Response();
        $response->cache(3600, true);
        
        $this->assertEquals('public, max-age=3600', $response->getHeader('cache-control'));
    }

    #[Test]
    public function it_sets_no_cache_headers(): void
    {
        $response = new Response();
        $response->noCache();
        
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeader('cache-control'));
        $this->assertEquals('no-cache', $response->getHeader('pragma'));
    }

    #[Test]
    public function it_can_be_made_statically(): void
    {
        $response = Response::make('Content', 201, ['X-Custom' => 'value']);
        
        $this->assertEquals('Content', $response->getContent());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('value', $response->getHeader('x-custom'));
    }

    #[Test]
    public function it_gets_header_bag(): void
    {
        $response = new Response('', 200, ['X-Custom' => 'value']);
        $bag = $response->getHeaderBag();
        
        $this->assertInstanceOf(\App\Core\Http\HeaderBag::class, $bag);
        $this->assertEquals('value', $bag->get('x-custom'));
    }

    #[Test]
    public function it_sets_and_gets_protocol_version(): void
    {
        $response = new Response();
        $response->setProtocolVersion('2.0');
        
        $this->assertEquals('2.0', $response->getProtocolVersion());
    }

    #[Test]
    public function it_checks_if_informational(): void
    {
        $continue = new Response('', 100);
        $ok = new Response('', 200);
        
        $this->assertTrue($continue->isInformational());
        $this->assertFalse($ok->isInformational());
    }

    #[Test]
    public function it_checks_if_empty(): void
    {
        $noContent = new Response('', 204);
        $notModified = new Response('', 304);
        $ok = new Response('', 200);
        
        $this->assertTrue($noContent->isEmpty());
        $this->assertTrue($notModified->isEmpty());
        $this->assertFalse($ok->isEmpty());
    }

    #[Test]
    public function it_sets_private_cache(): void
    {
        $response = new Response();
        $response->cache(3600, false);
        
        $this->assertEquals('private, max-age=3600', $response->getHeader('cache-control'));
    }

    #[Test]
    public function it_sends_content_to_output(): void
    {
        $response = new Response('Hello World');
        
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();
        
        $this->assertEquals('Hello World', $output);
    }

    #[Test]
    public function it_sends_full_response(): void
    {
        $response = new Response('Test Content', 200, ['X-Test' => 'value']);
        
        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        
        $this->assertEquals('Test Content', $output);
        $this->assertSame($response, $result); // Returns self for chaining
    }

    #[Test]
    public function it_does_not_resend_headers(): void
    {
        $response = new Response('Content');
        
        // First call sets headersSent flag
        ob_start();
        $response->sendHeaders();
        ob_get_clean();
        
        // Second call should be a no-op (returns early)
        ob_start();
        $result = $response->sendHeaders();
        ob_get_clean();
        
        $this->assertSame($response, $result);
    }
}
