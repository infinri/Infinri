<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Contracts;

use App\Core\Contracts\Auth\AuthorizableInterface;

/**
 * Guard Interface
 * 
 * Contract for authentication guards (session, token, etc.)
 */
interface GuardInterface
{
    /**
     * Determine if the current user is authenticated
     */
    public function check(): bool;

    /**
     * Determine if the current user is a guest (not authenticated)
     */
    public function guest(): bool;

    /**
     * Get the currently authenticated user
     */
    public function user(): ?AuthorizableInterface;

    /**
     * Get the ID for the currently authenticated user
     */
    public function id(): int|string|null;

    /**
     * Validate a user's credentials
     *
     * @param array{email?: string, password?: string} $credentials
     */
    public function validate(array $credentials = []): bool;

    /**
     * Attempt to authenticate a user using the given credentials
     *
     * @param array{email?: string, password?: string} $credentials
     * @param bool $remember Create a remember token
     * @return bool True if authentication was successful
     */
    public function attempt(array $credentials = [], bool $remember = false): bool;

    /**
     * Log a user into the application without sessions or cookies
     * (useful for API tokens or testing)
     */
    public function once(array $credentials = []): bool;

    /**
     * Log a user into the application
     *
     * @param AuthorizableInterface $user The user to authenticate
     * @param bool $remember Create a remember token
     */
    public function login(AuthorizableInterface $user, bool $remember = false): void;

    /**
     * Log the given user ID into the application
     *
     * @param int|string $id The user ID
     * @param bool $remember Create a remember token
     */
    public function loginUsingId(int|string $id, bool $remember = false): ?AuthorizableInterface;

    /**
     * Log the given user ID into the application without sessions or cookies
     */
    public function onceUsingId(int|string $id): ?AuthorizableInterface;

    /**
     * Determine if the user was authenticated via "remember me" cookie
     */
    public function viaRemember(): bool;

    /**
     * Log the user out of the application
     */
    public function logout(): void;

    /**
     * Get the name of the guard
     */
    public function getName(): string;
}
