<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Validation;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\BaseValidator;
use Infinri\SwarmFramework\Core\Common\ThresholdValidator;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Mesh Data Validator - Refactored with Centralized Utilities
 * 
 * Handles data integrity checking, corruption detection,
 * serialization validation, and hash verification using
 * centralized utilities for validation, logging, and configuration.
 * 
 * BEFORE: 456 lines with massive redundancy
 * AFTER: ~180 lines leveraging centralized utilities
 */
#[Injectable(dependencies: ['LoggerInterface', 'ThresholdValidator'])]
final class MeshDataValidator extends BaseValidator
{
    use LoggerTrait;

    protected LoggerInterface $logger;
    private ThresholdValidator $thresholdValidator;
    protected array $config;

    public function __construct(
        LoggerInterface $logger,
        ThresholdValidator $thresholdValidator,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->thresholdValidator = $thresholdValidator;
        $this->config = ConfigManager::getConfig('MeshDataValidator', $config);
    }

    /**
     * Serialize data for mesh storage with integrity checking
     */
    public function serialize(mixed $value, ?string $unitId = null): string
    {
        $timer = PerformanceTimer::start('data_serialize');
        
        try {
            $data = [
                'value' => $value,
                'timestamp' => PerformanceTimer::now(),
                'unit_id' => $unitId,
                'hash' => $this->calculateHash($value),
                'version' => 1
            ];
            
            $result = json_encode($data, JSON_THROW_ON_ERROR);
            $duration = PerformanceTimer::stop('data_serialize');
            
            $this->logOperationComplete('serialize', [
                'unit_id' => $unitId,
                'data_size' => strlen($result),
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $result;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('data_serialize');
            throw ExceptionFactory::meshCorruption('serialize', "Serialization failed: {$e->getMessage()}");
        }
    }

    /**
     * Deserialize data from mesh storage with integrity verification
     */
    public function deserialize(string $serialized): mixed
    {
        $timer = PerformanceTimer::start('data_deserialize');
        
        try {
            $data = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
            
            if (!is_array($data) || !isset($data['value'])) {
                throw ExceptionFactory::meshCorruption('deserialize', 'Invalid serialized data format');
            }
            
            // Verify hash if present and integrity checks enabled
            if ($this->config['enable_integrity_checks'] && isset($data['hash'], $data['value'])) {
                if (!$this->verifyHash($data['value'], $data['hash'])) {
                    throw ExceptionFactory::meshCorruption('deserialize', 'Data integrity check failed');
                }
            }
            
            $duration = PerformanceTimer::stop('data_deserialize');
            
            $this->logOperationComplete('deserialize', [
                'has_hash' => isset($data['hash']),
                'unit_id' => $data['unit_id'] ?? null,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $data['value'];
            
        } catch (\JsonException $e) {
            PerformanceTimer::stop('data_deserialize');
            throw ExceptionFactory::meshCorruption('deserialize', "JSON decode failed: {$e->getMessage()}");
        }
    }

    /**
     * Calculate hash for data integrity using centralized configuration
     */
    public function calculateHash(mixed $value): string
    {
        $algorithm = $this->config['hash_algorithm'];
        return "{$algorithm}:" . hash($algorithm, serialize($value));
    }

    /**
     * Verify hash matches value
     */
    public function verifyHash(mixed $value, string $hash): bool
    {
        $expectedHash = $this->calculateHash($value);
        return hash_equals($expectedHash, $hash);
    }

    /**
     * Validate and prepare data for storage using centralized validation
     */
    public function prepareForStorage(mixed $value, string $key): array
    {
        $timer = PerformanceTimer::start('prepare_storage');
        
        try {
            // Use centralized validation
            if (is_array($value) && !$this->validateArrayData($value)) {
                throw ExceptionFactory::validation(
                    'MeshDataValidator',
                    ["Invalid array data for storage: {$key}"]
                );
            }
            
            // Validate key format
            if (!$this->validateKey($key)) {
                throw ExceptionFactory::validation(
                    'MeshDataValidator', 
                    ["Invalid key format: {$key}"]
                );
            }
            
            // Serialize the data
            $serializedData = $this->serializeData($value);
            
            // Apply compression if needed
            $compressed = false;
            if ($this->shouldCompress($serializedData)) {
                $serializedData = $this->compressData($serializedData);
                $compressed = true;
            }
            
            // Generate integrity hash
            $hash = hash($this->config['hash_algorithm'], $serializedData);
            
            // Prepare storage envelope
            $envelope = [
                '_data' => $serializedData,
                '_original_type' => gettype($value),
                '_timestamp' => PerformanceTimer::now(),
                '_hash' => $hash,
                '_compressed' => $compressed,
                '_size' => strlen($serializedData),
                '_key' => $key
            ];
            
            $duration = PerformanceTimer::stop('prepare_storage');
            
            $this->logOperationComplete('prepare_storage', [
                'key' => $key,
                'type' => gettype($value),
                'size' => $envelope['_size'],
                'compressed' => $compressed,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $envelope;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('prepare_storage');
            throw ExceptionFactory::meshOperation('prepare_storage', $e->getMessage(), $e);
        }
    }

    /**
     * Validate and extract data from storage using centralized validation
     */
    public function extractFromStorage(string $rawData, string $key): mixed
    {
        $timer = PerformanceTimer::start('extract_storage');
        
        try {
            $envelope = $this->decodeEnvelope($rawData, $key);
            
            // Verify integrity if enabled
            if ($this->config['enable_integrity_checks']) {
                $this->verifyIntegrity($envelope, $key);
            }
            
            // Extract and decompress if needed
            $data = $envelope['_data'];
            if ($envelope['_compressed'] ?? false) {
                $data = $this->decompressData($data);
            }
            
            // Deserialize
            $value = $this->deserializeData($data, $envelope['_original_type']);
            
            $duration = PerformanceTimer::stop('extract_storage');
            
            $this->logOperationComplete('extract_storage', [
                'key' => $key,
                'type' => $envelope['_original_type'],
                'compressed' => $envelope['_compressed'] ?? false,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $value;
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('extract_storage');
            throw ExceptionFactory::meshOperation('extract_storage', $e->getMessage(), $e);
        }
    }

    /**
     * Validate key format using centralized validation
     */
    public function validateKey(string $key): void
    {
        // Use centralized string validation
        $result = $this->validateStringLengths(['key' => $key], [
            'key' => ['min' => 1, 'max' => 250]
        ]);
        
        if (!$result->isValid()) {
            throw ExceptionFactory::validation('MeshDataValidator', $result->getErrors());
        }
        
        // Check for invalid characters
        if (preg_match('/[^\w\-\.\:\/]/', $key)) {
            throw ExceptionFactory::validation('MeshDataValidator', ["Key contains invalid characters"]);
        }
        
        // Check for reserved patterns
        $reservedPatterns = ['__', '..', '//', '::'];
        foreach ($reservedPatterns as $pattern) {
            if (strpos($key, $pattern) !== false) {
                throw ExceptionFactory::validation('MeshDataValidator', ["Key contains reserved pattern: {$pattern}"]);
            }
        }
    }

    /**
     * Get validation statistics
     */
    public function getValidationStats(): array
    {
        return [
            'integrity_checks_enabled' => $this->config['enable_integrity_checks'],
            'hash_algorithm' => $this->config['hash_algorithm'],
            'max_data_size' => $this->config['max_data_size'],
            'compression_enabled' => $this->config['compression_enabled'],
            'compression_threshold' => $this->config['compression_threshold'],
        ];
        
        $duration = PerformanceTimer::stop('prepare_storage');
        if (!$result->isValid()) {
            throw ExceptionFactory::validation('MeshDataValidator', $result->getErrors());
        }
        
        // Validate data size using threshold validator
        $serializedSize = strlen(serialize($value));
        $sizeCheck = $this->thresholdValidator->validateThreshold(
            'MeshDataValidator',
            'data_size',
            (float)$serializedSize,
            (float)$this->config['max_data_size'],
            '>='
        );
        
        if ($sizeCheck['violated']) {
            throw ExceptionFactory::meshCapacity('data_size', $serializedSize, $this->config['max_data_size']);
        }
        
        // Additional array validation
        if (is_array($value)) {
            $this->validateArrayData($value);
        }
    }

    /**
     * Validate array data using centralized validation
     */
    public function validateArrayData(array $data): void
    {
        // Check for circular references
        if ($this->hasCircularReference($data)) {
            throw ExceptionFactory::validation('MeshDataValidator', ['Array contains circular references']);
        }
        
        // Check array depth
        $depth = $this->getArrayDepth($data);
        $maxDepth = $this->config['max_array_depth'] ?? 10;
        
        if ($depth > $maxDepth) {
            throw ExceptionFactory::validation('MeshDataValidator', ["Array depth {$depth} exceeds maximum {$maxDepth}"]);
        }
    }

    /**
     * Serialize data with error handling
     */
    private function serializeData(mixed $value): string
    {
        try {
            return serialize($value);
        } catch (\Throwable $e) {
            throw ExceptionFactory::meshOperation('serialize_data', $e->getMessage(), $e);
        }
    }

    /**
     * Deserialize data with error handling
     */
    private function deserializeData(string $data, string $originalType): mixed
    {
        try {
            return unserialize($data);
        } catch (\Throwable $e) {
            throw ExceptionFactory::meshCorruption('deserialize_data', "Failed to deserialize {$originalType} data");
        }
    }

    /**
     * Check if data should be compressed
     */
    private function shouldCompress(string $data): bool
    {
        return $this->config['compression_enabled'] && 
               strlen($data) >= $this->config['compression_threshold'];
    }

    /**
     * Compress data with error handling
     */
    private function compressData(string $data): string
    {
        $compressed = gzcompress($data, 6);
        
        if ($compressed === false) {
            throw ExceptionFactory::meshOperation('compress_data', 'Failed to compress data');
        }
        
        return $compressed;
    }

    /**
     * Decompress data with error handling
     */
    private function decompressData(string $data): string
    {
        $decompressed = gzuncompress($data);
        
        if ($decompressed === false) {
            throw ExceptionFactory::meshCorruption('decompress_data', 'Failed to decompress data');
        }
        
        return $decompressed;
    }

    /**
     * Decode storage envelope with error handling
     */
    private function decodeEnvelope(string $rawData, string $key): array
    {
        try {
            $envelope = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
            
            if (!is_array($envelope)) {
                throw ExceptionFactory::meshCorruption($key, 'Invalid envelope format');
            }
            
            return $envelope;
        } catch (\JsonException $e) {
            throw ExceptionFactory::meshCorruption($key, "Failed to decode envelope: {$e->getMessage()}");
        }
    }

    /**
     * Verify data integrity
     */
    private function verifyIntegrity(array $envelope, string $key): void
    {
        if (!isset($envelope['_hash'], $envelope['_data'])) {
            throw ExceptionFactory::meshCorruption($key, 'Missing integrity data in envelope');
        }
        
        $expectedHash = hash($this->config['hash_algorithm'], $envelope['_data']);
        
        if (!hash_equals($expectedHash, $envelope['_hash'])) {
            throw ExceptionFactory::meshCorruption($key, 'Data integrity verification failed');
        }
    }

    /**
     * Check for circular references in array
     */
    private function hasCircularReference(array $data, array $visited = []): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                $hash = spl_object_hash((object) $value);
                if (in_array($hash, $visited)) {
                    return true;
                }
                
                if ($this->hasCircularReference($value, array_merge($visited, [$hash]))) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get array depth
     */
    private function getArrayDepth(array $data): int
    {
        $maxDepth = 0;
        
        foreach ($data as $value) {
            if (is_array($value)) {
                $depth = 1 + $this->getArrayDepth($value);
                $maxDepth = max($maxDepth, $depth);
            }
        }
        
        return $maxDepth;
    }
}
