<?php
declare(strict_types=1);
/**
 * Rate Limiter Helper
 *
 * Prevents spam and brute force attacks by limiting requests per IP
 */

namespace App\Base\Helpers;

class RateLimiter
{
    private const RATE_LIMIT_FILE = __DIR__ . '/../../../var/cache/rate_limits.json';
    
    /**
     * Check if an IP has exceeded rate limit
     *
     * @param string $ip IP address to check
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public static function check(string $ip, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        // Ensure cache directory exists
        $cacheDir = dirname(self::RATE_LIMIT_FILE);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        // Load existing rate limit data
        $rateLimits = self::loadRateLimits();
        
        // Clean up old entries (older than window)
        $rateLimits = self::cleanupOldEntries($rateLimits, $windowSeconds);
        
        // Get current IP's attempts
        $ipKey = hash('sha256', $ip);
        $attempts = $rateLimits[$ipKey] ?? [];
        
        // Count attempts within the time window
        $now = time();
        $recentAttempts = array_filter($attempts, fn($timestamp) => $timestamp > ($now - $windowSeconds));
        
        // Check if exceeded limit
        if (count($recentAttempts) >= $maxAttempts) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Record an attempt for an IP address
     *
     * @param string $ip IP address
     * @return void
     */
    public static function record(string $ip): void
    {
        $rateLimits = self::loadRateLimits();
        $ipKey = hash('sha256', $ip);
        
        if (!isset($rateLimits[$ipKey])) {
            $rateLimits[$ipKey] = [];
        }
        
        $rateLimits[$ipKey][] = time();
        
        self::saveRateLimits($rateLimits);
    }
    
    /**
     * Get seconds until rate limit resets for an IP
     *
     * @param string $ip IP address
     * @param int $windowSeconds Time window in seconds
     * @return int Seconds until reset
     */
    public static function getResetTime(string $ip, int $windowSeconds = 300): int
    {
        $rateLimits = self::loadRateLimits();
        $ipKey = hash('sha256', $ip);
        $attempts = $rateLimits[$ipKey] ?? [];
        
        if (empty($attempts)) {
            return 0;
        }
        
        $oldestAttempt = min($attempts);
        $resetTime = $oldestAttempt + $windowSeconds;
        $now = time();
        
        return max(0, $resetTime - $now);
    }
    
    /**
     * Load rate limit data from file
     *
     * @return array
     */
    private static function loadRateLimits(): array
    {
        if (!file_exists(self::RATE_LIMIT_FILE)) {
            return [];
        }
        
        $data = file_get_contents(self::RATE_LIMIT_FILE);
        return $data ? json_decode($data, true) : [];
    }
    
    /**
     * Save rate limit data to file
     *
     * @param array $data
     * @return void
     */
    private static function saveRateLimits(array $data): void
    {
        file_put_contents(
            self::RATE_LIMIT_FILE,
            json_encode($data, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
    
    /**
     * Clean up old entries beyond the time window
     *
     * @param array $rateLimits
     * @param int $windowSeconds
     * @return array
     */
    private static function cleanupOldEntries(array $rateLimits, int $windowSeconds): array
    {
        $now = time();
        $cutoff = $now - $windowSeconds;
        
        foreach ($rateLimits as $ipKey => $attempts) {
            $rateLimits[$ipKey] = array_filter(
                $attempts,
                fn($timestamp) => $timestamp > $cutoff
            );
            
            // Remove empty entries
            if (empty($rateLimits[$ipKey])) {
                unset($rateLimits[$ipKey]);
            }
        }
        
        return $rateLimits;
    }
}
