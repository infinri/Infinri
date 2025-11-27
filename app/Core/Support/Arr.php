<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Array Helper
 * 
 * Centralized array manipulation utilities.
 * Single source of truth for common array operations.
 */
final class Arr
{
    /**
     * Get a value from an array using dot notation
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        
        if (!str_contains($key, '.')) {
            return $default;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }

    /**
     * Set a value in an array using dot notation
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $i => $segment) {
            if (count($keys) === 1) {
                break;
            }
            
            unset($keys[$i]);
            
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            
            $current = &$current[$segment];
        }
        
        $current[array_shift($keys)] = $value;
    }

    /**
     * Check if a key exists using dot notation
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }
        
        if (!str_contains($key, '.')) {
            return false;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }
        
        return true;
    }

    /**
     * Flatten a multi-dimensional array into a single level
     */
    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];
        
        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, self::flatten($item, $depth - 1));
            }
        }
        
        return $result;
    }

    /**
     * Check if array is associative
     */
    public static function isAssoc(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Recursively merge arrays (overwrites values, not numeric keys)
     */
    public static function mergeRecursive(array $array1, array $array2): array
    {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::mergeRecursive($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
}
