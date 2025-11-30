<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Security\PasswordHasher;

/**
 * Password Reset Service
 * 
 * Handles password reset token generation, validation, and execution.
 */
class PasswordResetService
{
    protected UserRepositoryInterface $users;
    protected PasswordHasher $hasher;
    
    /**
     * Token expiration in seconds (default: 1 hour)
     */
    protected int $expiration;

    /**
     * Throttle in seconds between reset requests
     */
    protected int $throttle;

    public function __construct(
        UserRepositoryInterface $users,
        PasswordHasher $hasher,
        ?array $config = null
    ) {
        $this->users = $users;
        $this->hasher = $hasher;
        $this->expiration = $config['expiration'] ?? 3600;
        $this->throttle = $config['throttle'] ?? 60;
    }

    /**
     * Send password reset link to user
     * 
     * @return array{success: bool, message: string}
     */
    public function sendResetLink(AuthenticatableInterface $user): array
    {
        $email = $user->getEmailForPasswordReset();

        // Check if recently requested (throttle)
        if ($this->recentlyCreated($email)) {
            return [
                'success' => false,
                'message' => 'Please wait before requesting another reset link.',
            ];
        }

        // Generate token
        $token = $this->createToken($email);

        // Store token (hashed)
        $this->storeToken($email, $token);

        // Send email
        $this->sendResetEmail($user, $token);

        return [
            'success' => true,
            'message' => 'Reset link sent.',
        ];
    }

    /**
     * Reset password using token
     * 
     * @return array{success: bool, message: string, user?: AuthenticatableInterface}
     */
    public function reset(string $email, string $token, string $password): array
    {
        // Validate token
        if (!$this->tokenExists($email, $token)) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ];
        }

        // Find user
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        // Update password
        $this->users->updatePassword($user, $this->hasher->hash($password));

        // Delete token
        $this->deleteToken($email);

        return [
            'success' => true,
            'message' => 'Password reset successful.',
            'user' => $user,
        ];
    }

    /**
     * Generate a new reset token
     */
    protected function createToken(string $email): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Store the token in database (hashed)
     */
    protected function storeToken(string $email, string $token): void
    {
        $hashedToken = hash('sha256', $token);
        $createdAt = date('Y-m-d H:i:s');

        // Delete any existing token for this email
        $this->deleteToken($email);

        // Insert new token
        db()->table('password_resets')->insert([
            'email' => $email,
            'token' => $hashedToken,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * Check if token exists and is valid
     */
    protected function tokenExists(string $email, string $token): bool
    {
        $hashedToken = hash('sha256', $token);

        $record = db()->table('password_resets')
            ->where('email', $email)
            ->where('token', $hashedToken)
            ->first();

        if ($record === null) {
            return false;
        }

        // Check expiration
        $createdAt = strtotime($record['created_at'] ?? '');
        if (time() - $createdAt > $this->expiration) {
            $this->deleteToken($email);
            return false;
        }

        return true;
    }

    /**
     * Delete token for email
     */
    protected function deleteToken(string $email): void
    {
        db()->table('password_resets')
            ->where('email', $email)
            ->delete();
    }

    /**
     * Check if a token was recently created (throttle)
     */
    protected function recentlyCreated(string $email): bool
    {
        $record = db()->table('password_resets')
            ->where('email', $email)
            ->first();

        if ($record === null) {
            return false;
        }

        $createdAt = strtotime($record['created_at'] ?? '');
        return (time() - $createdAt) < $this->throttle;
    }

    /**
     * Send reset email to user
     * TODO: Integrate with Mail system
     */
    protected function sendResetEmail(AuthenticatableInterface $user, string $token): void
    {
        $email = $user->getEmailForPasswordReset();
        $resetUrl = $this->buildResetUrl($token, $email);

        // TODO: Use proper mail system
        // Mail::to($email)->send(new ResetPasswordMail($user, $resetUrl));
        
        // Log for development using Core's logger
        if (config('app.debug', false)) {
            logger()->debug("Password reset link for {$email}: {$resetUrl}");
        }
    }

    /**
     * Build password reset URL
     */
    protected function buildResetUrl(string $token, string $email): string
    {
        $baseUrl = config('app.url', '');
        $path = '/reset-password/' . $token . '?email=' . urlencode($email);
        
        return rtrim($baseUrl, '/') . $path;
    }
}
