<?php
declare(strict_types=1);
/**
 * Form Validator
 *
 * Simple form validation for contact forms
 *
 * @package App\Helpers
 */

namespace App\Base\Helpers;

class Validator
{
    private array $data;
    private array $errors = [];
    private array $validated = [];
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * Validate required fields
     */
    public function required(array $fields): self
    {
        foreach ($fields as $field) {
            if (empty($this->data[$field])) {
                $this->errors[$field] = ucfirst($field) . ' is required';
            } else {
                $this->validated[$field] = trim($this->data[$field]);
            }
        }
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = 'Please enter a valid email address';
            }
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = ucfirst($field) . " must be less than {$max} characters";
        }
        return $this;
    }
    
    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get validated data
     */
    public function validated(): array
    {
        // Add optional fields that passed validation
        foreach ($this->data as $key => $value) {
            if (!isset($this->validated[$key]) && !isset($this->errors[$key]) && !empty($value)) {
                $this->validated[$key] = trim($value);
            }
        }
        return $this->validated;
    }
}
