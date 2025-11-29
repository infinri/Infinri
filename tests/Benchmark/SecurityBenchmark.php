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
namespace Tests\Benchmark;

/**
 * Serialization Safety Benchmark (Cookie, Session, Cache)
 * 
 * Tests:
 * - Cookie tampering detection
 * - HMAC verification speed
 * - Invalid payload recovery
 * - Corrupted cache entries
 * - Expired session handling
 * 
 * This is critical for real-world robustness.
 * 
 * Run with: php tests/Benchmark/SecurityBenchmark.php
 */
final class SecurityBenchmark
{
    private array $results = [];
    private const ITERATIONS = 1000;
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = bin2hex(random_bytes(32));
    }

    public function run(): void
    {
        echo "========================================\n";
        echo "  Security & Serialization Safety\n";
        echo "========================================\n\n";

        $this->benchmarkHmacOperations();
        $this->benchmarkCookieSigning();
        $this->benchmarkTamperDetection();
        $this->benchmarkEncryption();
        $this->benchmarkInvalidPayloadRecovery();
        $this->benchmarkSessionSerialization();
        $this->benchmarkCacheCorruption();
        $this->benchmarkExpirationChecks();
        $this->printResults();
    }

    /**
     * Benchmark HMAC operations
     */
    private function benchmarkHmacOperations(): void
    {
        echo "Benchmarking HMAC Operations... ";

        $data = 'session_data_' . str_repeat('x', 500);
        $algorithms = ['sha256', 'sha384', 'sha512'];

        foreach ($algorithms as $algo) {
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                hash_hmac($algo, $data, $this->secretKey);
                $times[] = hrtime(true) - $start;
            }

            $this->results["HMAC ({$algo})"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            ];
        }

        // HMAC verification
        $signature = hash_hmac('sha256', $data, $this->secretKey);
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            hash_equals($signature, hash_hmac('sha256', $data, $this->secretKey));
            $times[] = hrtime(true) - $start;
        }

        $this->results['HMAC Verify (sha256)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark cookie signing
     */
    private function benchmarkCookieSigning(): void
    {
        echo "Benchmarking Cookie Signing... ";

        $cookieData = json_encode([
            'user_id' => 12345,
            'session_id' => bin2hex(random_bytes(16)),
            'expires' => time() + 3600,
            'data' => ['preferences' => ['theme' => 'dark', 'lang' => 'en']],
        ]);

        // Sign cookie
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $signature = hash_hmac('sha256', $cookieData, $this->secretKey);
            $signedCookie = base64_encode($cookieData) . '.' . $signature;
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cookie Sign'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'cookie_size' => strlen($signedCookie ?? ''),
        ];

        // Verify cookie
        $signature = hash_hmac('sha256', $cookieData, $this->secretKey);
        $signedCookie = base64_encode($cookieData) . '.' . $signature;

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            [$encodedData, $sig] = explode('.', $signedCookie, 2);
            $data = base64_decode($encodedData);
            $valid = hash_equals($sig, hash_hmac('sha256', $data, $this->secretKey));
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cookie Verify'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark tamper detection
     */
    private function benchmarkTamperDetection(): void
    {
        echo "Benchmarking Tamper Detection... ";

        $originalData = json_encode(['user_id' => 123, 'role' => 'user']);
        $signature = hash_hmac('sha256', $originalData, $this->secretKey);

        // Detect valid signature
        $times = [];
        $validCount = 0;
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            if (hash_equals($signature, hash_hmac('sha256', $originalData, $this->secretKey))) {
                $validCount++;
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Tamper Check (Valid)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'valid_rate' => ($validCount / self::ITERATIONS) * 100,
        ];

        // Detect tampered data
        $tamperedData = json_encode(['user_id' => 123, 'role' => 'admin']); // Changed role
        
        $times = [];
        $invalidCount = 0;
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            if (!hash_equals($signature, hash_hmac('sha256', $tamperedData, $this->secretKey))) {
                $invalidCount++;
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Tamper Check (Tampered)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'detection_rate' => ($invalidCount / self::ITERATIONS) * 100,
        ];

        // Detect corrupted signature
        $corruptedSignature = substr($signature, 0, -4) . 'XXXX';
        
        $times = [];
        $detectedCount = 0;
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            if (!hash_equals($corruptedSignature, hash_hmac('sha256', $originalData, $this->secretKey))) {
                $detectedCount++;
            }
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Tamper Check (Corrupted Sig)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'detection_rate' => ($detectedCount / self::ITERATIONS) * 100,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark encryption operations
     */
    private function benchmarkEncryption(): void
    {
        echo "Benchmarking Encryption... ";

        $data = json_encode([
            'sensitive' => 'data',
            'credit_card' => '4111111111111111',
            'ssn' => '123-45-6789',
        ]);

        $key = hash('sha256', $this->secretKey, true);
        $iv = random_bytes(16);

        // Encrypt
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $iv = random_bytes(16);
            $start = hrtime(true);
            
            $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            $result = base64_encode($iv . $encrypted);
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['AES-256-CBC Encrypt'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        // Decrypt
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $payload = base64_encode($iv . $encrypted);

        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $decoded = base64_decode($payload);
            $iv = substr($decoded, 0, 16);
            $ciphertext = substr($decoded, 16);
            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['AES-256-CBC Decrypt'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark invalid payload recovery
     */
    private function benchmarkInvalidPayloadRecovery(): void
    {
        echo "Benchmarking Invalid Payload Recovery... ";

        $payloads = [
            'valid_json' => '{"user_id":123,"name":"test"}',
            'invalid_json' => '{"user_id":123,"name":}',
            'truncated' => '{"user_id":123,"na',
            'empty' => '',
            'null_bytes' => "data\x00with\x00nulls",
            'binary' => random_bytes(50),
        ];

        foreach ($payloads as $name => $payload) {
            $times = [];
            $recovered = 0;
            $failed = 0;

            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                
                try {
                    $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                    $recovered++;
                } catch (\JsonException $e) {
                    // Recovery: use default
                    $decoded = ['error' => true, 'default' => true];
                    $failed++;
                }
                
                $times[] = hrtime(true) - $start;
            }

            $this->results["Payload: {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'success_rate' => ($recovered / self::ITERATIONS) * 100,
                'recovery_rate' => ($failed / self::ITERATIONS) * 100,
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark session serialization safety
     */
    private function benchmarkSessionSerialization(): void
    {
        echo "Benchmarking Session Serialization... ";

        $sessionData = [
            'user_id' => 12345,
            'username' => 'testuser',
            'roles' => ['user', 'editor'],
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true,
            ],
            'last_activity' => time(),
            'csrf_token' => bin2hex(random_bytes(32)),
        ];

        // Safe serialization (JSON)
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $serialized = json_encode($sessionData);
            $unserialized = json_decode($serialized, true);
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Session: JSON'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'size' => strlen(json_encode($sessionData)),
        ];

        // PHP serialize (with safety)
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $serialized = serialize($sessionData);
            $unserialized = unserialize($serialized, ['allowed_classes' => false]);
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Session: serialize (safe)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'size' => strlen(serialize($sessionData)),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark cache corruption handling
     */
    private function benchmarkCacheCorruption(): void
    {
        echo "Benchmarking Cache Corruption... ";

        $validCache = json_encode(['key' => 'value', 'expires' => time() + 3600]);
        
        $corruptedCaches = [
            'valid' => $validCache,
            'truncated' => substr($validCache, 0, 10),
            'garbage' => 'not_json_at_all',
            'partial_corruption' => substr_replace($validCache, 'XXX', 5, 3),
        ];

        foreach ($corruptedCaches as $name => $cache) {
            $times = [];
            $successCount = 0;
            $fallbackCount = 0;

            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                
                $data = @json_decode($cache, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    // Corruption detected - use fallback
                    $data = ['fallback' => true];
                    $fallbackCount++;
                } else {
                    $successCount++;
                }
                
                $times[] = hrtime(true) - $start;
            }

            $this->results["Cache: {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'success_rate' => ($successCount / self::ITERATIONS) * 100,
                'fallback_rate' => ($fallbackCount / self::ITERATIONS) * 100,
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark expiration checks
     */
    private function benchmarkExpirationChecks(): void
    {
        echo "Benchmarking Expiration Checks... ";

        $now = time();
        
        $items = [
            'valid' => ['data' => 'value', 'expires_at' => $now + 3600],
            'expired' => ['data' => 'value', 'expires_at' => $now - 3600],
            'no_expiry' => ['data' => 'value'],
        ];

        foreach ($items as $name => $item) {
            $times = [];
            $validCount = 0;

            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                
                $isValid = !isset($item['expires_at']) || $item['expires_at'] > time();
                if ($isValid) $validCount++;
                
                $times[] = hrtime(true) - $start;
            }

            $this->results["Expiry: {$name}"] = [
                'avg_ns' => array_sum($times) / count($times),
                'valid_rate' => ($validCount / self::ITERATIONS) * 100,
            ];
        }

        // DateTime comparison
        $expiry = new \DateTimeImmutable('+1 hour');
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = hrtime(true);
            
            $isValid = $expiry > new \DateTimeImmutable();
            
            $times[] = hrtime(true) - $start;
        }

        $this->results['Expiry: DateTime'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
        ];

        echo "Done\n";
    }

    /**
     * Print results
     */
    private function printResults(): void
    {
        echo "\n========================================\n";
        echo "  Results\n";
        echo "========================================\n\n";

        foreach ($this->results as $name => $data) {
            echo "{$name}:\n";
            
            foreach ($data as $key => $value) {
                $label = str_replace('_', ' ', ucfirst($key));
                
                if (is_float($value)) {
                    if (str_contains($key, '_us')) {
                        printf("  %s: %.2f µs\n", $label, $value);
                    } elseif (str_contains($key, '_ns')) {
                        printf("  %s: %.0f ns\n", $label, $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", $label, number_format($value, 0));
                    } elseif (str_contains($key, 'rate')) {
                        printf("  %s: %.1f%%\n", $label, $value);
                    } else {
                        printf("  %s: %.2f\n", $label, $value);
                    }
                } else {
                    printf("  %s: %s\n", $label, $value);
                }
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "  Security Recommendations\n";
        echo "========================================\n";
        echo "  - Always use constant-time comparison (hash_equals)\n";
        echo "  - Use JSON for session data (safer than serialize)\n";
        echo "  - Implement graceful fallback for corrupted data\n";
        echo "  - Use HMAC-SHA256 minimum for signatures\n";
        echo "  - Validate expiration before using cached data\n";
        echo "\n";

        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    (new SecurityBenchmark())->run();
}
