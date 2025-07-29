<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Exceptions\MeshOperationException;
use Infinri\SwarmFramework\Exceptions\MeshAccessException;
use Infinri\SwarmFramework\Exceptions\MeshCapacityException;
use Infinri\SwarmFramework\Exceptions\MeshCorruptionException;
use Infinri\SwarmFramework\Exceptions\MeshPublishException;
use Infinri\SwarmFramework\Exceptions\MeshSnapshotException;
use Infinri\SwarmFramework\Exceptions\MeshSubscriptionException;
use Infinri\SwarmFramework\Exceptions\SafetyLimitExceededException;
use Infinri\SwarmFramework\Exceptions\InvalidUnitIdentityException;
use Infinri\SwarmFramework\Exceptions\InvalidTacticException;
use Infinri\SwarmFramework\Exceptions\InvalidGoalException;

/**
 * Exception Factory - Centralized exception creation
 * 
 * Eliminates redundant exception creation patterns and provides
 * consistent error handling across the framework.
 */
#[Injectable(dependencies: [])]
final class ExceptionFactory
{
    /**
     * Create a mesh operation exception
     */
    public static function meshOperation(string $operation, string $message, ?\Throwable $previous = null): MeshOperationException
    {
        return new MeshOperationException(
            "Mesh {$operation} operation failed: {$message}",
            0,
            $previous
        );
    }

    /**
     * Create a mesh access exception
     */
    public static function meshAccess(string $key, string $operation, string $reason): MeshAccessException
    {
        return new MeshAccessException(
            "Access denied for key '{$key}' operation '{$operation}': {$reason}"
        );
    }

    /**
     * Create a mesh capacity exception
     */
    public static function meshCapacity(string $resource, int $current, int $limit): MeshCapacityException
    {
        return new MeshCapacityException(
            "Mesh capacity exceeded for {$resource}: {$current}/{$limit}"
        );
    }

    /**
     * Create a mesh corruption exception
     */
    public static function meshCorruption(string $key, string $reason): MeshCorruptionException
    {
        return new MeshCorruptionException(
            "Mesh data corruption detected for key '{$key}': {$reason}"
        );
    }

    /**
     * Create a mesh publish exception
     */
    public static function meshPublish(string $channel, string $reason): MeshPublishException
    {
        return new MeshPublishException(
            "Failed to publish to channel '{$channel}': {$reason}"
        );
    }

    /**
     * Create a mesh snapshot exception
     */
    public static function meshSnapshot(string $operation, string $reason): MeshSnapshotException
    {
        return new MeshSnapshotException(
            "Mesh snapshot {$operation} failed: {$reason}"
        );
    }

    /**
     * Create a mesh subscription exception
     */
    public static function meshSubscription(string $pattern, string $reason): MeshSubscriptionException
    {
        return new MeshSubscriptionException(
            "Subscription to pattern '{$pattern}' failed: {$reason}"
        );
    }

    /**
     * Create a safety limit exceeded exception
     */
    public static function safetyLimit(string $limit, mixed $current, mixed $maximum): SafetyLimitExceededException
    {
        return new SafetyLimitExceededException(
            "Safety limit exceeded for {$limit}: {$current} > {$maximum}"
        );
    }

    /**
     * Create an invalid unit identity exception
     */
    public static function invalidUnitIdentity(string $reason, ?string $unitId = null): InvalidUnitIdentityException
    {
        $message = $unitId 
            ? "Invalid unit identity for '{$unitId}': {$reason}"
            : "Invalid unit identity: {$reason}";
            
        return new InvalidUnitIdentityException($message);
    }

    /**
     * Create an invalid tactic exception
     */
    public static function invalidTactic(string $tactic, string $reason): InvalidTacticException
    {
        return new InvalidTacticException(
            "Invalid tactic '{$tactic}': {$reason}"
        );
    }

    /**
     * Create an invalid goal exception
     */
    public static function invalidGoal(string $goal, string $reason): InvalidGoalException
    {
        return new InvalidGoalException(
            "Invalid goal '{$goal}': {$reason}"
        );
    }

    /**
     * Create a validation exception with context
     */
    public static function validation(string $component, array $errors, array $context = []): \InvalidArgumentException
    {
        $message = "Validation failed in {$component}: " . implode(', ', $errors);
        
        if (!empty($context)) {
            $message .= " Context: " . json_encode($context);
        }
        
        return new \InvalidArgumentException($message);
    }

    /**
     * Create InvalidArgumentException with formatted message
     */
    public static function invalidArgument(string $message, array $context = []): \InvalidArgumentException
    {
        $fullMessage = empty($context) ? $message : $message . ' Context: ' . json_encode($context);
        return new \InvalidArgumentException($fullMessage);
    }

    /**
     * Create RuntimeException with formatted message
     */
    public static function runtime(string $message, array $context = []): \RuntimeException
    {
        $fullMessage = empty($context) ? $message : $message . ' Context: ' . json_encode($context);
        return new \RuntimeException($fullMessage);
    }

    /**
     * Create exception for timer operations
     */
    public static function timer(string $timerName, string $operation): \InvalidArgumentException
    {
        return new \InvalidArgumentException("Timer '{$timerName}' {$operation}");
    }

    /**
     * Create exception for file operations
     */
    public static function fileOperation(string $operation, string $path, string $reason = ''): \RuntimeException
    {
        $message = "Failed to {$operation} file: {$path}";
        if (!empty($reason)) {
            $message .= " - {$reason}";
        }
        return new \RuntimeException($message);
    }

    /**
     * Create exception for backup operations
     */
    public static function backup(string $operation, string $details = ''): \RuntimeException
    {
        $message = "Backup {$operation} failed";
        if (!empty($details)) {
            $message .= ": {$details}";
        }
        return new \RuntimeException($message);
    }

    /**
     * Create exception for circular dependencies
     */
    public static function circularDependency(string $moduleName): \RuntimeException
    {
        return new \RuntimeException("Circular dependency detected involving: {$moduleName}");
    }

    /**
     * Create exception for read-only operations
     */
    public static function readOnly(string $operation): \RuntimeException
    {
        return new \RuntimeException("Cannot perform {$operation}: resource is read-only");
    }

    /**
     * Create exception for unsupported operations
     */
    public static function unsupported(string $operation, string $context = ''): \RuntimeException
    {
        $message = "Unsupported operation: {$operation}";
        if (!empty($context)) {
            $message .= " in {$context}";
        }
        return new \RuntimeException($message);
    }

    /**
     * Create exception from another exception with additional context
     */
    public static function fromException(\Throwable $previous, string $context): \RuntimeException
    {
        return new \RuntimeException("{$context}: {$previous->getMessage()}", 0, $previous);
    }

    /**
     * Create a configuration exception
     */
    public static function configuration(string $component, string $key, string $reason): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            "Invalid configuration for {$component}.{$key}: {$reason}"
        );
    }

    /**
     * Create a timeout exception
     */
    public static function timeout(string $operation, float $duration, float $limit): \RuntimeException
    {
        return new \RuntimeException(
            "Operation '{$operation}' timed out after {$duration}s (limit: {$limit}s)"
        );
    }

    /**
     * Create a dependency exception
     */
    public static function dependency(string $component, string $dependency, string $reason): \RuntimeException
    {
        return new \RuntimeException(
            "Dependency error in {$component} for '{$dependency}': {$reason}"
        );
    }

    /**
     * Create a module exception
     */
    public static function module(string $moduleName, string $operation, string $reason): \RuntimeException
    {
        return new \RuntimeException(
            "Module '{$moduleName}' {$operation} failed: {$reason}"
        );
    }

    /**
     * Create a reactor exception
     */
    public static function reactor(string $operation, int $tickId, string $reason): \RuntimeException
    {
        return new \RuntimeException(
            "Reactor {$operation} failed on tick {$tickId}: {$reason}"
        );
    }

    /**
     * Create a unit execution exception
     */
    public static function unitExecution(string $unitId, string $reason, ?\Throwable $previous = null): \RuntimeException
    {
        return new \RuntimeException(
            "Unit '{$unitId}' execution failed: {$reason}",
            0,
            $previous
        );
    }

    /**
     * Create a tracing exception
     */
    public static function tracing(string $operation, string $spanId, string $reason): \RuntimeException
    {
        return new \RuntimeException(
            "Tracing {$operation} failed for span '{$spanId}': {$reason}"
        );
    }

    /**
     * Create a swap operation exception
     */
    public static function swapOperation(string $moduleName, string $operation, string $reason): \RuntimeException
    {
        return new \RuntimeException(
            "Hot swap {$operation} failed for module '{$moduleName}': {$reason}"
        );
    }

    /**
     * Wrap an exception with additional context
     */
    public static function wrapWithContext(\Throwable $exception, string $context): \RuntimeException
    {
        return new \RuntimeException(
            "{$context}: {$exception->getMessage()}",
            $exception->getCode(),
            $exception
        );
    }

    /**
     * Create an exception from validation result
     */
    public static function fromValidationResult(string $component, array $errors): \InvalidArgumentException
    {
        return self::validation($component, $errors);
    }
}
