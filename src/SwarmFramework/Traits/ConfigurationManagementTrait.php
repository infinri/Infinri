<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Traits;

/**
 * Configuration Management Trait
 * 
 * Provides centralized configuration management for consciousness services.
 * Consolidates environment variable handling and default configurations.
 * 
 * @architecture Configuration management and defaults
 * @author Infinri Framework
 * @version 1.0.0
 */
trait ConfigurationManagementTrait
{
    /**
     * Get Redis configuration for semantic mesh
     * 
     * @return array Redis configuration
     */
    protected function getRedisConfig(): array
    {
        return [
            'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
            'timeout' => (float)($_ENV['REDIS_TIMEOUT'] ?? 5.0),
            'retry_interval' => (int)($_ENV['REDIS_RETRY_INTERVAL'] ?? 100),
            'read_timeout' => (float)($_ENV['REDIS_READ_TIMEOUT'] ?? 5.0),
            'persistent' => (bool)($_ENV['REDIS_PERSISTENT'] ?? false),
            'prefix' => $_ENV['REDIS_PREFIX'] ?? 'infinri:mesh:'
        ];
    }

    /**
     * Get entropy monitoring configuration
     * 
     * @return array Entropy configuration
     */
    protected function getEntropyConfig(): array
    {
        return [
            'max_entropy_threshold' => (float)($_ENV['ENTROPY_MAX_THRESHOLD'] ?? 0.8),
            'auto_pruning_enabled' => (bool)($_ENV['ENTROPY_AUTO_PRUNING'] ?? true),
            'monitoring_interval' => (int)($_ENV['ENTROPY_MONITORING_INTERVAL'] ?? 60),
            'health_check_interval' => (int)($_ENV['ENTROPY_HEALTH_CHECK_INTERVAL'] ?? 300),
            'degradation_threshold' => (float)($_ENV['ENTROPY_DEGRADATION_THRESHOLD'] ?? 0.6),
            'critical_threshold' => (float)($_ENV['ENTROPY_CRITICAL_THRESHOLD'] ?? 0.9)
        ];
    }

    /**
     * Get ethical validation configuration
     * 
     * @return array Ethical configuration
     */
    protected function getEthicalConfig(): array
    {
        return [
            'toxicity_threshold' => (float)($_ENV['ETHICAL_TOXICITY_THRESHOLD'] ?? 0.7),
            'bias_threshold' => (float)($_ENV['ETHICAL_BIAS_THRESHOLD'] ?? 0.6),
            'manipulation_threshold' => (float)($_ENV['ETHICAL_MANIPULATION_THRESHOLD'] ?? 0.8),
            'hate_speech_threshold' => (float)($_ENV['ETHICAL_HATE_SPEECH_THRESHOLD'] ?? 0.9),
            'misinformation_threshold' => (float)($_ENV['ETHICAL_MISINFORMATION_THRESHOLD'] ?? 0.8),
            'ai_safety_enabled' => (bool)($_ENV['AI_SAFETY_ENABLED'] ?? true),
            'human_review_threshold' => (float)($_ENV['ETHICAL_HUMAN_REVIEW_THRESHOLD'] ?? 0.5)
        ];
    }

    /**
     * Get temporal engine configuration
     * 
     * @return array Temporal configuration
     */
    protected function getTemporalConfig(): array
    {
        return [
            'default_timezone' => $_ENV['TEMPORAL_DEFAULT_TIMEZONE'] ?? 'UTC',
            'business_hours_start' => $_ENV['TEMPORAL_BUSINESS_START'] ?? '09:00',
            'business_hours_end' => $_ENV['TEMPORAL_BUSINESS_END'] ?? '17:00',
            'weekend_execution' => (bool)($_ENV['TEMPORAL_WEEKEND_EXECUTION'] ?? false),
            'holiday_calendar' => $_ENV['TEMPORAL_HOLIDAY_CALENDAR'] ?? 'US',
            'staleness_threshold' => (int)($_ENV['TEMPORAL_STALENESS_THRESHOLD'] ?? 3600)
        ];
    }

    /**
     * Get stigmergic tracing configuration
     * 
     * @return array Tracing configuration
     */
    protected function getTracingConfig(): array
    {
        return [
            'enabled' => (bool)($_ENV['TRACING_ENABLED'] ?? true),
            'pheromone_decay_rate' => (float)($_ENV['TRACING_DECAY_RATE'] ?? 0.1),
            'trace_retention' => (int)($_ENV['TRACING_RETENTION'] ?? 86400),
            'behavioral_analysis' => (bool)($_ENV['TRACING_BEHAVIORAL_ANALYSIS'] ?? true),
            'causality_tracking' => (bool)($_ENV['TRACING_CAUSALITY_TRACKING'] ?? true),
            'max_trace_depth' => (int)($_ENV['TRACING_MAX_DEPTH'] ?? 10)
        ];
    }

    /**
     * Get throttling configuration
     * 
     * @return array Throttling configuration
     */
    protected function getThrottlingConfig(): array
    {
        return [
            'max_units_per_tick' => (int)($_ENV['THROTTLING_MAX_UNITS_PER_TICK'] ?? 20000),
            'adaptive_throttling' => (bool)($_ENV['THROTTLING_ADAPTIVE'] ?? true),
            'throttle_threshold' => (float)($_ENV['THROTTLING_THRESHOLD'] ?? 0.8),
            'recovery_factor' => (float)($_ENV['THROTTLING_RECOVERY_FACTOR'] ?? 0.9),
            'emergency_brake_threshold' => (float)($_ENV['THROTTLING_EMERGENCY_THRESHOLD'] ?? 0.95)
        ];
    }

    /**
     * Get validation configuration
     * 
     * @return array Validation configuration
     */
    protected function getValidationConfig(): array
    {
        return [
            'max_value_size' => (int)($_ENV['VALIDATION_MAX_VALUE_SIZE'] ?? 1048576), // 1MB
            'schema_validation' => (bool)($_ENV['VALIDATION_SCHEMA_ENABLED'] ?? true),
            'type_checking' => (bool)($_ENV['VALIDATION_TYPE_CHECKING'] ?? true),
            'sanitization' => (bool)($_ENV['VALIDATION_SANITIZATION'] ?? true),
            'encoding_validation' => (bool)($_ENV['VALIDATION_ENCODING'] ?? true)
        ];
    }

    /**
     * Get ACL configuration
     * 
     * @return array ACL configuration
     */
    protected function getACLConfig(): array
    {
        return [
            'default_policy' => $_ENV['ACL_DEFAULT_POLICY'] ?? 'deny',
            'admin_bypass' => (bool)($_ENV['ACL_ADMIN_BYPASS'] ?? false),
            'audit_enabled' => (bool)($_ENV['ACL_AUDIT_ENABLED'] ?? true),
            'cache_permissions' => (bool)($_ENV['ACL_CACHE_PERMISSIONS'] ?? true),
            'permission_ttl' => (int)($_ENV['ACL_PERMISSION_TTL'] ?? 300),
            'namespace_isolation' => (bool)($_ENV['ACL_NAMESPACE_ISOLATION'] ?? true)
        ];
    }

    /**
     * Get configuration by type with validation
     * 
     * @param string $type Configuration type
     * @return array Configuration array
     * @throws \InvalidArgumentException If configuration type is invalid
     */
    protected function getConfigByType(string $type): array
    {
        return match($type) {
            'redis' => $this->getRedisConfig(),
            'entropy' => $this->getEntropyConfig(),
            'ethical' => $this->getEthicalConfig(),
            'temporal' => $this->getTemporalConfig(),
            'tracing' => $this->getTracingConfig(),
            'throttling' => $this->getThrottlingConfig(),
            'validation' => $this->getValidationConfig(),
            'acl' => $this->getACLConfig(),
            default => throw new \InvalidArgumentException("Unknown configuration type: {$type}")
        };
    }
}
