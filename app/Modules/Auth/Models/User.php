<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Models;

use App\Core\Database\Model;
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Models\Concerns\HasRoles;

/**
 * User Model
 * 
 * Implements AuthenticatableInterface which extends Core's AuthorizableInterface.
 * This provides full integration with both Auth module and Core's Gate/Policy system.
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string $created_at
 * @property string $updated_at
 */
class User extends Model implements AuthenticatableInterface
{
    use HasRoles;
    /**
     * The table associated with the model
     */
    protected string $table = 'users';

    /**
     * The attributes that are mass assignable
     */
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization
     */
    protected array $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast
     */
    protected array $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
    ];

    // =========================================================================
    // AuthorizableInterface (from Core) - getAuthIdentifier() + HasRoles trait
    // =========================================================================

    /**
     * Get the unique identifier for the user
     */
    public function getAuthIdentifier(): int|string
    {
        return $this->getKey();
    }

    // =========================================================================
    // AuthenticatableInterface (Auth-specific) - Required for Authentication
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function getAuthIdentifierName(): string
    {
        return $this->primaryKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthPassword(): string
    {
        return $this->getAttribute('password') ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function getRememberToken(): ?string
    {
        return $this->getAttribute($this->getRememberTokenName());
    }

    /**
     * {@inheritDoc}
     */
    public function setRememberToken(string $value): void
    {
        $this->setAttribute($this->getRememberTokenName(), $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->getAttribute('email') ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->getAttribute('email_verified_at') !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function markEmailAsVerified(): bool
    {
        $this->setAttribute('email_verified_at', date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * {@inheritDoc}
     */
    public function getTwoFactorSecret(): ?string
    {
        return $this->getAttribute('two_factor_secret');
    }

    /**
     * {@inheritDoc}
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->getAttribute('two_factor_secret') !== null
            && $this->getAttribute('two_factor_confirmed_at') !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getTwoFactorRecoveryCodes(): array
    {
        $codes = $this->getAttribute('two_factor_recovery_codes');

        if ($codes === null) {
            return [];
        }

        return json_decode($codes, true) ?? [];
    }

}
