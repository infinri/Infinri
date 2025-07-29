<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Attributes\Injectable;

/**
 * Configuration Manager - Centralized configuration management
 * 
 * Eliminates redundant array_merge patterns across the codebase
 * and provides consistent configuration defaults and validation.
 */
#[Injectable(dependencies: [])]
final class ConfigManager
{
    private static array $globalDefaults = [
        // Core system limits from .windsurfrules
        'max_execution_time' => 30,
        'max_memory_mb' => 256,
        'max_mesh_keys' => 1000,
        'max_concurrent_units' => 20000,
        'max_mesh_value_size_mb' => 1,
        'max_recursion_depth' => 5,
        
        // Performance defaults
        'tick_timeout_ms' => 50,
        'evaluation_timeout_ms' => 10,
        'health_check_interval' => 100,
        
        // Logging and monitoring
        'enable_detailed_tracing' => true,
        'enable_performance_tracking' => true,
        'trace_retention_hours' => 24,
        
        // Validation and safety
        'strict_validation' => true,
        'enable_safety_limits' => true,
        'enable_runtime_validation' => true,
    ];

    private static array $componentDefaults = [
        'ModuleRegistry' => [
            'module_paths' => ['modules/'],
            'auto_discover' => true,
            'validate_on_register' => true,
            'enable_hot_swap' => true,
            'max_modules' => 1000
        ],
        
        'SwarmReactor' => [
            'max_units_per_tick' => 20000,
            'adaptive_throttling' => true,
            'max_concurrent_units' => 1000
        ],
        
        'UnitEvaluationEngine' => [
            'max_units_per_evaluation' => 20000,
            'enable_parallel_evaluation' => true
        ],
        
        'ExecutionMonitor' => [
            'execution_timeout_seconds' => 30,
            'memory_limit_mb' => 256,
            'enable_detailed_tracing' => true
        ],
        
        'ThrottlingController' => [
            'target_tick_duration_ms' => 50,
            'max_throttle_rate' => 0.1,
            'throttle_adjustment_factor' => 0.1,
            'performance_window_size' => 10,
            'enable_adaptive_throttling' => true
        ],
        
        'MutexResolutionEngine' => [
            'resolution_strategy' => 'priority_based',
            'enable_fairness_rotation' => true,
            'max_mutex_groups' => 1000
        ],
        
        'SemanticMesh' => [
            'namespace_separator' => '.',
            'default_ttl' => 3600,
            'enable_versioning' => true
        ],
        
        'MeshPerformanceMonitor' => [
            'max_keys' => 1000000,
            'max_memory' => 1073741824, // 1GB
            'warning_threshold' => 0.8,
            'critical_threshold' => 0.95,
            'stats_cache_ttl' => 60,
            'enable_detailed_metrics' => true
        ],
        
        'MeshSubscriptionManager' => [
            'max_subscriptions' => 1000,
            'subscription_timeout' => 30,
            'enable_pattern_matching' => true,
            'max_message_size' => 65536, // 64KB
            'retry_attempts' => 3,
            'retry_delay' => 1000
        ],
        
        'MeshAccessController' => [
            'enable_acl' => true,
            'default_permissions' => ['read', 'write'],
            'admin_keys' => ['admin.*', 'system.*'],
            'readonly_keys' => ['config.*', 'schema.*'],
            'public_keys' => ['public.*', 'cache.*']
        ],
        
        'MeshDataValidator' => [
            'enable_integrity_checks' => true,
            'hash_algorithm' => 'sha256',
            'max_data_size' => 1048576, // 1MB
            'compression_enabled' => false,
            'compression_threshold' => 1024,
            'allowed_types' => ['string', 'integer', 'double', 'boolean', 'array', 'NULL']
        ],
        
        'StigmergicTracer' => [
            'max_history_size' => 10000,
            'trace_retention_hours' => 24,
            'enable_mesh_correlation' => true,
            'enable_performance_tracking' => true,
            'trace_sampling_rate' => 1.0
        ],
        
        'PheromoneAnalyzer' => [
            'intensity_threshold' => 0.1,
            'decay_rate' => 0.95,
            'trail_lifetime_hours' => 24
        ],
        
        'BehavioralAnalyzer' => [
            'pattern_window_hours' => 24,
            'burst_threshold' => 5,
            'burst_window_seconds' => 60,
            'min_pattern_occurrences' => 3
        ],
        
        'CausalityAnalyzer' => [
            'causality_window_seconds' => 60,
            'min_confidence_threshold' => 0.3,
            'max_chain_depth' => 5
        ],
        
        'DependencyResolver' => [
            'max_dependency_depth' => 10,
            'allow_circular_dependencies' => false,
            'strict_version_matching' => true
        ],
        
        'ModuleValidator' => [
            'require_manifest' => true,
            'validate_dependencies' => true,
            'check_unit_interfaces' => true,
            'validate_version_format' => true,
            'required_directories' => ['src'],
            'optional_directories' => ['units', 'config', 'tests']
        ],
        
        'ModuleDiscovery' => [
            'module_paths' => ['modules/'],
            'manifest_filename' => 'swarm-module.json',
            'unit_file_pattern' => '*Unit.php',
            'max_scan_depth' => 3,
            'exclude_patterns' => ['test*', 'vendor*', 'node_modules*']
        ],
        
        'SwapValidator' => [
            'validation_timeout' => 10,
            'strict_validation' => true
        ],
        
        'SwapOrchestrator' => [
            'max_swap_time' => 30,
            'enable_rollback' => true,
            'health_check_interval' => 1
        ],
        
        'SwapBackupManager' => [
            'backup_old_modules' => true,
            'backup_directory' => '/tmp/swarm_backups',
            'max_backup_age' => 86400, // 24 hours
            'modules_directory' => '/var/swarm/modules'
        ],
        
        'UnitLifecycleManager' => [
            'graceful_shutdown_timeout' => 15,
            'health_check_interval' => 1,
            'max_shutdown_retries' => 3
        ],
        
        'HealthCheckManager' => [
            'warning_threshold' => 0.8,
            'critical_threshold' => 0.95,
            'default_warning_threshold' => 0.8,
            'default_critical_threshold' => 0.95,
            'performance_thresholds' => [
                'response_time' => ['warning' => 1.0, 'critical' => 5.0, 'operator' => '>='],
                'error_rate' => ['warning' => 5.0, 'critical' => 10.0, 'operator' => '>='],
                'hit_rate' => ['warning' => 80.0, 'critical' => 50.0, 'operator' => '<='],
                'throughput' => ['warning' => 100.0, 'critical' => 50.0, 'operator' => '<='],
                'cpu_usage' => ['warning' => 80.0, 'critical' => 95.0, 'operator' => '>='],
                'memory_usage' => ['warning' => 80.0, 'critical' => 95.0, 'operator' => '>=']
            ]
        ],
        
        'RedisOperationWrapper' => [
            'default_timeout' => 5,
            'max_retries' => 3,
            'retry_delay_ms' => 100,
            'enable_detailed_logging' => true,
            'operation_timeout' => 30
        ],
        
        'CacheManager' => [
            'default_ttl' => 3600,
            'enable_compression' => false,
            'compression_threshold' => 1024,
            'max_key_length' => 250,
            'enable_detailed_logging' => true
        ],
        
        'ThresholdValidator' => [
            'default_warning_threshold' => 0.8,
            'default_critical_threshold' => 0.95,
            'enable_alerts' => true,
            'alert_cooldown_seconds' => 300
        ],
        
        'StatisticsCalculator' => [
            'enable_advanced_metrics' => true,
            'percentile_calculations' => [95, 99],
            'moving_average_window' => 10,
            'burst_detection_threshold' => 2.0
        ]
    ];

    /**
     * Get configuration for a component with defaults merged
     */
    public static function getConfig(string $componentName, array $userConfig = []): array
    {
        $componentDefaults = self::$componentDefaults[$componentName] ?? [];
        $mergedDefaults = array_merge(self::$globalDefaults, $componentDefaults);
        
        return array_merge($mergedDefaults, $userConfig);
    }

    /**
     * Get global system defaults
     */
    public static function getGlobalDefaults(): array
    {
        return self::$globalDefaults;
    }

    /**
     * Get component-specific defaults
     */
    public static function getComponentDefaults(string $componentName): array
    {
        return self::$componentDefaults[$componentName] ?? [];
    }

    /**
     * Validate configuration against safety limits
     */
    public static function validateConfig(array $config): array
    {
        $errors = [];
        
        // Validate safety limits from .windsurfrules
        if (isset($config['max_execution_time']) && $config['max_execution_time'] > 30) {
            $errors[] = 'max_execution_time cannot exceed 30 seconds';
        }
        
        if (isset($config['max_memory_mb']) && $config['max_memory_mb'] > 256) {
            $errors[] = 'max_memory_mb cannot exceed 256MB';
        }
        
        if (isset($config['max_mesh_keys']) && $config['max_mesh_keys'] > 1000) {
            $errors[] = 'max_mesh_keys cannot exceed 1000';
        }
        
        if (isset($config['max_concurrent_units']) && $config['max_concurrent_units'] > 20000) {
            $errors[] = 'max_concurrent_units cannot exceed 20000';
        }
        
        if (isset($config['max_mesh_value_size_mb']) && $config['max_mesh_value_size_mb'] > 1) {
            $errors[] = 'max_mesh_value_size_mb cannot exceed 1MB';
        }
        
        if (isset($config['max_recursion_depth']) && $config['max_recursion_depth'] > 5) {
            $errors[] = 'max_recursion_depth cannot exceed 5';
        }
        
        return $errors;
    }

    /**
     * Register new component defaults
     */
    public static function registerComponentDefaults(string $componentName, array $defaults): void
    {
        self::$componentDefaults[$componentName] = $defaults;
    }

    /**
     * Update global defaults (use with caution)
     */
    public static function updateGlobalDefaults(array $newDefaults): void
    {
        self::$globalDefaults = array_merge(self::$globalDefaults, $newDefaults);
    }
}
