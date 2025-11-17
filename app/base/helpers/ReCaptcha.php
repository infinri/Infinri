<?php
declare(strict_types=1);
/**
 * Google reCAPTCHA v3 Helper
 * 
 * Verifies reCAPTCHA tokens from form submissions
 * 
 * @package App\Base\Helpers
 */

namespace App\Base\Helpers;

use App\Helpers\Env;

class ReCaptcha
{
    /**
     * Verify reCAPTCHA token
     *
     * @param string $token The reCAPTCHA token from the frontend
     * @param string $action The action name (e.g., 'contact_form')
     * @return bool True if verification passes, false otherwise
     */
    public static function verify(string $token, string $action = 'submit'): bool
    {
        // Check if reCAPTCHA is enabled
        if (!self::isEnabled()) {
            return true; // Pass through if disabled
        }

        $secretKey = Env::get('RECAPTCHA_SECRET_KEY');
        if (empty($secretKey)) {
            error_log('ReCaptcha: SECRET_KEY not configured');
            return false;
        }

        // Validate token format
        if (empty($token) || !is_string($token)) {
            error_log('ReCaptcha: Invalid token format');
            return false;
        }

        // Prepare verification request
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        // Make request to Google
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
                'timeout' => 10
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($verifyUrl, false, $context);

        if ($response === false) {
            error_log('ReCaptcha: Failed to contact Google verification server');
            return false;
        }

        $result = json_decode($response, true);

        if (!$result) {
            error_log('ReCaptcha: Invalid response from Google');
            return false;
        }

        // Check if verification succeeded
        if (!isset($result['success']) || $result['success'] !== true) {
            error_log('ReCaptcha: Verification failed - ' . json_encode($result['error-codes'] ?? []));
            return false;
        }

        // Verify action matches
        if (isset($result['action']) && $result['action'] !== $action) {
            error_log("ReCaptcha: Action mismatch - expected '{$action}', got '{$result['action']}'");
            return false;
        }

        // Check score threshold
        $minScore = (float) Env::get('RECAPTCHA_MIN_SCORE', '0.5');
        $score = $result['score'] ?? 0.0;

        if ($score < $minScore) {
            error_log("ReCaptcha: Score too low - {$score} < {$minScore}");
            return false;
        }

        error_log("ReCaptcha: Verification passed - Score: {$score}, Action: {$action}");
        return true;
    }

    /**
     * Check if reCAPTCHA is enabled
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Env::get('RECAPTCHA_ENABLED', 'false') === 'true';
    }

    /**
     * Get site key for frontend
     *
     * @return string
     */
    public static function getSiteKey(): string
    {
        return Env::get('RECAPTCHA_SITE_KEY', '');
    }
}
