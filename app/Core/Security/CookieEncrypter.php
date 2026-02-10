<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Security;

use App\Core\Http\Cookie;
use JsonException;
use RuntimeException;

/**
 * Cookie Encrypter
 *
 * Provides signing (HMAC) and encryption (AES-256-GCM) for cookies.
 * Enterprise-grade cookie security with tamper detection.
 */
class CookieEncrypter
{
    /**
     * Encryption algorithm
     */
    private const CIPHER = 'aes-256-gcm';

    /**
     * HMAC algorithm for signing
     */
    private const HMAC_ALGO = 'sha256';

    /**
     * Application secret key
     */
    private string $key;

    /**
     * Cookies that should not be encrypted (e.g., CSRF token for JS access)
     *
     * @var string[]
     */
    private array $except = [];

    public function __construct(?string $key = null, array $except = [])
    {
        $this->key = $key ?? $this->getKeyFromEnv();
        $this->except = $except;

        if (strlen($this->key) < 32) {
            throw new RuntimeException(
                'Cookie encryption key must be at least 32 characters. ' .
                'Set APP_KEY in your .env file.'
            );
        }
    }

    /**
     * Get the key from environment
     */
    private function getKeyFromEnv(): string
    {
        $key = env('APP_KEY', '');

        // Handle base64 encoded keys (Laravel-style)
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        return $key;
    }

    /**
     * Encrypt a cookie value
     *
     * Structure: base64(iv + ciphertext + tag + hmac)
     */
    public function encrypt(string $value): string
    {
        $iv = random_bytes(12); // GCM recommended IV size
        $tag = '';

        $ciphertext = openssl_encrypt(
            $value,
            self::CIPHER,
            $this->deriveKey('encrypt'),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '', // Additional authenticated data
            16  // Tag length
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Cookie encryption failed');
        }

        // Combine: IV + ciphertext + tag
        $payload = $iv . $ciphertext . $tag;

        // Add HMAC for additional integrity verification
        $hmac = $this->sign($payload);

        return base64_encode($payload . $hmac);
    }

    /**
     * Decrypt a cookie value
     */
    public function decrypt(string $encrypted): ?string
    {
        $decoded = base64_decode($encrypted, true);

        if ($decoded === false || strlen($decoded) < 44) {
            return null; // Invalid format
        }

        // Extract HMAC (last 32 bytes)
        $hmac = substr($decoded, -32);
        $payload = substr($decoded, 0, -32);

        // Verify HMAC first
        if (! $this->verifySignature($payload, $hmac)) {
            return null; // Tampered
        }

        // Extract components
        $iv = substr($payload, 0, 12);
        $tag = substr($payload, -16);
        $ciphertext = substr($payload, 12, -16);

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->deriveKey('encrypt'),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $decrypted === false ? null : $decrypted;
    }

    /**
     * Sign a value (HMAC only, no encryption)
     *
     * Use for cookies that need tamper protection but not confidentiality.
     * Returns: value|signature
     */
    public function sign(string $value): string
    {
        return hash_hmac(self::HMAC_ALGO, $value, $this->deriveKey('sign'), true);
    }

    /**
     * Create a signed cookie value (readable but tamper-proof)
     */
    public function signValue(string $value): string
    {
        $signature = base64_encode($this->sign($value));

        return $value . '|' . $signature;
    }

    /**
     * Verify and extract a signed value
     */
    public function unsignValue(string $signedValue): ?string
    {
        $parts = explode('|', $signedValue, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$value, $signature] = $parts;
        $expectedSignature = base64_encode($this->sign($value));

        if (! hash_equals($expectedSignature, $signature)) {
            return null; // Tampered
        }

        return $value;
    }

    /**
     * Verify a signature
     */
    public function verifySignature(string $value, string $signature): bool
    {
        $expected = $this->sign($value);

        return hash_equals($expected, $signature);
    }

    /**
     * Derive a key for a specific purpose (key separation)
     */
    private function deriveKey(string $purpose): string
    {
        return hash_hmac('sha256', $purpose, $this->key, true);
    }

    /**
     * Check if a cookie should be encrypted
     */
    public function shouldEncrypt(string $cookieName): bool
    {
        return ! in_array($cookieName, $this->except, true);
    }

    /**
     * Add cookies to the exception list
     */
    public function except(string|array $cookies): static
    {
        $cookies = is_array($cookies) ? $cookies : [$cookies];
        $this->except = array_merge($this->except, $cookies);

        return $this;
    }

    /**
     * Get the except list
     */
    public function getExcept(): array
    {
        return $this->except;
    }

    /**
     * Encrypt cookie data with metadata (Laravel-style)
     *
     * Stores value with expiration for additional validation.
     */
    public function encryptWithMeta(string $value, ?int $expires = null): string
    {
        $payload = json_encode([
            'v' => $value,
            'e' => $expires,
            't' => time(),
        ], JSON_THROW_ON_ERROR);

        return $this->encrypt($payload);
    }

    /**
     * Decrypt and validate metadata
     */
    public function decryptWithMeta(string $encrypted): ?string
    {
        $payload = $this->decrypt($encrypted);

        if ($payload === null) {
            return null;
        }

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        // Check expiration if set
        if (isset($data['e']) && $data['e'] < time()) {
            return null; // Expired
        }

        return $data['v'] ?? null;
    }

    /**
     * Generate a secure random key (for initial setup)
     */
    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }
}
