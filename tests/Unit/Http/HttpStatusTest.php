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

use App\Core\Http\HttpStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpStatusTest extends TestCase
{
    #[Test]
    public function it_has_correct_status_constants(): void
    {
        $this->assertEquals(200, HttpStatus::OK);
        $this->assertEquals(201, HttpStatus::CREATED);
        $this->assertEquals(204, HttpStatus::NO_CONTENT);
        $this->assertEquals(301, HttpStatus::MOVED_PERMANENTLY);
        $this->assertEquals(302, HttpStatus::FOUND);
        $this->assertEquals(400, HttpStatus::BAD_REQUEST);
        $this->assertEquals(401, HttpStatus::UNAUTHORIZED);
        $this->assertEquals(403, HttpStatus::FORBIDDEN);
        $this->assertEquals(404, HttpStatus::NOT_FOUND);
        $this->assertEquals(405, HttpStatus::METHOD_NOT_ALLOWED);
        $this->assertEquals(422, HttpStatus::UNPROCESSABLE_ENTITY);
        $this->assertEquals(500, HttpStatus::INTERNAL_SERVER_ERROR);
    }

    #[Test]
    public function it_returns_correct_status_text(): void
    {
        $this->assertEquals('OK', HttpStatus::text(200));
        $this->assertEquals('Created', HttpStatus::text(201));
        $this->assertEquals('Not Found', HttpStatus::text(404));
        $this->assertEquals('Internal Server Error', HttpStatus::text(500));
        $this->assertEquals('Unknown Status', HttpStatus::text(999));
    }

    #[Test]
    public function it_checks_informational_status(): void
    {
        $this->assertTrue(HttpStatus::isInformational(100));
        $this->assertTrue(HttpStatus::isInformational(101));
        $this->assertFalse(HttpStatus::isInformational(200));
    }

    #[Test]
    public function it_checks_successful_status(): void
    {
        $this->assertTrue(HttpStatus::isSuccessful(200));
        $this->assertTrue(HttpStatus::isSuccessful(201));
        $this->assertTrue(HttpStatus::isSuccessful(204));
        $this->assertFalse(HttpStatus::isSuccessful(300));
        $this->assertFalse(HttpStatus::isSuccessful(404));
    }

    #[Test]
    public function it_checks_redirect_status(): void
    {
        $this->assertTrue(HttpStatus::isRedirect(301));
        $this->assertTrue(HttpStatus::isRedirect(302));
        $this->assertTrue(HttpStatus::isRedirect(307));
        $this->assertFalse(HttpStatus::isRedirect(200));
        $this->assertFalse(HttpStatus::isRedirect(404));
    }

    #[Test]
    public function it_checks_client_error_status(): void
    {
        $this->assertTrue(HttpStatus::isClientError(400));
        $this->assertTrue(HttpStatus::isClientError(404));
        $this->assertTrue(HttpStatus::isClientError(422));
        $this->assertFalse(HttpStatus::isClientError(200));
        $this->assertFalse(HttpStatus::isClientError(500));
    }

    #[Test]
    public function it_checks_server_error_status(): void
    {
        $this->assertTrue(HttpStatus::isServerError(500));
        $this->assertTrue(HttpStatus::isServerError(502));
        $this->assertTrue(HttpStatus::isServerError(503));
        $this->assertFalse(HttpStatus::isServerError(200));
        $this->assertFalse(HttpStatus::isServerError(404));
    }

    #[Test]
    public function it_checks_empty_status(): void
    {
        $this->assertTrue(HttpStatus::isEmpty(204));
        $this->assertTrue(HttpStatus::isEmpty(304));
        $this->assertFalse(HttpStatus::isEmpty(200));
        $this->assertFalse(HttpStatus::isEmpty(404));
    }
}
