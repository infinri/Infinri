<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Contracts;

/**
 * User Repository Interface
 * 
 * Contract for retrieving users for authentication.
 */
interface UserRepositoryInterface
{
    /**
     * Retrieve a user by their unique identifier
     */
    public function findById(int|string $id): ?AuthenticatableInterface;

    /**
     * Retrieve a user by their email address
     */
    public function findByEmail(string $email): ?AuthenticatableInterface;

    /**
     * Retrieve a user by their remember token
     */
    public function findByRememberToken(int|string $id, string $token): ?AuthenticatableInterface;

    /**
     * Retrieve a user by a unique token (e.g., password reset, email verification)
     */
    public function findByToken(string $token, string $type = 'password_reset'): ?AuthenticatableInterface;

    /**
     * Update the "remember me" token for the given user
     */
    public function updateRememberToken(AuthenticatableInterface $user, string $token): void;

    /**
     * Create a new user
     *
     * @param array{name: string, email: string, password: string} $data
     */
    public function create(array $data): AuthenticatableInterface;

    /**
     * Update the user's password
     */
    public function updatePassword(AuthenticatableInterface $user, string $hashedPassword): void;

    /**
     * Update the user's two-factor secret
     */
    public function updateTwoFactorSecret(AuthenticatableInterface $user, ?string $secret): void;

    /**
     * Update the user's two-factor recovery codes
     *
     * @param string[] $codes
     */
    public function updateTwoFactorRecoveryCodes(AuthenticatableInterface $user, array $codes): void;

    /**
     * Mark user's email as verified
     */
    public function markEmailAsVerified(AuthenticatableInterface $user): bool;
}
