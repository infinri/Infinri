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
namespace Tests\Unit\Validation;

use App\Core\Validation\ValidationException;
use App\Core\Validation\Validator;
use App\Core\Error\HttpException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_http_exception(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);
        $exception = new ValidationException($validator);
        
        $this->assertInstanceOf(HttpException::class, $exception);
    }

    #[Test]
    public function it_has_422_status_code(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);
        $exception = new ValidationException($validator);
        
        $this->assertEquals(422, $exception->getStatusCode());
    }

    #[Test]
    public function it_contains_validator_instance(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);
        $exception = new ValidationException($validator);
        
        $this->assertSame($validator, $exception->validator());
    }

    #[Test]
    public function it_returns_validation_errors(): void
    {
        $validator = Validator::make(['email' => 'invalid'], ['email' => 'required|email']);
        $exception = new ValidationException($validator);
        
        $errors = $exception->errors();
        
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('email', $errors);
    }

    #[Test]
    public function it_uses_first_error_as_message(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);
        $exception = new ValidationException($validator);
        
        $this->assertStringContainsString('required', strtolower($exception->getMessage()));
    }

    #[Test]
    public function it_uses_default_message_when_no_errors(): void
    {
        // Create validator that passes (no errors)
        $validator = Validator::make(['name' => 'John'], ['name' => 'required']);
        
        // Force creation even though it passes
        $exception = new ValidationException($validator);
        
        // Should have default message since no errors
        $this->assertEquals('The given data was invalid.', $exception->getMessage());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $validator = Validator::make(['email' => 'bad'], ['email' => 'email']);
        $exception = new ValidationException($validator);
        
        $array = $exception->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertIsArray($array['errors']);
    }

    #[Test]
    public function it_includes_all_errors_in_to_array(): void
    {
        $validator = Validator::make(
            ['name' => '', 'email' => 'invalid'],
            ['name' => 'required', 'email' => 'email']
        );
        $exception = new ValidationException($validator);
        
        $array = $exception->toArray();
        
        $this->assertArrayHasKey('name', $array['errors']);
        $this->assertArrayHasKey('email', $array['errors']);
    }

    #[Test]
    public function it_has_empty_headers_by_default(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);
        $exception = new ValidationException($validator);
        
        $this->assertEquals([], $exception->getHeaders());
    }

    #[Test]
    public function it_can_be_thrown_and_caught(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);
        
        $this->expectException(ValidationException::class);
        
        throw new ValidationException($validator);
    }

    #[Test]
    public function it_preserves_multiple_field_errors(): void
    {
        $validator = Validator::make(
            [
                'name' => '',
                'email' => 'not-an-email',
                'age' => 'not-a-number'
            ],
            [
                'name' => 'required',
                'email' => 'email',
                'age' => 'numeric'
            ]
        );
        
        $exception = new ValidationException($validator);
        $errors = $exception->errors();
        
        $this->assertCount(3, $errors);
    }
}
