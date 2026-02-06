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
namespace Tests\Unit\Error;

use App\Core\Error\HttpException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    #[Test]
    public function constructor_sets_status_code(): void
    {
        $exception = new HttpException(404, 'Not Found');
        
        $this->assertSame(404, $exception->getStatusCode());
    }

    #[Test]
    public function constructor_sets_message(): void
    {
        $exception = new HttpException(404, 'Page not found');
        
        $this->assertSame('Page not found', $exception->getMessage());
    }

    #[Test]
    public function constructor_sets_headers(): void
    {
        $exception = new HttpException(401, 'Unauthorized', null, ['WWW-Authenticate' => 'Bearer']);
        
        $this->assertSame(['WWW-Authenticate' => 'Bearer'], $exception->getHeaders());
    }

    #[Test]
    public function bad_request_creates_400(): void
    {
        $exception = HttpException::badRequest();
        
        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('Bad Request', $exception->getMessage());
    }

    #[Test]
    public function unauthorized_creates_401(): void
    {
        $exception = HttpException::unauthorized();
        
        $this->assertSame(401, $exception->getStatusCode());
    }

    #[Test]
    public function forbidden_creates_403(): void
    {
        $exception = HttpException::forbidden();
        
        $this->assertSame(403, $exception->getStatusCode());
    }

    #[Test]
    public function not_found_creates_404(): void
    {
        $exception = HttpException::notFound();
        
        $this->assertSame(404, $exception->getStatusCode());
    }

    #[Test]
    public function method_not_allowed_creates_405(): void
    {
        $exception = HttpException::methodNotAllowed();
        
        $this->assertSame(405, $exception->getStatusCode());
    }

    #[Test]
    public function unprocessable_creates_422(): void
    {
        $exception = HttpException::unprocessable();
        
        $this->assertSame(422, $exception->getStatusCode());
    }

    #[Test]
    public function too_many_requests_creates_429(): void
    {
        $exception = HttpException::tooManyRequests();
        
        $this->assertSame(429, $exception->getStatusCode());
    }

    #[Test]
    public function server_error_creates_500(): void
    {
        $exception = HttpException::serverError();
        
        $this->assertSame(500, $exception->getStatusCode());
    }

    #[Test]
    public function service_unavailable_creates_503(): void
    {
        $exception = HttpException::serviceUnavailable();
        
        $this->assertSame(503, $exception->getStatusCode());
    }

    #[Test]
    public function factory_methods_accept_custom_message(): void
    {
        $exception = HttpException::notFound('User not found');
        
        $this->assertSame('User not found', $exception->getMessage());
    }
}
