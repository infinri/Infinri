<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Safety;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Infinri\SwarmFramework\Exceptions\InvalidUnitIdentityException;
use Infinri\SwarmFramework\Exceptions\SafetyLimitExceededException;

/**
 * Runtime Validator - Enforces .windsurfrules hallucination prevention
 * 
 * Implements runtime validation for:
 * - Unit identity verification before execution
 * - Mesh key validation before access
 * - Schema validation for all configurations
 * - Class name validation against PSR-4 standards
 * - Method signature validation against interfaces
 * 
 * @reference .windsurfrules → HALLUCINATION PREVENTION
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: [])]
final class RuntimeValidator
{
    /**
     * Validate unit identity before execution
     * 
     * @param UnitIdentity $identity Unit identity to validate
     * @throws InvalidUnitIdentityException If identity is invalid
     */
    public function validateUnitIdentity(UnitIdentity $identity): void
    {
        // Validate ID format
        if (empty($identity->id) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $identity->id)) {
            throw new InvalidUnitIdentityException(
                "Invalid unit ID format: {$identity->id}"
            );
        }

        // Validate version format
        if (empty($identity->version) || !preg_match('/^\d+\.\d+\.\d+/', $identity->version)) {
            throw new InvalidUnitIdentityException(
                "Invalid version format: {$identity->version}"
            );
        }

        // Validate hash format (SHA-256 with prefix)
        if (empty($identity->hash) || !preg_match('/^sha256:[a-f0-9]{64}$/', $identity->hash)) {
            throw new InvalidUnitIdentityException(
                "Invalid hash format: {$identity->hash}"
            );
        }

        // Validate capabilities array
        if (!is_array($identity->capabilities)) {
            throw new InvalidUnitIdentityException(
                "Capabilities must be an array"
            );
        }

        // Validate dependencies array
        if (!is_array($identity->dependencies)) {
            throw new InvalidUnitIdentityException(
                "Dependencies must be an array"
            );
        }

        // Validate mesh keys array
        if (!is_array($identity->meshKeys)) {
            throw new InvalidUnitIdentityException(
                "Mesh keys must be an array"
            );
        }

        // Validate mesh keys count against safety limits
        if (count($identity->meshKeys) > 1000) {
            throw new SafetyLimitExceededException(
                "Mesh keys count exceeds safety limit: " . count($identity->meshKeys) . " > 1000"
            );
        }
    }

    /**
     * Validate mesh key format and access permissions
     * 
     * @param string $key Mesh key to validate
     * @param array $allowedNamespaces Allowed namespace prefixes
     * @throws SafetyLimitExceededException If key is invalid or unauthorized
     */
    public function validateMeshKey(string $key, array $allowedNamespaces = []): void
    {
        // Validate key format
        if (empty($key) || !preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $key)) {
            throw new SafetyLimitExceededException(
                "Invalid mesh key format: {$key}"
            );
        }

        // Validate key length
        if (strlen($key) > 255) {
            throw new SafetyLimitExceededException(
                "Mesh key too long: " . strlen($key) . " > 255 characters"
            );
        }

        // Validate namespace access if restrictions are specified
        if (!empty($allowedNamespaces)) {
            $hasAccess = false;
            foreach ($allowedNamespaces as $namespace) {
                if (str_starts_with($key, $namespace)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                throw new SafetyLimitExceededException(
                    "Unauthorized mesh key access: {$key}"
                );
            }
        }
    }

    /**
     * Validate class name against PSR-4 standards
     * 
     * @param string $className Class name to validate
     * @param string $filePath Expected file path
     * @throws InvalidUnitIdentityException If class name doesn't match PSR-4
     */
    public function validatePsr4Compliance(string $className, string $filePath): void
    {
        // Extract class name from fully qualified name
        $classBaseName = basename(str_replace('\\', '/', $className));
        
        // Extract file name without extension
        $fileBaseName = basename($filePath, '.php');

        // Validate class name matches file name
        if ($classBaseName !== $fileBaseName) {
            throw new InvalidUnitIdentityException(
                "Class name '{$classBaseName}' doesn't match file name '{$fileBaseName}'"
            );
        }

        // Validate class name format
        if (!preg_match('/^[A-Z][a-zA-Z0-9_]*$/', $classBaseName)) {
            throw new InvalidUnitIdentityException(
                "Class name '{$classBaseName}' doesn't follow PSR-4 naming conventions"
            );
        }
    }

    /**
     * Validate method signature against interface
     * 
     * @param object $instance Object instance
     * @param string $interfaceName Interface name
     * @param string $methodName Method name
     * @throws InvalidUnitIdentityException If method signature doesn't match
     */
    public function validateMethodSignature(object $instance, string $interfaceName, string $methodName): void
    {
        $className = get_class($instance);

        // Check if class implements interface
        if (!in_array($interfaceName, class_implements($instance))) {
            throw new InvalidUnitIdentityException(
                "Class '{$className}' doesn't implement interface '{$interfaceName}'"
            );
        }

        // Check if method exists
        if (!method_exists($instance, $methodName)) {
            throw new InvalidUnitIdentityException(
                "Method '{$methodName}' doesn't exist in class '{$className}'"
            );
        }

        // Get method reflection
        $classReflection = new \ReflectionClass($instance);
        $method = $classReflection->getMethod($methodName);

        // Get interface method reflection
        $interfaceReflection = new \ReflectionClass($interfaceName);
        $interfaceMethod = $interfaceReflection->getMethod($methodName);

        // Validate parameter count
        if ($method->getNumberOfParameters() !== $interfaceMethod->getNumberOfParameters()) {
            throw new InvalidUnitIdentityException(
                "Method '{$methodName}' parameter count mismatch in class '{$className}'"
            );
        }

        // Validate return type
        $methodReturnType = $method->getReturnType();
        $interfaceReturnType = $interfaceMethod->getReturnType();

        if ($methodReturnType?->getName() !== $interfaceReturnType?->getName()) {
            throw new InvalidUnitIdentityException(
                "Method '{$methodName}' return type mismatch in class '{$className}'"
            );
        }
    }

    /**
     * Validate configuration schema
     * 
     * @param array $config Configuration to validate
     * @param array $schema Expected schema structure
     * @throws SafetyLimitExceededException If configuration is invalid
     */
    public function validateConfigurationSchema(array $config, array $schema): void
    {
        foreach ($schema as $key => $expectedType) {
            if (!array_key_exists($key, $config)) {
                throw new SafetyLimitExceededException(
                    "Missing required configuration key: {$key}"
                );
            }

            $actualType = gettype($config[$key]);
            if ($actualType !== $expectedType) {
                throw new SafetyLimitExceededException(
                    "Configuration key '{$key}' type mismatch: expected {$expectedType}, got {$actualType}"
                );
            }
        }
    }

    /**
     * Validate file path exists and is readable
     * 
     * @param string $filePath File path to validate
     * @throws InvalidUnitIdentityException If file doesn't exist or isn't readable
     */
    public function validateFilePath(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new InvalidUnitIdentityException(
                "File doesn't exist: {$filePath}"
            );
        }

        if (!is_readable($filePath)) {
            throw new InvalidUnitIdentityException(
                "File isn't readable: {$filePath}"
            );
        }
    }

    /**
     * Validate namespace matches directory structure
     * 
     * @param string $namespace Namespace to validate
     * @param string $filePath File path
     * @param string $basePath Base source path
     * @throws InvalidUnitIdentityException If namespace doesn't match directory
     */
    public function validateNamespaceStructure(string $namespace, string $filePath, string $basePath): void
    {
        // Convert namespace to expected directory path
        $expectedPath = str_replace('\\', '/', $namespace);
        
        // Get relative path from base
        $relativePath = str_replace($basePath . '/', '', dirname($filePath));
        
        // Validate structure matches
        if (!str_ends_with($expectedPath, $relativePath)) {
            throw new InvalidUnitIdentityException(
                "Namespace '{$namespace}' doesn't match directory structure '{$relativePath}'"
            );
        }
    }

    /**
     * Get validation summary for debugging
     * 
     * @return array Validation metrics
     */
    public function getValidationMetrics(): array
    {
        return [
            'validator_version' => '1.0.0',
            'psr4_compliance' => true,
            'safety_limits_enforced' => true,
            'runtime_validation_active' => true,
            'supported_validations' => [
                'unit_identity',
                'mesh_keys',
                'psr4_compliance',
                'method_signatures',
                'configuration_schema',
                'file_paths',
                'namespace_structure'
            ]
        ];
    }
}
