<?php
declare(strict_types=1);
/**
 * Environment Helper
 *
 * Centralized environment variable access with type casting
 *
 * @package App\Helpers
 */

namespace App\Helpers;

class Env
{
    /**
     * Get environment variable with type casting
     *
     * @param string $key Environment key
     * @param mixed $default Default value if key not found
     * @param string $type Type to cast to (string|bool|int|float|array)
     * @return mixed
     */
    public static function get(string $key, $default = null, string $type = 'string')
    {
        // Security: Validate type parameter
        $allowedTypes = ['string', 'bool', 'int', 'float', 'array'];
        if (! in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid type: {$type}");
        }

        $value = $_ENV[$key] ?? $default;

        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int)$value,
            'float' => (float)$value,
            'array' => is_string($value) ? explode(',', $value) : (array)$value,
            default => (string)$value
        };
    }

    /**
     * Get required environment variable or throw exception
     *
     * @param string $key Environment key
     * @param string $type Type to cast to
     * @return mixed
     * @throws \RuntimeException
     */
    public static function require(string $key, string $type = 'string')
    {
        if (! array_key_exists($key, $_ENV)) {
            throw new \RuntimeException("Required environment variable '{$key}' is not set");
        }

        return self::get($key, null, $type);
    }
}
