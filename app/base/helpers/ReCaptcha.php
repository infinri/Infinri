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
            return true;
        }

        $secretKey = Env::get('RECAPTCHA_SECRET_KEY');
        if (empty($secretKey)) {
            error_log('ReCaptcha: SECRET_KEY not configured');
            return false;
        }

        // Sanitize & validate token
        $token = trim($token);
        if ($token === '') {
            error_log('ReCaptcha: Empty or invalid token');
            return false;
        }

        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => $secretKey,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        /** --------------------------------------------------------
         *  1) Try cURL verification
         * -------------------------------------------------------- */
        $response = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($verifyUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            curl_close($ch);
        }

        /** --------------------------------------------------------
         *  2) Fallback to stream context if cURL unavailable
         * -------------------------------------------------------- */
        if ($response === null) {
            $options = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data),
                    'timeout' => 10,
                ]
            ];
            $context = stream_context_create($options);
            $response = @file_get_contents($verifyUrl, false, $context);
        }

        if ($response === false || !$response) {
            error_log('ReCaptcha: Failed to contact Google verification server');
            return false;
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            error_log('ReCaptcha: Invalid JSON response from Google');
            return false;
        }

        /** --------------------------------------------------------
         *  Google Verification Checks
         * -------------------------------------------------------- */

        // Must be successful
        if (!($result['success'] ?? false)) {
            error_log('ReCaptcha: Verification failed - ' . json_encode($result['error-codes'] ?? []));
            return false;
        }

        // Action must match
        if (!isset($result['action']) || $result['action'] !== $action) {
            error_log("ReCaptcha: Action mismatch - expected '{$action}', got '" . ($result['action'] ?? 'none') . "'");
            return false;
        }

        // Token must be fresh (max 2 minutes old)
        $timestamp = strtotime($result['challenge_ts'] ?? '');
        if (!$timestamp || (time() - $timestamp) > 120) {
            error_log('ReCaptcha: Token expired (older than 120 seconds)');
            return false;
        }

        // Hostname check (protects against token reuse on other domains)
        $expectedHost = $_SERVER['SERVER_NAME'] ?? '';
        if (!isset($result['hostname']) || $result['hostname'] !== $expectedHost) {
            error_log("ReCaptcha: Hostname mismatch - expected '{$expectedHost}', got '" . ($result['hostname'] ?? 'none') . "'");
            return false;
        }

        // Score threshold check
        $minScore = (float) Env::get('RECAPTCHA_MIN_SCORE', '0.5');
        $score = (float) ($result['score'] ?? 0.0);

        if ($score < $minScore) {
            error_log("ReCaptcha: Score too low ({$score}) threshold is {$minScore}");
            return false;
        }

        error_log("ReCaptcha: Verification passed | Score={$score} | Action={$action}");
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
