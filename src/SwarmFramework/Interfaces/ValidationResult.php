<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Interfaces;

/**
 * Validation Result - Encapsulates validation outcomes
 * 
 * Represents the result of validation operations with success status,
 * error messages, warnings, and additional context information.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
final class ValidationResult
{
    /**
     * @param bool $isValid Whether validation passed
     * @param array $errors List of error messages
     * @param array $warnings List of warning messages
     * @param array $context Additional context information
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly array $warnings = [],
        public readonly array $context = []
    ) {}

    /**
     * Create a successful validation result
     * 
     * @param array $warnings Optional warning messages
     * @param array $context Optional context information
     * @return ValidationResult
     */
    public static function success(array $warnings = [], array $context = []): ValidationResult
    {
        return new self(true, [], $warnings, $context);
    }

    /**
     * Create a failed validation result
     * 
     * @param array $errors Error messages
     * @param array $warnings Optional warning messages
     * @param array $context Optional context information
     * @return ValidationResult
     */
    public static function failure(array $errors, array $warnings = [], array $context = []): ValidationResult
    {
        return new self(false, $errors, $warnings, $context);
    }

    /**
     * Check if validation passed
     * 
     * @return bool True if validation is valid
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get all error messages
     * 
     * @return array List of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation has any errors
     * 
     * @return bool True if there are errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all warning messages
     * 
     * @return array List of warning messages
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if validation has any warnings
     * 
     * @return bool True if there are warnings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get all error messages as a single string
     * 
     * @param string $separator Separator between messages
     * @return string Combined error messages
     */
    public function getErrorsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->errors);
    }

    /**
     * Get all warning messages as a single string
     * 
     * @param string $separator Separator between messages
     * @return string Combined warning messages
     */
    public function getWarningsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->warnings);
    }

    /**
     * Get validation summary
     * 
     * @return array Summary with counts and status
     */
    public function getSummary(): array
    {
        return [
            'is_valid' => $this->isValid,
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'has_context' => !empty($this->context)
        ];
    }

    /**
     * Merge with another validation result
     * 
     * @param ValidationResult $other Other validation result
     * @return ValidationResult Combined result
     */
    public function merge(ValidationResult $other): ValidationResult
    {
        return new self(
            $this->isValid && $other->isValid,
            array_merge($this->errors, $other->errors),
            array_merge($this->warnings, $other->warnings),
            array_merge($this->context, $other->context)
        );
    }

    /**
     * Convert to array representation
     * 
     * @return array Array representation
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'context' => $this->context
        ];
    }
}
