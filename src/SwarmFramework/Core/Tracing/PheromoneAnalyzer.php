<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Tracing;

use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Attributes\Injectable;

/**
 * Pheromone Analyzer - Digital Scent Trail Analysis
 * 
 * Analyzes pheromone trails in the semantic mesh to identify
 * behavioral patterns and intensity distributions across mesh keys.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: [])]
final class PheromoneAnalyzer
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = ConfigManager::getConfig('PheromoneAnalyzer', $config);
    }

    /**
     * Extract pheromone trails from mesh changes
     */
    public function extractPheromoneTrails(array $meshBefore, array $meshAfter): array
    {
        $trails = [];
        $timestamp = PerformanceTimer::now();

        foreach ($meshAfter as $key => $value) {
            $beforeValue = $meshBefore[$key] ?? null;
            
            if ($beforeValue !== $value) {
                $intensity = $this->calculateChangeIntensity([
                    'key' => $key,
                    'before' => $beforeValue,
                    'after' => $value,
                    'type' => $this->determineChangeType($beforeValue, $value)
                ]);

                if ($intensity > $this->config['intensity_threshold']) {
                    $trails[] = [
                        'mesh_key' => $key,
                        'intensity' => $intensity,
                        'timestamp' => $timestamp,
                        'change_type' => $this->determineChangeType($beforeValue, $value),
                        'decay_rate' => $this->calculateDecayRate([
                            'key' => $key,
                            'before' => $beforeValue,
                            'after' => $value
                        ])
                    ];
                }
            }
        }

        return $trails;
    }

    /**
     * Get pheromone trail intensity for a mesh key
     */
    public function getPheromoneIntensity(string $meshKey, array $traceHistory): float
    {
        $recentTraces = array_filter($traceHistory, function($trace) {
            return $trace['timestamp'] > (time() - 3600); // Last hour
        });

        $intensity = 0.0;
        $totalTraces = count($recentTraces);

        if ($totalTraces === 0) {
            return 0.0;
        }

        foreach ($recentTraces as $trace) {
            if (isset($trace['pheromone_trails'])) {
                foreach ($trace['pheromone_trails'] as $trail) {
                    if ($trail['mesh_key'] === $meshKey) {
                        $intensity += $trail['intensity'];
                    }
                }
            }
        }

        return min(1.0, $intensity / $totalTraces);
    }

    /**
     * Calculate pheromone intensity from trails
     */
    public function calculatePheromoneIntensity(array $trails): float
    {
        if (empty($trails)) {
            return 0.0;
        }

        $totalIntensity = array_sum(array_column($trails, 'intensity'));
        return min(1.0, $totalIntensity / count($trails));
    }

    /**
     * Calculate intensity of a mesh change
     */
    private function calculateChangeIntensity(array $change): float
    {
        $baseIntensity = 0.5;
        
        // Adjust based on change type
        switch ($change['type']) {
            case 'create':
                $baseIntensity = 0.8;
                break;
            case 'update':
                $baseIntensity = 0.6;
                break;
            case 'delete':
                $baseIntensity = 0.9;
                break;
        }

        // Adjust based on value complexity
        if (is_array($change['after'])) {
            $baseIntensity *= 1.2;
        }

        return min(1.0, $baseIntensity);
    }

    /**
     * Calculate decay rate for pheromone trail
     */
    private function calculateDecayRate(array $change): float
    {
        $baseDecay = $this->config['decay_rate'];
        
        // More important changes decay slower
        if ($change['key'] === 'system.state' || strpos($change['key'], 'critical.') === 0) {
            $baseDecay = 0.98;
        }

        return $baseDecay;
    }

    /**
     * Determine the type of change
     */
    private function determineChangeType(mixed $before, mixed $after): string
    {
        if ($before === null) {
            return 'create';
        } elseif ($after === null) {
            return 'delete';
        } else {
            return 'update';
        }
    }

    /**
     * Update pheromone trails based on execution
     */
    public function updatePheromoneTrails(TraceSpan $span, array $mutations): array
    {
        $trails = [];
        $timestamp = PerformanceTimer::now();

        foreach ($mutations as $mutation) {
            $intensity = $this->calculateChangeIntensity($mutation);
            
            $trails[] = [
                'span_id' => $span->getSpanId(),
                'mesh_key' => $mutation['key'],
                'intensity' => $intensity,
                'timestamp' => $timestamp,
                'change_type' => $mutation['type'],
                'decay_rate' => $this->calculateDecayRate($mutation)
            ];
        }

        return $trails;
    }

    /**
     * Analyze pheromone distribution patterns
     */
    public function analyzePheromoneDistribution(array $traceHistory): array
    {
        $keyIntensities = [];
        $totalTrails = 0;

        foreach ($traceHistory as $trace) {
            if (isset($trace['pheromone_trails'])) {
                foreach ($trace['pheromone_trails'] as $trail) {
                    $key = $trail['mesh_key'];
                    if (!isset($keyIntensities[$key])) {
                        $keyIntensities[$key] = 0.0;
                    }
                    $keyIntensities[$key] += $trail['intensity'];
                    $totalTrails++;
                }
            }
        }

        // Normalize intensities
        foreach ($keyIntensities as $key => $intensity) {
            $keyIntensities[$key] = $intensity / $totalTrails;
        }

        arsort($keyIntensities);

        return [
            'key_intensities' => $keyIntensities,
            'total_trails' => $totalTrails,
            'hottest_keys' => array_slice(array_keys($keyIntensities), 0, 10),
            'average_intensity' => array_sum($keyIntensities) / count($keyIntensities)
        ];
    }
}
