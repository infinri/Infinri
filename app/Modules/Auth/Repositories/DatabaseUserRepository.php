<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Services\PasswordService;

/**
 * Database User Repository
 * 
 * Retrieves users from the database using the Model class.
 */
class DatabaseUserRepository implements UserRepositoryInterface
{
    /**
     * The user model class
     * 
     * @var class-string<AuthenticatableInterface>
     */
    protected string $model;

    /**
     * Password service for managing the passwords table
     */
    protected ?PasswordService $passwordService = null;

    /**
     * Create a new database user repository
     *
     * @param class-string<AuthenticatableInterface> $model
     */
    public function __construct(string $model, ?PasswordService $passwordService = null)
    {
        $this->model = $model;
        $this->passwordService = $passwordService;
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int|string $id): ?AuthenticatableInterface
    {
        return $this->createModel()::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?AuthenticatableInterface
    {
        return $this->createModel()::where('email', $email)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByRememberToken(int|string $id, string $token): ?AuthenticatableInterface
    {
        $user = $this->findById($id);

        if ($user === null) {
            return null;
        }

        $rememberToken = $user->getRememberToken();

        // Timing-safe comparison
        if ($rememberToken !== null && hash_equals($rememberToken, $token)) {
            return $user;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByToken(string $token, string $type = 'password_reset'): ?AuthenticatableInterface
    {
        return match ($type) {
            'password_reset' => $this->findByPasswordResetToken($token),
            'email_verification' => $this->findByEmailVerificationToken($token),
            'api_token' => $this->findByApiToken($token),
            default => null,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function updateRememberToken(AuthenticatableInterface $user, string $token): void
    {
        $user->setRememberToken($token);

        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): AuthenticatableInterface
    {
        $model = $this->createModel();

        $model->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Should already be hashed
        ]);

        $model->save();

        return $model;
    }

    /**
     * {@inheritDoc}
     * 
     * Note: Password is stored in the `passwords` table, not on user.
     * The $hashedPassword param is already hashed by the caller.
     */
    public function updatePassword(AuthenticatableInterface $user, string $hashedPassword): void
    {
        if ($this->passwordService !== null) {
            // Use PasswordService to store in passwords table
            // Note: We need plain password, but interface receives hash
            // This is called from places that already hash, so we store directly
            $this->storePasswordHash($user->getAuthIdentifier(), $hashedPassword);
        } else {
            // Fallback: store on user table (legacy behavior)
            if (method_exists($user, 'setAttribute')) {
                $user->setAttribute('password', $hashedPassword);
            }
            if (method_exists($user, 'save')) {
                $user->save();
            }
        }
    }

    /**
     * Store a pre-hashed password in the passwords table
     */
    protected function storePasswordHash(int|string $userId, string $hashedPassword): void
    {
        if ($this->passwordService === null) {
            return;
        }

        // Use raw DB to store pre-hashed password (bypassing PasswordService hashing)
        $db = app('db');
        
        $db->transaction(function () use ($db, $userId, $hashedPassword) {
            // Mark existing as not current
            $db->update(
                'UPDATE "passwords" SET is_current = false WHERE user_id = ?',
                [$userId]
            );
            
            // Insert new
            $db->insert(
                'INSERT INTO "passwords" (user_id, password_hash, is_current, created_at, updated_at) VALUES (?, ?, true, NOW(), NOW())',
                [$userId, $hashedPassword]
            );
        });
    }

    /**
     * {@inheritDoc}
     */
    public function updateTwoFactorSecret(AuthenticatableInterface $user, ?string $secret): void
    {
        if (method_exists($user, 'setAttribute')) {
            $user->setAttribute('two_factor_secret', $secret);

            if ($secret === null) {
                $user->setAttribute('two_factor_confirmed_at', null);
            }
        }

        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateTwoFactorRecoveryCodes(AuthenticatableInterface $user, array $codes): void
    {
        if (method_exists($user, 'setAttribute')) {
            $user->setAttribute('two_factor_recovery_codes', json_encode($codes));
        }

        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function markEmailAsVerified(AuthenticatableInterface $user): bool
    {
        return $user->markEmailAsVerified();
    }

    /**
     * Find user by password reset token
     */
    protected function findByPasswordResetToken(string $token): ?AuthenticatableInterface
    {
        // Query the password_resets table
        $reset = db()->table('password_resets')
            ->where('token', hash('sha256', $token))
            ->first();

        if ($reset === null) {
            return null;
        }

        return $this->findByEmail($reset['email']);
    }

    /**
     * Find user by email verification token
     */
    protected function findByEmailVerificationToken(string $token): ?AuthenticatableInterface
    {
        // Email verification uses signed URLs, not stored tokens
        // The token contains the user ID and signature
        // This is handled by the verification controller
        return null;
    }

    /**
     * Find user by API token
     */
    protected function findByApiToken(string $token): ?AuthenticatableInterface
    {
        // Query personal_access_tokens table
        $accessToken = db()->table('personal_access_tokens')
            ->where('token', $token)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->first();

        if ($accessToken === null) {
            return null;
        }

        // Update last_used_at
        db()->table('personal_access_tokens')
            ->where('id', $accessToken['id'])
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        return $this->findById($accessToken['user_id']);
    }

    /**
     * Create a new instance of the model
     */
    protected function createModel(): AuthenticatableInterface
    {
        return new $this->model();
    }
}
