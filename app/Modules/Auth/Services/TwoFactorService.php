<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Core\Support\Str;
use App\Modules\Auth\Contracts\UserRepositoryInterface;

/**
 * Two-Factor Authentication Service
 * 
 * Handles TOTP-based 2FA with recovery codes.
 */
class TwoFactorService
{
    protected UserRepositoryInterface $users;
    
    /**
     * TOTP settings
     */
    protected int $digits = 6;
    protected int $period = 30;
    protected string $algorithm = 'sha1';
    protected int $window = 1; // Allow 1 period before/after

    /**
     * Recovery code settings
     */
    protected int $recoveryCodeCount = 8;
    protected int $recoveryCodeLength = 10;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * Generate a new 2FA secret for a user
     * 
     * @return array{secret: string, qr_url: string, recovery_codes: string[]}
     */
    public function generateSecret(AuthenticatableInterface $user): array
    {
        $secret = $this->createSecret();
        $recoveryCodes = $this->generateRecoveryCodes();

        return [
            'secret' => $secret,
            'qr_url' => $this->getQrCodeUrl($user, $secret),
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Enable 2FA for a user after verification
     */
    public function enable(AuthenticatableInterface $user, string $secret, string $code, array $recoveryCodes): bool
    {
        // Verify the code before enabling
        if (!$this->verify($secret, $code)) {
            return false;
        }

        // Hash recovery codes before storing
        $hashedCodes = array_map(
            fn($code) => hash('sha256', $code),
            $recoveryCodes
        );

        $this->users->updateTwoFactorSecret($user, $secret);
        $this->users->updateTwoFactorRecoveryCodes($user, $hashedCodes);

        return true;
    }

    /**
     * Disable 2FA for a user
     */
    public function disable(AuthenticatableInterface $user): void
    {
        $this->users->updateTwoFactorSecret($user, null);
        $this->users->updateTwoFactorRecoveryCodes($user, []);
    }

    /**
     * Verify a TOTP code
     */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code); // Remove spaces

        if (strlen($code) !== $this->digits) {
            return false;
        }

        $timestamp = time();

        // Check current and adjacent time windows
        for ($i = -$this->window; $i <= $this->window; $i++) {
            $checkTime = $timestamp + ($i * $this->period);
            $expectedCode = $this->generateCode($secret, $checkTime);

            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify a recovery code
     */
    public function verifyRecoveryCode(AuthenticatableInterface $user, string $code): bool
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
        $hashedCode = hash('sha256', $code);

        $recoveryCodes = $user->getTwoFactorRecoveryCodes();

        foreach ($recoveryCodes as $index => $storedHash) {
            if (hash_equals($storedHash, $hashedCode)) {
                // Remove used code
                unset($recoveryCodes[$index]);
                $this->users->updateTwoFactorRecoveryCodes($user, array_values($recoveryCodes));
                return true;
            }
        }

        return false;
    }

    /**
     * Regenerate recovery codes for a user
     * 
     * @return string[] New recovery codes (plain text, show to user once)
     */
    public function regenerateRecoveryCodes(AuthenticatableInterface $user): array
    {
        $codes = $this->generateRecoveryCodes();
        
        $hashedCodes = array_map(
            fn($code) => hash('sha256', $code),
            $codes
        );

        $this->users->updateTwoFactorRecoveryCodes($user, $hashedCodes);

        return $codes;
    }

    /**
     * Get remaining recovery code count
     */
    public function getRemainingRecoveryCodeCount(AuthenticatableInterface $user): int
    {
        return count($user->getTwoFactorRecoveryCodes());
    }

    /**
     * Generate a random secret (Base32 encoded)
     */
    protected function createSecret(int $length = 16): string
    {
        $bytes = random_bytes($length);
        return $this->base32Encode($bytes);
    }

    /**
     * Generate recovery codes
     * 
     * @return string[]
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        
        for ($i = 0; $i < $this->recoveryCodeCount; $i++) {
            $codes[] = strtoupper(Str::randomHex((int) ($this->recoveryCodeLength / 2)));
        }

        return $codes;
    }

    /**
     * Generate TOTP code for a given time
     */
    protected function generateCode(string $secret, int $timestamp): string
    {
        $counter = (int) floor($timestamp / $this->period);
        $binarySecret = $this->base32Decode($secret);

        // Pack counter as 64-bit big-endian
        $counterBytes = pack('J', $counter);

        // Generate HMAC
        $hash = hash_hmac($this->algorithm, $counterBytes, $binarySecret, true);

        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, $this->digits);

        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Get QR code URL for authenticator apps
     */
    protected function getQrCodeUrl(AuthenticatableInterface $user, string $secret): string
    {
        $issuer = config('app.name', 'Infinri');
        $email = method_exists($user, 'getEmailForPasswordReset') 
            ? $user->getEmailForPasswordReset() 
            : 'user';

        $otpAuthUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=%s&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            strtoupper($this->algorithm),
            $this->digits,
            $this->period
        );

        // Return URL for QR code generation (use Google Charts API or similar)
        return 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($otpAuthUrl);
    }

    /**
     * Base32 encode
     */
    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        $chunks = str_split($binary, 5);
        
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $result .= $alphabet[bindec($chunk)];
        }

        return $result;
    }

    /**
     * Base32 decode
     */
    protected function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $binary = '';

        foreach (str_split($data) as $char) {
            $index = strpos($alphabet, $char);
            if ($index !== false) {
                $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
            }
        }

        $result = '';
        $chunks = str_split($binary, 8);
        
        foreach ($chunks as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr(bindec($chunk));
            }
        }

        return $result;
    }
}
