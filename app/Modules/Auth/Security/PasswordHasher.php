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

/**
 * Password Hasher
 * 
 * Enterprise-grade password hashing with Argon2id (preferred) and bcrypt fallback.
 * Supports automatic hash upgrading when algorithm improves.
 * 
 * Security features:
 * - Argon2id with configurable memory cost (default: 65536 KB / 64 MB)
 * - bcrypt fallback for environments without Argon2
 * - Automatic rehashing when algorithm/cost changes
 * - Timing-safe password verification
 * - Password strength validation
 */
final class PasswordHasher
{
    /**
     * Algorithm preference order
     */
    private const ALGORITHM_ARGON2ID = PASSWORD_ARGON2ID;
    private const ALGORITHM_BCRYPT = PASSWORD_BCRYPT;

    /**
     * Default Argon2id options (OWASP recommended)
     */
    private array $argon2Options = [
        'memory_cost' => 65536,  // 64 MB
        'time_cost' => 4,        // 4 iterations
        'threads' => 1,          // Single thread for consistent timing
    ];

    /**
     * Default bcrypt options
     */
    private array $bcryptOptions = [
        'cost' => 12,  // 2^12 iterations (~250ms on modern hardware)
    ];

    /**
     * Current algorithm in use
     */
    private string|int $algorithm;

    /**
     * Current options for hashing
     */
    private array $options;

    public function __construct(?array $config = null)
    {
        // Determine best available algorithm
        if (defined('PASSWORD_ARGON2ID')) {
            $this->algorithm = self::ALGORITHM_ARGON2ID;
            $this->options = array_merge($this->argon2Options, $config['argon2'] ?? []);
        } else {
            $this->algorithm = self::ALGORITHM_BCRYPT;
            $this->options = array_merge($this->bcryptOptions, $config['bcrypt'] ?? []);
        }
    }

    /**
     * Hash a password
     *
     * @param string $password The plaintext password
     * @return string The hashed password
     * @throws \RuntimeException If hashing fails
     */
    public function hash(string $password): string
    {
        $hash = password_hash($password, $this->algorithm, $this->options);

        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed');
        }

        return $hash;
    }

    /**
     * Verify a password against a hash
     *
     * Uses timing-safe comparison internally.
     *
     * @param string $password The plaintext password
     * @param string $hash The stored hash
     * @return bool True if password matches
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs to be rehashed
     *
     * Returns true if:
     * - Algorithm has been upgraded (e.g., bcrypt → Argon2id)
     * - Cost parameters have increased
     * - Hash format is outdated
     *
     * @param string $hash The stored hash
     * @return bool True if rehash is recommended
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }

    /**
     * Get information about a hash
     *
     * @param string $hash The hash to analyze
     * @return array{algo: int|string, algoName: string, options: array}
     */
    public function info(string $hash): array
    {
        return password_get_info($hash);
    }

    /**
     * Validate password strength
     *
     * @param string $password The password to validate
     * @param array $options Validation options
     * @return array{valid: bool, errors: string[]}
     */
    public function validateStrength(string $password, array $options = []): array
    {
        $minLength = $options['min_length'] ?? 12;
        $requireUppercase = $options['require_uppercase'] ?? true;
        $requireLowercase = $options['require_lowercase'] ?? true;
        $requireNumbers = $options['require_numbers'] ?? true;
        $requireSpecial = $options['require_special'] ?? true;
        $maxLength = $options['max_length'] ?? 128;

        $errors = [];

        // Length checks
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
        }
        if (strlen($password) > $maxLength) {
            $errors[] = "Password must not exceed {$maxLength} characters";
        }

        // Character class checks
        if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if ($requireNumbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if ($requireSpecial && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        // Common password check (basic - should use a proper list in production)
        $commonPasswords = [
            'password', '123456', 'qwerty', 'letmein', 'admin', 'welcome',
            'monkey', 'dragon', 'master', 'login', 'princess', 'iloveyou',
        ];
        if (in_array(strtolower($password), $commonPasswords, true)) {
            $errors[] = 'Password is too common';
        }

        // Sequential character check
        if (preg_match('/(.)\1{2,}/', $password)) {
            $errors[] = 'Password must not contain 3+ repeated characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if password was previously used (requires password history)
     *
     * @param string $password New password to check
     * @param string[] $previousHashes Array of previous password hashes
     * @param int $checkCount Number of previous passwords to check
     * @return bool True if password was previously used
     */
    public function wasUsedBefore(string $password, array $previousHashes, int $checkCount = 5): bool
    {
        $hashesToCheck = array_slice($previousHashes, 0, $checkCount);

        foreach ($hashesToCheck as $hash) {
            if ($this->verify($password, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the current algorithm name
     */
    public function getAlgorithmName(): string
    {
        return match ($this->algorithm) {
            PASSWORD_ARGON2ID => 'argon2id',
            PASSWORD_ARGON2I => 'argon2i',
            PASSWORD_BCRYPT => 'bcrypt',
            default => 'unknown',
        };
    }

    /**
     * Check if Argon2id is available
     */
    public static function isArgon2idAvailable(): bool
    {
        return defined('PASSWORD_ARGON2ID');
    }

    /**
     * Create a pepper-enhanced hash (additional server-side secret)
     *
     * @param string $password The plaintext password
     * @param string $pepper Server-side secret (from environment)
     * @return string The peppered hash
     */
    public function hashWithPepper(string $password, string $pepper): string
    {
        // HMAC the password with pepper, then hash
        $pepperedPassword = hash_hmac('sha256', $password, $pepper, true);
        return $this->hash(base64_encode($pepperedPassword));
    }

    /**
     * Verify a peppered password
     *
     * @param string $password The plaintext password
     * @param string $hash The stored hash
     * @param string $pepper Server-side secret
     * @return bool True if password matches
     */
    public function verifyWithPepper(string $password, string $hash, string $pepper): bool
    {
        $pepperedPassword = hash_hmac('sha256', $password, $pepper, true);
        return $this->verify(base64_encode($pepperedPassword), $hash);
    }
}
