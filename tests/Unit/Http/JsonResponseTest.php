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
namespace Tests\Unit\Http;

use App\Core\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    #[Test]
    public function it_creates_json_response(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->getHeader('content-type'));
    }

    #[Test]
    public function it_can_set_data(): void
    {
        $response = new JsonResponse();
        $response->setData(['key' => 'value']);
        
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    #[Test]
    public function it_can_get_data(): void
    {
        $data = ['foo' => 'bar'];
        $response = new JsonResponse($data);
        
        $this->assertEquals($data, $response->getData());
    }

    #[Test]
    public function it_creates_success_response(): void
    {
        $response = JsonResponse::success(['id' => 1], 'Created successfully', 201);
        $data = $response->getData();
        
        $this->assertTrue($data['success']);
        $this->assertEquals('Created successfully', $data['message']);
        $this->assertEquals(['id' => 1], $data['data']);
        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function it_creates_error_response(): void
    {
        $response = JsonResponse::error('Validation failed', 422, ['name' => 'Required']);
        $data = $response->getData();
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals(['name' => 'Required'], $data['errors']);
        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function it_enables_pretty_print(): void
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $response->withPrettyPrint();
        
        $this->assertStringContainsString("\n", $response->getContent());
    }

    #[Test]
    public function it_uses_encoding_options(): void
    {
        $response = new JsonResponse(['url' => 'http://example.com'], 200, [], JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('http://example.com', $response->getContent());
    }

    #[Test]
    public function it_can_set_encoding_options(): void
    {
        $response = new JsonResponse(['key' => 'value']);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $response->setData(['key' => 'updated']);
        
        $this->assertStringContainsString("\n", $response->getContent());
    }

    #[Test]
    public function it_can_get_encoding_options(): void
    {
        $options = JSON_UNESCAPED_SLASHES;
        $response = new JsonResponse(['test' => true], 200, [], $options);
        
        // Options are combined with default options
        $this->assertGreaterThan(0, $response->getEncodingOptions());
    }

    #[Test]
    public function it_creates_success_without_message(): void
    {
        $response = JsonResponse::success(['id' => 1]);
        $data = $response->getData();
        
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    #[Test]
    public function it_creates_error_without_errors(): void
    {
        $response = JsonResponse::error('Server error', 500);
        $data = $response->getData();
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Server error', $data['message']);
        $this->assertArrayNotHasKey('errors', $data);
    }

    #[Test]
    public function it_creates_error_with_errors(): void
    {
        $response = JsonResponse::error('Validation failed', 422, ['email' => 'Invalid']);
        $data = $response->getData();
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals(['email' => 'Invalid'], $data['errors']);
    }

    #[Test]
    public function it_creates_with_status_and_headers(): void
    {
        $response = new JsonResponse(['key' => 'value'], 201, ['X-Test' => 'yes']);
        
        $this->assertEquals('{"key":"value"}', $response->getContent());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('yes', $response->getHeader('x-test'));
    }

    #[Test]
    public function it_unescapes_slashes(): void
    {
        $response = new JsonResponse(['url' => 'https://example.com/path']);
        $response->withUnescapedSlashes();
        
        $this->assertStringContainsString('https://example.com/path', $response->getContent());
        $this->assertStringNotContainsString('\/', $response->getContent());
    }

    #[Test]
    public function it_unescapes_unicode(): void
    {
        $response = new JsonResponse(['name' => '日本語']);
        $response->withUnescapedUnicode();
        
        $this->assertStringContainsString('日本語', $response->getContent());
    }

    #[Test]
    public function it_throws_on_invalid_json_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $resource = fopen('php://memory', 'r');
        try {
            new JsonResponse(['resource' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    #[Test]
    public function it_sends_json_content(): void
    {
        $response = new JsonResponse(['test' => true]);
        
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();
        
        $this->assertEquals('{"test":true}', $output);
    }

    #[Test]
    public function from_data_creates_response(): void
    {
        $response = JsonResponse::fromData(['key' => 'value'], 201, ['X-Custom' => 'header']);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('header', $response->getHeader('x-custom'));
    }
}
