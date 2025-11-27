<?php

declare(strict_types=1);

namespace App\Core\Config;

/**
 * Config Validator
 * 
 * Validates configuration against schema definitions.
 */
class ConfigValidator
{
    /**
     * Validation errors
     */
    protected array $errors = [];

    /**
     * Validate config against a schema
     * 
     * Schema format:
     * [
     *     'key' => ['type' => 'string', 'required' => true],
     *     'nested.key' => ['type' => 'int', 'min' => 0, 'max' => 100],
     * ]
     */
    public function validate(array $config, array $schema): bool
    {
        $this->errors = [];

        foreach ($schema as $key => $rules) {
            $value = $this->getValue($config, $key);
            $this->validateField($key, $value, $rules);
        }

        return empty($this->errors);
    }

    /**
     * Validate a single field
     */
    protected function validateField(string $key, mixed $value, array $rules): void
    {
        // Required check
        if (($rules['required'] ?? false) && $value === null) {
            $this->errors[$key][] = "Field '{$key}' is required";
            return;
        }

        // Skip optional null values
        if ($value === null) {
            return;
        }

        // Type check
        if (isset($rules['type'])) {
            $this->validateType($key, $value, $rules['type']);
        }

        // Min/Max for numbers
        if (isset($rules['min']) && is_numeric($value)) {
            if ($value < $rules['min']) {
                $this->errors[$key][] = "Field '{$key}' must be at least {$rules['min']}";
            }
        }

        if (isset($rules['max']) && is_numeric($value)) {
            if ($value > $rules['max']) {
                $this->errors[$key][] = "Field '{$key}' must be at most {$rules['max']}";
            }
        }

        // Min/Max length for strings
        if (isset($rules['minLength']) && is_string($value)) {
            if (strlen($value) < $rules['minLength']) {
                $this->errors[$key][] = "Field '{$key}' must be at least {$rules['minLength']} characters";
            }
        }

        if (isset($rules['maxLength']) && is_string($value)) {
            if (strlen($value) > $rules['maxLength']) {
                $this->errors[$key][] = "Field '{$key}' must be at most {$rules['maxLength']} characters";
            }
        }

        // Enum/in check
        if (isset($rules['in']) && !in_array($value, $rules['in'], true)) {
            $allowed = implode(', ', $rules['in']);
            $this->errors[$key][] = "Field '{$key}' must be one of: {$allowed}";
        }

        // Pattern check
        if (isset($rules['pattern']) && is_string($value)) {
            if (!preg_match($rules['pattern'], $value)) {
                $this->errors[$key][] = "Field '{$key}' does not match required pattern";
            }
        }

        // Custom validator
        if (isset($rules['validator']) && is_callable($rules['validator'])) {
            $result = $rules['validator']($value, $key);
            if ($result !== true) {
                $this->errors[$key][] = is_string($result) ? $result : "Field '{$key}' is invalid";
            }
        }
    }

    /**
     * Validate type
     */
    protected function validateType(string $key, mixed $value, string $type): void
    {
        $valid = match ($type) {
            'string' => is_string($value),
            'int', 'integer' => is_int($value),
            'float', 'double' => is_float($value) || is_int($value),
            'bool', 'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'numeric' => is_numeric($value),
            'callable' => is_callable($value),
            default => true,
        };

        if (!$valid) {
            $actualType = gettype($value);
            $this->errors[$key][] = "Field '{$key}' must be of type {$type}, got {$actualType}";
        }
    }

    /**
     * Get value using dot notation
     */
    protected function getValue(array $config, string $key): mixed
    {
        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get flat list of error messages
     */
    public function getErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
}
