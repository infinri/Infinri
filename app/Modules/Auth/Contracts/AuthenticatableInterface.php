<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Contracts;

use App\Core\Contracts\Auth\AuthorizableInterface;

/**
 * Authenticatable Interface
 * 
 * Extends AuthorizableInterface (from Core) with authentication-specific methods.
 * User model MUST implement this interface.
 */
interface AuthenticatableInterface extends AuthorizableInterface
{
    /**
     * Get the name of the unique identifier for the user (primary key column)
     */
    public function getAuthIdentifierName(): string;

    /**
     * Get the password for the user
     */
    public function getAuthPassword(): string;

    /**
     * Get the token value for the "remember me" session
     */
    public function getRememberToken(): ?string;

    /**
     * Set the token value for the "remember me" session
     */
    public function setRememberToken(string $value): void;

    /**
     * Get the column name for the "remember me" token
     */
    public function getRememberTokenName(): string;

    /**
     * Get the email address for the user (for password resets, notifications)
     */
    public function getEmailForPasswordReset(): string;

    /**
     * Check if the user's email has been verified
     */
    public function hasVerifiedEmail(): bool;

    /**
     * Mark the user's email as verified
     */
    public function markEmailAsVerified(): bool;

    /**
     * Get the two-factor authentication secret
     */
    public function getTwoFactorSecret(): ?string;

    /**
     * Check if the user has two-factor authentication enabled
     */
    public function hasTwoFactorEnabled(): bool;

    /**
     * Get the two-factor recovery codes
     *
     * @return string[]
     */
    public function getTwoFactorRecoveryCodes(): array;
}
