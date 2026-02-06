<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Authorization;

/**
 * Authorization Result
 *
 * Represents the result of an authorization check.
 * Allows policies to return detailed responses with messages.
 *
 * Note: This is NOT an HTTP Response - it's the result of a permission check.
 */
final class Response
{
    private function __construct(
        private bool $allowed,
        private ?string $message = null
    ) {
    }

    /**
     * Create an "allowed" response
     */
    public static function allow(?string $message = null): self
    {
        return new self(true, $message);
    }

    /**
     * Create a "denied" response
     */
    public static function deny(?string $message = null): self
    {
        return new self(false, $message ?? 'This action is unauthorized.');
    }

    /**
     * Create a "denied if" response (deny when condition is true)
     */
    public static function denyIf(bool $condition, ?string $message = null): self
    {
        return $condition ? self::deny($message) : self::allow();
    }

    /**
     * Create an "allow if" response (allow when condition is true)
     */
    public static function allowIf(bool $condition, ?string $message = null): self
    {
        return $condition ? self::allow() : self::deny($message);
    }

    /**
     * Check if the response allows the action
     */
    public function allowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Check if the response denies the action
     */
    public function denied(): bool
    {
        return ! $this->allowed;
    }

    /**
     * Get the response message
     */
    public function message(): ?string
    {
        return $this->message;
    }

    /**
     * Convert to boolean
     */
    public function toBoolean(): bool
    {
        return $this->allowed;
    }

    /**
     * Throw an exception if denied
     *
     * @throws AuthorizationException
     */
    public function authorize(): self
    {
        if ($this->denied()) {
            throw new AuthorizationException($this->message);
        }

        return $this;
    }
}
