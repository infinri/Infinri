<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Core\Http\RedirectResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RedirectResponseTest extends TestCase
{
    #[Test]
    public function it_creates_redirect_response(): void
    {
        $response = new RedirectResponse('/dashboard');
        
        $this->assertEquals('/dashboard', $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/dashboard', $response->getHeader('location'));
    }

    #[Test]
    public function it_creates_permanent_redirect(): void
    {
        $response = RedirectResponse::permanent('/new-url');
        
        $this->assertEquals(301, $response->getStatusCode());
    }

    #[Test]
    public function it_creates_temporary_redirect(): void
    {
        $response = RedirectResponse::temporary('/temp-url');
        
        $this->assertEquals(302, $response->getStatusCode());
    }

    #[Test]
    public function it_creates_see_other_redirect(): void
    {
        $response = RedirectResponse::seeOther('/result');
        
        $this->assertEquals(303, $response->getStatusCode());
    }

    #[Test]
    public function it_can_add_query_parameters(): void
    {
        $response = new RedirectResponse('/search');
        $response->withQuery(['q' => 'test', 'page' => 1]);
        
        $this->assertEquals('/search?q=test&page=1', $response->getTargetUrl());
    }

    #[Test]
    public function it_can_add_fragment(): void
    {
        $response = new RedirectResponse('/page');
        $response->withFragment('section1');
        
        $this->assertEquals('/page#section1', $response->getTargetUrl());
    }

    #[Test]
    public function it_throws_for_empty_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new RedirectResponse('');
    }

    #[Test]
    public function it_sends_redirect_content(): void
    {
        $response = new RedirectResponse('/target');
        
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('/target', $output);
    }

    #[Test]
    public function to_creates_redirect_response(): void
    {
        $response = RedirectResponse::to('/destination', 303);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/destination', $response->getTargetUrl());
        $this->assertEquals(303, $response->getStatusCode());
    }
}
