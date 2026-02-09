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
namespace App\Core\Validation;

/**
 * Validator
 *
 * Fluent input validation with common rules.
 */
class Validator
{
    /**
     * Data to validate
     */
    protected array $data;

    /**
     * Validation errors
     */
    protected array $errors = [];

    /**
     * Validated data
     */
    protected array $validated = [];

    /**
     * Custom error messages
     */
    protected array $messages = [];

    public function __construct(array $data, array $messages = [])
    {
        $this->data = $data;
        $this->messages = $messages;
    }

    /**
     * Create a new validator instance
     */
    public static function make(array $data, array $rules = [], array $messages = []): static
    {
        $validator = new static($data, $messages);

        foreach ($rules as $field => $rule) {
            $validator->applyRules($field, $rule);
        }

        return $validator;
    }

    /**
     * Validate required fields
     */
    public function required(string|array $fields): static
    {
        $fields = is_array($fields) ? $fields : [$fields];

        foreach ($fields as $field) {
            if (! isset($this->data[$field]) || $this->isEmpty($this->data[$field])) {
                $this->addError($field, 'required', "{$this->formatField($field)} is required");
            } else {
                $this->validated[$field] = $this->sanitize($this->data[$field]);
            }
        }

        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field): static
    {
        if ($this->hasValue($field)) {
            if (! filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, 'email', 'Please enter a valid email address');
            }
        }

        return $this;
    }

    /**
     * Validate URL format
     */
    public function url(string $field): static
    {
        if ($this->hasValue($field)) {
            if (! filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->addError($field, 'url', 'Please enter a valid URL');
            }
        }

        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $min): static
    {
        if ($this->hasValue($field) && strlen($this->data[$field]) < $min) {
            $this->addError($field, 'min', "{$this->formatField($field)} must be at least {$min} characters");
        }

        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max): static
    {
        if ($this->hasValue($field) && strlen($this->data[$field]) > $max) {
            $this->addError($field, 'max', "{$this->formatField($field)} must be less than {$max} characters");
        }

        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field): static
    {
        if ($this->hasValue($field) && ! is_numeric($this->data[$field])) {
            $this->addError($field, 'numeric', "{$this->formatField($field)} must be a number");
        }

        return $this;
    }

    /**
     * Validate integer value
     */
    public function integer(string $field): static
    {
        if ($this->hasValue($field) && ! filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->addError($field, 'integer', "{$this->formatField($field)} must be an integer");
        }

        return $this;
    }

    /**
     * Validate minimum value
     */
    public function min(string $field, int|float $min): static
    {
        if ($this->hasValue($field) && is_numeric($this->data[$field]) && $this->data[$field] < $min) {
            $this->addError($field, 'min_value', "{$this->formatField($field)} must be at least {$min}");
        }

        return $this;
    }

    /**
     * Validate maximum value
     */
    public function max(string $field, int|float $max): static
    {
        if ($this->hasValue($field) && is_numeric($this->data[$field]) && $this->data[$field] > $max) {
            $this->addError($field, 'max_value', "{$this->formatField($field)} must be at most {$max}");
        }

        return $this;
    }

    /**
     * Validate value is in list
     */
    public function in(string $field, array $allowed): static
    {
        if ($this->hasValue($field) && ! in_array($this->data[$field], $allowed, true)) {
            $this->addError($field, 'in', "{$this->formatField($field)} must be one of: " . implode(', ', $allowed));
        }

        return $this;
    }

    /**
     * Validate regex pattern
     */
    public function regex(string $field, string $pattern): static
    {
        if ($this->hasValue($field) && ! preg_match($pattern, $this->data[$field])) {
            $this->addError($field, 'regex', "{$this->formatField($field)} format is invalid");
        }

        return $this;
    }

    /**
     * Validate field matches another field
     */
    public function same(string $field, string $otherField): static
    {
        if ($this->hasValue($field) && ($this->data[$field] ?? null) !== ($this->data[$otherField] ?? null)) {
            $this->addError($field, 'same', "{$this->formatField($field)} must match {$this->formatField($otherField)}");
        }

        return $this;
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * Get all validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for a field
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Get validated data
     */
    public function validated(): array
    {
        // Include optional fields that passed
        foreach ($this->data as $key => $value) {
            if (! isset($this->validated[$key]) && ! isset($this->errors[$key]) && ! $this->isEmpty($value)) {
                $this->validated[$key] = $this->sanitize($value);
            }
        }

        return $this->validated;
    }

    /**
     * Apply string rules (e.g., "required|email|max:255")
     */
    protected function applyRules(string $field, string $rules): void
    {
        $ruleList = explode('|', $rules);

        foreach ($ruleList as $rule) {
            $params = [];

            if (str_contains($rule, ':')) {
                [$rule, $paramStr] = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }

            match ($rule) {
                'required' => $this->required($field),
                'email' => $this->email($field),
                'url' => $this->url($field),
                'numeric' => $this->numeric($field),
                'integer' => $this->integer($field),
                'min' => $this->minLength($field, (int) $params[0]),
                'max' => $this->maxLength($field, (int) $params[0]),
                'in' => $this->in($field, $params),
                'regex' => $this->regex($field, $params[0]),
                'same' => $this->same($field, $params[0]),
                default => null,
            };
        }
    }

    /**
     * Add an error
     */
    protected function addError(string $field, string $rule, string $default): void
    {
        $key = "{$field}.{$rule}";
        $this->errors[$field] = $this->messages[$key] ?? $this->messages[$field] ?? $default;
    }

    /**
     * Check if field has a non-empty value
     */
    protected function hasValue(string $field): bool
    {
        return isset($this->data[$field]) && ! $this->isEmpty($this->data[$field]);
    }

    /**
     * Check if value is empty
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Sanitize a value
     */
    protected function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Format field name for display
     */
    protected function formatField(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }
}
