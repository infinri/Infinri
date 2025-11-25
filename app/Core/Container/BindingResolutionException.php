<?php

declare(strict_types=1);

namespace App\Core\Container;

use Exception;

/**
 * Binding Resolution Exception
 * 
 * Thrown when the container cannot resolve a dependency
 */
class BindingResolutionException extends Exception
{
    /**
     * Create exception for unresolvable binding
     *
     * @param string $abstract The type that couldn't be resolved
     * @param string|null $message Optional custom message
     * @return static
     */
    public static function unresolvable(string $abstract, ?string $message = null): static
    {
        $message = $message ?? "Unable to resolve binding for [{$abstract}]";
        
        return new static($message);
    }

    /**
     * Create exception for circular dependency
     *
     * @param string $abstract The type causing the circular dependency
     * @return static
     */
    public static function circularDependency(string $abstract): static
    {
        return new static(
            "Circular dependency detected while resolving [{$abstract}]"
        );
    }

    /**
     * Create exception for uninstantiable type
     *
     * @param string $abstract The type that can't be instantiated
     * @param string|null $reason Optional reason
     * @return static
     */
    public static function uninstantiable(string $abstract, ?string $reason = null): static
    {
        $message = "Target [{$abstract}] is not instantiable";
        
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        return new static($message);
    }
}
