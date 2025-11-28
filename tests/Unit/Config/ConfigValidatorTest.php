<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Core\Config\ConfigValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfigValidatorTest extends TestCase
{
    private ConfigValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ConfigValidator();
    }

    #[Test]
    public function validate_returns_true_for_valid_config(): void
    {
        $config = ['name' => 'test'];
        $schema = ['name' => ['type' => 'string']];
        
        $this->assertTrue($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_required_fields(): void
    {
        $config = [];
        $schema = ['name' => ['required' => true]];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_string_type(): void
    {
        $config = ['name' => 123];
        $schema = ['name' => ['type' => 'string']];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_int_type(): void
    {
        $config = ['count' => 'not-a-number'];
        $schema = ['count' => ['type' => 'int']];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_bool_type(): void
    {
        $config = ['active' => 'yes'];
        $schema = ['active' => ['type' => 'bool']];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_array_type(): void
    {
        $config = ['items' => 'not-an-array'];
        $schema = ['items' => ['type' => 'array']];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_min_value(): void
    {
        $config = ['count' => 5];
        $schema = ['count' => ['min' => 10]];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_max_value(): void
    {
        $config = ['count' => 100];
        $schema = ['count' => ['max' => 50]];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_min_length(): void
    {
        $config = ['name' => 'ab'];
        $schema = ['name' => ['minLength' => 5]];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_max_length(): void
    {
        $config = ['name' => 'very long string'];
        $schema = ['name' => ['maxLength' => 5]];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_in_values(): void
    {
        $config = ['status' => 'invalid'];
        $schema = ['status' => ['in' => ['active', 'inactive']]];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_pattern(): void
    {
        $config = ['code' => 'abc'];
        $schema = ['code' => ['pattern' => '/^[0-9]+$/']];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_uses_custom_validator(): void
    {
        $config = ['value' => 'invalid'];
        $schema = [
            'value' => [
                'validator' => fn($v) => $v === 'valid' ? true : 'Must be valid',
            ],
        ];
        
        $this->assertFalse($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_supports_dot_notation(): void
    {
        $config = ['database' => ['host' => 'localhost']];
        $schema = ['database.host' => ['type' => 'string']];
        
        $this->assertTrue($this->validator->validate($config, $schema));
    }

    #[Test]
    public function get_errors_returns_errors(): void
    {
        $config = [];
        $schema = ['name' => ['required' => true]];
        
        $this->validator->validate($config, $schema);
        
        $this->assertArrayHasKey('name', $this->validator->getErrors());
    }

    #[Test]
    public function get_error_messages_returns_flat_list(): void
    {
        $config = [];
        $schema = [
            'name' => ['required' => true],
            'email' => ['required' => true],
        ];
        
        $this->validator->validate($config, $schema);
        $messages = $this->validator->getErrorMessages();
        
        $this->assertCount(2, $messages);
    }

    #[Test]
    public function passes_returns_true_when_no_errors(): void
    {
        $config = ['name' => 'test'];
        $schema = ['name' => ['type' => 'string']];
        
        $this->validator->validate($config, $schema);
        
        $this->assertTrue($this->validator->passes());
    }

    #[Test]
    public function fails_returns_true_when_errors(): void
    {
        $config = [];
        $schema = ['name' => ['required' => true]];
        
        $this->validator->validate($config, $schema);
        
        $this->assertTrue($this->validator->fails());
    }

    #[Test]
    public function validate_skips_optional_null_values(): void
    {
        $config = ['name' => null];
        $schema = ['name' => ['type' => 'string']];
        
        $this->assertTrue($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_float_type(): void
    {
        $config = ['price' => 19.99];
        $schema = ['price' => ['type' => 'float']];
        
        $this->assertTrue($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_numeric_type(): void
    {
        $config = ['value' => '123'];
        $schema = ['value' => ['type' => 'numeric']];
        
        $this->assertTrue($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_object_type(): void
    {
        $config = ['obj' => new \stdClass()];
        $schema = ['obj' => ['type' => 'object']];
        
        $this->assertTrue($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_checks_callable_type(): void
    {
        $config = ['callback' => fn() => 'test'];
        $schema = ['callback' => ['type' => 'callable']];
        
        $this->assertTrue($this->validator->validate($config, $schema));
    }

    #[Test]
    public function validate_accepts_unknown_type(): void
    {
        $config = ['value' => 'anything'];
        $schema = ['value' => ['type' => 'unknown_type']];
        
        // Unknown types should pass (default case returns true)
        $this->assertTrue($this->validator->validate($config, $schema));
    }
}
