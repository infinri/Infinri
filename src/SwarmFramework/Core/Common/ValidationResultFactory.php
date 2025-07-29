<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Interfaces\ValidationResult;

/**
 * ValidationResult Factory - Centralized ValidationResult Creation
 * 
 * Eliminates redundant ValidationResult::success() and ValidationResult::failure() patterns
 * across the codebase by providing common factory methods.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
final class ValidationResultFactory
{
    /**
     * Create success result with optional warnings
     */
    public static function success(array $warnings = []): ValidationResult
    {
        return ValidationResult::success($warnings);
    }

    /**
     * Create failure result with errors and optional warnings
     */
    public static function failure(array $errors, array $warnings = []): ValidationResult
    {
        return ValidationResult::failure($errors, $warnings);
    }

    /**
     * Create conditional result based on error array
     */
    public static function conditional(array $errors, array $warnings = []): ValidationResult
    {
        return empty($errors) 
            ? self::success($warnings)
            : self::failure($errors, $warnings);
    }

    /**
     * Create result from boolean with custom messages
     */
    public static function fromBoolean(
        bool $isValid, 
        string $successMessage = '', 
        string $failureMessage = 'Validation failed'
    ): ValidationResult {
        if ($isValid) {
            return empty($successMessage) 
                ? self::success() 
                : self::success([$successMessage]);
        }
        
        return self::failure([$failureMessage]);
    }

    /**
     * Create result from exception
     */
    public static function fromException(\Throwable $e, string $context = ''): ValidationResult
    {
        $message = empty($context) 
            ? $e->getMessage()
            : "{$context}: {$e->getMessage()}";
            
        return self::failure([$message]);
    }

    /**
     * Merge multiple ValidationResult objects
     */
    public static function merge(ValidationResult ...$results): ValidationResult
    {
        $allErrors = [];
        $allWarnings = [];
        
        foreach ($results as $result) {
            $allErrors = array_merge($allErrors, $result->getErrors());
            $allWarnings = array_merge($allWarnings, $result->getWarnings() ?? []);
        }
        
        return self::conditional($allErrors, $allWarnings);
    }

    /**
     * Create result with performance context
     */
    public static function withPerformance(
        bool $isValid,
        float $duration,
        string $operation,
        array $additionalContext = []
    ): ValidationResult {
        $context = array_merge([
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2)
        ], $additionalContext);
        
        if ($isValid) {
            return self::success(["Operation '{$operation}' completed successfully"]);
        }
        
        return self::failure(["Operation '{$operation}' failed"], [json_encode($context)]);
    }

    /**
     * Create result for path validation
     */
    public static function validatePath(string $path, string $context = 'Path'): ValidationResult
    {
        if (empty($path)) {
            return self::failure(["{$context} cannot be empty"]);
        }
        
        if (!file_exists($path)) {
            return self::failure(["{$context} does not exist: {$path}"]);
        }
        
        return self::success();
    }

    /**
     * Create result for required field validation
     */
    public static function validateRequired(array $data, array $requiredFields): ValidationResult
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }
        
        return self::conditional($errors);
    }

    /**
     * Create result for type validation
     */
    public static function validateType(mixed $value, string $expectedType, string $fieldName): ValidationResult
    {
        $actualType = gettype($value);
        
        if ($actualType !== $expectedType) {
            return self::failure([
                "Field '{$fieldName}' must be of type '{$expectedType}', got '{$actualType}'"
            ]);
        }
        
        return self::success();
    }
}
