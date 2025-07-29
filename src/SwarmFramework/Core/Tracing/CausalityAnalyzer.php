<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Tracing;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;

/**
 * Causality Analyzer - Cause and Effect Chain Analysis
 * 
 * Analyzes causality chains in SwarmUnit executions to understand
 * how actions lead to consequences in the semantic mesh.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: [])]
final class CausalityAnalyzer
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = ConfigManager::getConfig('CausalityAnalyzer', $config);
    }

    /**
     * Build causality chain for execution span
     */
    public function buildCausalityChain(TraceSpan $span, array $mutations, array $traceHistory): array
    {
        $chain = [];
        $spanTime = $span->getStartTime();

        foreach ($mutations as $mutation) {
            $causes = $this->findPotentialCauses($mutation, $spanTime, $traceHistory);
            $confidence = $this->calculateCausalityConfidence($causes);

            if ($confidence >= $this->config['min_confidence_threshold']) {
                $chain[] = [
                    'mutation' => $mutation,
                    'potential_causes' => $causes,
                    'confidence' => $confidence,
                    'timestamp' => $spanTime
                ];
            }
        }

        return $chain;
    }

    /**
     * Find potential causes for a mutation
     */
    private function findPotentialCauses(array $mutation, float $beforeTime, array $traceHistory): array
    {
        $causes = [];
        $windowStart = $beforeTime - $this->config['causality_window_seconds'];

        foreach ($traceHistory as $trace) {
            if ($trace['timestamp'] >= $windowStart && $trace['timestamp'] < $beforeTime) {
                if (isset($trace['mesh_changes'])) {
                    foreach ($trace['mesh_changes'] as $change) {
                        if ($this->isRelatedChange($change, $mutation)) {
                            $causes[] = [
                                'trace_id' => $trace['trace_id'],
                                'unit_id' => $trace['unit_id'],
                                'change' => $change,
                                'timestamp' => $trace['timestamp'],
                                'relation_type' => $this->determineRelationType($change, $mutation)
                            ];
                        }
                    }
                }
            }
        }

        return $causes;
    }

    /**
     * Calculate confidence in causality relationship
     */
    private function calculateCausalityConfidence(array $causes): float
    {
        if (empty($causes)) {
            return 0.0;
        }

        $baseConfidence = min(1.0, count($causes) * 0.2);
        
        // Boost confidence for direct key matches
        $directMatches = array_filter($causes, fn($cause) => $cause['relation_type'] === 'direct');
        if (!empty($directMatches)) {
            $baseConfidence += 0.3;
        }

        // Boost confidence for temporal proximity
        $recentCauses = array_filter($causes, fn($cause) => 
            (microtime(true) - $cause['timestamp']) < 30
        );
        if (!empty($recentCauses)) {
            $baseConfidence += 0.2;
        }

        return min(1.0, $baseConfidence);
    }

    /**
     * Check if two changes are related
     */
    private function isRelatedChange(array $change1, array $change2): bool
    {
        // Direct key match
        if ($change1['key'] === $change2['key']) {
            return true;
        }

        // Hierarchical key relationship
        if (strpos($change1['key'], $change2['key'] . '.') === 0 ||
            strpos($change2['key'], $change1['key'] . '.') === 0) {
            return true;
        }

        // Pattern-based relationships
        if ($this->hasPatternRelationship($change1['key'], $change2['key'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine the type of relationship between changes
     */
    private function determineRelationType(array $cause, array $effect): string
    {
        if ($cause['key'] === $effect['key']) {
            return 'direct';
        }

        if (strpos($effect['key'], $cause['key'] . '.') === 0) {
            return 'hierarchical_child';
        }

        if (strpos($cause['key'], $effect['key'] . '.') === 0) {
            return 'hierarchical_parent';
        }

        if ($this->hasPatternRelationship($cause['key'], $effect['key'])) {
            return 'pattern_based';
        }

        return 'indirect';
    }

    /**
     * Check for pattern-based relationships between keys
     */
    private function hasPatternRelationship(string $key1, string $key2): bool
    {
        // Check for common prefixes
        $parts1 = explode('.', $key1);
        $parts2 = explode('.', $key2);

        if (count($parts1) >= 2 && count($parts2) >= 2) {
            return $parts1[0] === $parts2[0]; // Same domain
        }

        return false;
    }

    /**
     * Analyze causality patterns across multiple traces
     */
    public function analyzeCausalityPatterns(array $traceHistory): array
    {
        $patterns = [];
        $causalityMap = [];

        foreach ($traceHistory as $trace) {
            if (isset($trace['mesh_changes'])) {
                foreach ($trace['mesh_changes'] as $change) {
                    $causes = $this->findPotentialCauses($change, $trace['timestamp'], $traceHistory);
                    
                    if (!empty($causes)) {
                        $key = $change['key'];
                        if (!isset($causalityMap[$key])) {
                            $causalityMap[$key] = [];
                        }
                        $causalityMap[$key] = array_merge($causalityMap[$key], $causes);
                    }
                }
            }
        }

        // Analyze patterns
        foreach ($causalityMap as $key => $causes) {
            $unitCounts = [];
            foreach ($causes as $cause) {
                $unitId = $cause['unit_id'];
                if (!isset($unitCounts[$unitId])) {
                    $unitCounts[$unitId] = 0;
                }
                $unitCounts[$unitId]++;
            }

            if (max($unitCounts) >= 3) { // Pattern threshold
                $patterns[] = [
                    'affected_key' => $key,
                    'primary_cause_unit' => array_keys($unitCounts, max($unitCounts))[0],
                    'cause_frequency' => max($unitCounts),
                    'total_causes' => count($causes)
                ];
            }
        }

        return [
            'causality_map' => $causalityMap,
            'patterns' => $patterns,
            'total_relationships' => array_sum(array_map('count', $causalityMap))
        ];
    }

    /**
     * Build dependency graph from causality data
     */
    public function buildDependencyGraph(array $traceHistory): array
    {
        $graph = [];
        $nodes = [];
        $edges = [];

        foreach ($traceHistory as $trace) {
            $unitId = $trace['unit_id'];
            $nodes[$unitId] = [
                'unit_id' => $unitId,
                'execution_count' => ($nodes[$unitId]['execution_count'] ?? 0) + 1,
                'last_execution' => $trace['timestamp']
            ];

            if (isset($trace['mesh_changes'])) {
                foreach ($trace['mesh_changes'] as $change) {
                    $causes = $this->findPotentialCauses($change, $trace['timestamp'], $traceHistory);
                    
                    foreach ($causes as $cause) {
                        $sourceUnit = $cause['unit_id'];
                        $edgeKey = $sourceUnit . '->' . $unitId;
                        
                        if (!isset($edges[$edgeKey])) {
                            $edges[$edgeKey] = [
                                'source' => $sourceUnit,
                                'target' => $unitId,
                                'weight' => 0,
                                'relation_types' => []
                            ];
                        }
                        
                        $edges[$edgeKey]['weight']++;
                        $edges[$edgeKey]['relation_types'][] = $cause['relation_type'];
                    }
                }
            }
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => array_values($edges),
            'node_count' => count($nodes),
            'edge_count' => count($edges)
        ];
    }
}
