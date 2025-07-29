<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ValidationResultFactory;
use Psr\Log\LoggerInterface;

/**
 * Base Validator - Common validation functionality
 * 
 * Provides reusable validation patterns and utilities
 * to eliminate code duplication across validators.
 */
abstract class BaseValidator
{
    protected LoggerInterface $logger;
    protected array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Validate required fields in data
     */
    protected function validateRequiredFields(array $data, array $requiredFields): ValidationResult
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }
        
        return ValidationResultFactory::createConditional($errors);
    }

    /**
     * Validate data types
     */
    protected function validateTypes(array $data, array $typeMap): ValidationResult
    {
        $errors = [];
        
        foreach ($typeMap as $field => $expectedType) {
            if (isset($data[$field])) {
                $actualType = gettype($data[$field]);
                if ($actualType !== $expectedType) {
                    $errors[] = "Field '{$field}' expected {$expectedType}, got {$actualType}";
                }
            }
        }
        
        return ValidationResultFactory::createConditional($errors);
    }

    /**
     * Validate string length constraints
     */
    protected function validateStringLengths(array $data, array $constraints): ValidationResult
    {
        $errors = [];
        
        foreach ($constraints as $field => $limits) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $length = strlen($data[$field]);
                
                if (isset($limits['min']) && $length < $limits['min']) {
                    $errors[] = "Field '{$field}' must be at least {$limits['min']} characters";
                }
                
                if (isset($limits['max']) && $length > $limits['max']) {
                    $errors[] = "Field '{$field}' must be no more than {$limits['max']} characters";
                }
            }
        }
        
        return ValidationResultFactory::createConditional($errors);
    }

    /**
     * Validate array constraints
     */
    protected function validateArrayConstraints(array $data, array $constraints): ValidationResult
    {
        $errors = [];
        
        foreach ($constraints as $field => $limits) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $count = count($data[$field]);
                
                if (isset($limits['min_items']) && $count < $limits['min_items']) {
                    $errors[] = "Field '{$field}' must have at least {$limits['min_items']} items";
                }
                
                if (isset($limits['max_items']) && $count > $limits['max_items']) {
                    $errors[] = "Field '{$field}' must have no more than {$limits['max_items']} items";
                }
            }
        }
        
        return ValidationResultFactory::createConditional($errors);
    }

    /**
     * Merge validation results
     */
    protected function mergeValidationResults(ValidationResult ...$results): ValidationResult
    {
        $allErrors = [];
        $allWarnings = [];
        
        foreach ($results as $result) {
            $allErrors = array_merge($allErrors, $result->getErrors());
            $allWarnings = array_merge($allWarnings, $result->getWarnings() ?? []);
        }
        
        return empty($allErrors) 
            ? ValidationResult::success($allWarnings)
            : ValidationResult::failure($allErrors, $allWarnings);
    }
}
