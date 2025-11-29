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

use App\Core\Validation\Validator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    #[Test]
    public function required_passes_with_value(): void
    {
        $validator = new Validator(['name' => 'John']);
        $validator->required('name');
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function required_fails_without_value(): void
    {
        $validator = new Validator([]);
        $validator->required('name');
        
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('required', $validator->error('name'));
    }

    #[Test]
    public function required_fails_with_empty_string(): void
    {
        $validator = new Validator(['name' => '']);
        $validator->required('name');
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function required_accepts_array_of_fields(): void
    {
        $validator = new Validator(['name' => 'John']);
        $validator->required(['name', 'email']);
        
        $this->assertTrue($validator->fails());
        $this->assertNull($validator->error('name'));
        $this->assertNotNull($validator->error('email'));
    }

    #[Test]
    public function email_passes_with_valid_email(): void
    {
        $validator = new Validator(['email' => 'test@example.com']);
        $validator->email('email');
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function email_fails_with_invalid_email(): void
    {
        $validator = new Validator(['email' => 'invalid']);
        $validator->email('email');
        
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('valid email', $validator->error('email'));
    }

    #[Test]
    public function url_passes_with_valid_url(): void
    {
        $validator = new Validator(['website' => 'https://example.com']);
        $validator->url('website');
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function url_fails_with_invalid_url(): void
    {
        $validator = new Validator(['website' => 'not-a-url']);
        $validator->url('website');
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function min_length_passes_when_met(): void
    {
        $validator = new Validator(['password' => '12345678']);
        $validator->minLength('password', 8);
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function min_length_fails_when_too_short(): void
    {
        $validator = new Validator(['password' => '123']);
        $validator->minLength('password', 8);
        
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('at least 8', $validator->error('password'));
    }

    #[Test]
    public function max_length_passes_when_under(): void
    {
        $validator = new Validator(['name' => 'John']);
        $validator->maxLength('name', 10);
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function max_length_fails_when_too_long(): void
    {
        $validator = new Validator(['name' => 'This is a very long name']);
        $validator->maxLength('name', 10);
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function numeric_passes_with_number(): void
    {
        $validator = new Validator(['age' => '25']);
        $validator->numeric('age');
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function numeric_fails_with_non_number(): void
    {
        $validator = new Validator(['age' => 'twenty']);
        $validator->numeric('age');
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function integer_passes_with_integer(): void
    {
        $validator = new Validator(['count' => '42']);
        $validator->integer('count');
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function integer_fails_with_float(): void
    {
        $validator = new Validator(['count' => '42.5']);
        $validator->integer('count');
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function min_value_passes_when_met(): void
    {
        $validator = new Validator(['age' => 18]);
        $validator->min('age', 18);
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function min_value_fails_when_below(): void
    {
        $validator = new Validator(['age' => 15]);
        $validator->min('age', 18);
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function max_value_passes_when_under(): void
    {
        $validator = new Validator(['quantity' => 5]);
        $validator->max('quantity', 10);
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function max_value_fails_when_above(): void
    {
        $validator = new Validator(['quantity' => 15]);
        $validator->max('quantity', 10);
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function in_passes_with_allowed_value(): void
    {
        $validator = new Validator(['status' => 'active']);
        $validator->in('status', ['active', 'inactive', 'pending']);
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function in_fails_with_disallowed_value(): void
    {
        $validator = new Validator(['status' => 'unknown']);
        $validator->in('status', ['active', 'inactive']);
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function regex_passes_with_matching_pattern(): void
    {
        $validator = new Validator(['code' => 'ABC123']);
        $validator->regex('code', '/^[A-Z]{3}[0-9]{3}$/');
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function regex_fails_with_non_matching_pattern(): void
    {
        $validator = new Validator(['code' => 'invalid']);
        $validator->regex('code', '/^[A-Z]{3}[0-9]{3}$/');
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function same_passes_when_fields_match(): void
    {
        $validator = new Validator([
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $validator->same('password', 'password_confirmation');
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function same_fails_when_fields_differ(): void
    {
        $validator = new Validator([
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ]);
        $validator->same('password', 'password_confirmation');
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function make_creates_validator_with_rules(): void
    {
        $validator = Validator::make(
            ['email' => 'test@example.com', 'name' => 'John'],
            ['email' => 'required|email', 'name' => 'required|max:50']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_with_failing_rules(): void
    {
        $validator = Validator::make(
            ['email' => 'invalid'],
            ['email' => 'required|email']
        );
        
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function validated_returns_only_validated_data(): void
    {
        $validator = new Validator(['name' => ' John ', 'age' => '25']);
        $validator->required('name');
        
        $validated = $validator->validated();
        
        $this->assertSame('John', $validated['name']); // Trimmed
    }

    #[Test]
    public function errors_returns_all_errors(): void
    {
        $validator = new Validator([]);
        $validator->required(['name', 'email']);
        
        $errors = $validator->errors();
        
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    #[Test]
    public function custom_messages_are_used(): void
    {
        $validator = new Validator([], ['name' => 'Please provide your name']);
        $validator->required('name');
        
        $this->assertSame('Please provide your name', $validator->error('name'));
    }

    #[Test]
    public function chaining_multiple_rules(): void
    {
        $validator = new Validator(['email' => 'test@example.com']);
        $validator
            ->required('email')
            ->email('email')
            ->maxLength('email', 255);
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function optional_field_skips_validation_when_empty(): void
    {
        $validator = new Validator(['name' => 'John']);
        $validator->email('email'); // email not provided
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function format_field_converts_snake_case(): void
    {
        $validator = new Validator([]);
        $validator->required('first_name');
        
        $this->assertStringContainsString('First name', $validator->error('first_name'));
    }

    #[Test]
    public function make_url_rule(): void
    {
        $validator = Validator::make(
            ['website' => 'https://example.com'],
            ['website' => 'url']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_numeric_rule(): void
    {
        $validator = Validator::make(
            ['amount' => '123.45'],
            ['amount' => 'numeric']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_integer_rule(): void
    {
        $validator = Validator::make(
            ['count' => 42],
            ['count' => 'integer']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_in_rule_with_params(): void
    {
        $validator = Validator::make(
            ['status' => 'active'],
            ['status' => 'in:active,pending,inactive']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_regex_rule_with_param(): void
    {
        $validator = Validator::make(
            ['code' => 'ABC'],
            ['code' => 'regex:/^[A-Z]+$/']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_same_rule_with_param(): void
    {
        $validator = Validator::make(
            ['password' => 'secret', 'confirm' => 'secret'],
            ['password' => 'same:confirm']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function sanitize_returns_non_string_as_is(): void
    {
        $validator = new Validator(['count' => 42]);
        $validator->required('count');
        
        // The value 42 (integer) should pass through sanitize unchanged
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_min_rule_with_param(): void
    {
        $validator = Validator::make(
            ['name' => 'John Doe'],
            ['name' => 'min:3']
        );
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function make_unknown_rule_is_ignored(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => 'unknown_rule']
        );
        
        // Unknown rules should be ignored (default case)
        $this->assertTrue($validator->passes());
    }
}
