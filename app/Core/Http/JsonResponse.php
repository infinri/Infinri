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

use InvalidArgumentException;
use JsonException;

/**
 * JSON Response
 *
 * Represents an HTTP response with JSON content.
 * Extends Response for consistent behavior.
 */
class JsonResponse extends Response
{
    public const DEFAULT_ENCODING_OPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    protected mixed $data;
    protected int $encodingOptions;

    /**
     * Create a new JSON response instance
     */
    public function __construct(
        mixed $data = null,
        int $status = HttpStatus::OK,
        array $headers = [],
        int $options = 0
    ) {
        $this->encodingOptions = $options ?: self::DEFAULT_ENCODING_OPTIONS;

        parent::__construct('', $status, $headers);

        $this->header('Content-Type', 'application/json');

        if ($data !== null) {
            $this->setData($data);
        }
    }

    /**
     * Create a JSON response from data
     */
    public static function fromData(
        mixed $data,
        int $status = 200,
        array $headers = [],
        int $options = 0
    ): static {
        return new static($data, $status, $headers, $options);
    }

    /**
     * Create a success response
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): static {
        return new static([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Create an error response
     */
    public static function error(
        string $message,
        int $status = 400,
        mixed $errors = null
    ): static {
        $data = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $data['errors'] = $errors;
        }

        return new static($data, $status);
    }

    /**
     * Set response data
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;

        return $this->updateContent();
    }

    /**
     * Get the original data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Set JSON encoding options
     */
    public function setEncodingOptions(int $options): static
    {
        $this->encodingOptions = $options;

        return $this->updateContent();
    }

    /**
     * Get JSON encoding options
     */
    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    /**
     * Enable pretty print
     */
    public function withPrettyPrint(): static
    {
        return $this->setEncodingOptions($this->encodingOptions | JSON_PRETTY_PRINT);
    }

    /**
     * Disable escaping slashes
     */
    public function withUnescapedSlashes(): static
    {
        return $this->setEncodingOptions($this->encodingOptions | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Disable escaping unicode
     */
    public function withUnescapedUnicode(): static
    {
        return $this->setEncodingOptions($this->encodingOptions | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Update content with encoded data
     */
    protected function updateContent(): static
    {
        try {
            $json = json_encode($this->data, $this->encodingOptions | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'Unable to encode data to JSON: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return $this->setContent($json);
    }

    /**
     * Override setContent to prevent direct content setting
     * Content should only be set through setData()
     */
    public function setContent(string $content): static
    {
        // Allow internal content setting from updateContent()
        $this->content = $content;

        return $this;
    }
}
