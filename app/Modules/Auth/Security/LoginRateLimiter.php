<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Modules\Auth\Security;

use App\Core\Contracts\Cache\CacheInterface;
use App\Core\Security\RateLimiter;

/**
 * Login Rate Limiter
 * 
 * Specialized rate limiting for login attempts to prevent:
 * - Brute-force attacks
 * - Credential stuffing
 * - Account enumeration
 * 
 * Features:
 * - IP-based rate limiting
 * - Email/username-based rate limiting
 * - Combined IP + email rate limiting
 * - Progressive delays (exponential backoff)
 * - Account lockout after threshold
 * - Clear on successful login
 */
final class LoginRateLimiter
{
    private RateLimiter $limiter;

    /**
     * Maximum failed attempts by IP (generous for shared IPs)
     */
    private int $maxAttemptsByIp = 50;

    /**
     * Maximum failed attempts by email/username
     */
    private int $maxAttemptsByEmail = 5;

    /**
     * Maximum failed attempts by IP + email combination
     */
    private int $maxAttemptsByCombo = 10;

    /**
     * Decay time in seconds (how long attempts are remembered)
     */
    private int $decaySeconds = 900; // 15 minutes

    /**
     * Lockout duration in seconds after max attempts
     */
    private int $lockoutSeconds = 3600; // 1 hour

    /**
     * Enable progressive delays
     */
    private bool $progressiveDelays = true;

    /**
     * Cache key prefixes
     */
    private const PREFIX_IP = 'login_ip:';
    private const PREFIX_EMAIL = 'login_email:';
    private const PREFIX_COMBO = 'login_combo:';
    private const PREFIX_LOCKOUT = 'login_lockout:';

    public function __construct(CacheInterface $cache, ?array $config = null)
    {
        $this->limiter = new RateLimiter($cache);

        if ($config !== null) {
            $this->maxAttemptsByIp = $config['max_by_ip'] ?? $this->maxAttemptsByIp;
            $this->maxAttemptsByEmail = $config['max_by_email'] ?? $this->maxAttemptsByEmail;
            $this->maxAttemptsByCombo = $config['max_by_combo'] ?? $this->maxAttemptsByCombo;
            $this->decaySeconds = $config['decay_seconds'] ?? $this->decaySeconds;
            $this->lockoutSeconds = $config['lockout_seconds'] ?? $this->lockoutSeconds;
            $this->progressiveDelays = $config['progressive_delays'] ?? $this->progressiveDelays;
        }
    }

    /**
     * Check if a login attempt is allowed
     *
     * @param string $ip Client IP address
     * @param string $email Email or username being attempted
     * @return array{allowed: bool, reason: string|null, retry_after: int|null, attempts: array}
     */
    public function check(string $ip, string $email): array
    {
        $email = $this->normalizeEmail($email);
        
        $ipKey = self::PREFIX_IP . $this->hashKey($ip);
        $emailKey = self::PREFIX_EMAIL . $this->hashKey($email);
        $comboKey = self::PREFIX_COMBO . $this->hashKey($ip . '|' . $email);
        $lockoutKey = self::PREFIX_LOCKOUT . $this->hashKey($email);

        // Check if account is locked out
        if ($this->isLockedOut($lockoutKey)) {
            return [
                'allowed' => false,
                'reason' => 'account_locked',
                'retry_after' => $this->getLockoutRemaining($lockoutKey),
                'attempts' => $this->getAttemptCounts($ipKey, $emailKey, $comboKey),
            ];
        }

        // Check rate limits
        if ($this->limiter->tooManyAttempts($emailKey, $this->maxAttemptsByEmail)) {
            return [
                'allowed' => false,
                'reason' => 'too_many_attempts_email',
                'retry_after' => $this->decaySeconds,
                'attempts' => $this->getAttemptCounts($ipKey, $emailKey, $comboKey),
            ];
        }

        if ($this->limiter->tooManyAttempts($ipKey, $this->maxAttemptsByIp)) {
            return [
                'allowed' => false,
                'reason' => 'too_many_attempts_ip',
                'retry_after' => $this->decaySeconds,
                'attempts' => $this->getAttemptCounts($ipKey, $emailKey, $comboKey),
            ];
        }

        if ($this->limiter->tooManyAttempts($comboKey, $this->maxAttemptsByCombo)) {
            return [
                'allowed' => false,
                'reason' => 'too_many_attempts_combined',
                'retry_after' => $this->decaySeconds,
                'attempts' => $this->getAttemptCounts($ipKey, $emailKey, $comboKey),
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'retry_after' => null,
            'attempts' => $this->getAttemptCounts($ipKey, $emailKey, $comboKey),
        ];
    }

    /**
     * Record a failed login attempt
     *
     * @param string $ip Client IP address
     * @param string $email Email or username attempted
     * @return array{locked_out: bool, attempts: array, delay: int}
     */
    public function recordFailure(string $ip, string $email): array
    {
        $email = $this->normalizeEmail($email);
        
        $ipKey = self::PREFIX_IP . $this->hashKey($ip);
        $emailKey = self::PREFIX_EMAIL . $this->hashKey($email);
        $comboKey = self::PREFIX_COMBO . $this->hashKey($ip . '|' . $email);
        $lockoutKey = self::PREFIX_LOCKOUT . $this->hashKey($email);

        // Increment all counters
        $this->limiter->hit($ipKey, $this->decaySeconds);
        $emailAttempts = $this->limiter->hit($emailKey, $this->decaySeconds);
        $this->limiter->hit($comboKey, $this->decaySeconds);

        // Check if we should lock out the account
        $lockedOut = false;
        if ($emailAttempts >= $this->maxAttemptsByEmail * 2) {
            $this->lockout($lockoutKey);
            $lockedOut = true;
        }

        // Calculate progressive delay
        $delay = $this->calculateDelay($emailAttempts);

        return [
            'locked_out' => $lockedOut,
            'attempts' => $this->getAttemptCounts($ipKey, $emailKey, $comboKey),
            'delay' => $delay,
        ];
    }

    /**
     * Clear rate limits after successful login
     *
     * @param string $ip Client IP address
     * @param string $email Email or username
     */
    public function clearOnSuccess(string $ip, string $email): void
    {
        $email = $this->normalizeEmail($email);
        
        $emailKey = self::PREFIX_EMAIL . $this->hashKey($email);
        $comboKey = self::PREFIX_COMBO . $this->hashKey($ip . '|' . $email);
        $lockoutKey = self::PREFIX_LOCKOUT . $this->hashKey($email);

        // Clear email-based limits (but NOT IP-based, to prevent abuse)
        $this->limiter->clear($emailKey);
        $this->limiter->clear($comboKey);
        $this->limiter->clear($lockoutKey);
    }

    /**
     * Get the required delay before next attempt (progressive)
     *
     * @param string $email Email or username
     * @return int Seconds to wait
     */
    public function getDelaySeconds(string $email): int
    {
        $email = $this->normalizeEmail($email);
        $emailKey = self::PREFIX_EMAIL . $this->hashKey($email);
        $attempts = $this->limiter->attempts($emailKey);
        
        return $this->calculateDelay($attempts);
    }

    /**
     * Calculate progressive delay based on attempt count
     *
     * Uses exponential backoff: 0, 1, 2, 4, 8, 16, 32, 60 seconds
     */
    private function calculateDelay(int $attempts): int
    {
        if (!$this->progressiveDelays || $attempts < 3) {
            return 0;
        }

        // Exponential backoff with cap at 60 seconds
        $delay = (int) pow(2, min($attempts - 3, 5));
        return min($delay, 60);
    }

    /**
     * Lock out an account
     */
    private function lockout(string $lockoutKey): void
    {
        // Use a simple cache set (not the rate limiter)
        // This requires direct cache access
        $this->limiter->hit($lockoutKey, $this->lockoutSeconds);
    }

    /**
     * Check if an account is locked out
     */
    private function isLockedOut(string $lockoutKey): bool
    {
        return $this->limiter->attempts($lockoutKey) > 0;
    }

    /**
     * Get remaining lockout time
     */
    private function getLockoutRemaining(string $lockoutKey): int
    {
        // This is approximate since we don't store the exact lockout time
        return $this->lockoutSeconds;
    }

    /**
     * Get current attempt counts
     */
    private function getAttemptCounts(string $ipKey, string $emailKey, string $comboKey): array
    {
        return [
            'ip' => $this->limiter->attempts($ipKey),
            'email' => $this->limiter->attempts($emailKey),
            'combo' => $this->limiter->attempts($comboKey),
        ];
    }

    /**
     * Hash a key for privacy (don't store raw emails/IPs in cache keys)
     */
    private function hashKey(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Normalize email for consistent rate limiting
     */
    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Get human-readable error message for rate limit reason
     */
    public static function getErrorMessage(string $reason): string
    {
        return match ($reason) {
            'account_locked' => 'This account has been temporarily locked due to too many failed login attempts. Please try again later or reset your password.',
            'too_many_attempts_email' => 'Too many failed login attempts for this account. Please wait a few minutes before trying again.',
            'too_many_attempts_ip' => 'Too many login attempts from your location. Please wait a few minutes before trying again.',
            'too_many_attempts_combined' => 'Too many login attempts. Please wait a few minutes before trying again.',
            default => 'Login temporarily unavailable. Please try again later.',
        };
    }

    /**
     * Manually unlock an account (admin function)
     */
    public function unlock(string $email): void
    {
        $email = $this->normalizeEmail($email);
        $emailKey = self::PREFIX_EMAIL . $this->hashKey($email);
        $lockoutKey = self::PREFIX_LOCKOUT . $this->hashKey($email);
        
        $this->limiter->clear($emailKey);
        $this->limiter->clear($lockoutKey);
    }

    /**
     * Check remaining attempts for an email
     */
    public function remainingAttempts(string $email): int
    {
        $email = $this->normalizeEmail($email);
        $emailKey = self::PREFIX_EMAIL . $this->hashKey($email);
        
        return max(0, $this->maxAttemptsByEmail - $this->limiter->attempts($emailKey));
    }
}
