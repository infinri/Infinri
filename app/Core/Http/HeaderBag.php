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
namespace App\Core\Http;

use App\Core\Support\Str;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Header Bag
 *
 * Container for HTTP headers with case-insensitive access
 */
class HeaderBag implements IteratorAggregate, Countable
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $headers = [];

    /**
     * @var array<string, string>
     */
    protected array $cacheControl = [];

    /**
     * Create a new header bag
     *
     * @param array<string, string|array<int, string>> $headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Get all headers
     *
     * @return array<string, array<int, string>>
     */
    public function all(): array
    {
        return $this->headers;
    }

    /**
     * Get header keys
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->headers);
    }

    /**
     * Replace all headers
     *
     * @param array<string, string|array<int, string>> $headers
     */
    public function replace(array $headers): void
    {
        $this->headers = [];
        $this->add($headers);
    }

    /**
     * Add headers (merge with existing)
     *
     * @param array<string, string|array<int, string>> $headers
     */
    public function add(array $headers): void
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Get header value (first value if multiple)
     *
     * @param string $key
     * @param string|null $default
     *
     * @return string|null
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $key = $this->normalizeKey($key);

        if (! array_key_exists($key, $this->headers)) {
            return $default;
        }

        return $this->headers[$key][0] ?? $default;
    }

    /**
     * Set header value
     *
     * @param string $key
     * @param string|array<int, string> $values
     * @param bool $replace
     */
    public function set(string $key, string|array $values, bool $replace = true): void
    {
        $key = $this->normalizeKey($key);
        $values = is_array($values) ? array_values($values) : [$values];

        if ($replace || ! isset($this->headers[$key])) {
            $this->headers[$key] = $values;
        } else {
            $this->headers[$key] = array_merge($this->headers[$key], $values);
        }
    }

    /**
     * Check if header exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($this->normalizeKey($key), $this->headers);
    }

    /**
     * Remove a header
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        $key = $this->normalizeKey($key);
        unset($this->headers[$key]);
    }

    /**
     * Get Content-Type header
     *
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->get('Content-Type');
    }

    /**
     * Get Content-Length header
     *
     * @return int|null
     */
    public function getContentLength(): ?int
    {
        $length = $this->get('Content-Length');

        return $length !== null ? (int) $length : null;
    }

    /**
     * Get iterator
     *
     * @return Traversable<string, array<int, string>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->headers);
    }

    /**
     * Get count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     * Normalize header key (lowercase with dashes)
     *
     * @param string $key
     *
     * @return string
     */
    protected function normalizeKey(string $key): string
    {
        return str_replace('_', '-', strtolower($key));
    }

    /**
     * Convert headers to string format for HTTP response
     *
     * @return string
     */
    public function __toString(): string
    {
        $headers = [];

        foreach ($this->headers as $name => $values) {
            $name = Str::headerToHttpFormat($name);
            foreach ($values as $value) {
                $headers[] = "$name: $value";
            }
        }

        return implode("\r\n", $headers);
    }
}
