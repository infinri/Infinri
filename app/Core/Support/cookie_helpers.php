<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */

// ==================== Cookie Helpers ====================

if (! function_exists('cookie')) {
    /**
     * Create a new cookie instance or get a cookie value
     *
     * @param string|null $name Cookie name (null to get CookieFactory)
     * @param string $value Cookie value
     * @param int $minutes Expiration in minutes (0 = session)
     * @param array $options Additional options (path, domain, secure, httpOnly, sameSite)
     *
     * @return \App\Core\Http\Cookie|string|null
     */
    function cookie(
        ?string $name = null,
        string $value = '',
        int $minutes = 0,
        array $options = []
    ): \App\Core\Http\Cookie|string|null {
        if ($name === null) {
            return null;
        }

        if ($value === '' && $minutes === 0 && $options === []) {
            return cookie_get($name);
        }

        return \App\Core\Http\Cookie::make($name, $value, array_merge(['minutes' => $minutes], $options));
    }
}

if (! function_exists('cookie_get')) {
    /**
     * Get a cookie value with validation and type safety
     *
     * @param string $name Cookie name
     * @param mixed $default Default value if cookie doesn't exist
     *
     * @return mixed Sanitized cookie value
     */
    function cookie_get(string $name, mixed $default = null): mixed
    {
        $app = app();
        if ($app->bound('request')) {
            $request = $app->make('request');
            if (method_exists($request, 'cookie')) {
                return $request->cookie($name, $default);
            }
        }

        $value = $_COOKIE[$name] ?? null;

        if ($value === null) {
            return $default;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return $default;
            }
        }

        return $value;
    }
}

if (! function_exists('cookie_string')) {
    /**
     * Get a cookie as a validated string
     *
     * @param string $name Cookie name
     * @param string $default Default value
     * @param int $maxLength Maximum allowed length (0 = no limit)
     *
     * @return string
     */
    function cookie_string(string $name, string $default = '', int $maxLength = 0): string
    {
        $value = cookie_get($name, $default);

        if (! is_string($value)) {
            return $default;
        }

        if ($maxLength > 0 && strlen($value) > $maxLength) {
            return $default;
        }

        return $value;
    }
}

if (! function_exists('cookie_int')) {
    /**
     * Get a cookie as a validated integer
     *
     * @param string $name Cookie name
     * @param int $default Default value
     * @param int|null $min Minimum allowed value
     * @param int|null $max Maximum allowed value
     *
     * @return int
     */
    function cookie_int(string $name, int $default = 0, ?int $min = null, ?int $max = null): int
    {
        $value = cookie_get($name);

        if ($value === null || ! is_numeric($value)) {
            return $default;
        }

        $intValue = (int) $value;

        if ($min !== null && $intValue < $min) {
            return $default;
        }
        if ($max !== null && $intValue > $max) {
            return $default;
        }

        return $intValue;
    }
}

if (! function_exists('cookie_bool')) {
    /**
     * Get a cookie as a validated boolean
     *
     * @param string $name Cookie name
     * @param bool $default Default value
     *
     * @return bool
     */
    function cookie_bool(string $name, bool $default = false): bool
    {
        $value = cookie_get($name);

        if ($value === null) {
            return $default;
        }

        $trueValues = ['1', 'true', 'yes', 'on'];
        $falseValues = ['0', 'false', 'no', 'off'];

        $lower = strtolower((string) $value);

        if (in_array($lower, $trueValues, true)) {
            return true;
        }
        if (in_array($lower, $falseValues, true)) {
            return false;
        }

        return $default;
    }
}

if (! function_exists('cookie_json')) {
    /**
     * Get a cookie as decoded JSON
     *
     * @param string $name Cookie name
     * @param array $default Default value
     *
     * @return array
     */
    function cookie_json(string $name, array $default = []): array
    {
        $value = cookie_get($name);

        if ($value === null || ! is_string($value)) {
            return $default;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : $default;
        } catch (\JsonException) {
            return $default;
        }
    }
}

if (! function_exists('cookie_forget')) {
    /**
     * Create a cookie that forgets/deletes an existing cookie
     *
     * @param string $name Cookie name
     * @param string $path Cookie path
     *
     * @return \App\Core\Http\Cookie
     */
    function cookie_forget(string $name, string $path = '/'): \App\Core\Http\Cookie
    {
        return \App\Core\Http\Cookie::forget($name, $path);
    }
}
