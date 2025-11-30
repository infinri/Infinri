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

/**
 * Email Verification Service
 * 
 * Handles email verification token generation and validation.
 */
class EmailVerificationService
{
    protected UserRepositoryInterface $users;
    
    /**
     * Token expiration in seconds (default: 1 hour)
     */
    protected int $expiration;

    /**
     * Throttle in seconds between verification requests
     */
    protected int $throttle;

    public function __construct(UserRepositoryInterface $users, ?array $config = null)
    {
        $this->users = $users;
        $this->expiration = $config['expiration'] ?? 3600;
        $this->throttle = $config['throttle'] ?? 60;
    }

    /**
     * Send verification email to user
     * 
     * @return array{success: bool, message: string}
     */
    public function sendVerificationEmail(AuthenticatableInterface $user): array
    {
        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Email is already verified.',
            ];
        }

        // Check throttle
        if ($this->wasRecentlySent($user)) {
            return [
                'success' => false,
                'message' => 'Please wait before requesting another verification email.',
            ];
        }

        // Generate and send verification link
        $verificationUrl = $this->generateVerificationUrl($user);
        $this->sendEmail($user, $verificationUrl);

        // Track when sent
        $this->markAsSent($user);

        return [
            'success' => true,
            'message' => 'Verification email sent.',
        ];
    }

    /**
     * Verify email using ID and hash
     * 
     * @return array{success: bool, message: string}
     */
    public function verify(int|string $id, string $hash): array
    {
        $user = $this->users->findById($id);

        if ($user === null) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'success' => true,
                'message' => 'Email is already verified.',
            ];
        }

        // Verify the hash matches user's email
        $expectedHash = $this->createHash($user);
        
        if (!hash_equals($expectedHash, $hash)) {
            return [
                'success' => false,
                'message' => 'Invalid verification link.',
            ];
        }

        // Mark as verified
        $this->users->markEmailAsVerified($user);

        return [
            'success' => true,
            'message' => 'Email verified successfully.',
        ];
    }

    /**
     * Generate verification URL for user
     */
    public function generateVerificationUrl(AuthenticatableInterface $user): string
    {
        $id = $user->getAuthIdentifier();
        $hash = $this->createHash($user);
        $expires = time() + $this->expiration;
        $signature = $this->createSignature($id, $hash, $expires);

        $baseUrl = config('app.url', '');
        $path = sprintf(
            '/email/verify/%s/%s?expires=%d&signature=%s',
            $id,
            $hash,
            $expires,
            $signature
        );

        return rtrim($baseUrl, '/') . $path;
    }

    /**
     * Verify URL signature
     */
    public function verifySignature(int|string $id, string $hash, int $expires, string $signature): bool
    {
        // Check expiration
        if (time() > $expires) {
            return false;
        }

        // Verify signature
        $expectedSignature = $this->createSignature($id, $hash, $expires);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create hash for user's email
     */
    protected function createHash(AuthenticatableInterface $user): string
    {
        $email = method_exists($user, 'getEmailForPasswordReset')
            ? $user->getEmailForPasswordReset()
            : '';

        return sha1($email);
    }

    /**
     * Create URL signature
     */
    protected function createSignature(int|string $id, string $hash, int $expires): string
    {
        $key = config('app.key', '');
        $data = "{$id}|{$hash}|{$expires}";
        
        return hash_hmac('sha256', $data, $key);
    }

    /**
     * Check if verification email was recently sent
     */
    protected function wasRecentlySent(AuthenticatableInterface $user): bool
    {
        $key = 'email_verification_sent:' . $user->getAuthIdentifier();
        $lastSent = session($key);

        if ($lastSent === null) {
            return false;
        }

        return (time() - (int) $lastSent) < $this->throttle;
    }

    /**
     * Mark verification as sent
     */
    protected function markAsSent(AuthenticatableInterface $user): void
    {
        $key = 'email_verification_sent:' . $user->getAuthIdentifier();
        session()->set($key, time());
    }

    /**
     * Send verification email
     * TODO: Integrate with Mail system
     */
    protected function sendEmail(AuthenticatableInterface $user, string $verificationUrl): void
    {
        $email = method_exists($user, 'getEmailForPasswordReset')
            ? $user->getEmailForPasswordReset()
            : '';

        // TODO: Use proper mail system
        // Mail::to($email)->send(new VerifyEmailMail($user, $verificationUrl));
        
        // Log for development using Core's logger
        if (config('app.debug', false)) {
            logger()->debug("Email verification link for {$email}: {$verificationUrl}");
        }
    }
}
