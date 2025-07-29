<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Reactor;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Psr\Log\LoggerInterface;

/**
 * Mutex Resolution Engine - Collision Resolution Logic
 * 
 * Resolves mutex collisions between SwarmUnits using priority-based
 * selection and conflict resolution algorithms.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class MutexResolutionEngine
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('MutexResolutionEngine', $config);
    }

    /**
     * Resolve mutex collisions using priority-based selection
     */
    public function resolveMutexCollisions(array $triggeredUnits, array $mutexGroups): array
    {
        $prioritizedUnits = [];
        $mutexGroupMap = [];

        // Group units by mutex group
        foreach ($triggeredUnits as $unitData) {
            $unit = $unitData['unit'];
            $mutexGroup = $unit->getMutexGroup();

            if ($mutexGroup === null) {
                // No mutex group - can execute freely
                $prioritizedUnits[] = $unitData;
            } else {
                if (!isset($mutexGroupMap[$mutexGroup])) {
                    $mutexGroupMap[$mutexGroup] = [];
                }
                $mutexGroupMap[$mutexGroup][] = $unitData;
            }
        }

        // Resolve conflicts within each mutex group
        foreach ($mutexGroupMap as $mutexGroup => $groupUnits) {
            $selectedUnit = $this->selectUnitFromMutexGroup($mutexGroup, $groupUnits, $mutexGroups);
            if ($selectedUnit !== null) {
                $prioritizedUnits[] = $selectedUnit;
            }
        }

        // Sort final list by priority
        usort($prioritizedUnits, fn($a, $b) => $b['priority'] <=> $a['priority']);

        $this->logger->debug('Mutex collision resolution completed', [
            'triggered_units' => count($triggeredUnits),
            'mutex_groups_processed' => count($mutexGroupMap),
            'final_units' => count($prioritizedUnits)
        ]);

        return $prioritizedUnits;
    }

    /**
     * Select the best unit from a mutex group
     */
    private function selectUnitFromMutexGroup(string $mutexGroup, array $groupUnits, array $mutexGroups): ?array
    {
        if (empty($groupUnits)) {
            return null;
        }

        // Single unit - no conflict
        if (count($groupUnits) === 1) {
            return $groupUnits[0];
        }

        // Multiple units - resolve conflict
        $this->logger->debug('Resolving mutex conflict', [
            'mutex_group' => $mutexGroup,
            'conflicting_units' => count($groupUnits)
        ]);

        switch ($this->config['resolution_strategy']) {
            case 'priority_based':
                return $this->resolvePriorityBased($groupUnits);
            
            case 'round_robin':
                return $this->resolveRoundRobin($mutexGroup, $groupUnits, $mutexGroups);
            
            case 'weighted_random':
                return $this->resolveWeightedRandom($groupUnits);
            
            default:
                return $this->resolvePriorityBased($groupUnits);
        }
    }

    /**
     * Resolve using priority-based selection
     */
    private function resolvePriorityBased(array $groupUnits): array
    {
        // Sort by priority (highest first)
        usort($groupUnits, fn($a, $b) => $b['priority'] <=> $a['priority']);
        
        $selected = $groupUnits[0];
        
        $this->logger->debug('Priority-based resolution', [
            'selected_unit' => $selected['unit']->getIdentity()->id,
            'selected_priority' => $selected['priority'],
            'total_candidates' => count($groupUnits)
        ]);

        return $selected;
    }

    /**
     * Resolve using round-robin fairness
     */
    private function resolveRoundRobin(string $mutexGroup, array $groupUnits, array $mutexGroups): array
    {
        // Get last selected unit for this mutex group
        $lastSelected = $mutexGroups[$mutexGroup]['last_selected'] ?? null;
        
        // Find next unit in rotation
        $unitIds = array_map(fn($unit) => $unit['unit']->getIdentity()->id, $groupUnits);
        
        if ($lastSelected === null) {
            $selectedIndex = 0;
        } else {
            $lastIndex = array_search($lastSelected, $unitIds);
            $selectedIndex = ($lastIndex + 1) % count($groupUnits);
        }

        $selected = $groupUnits[$selectedIndex];
        
        $this->logger->debug('Round-robin resolution', [
            'mutex_group' => $mutexGroup,
            'selected_unit' => $selected['unit']->getIdentity()->id,
            'last_selected' => $lastSelected,
            'rotation_index' => $selectedIndex
        ]);

        return $selected;
    }

    /**
     * Resolve using weighted random selection
     */
    private function resolveWeightedRandom(array $groupUnits): array
    {
        // Calculate weights based on priority
        $weights = [];
        $totalWeight = 0;
        
        foreach ($groupUnits as $index => $unitData) {
            $weight = max(1, $unitData['priority']); // Ensure positive weight
            $weights[$index] = $weight;
            $totalWeight += $weight;
        }

        // Random selection based on weights
        $randomValue = mt_rand(1, $totalWeight);
        $currentWeight = 0;
        
        foreach ($weights as $index => $weight) {
            $currentWeight += $weight;
            if ($randomValue <= $currentWeight) {
                $selected = $groupUnits[$index];
                
                $this->logger->debug('Weighted random resolution', [
                    'selected_unit' => $selected['unit']->getIdentity()->id,
                    'selected_weight' => $weight,
                    'total_weight' => $totalWeight,
                    'random_value' => $randomValue
                ]);

                return $selected;
            }
        }

        // Fallback to first unit
        return $groupUnits[0];
    }

    /**
     * Update mutex group state after resolution
     */
    public function updateMutexGroupState(string $mutexGroup, string $selectedUnitId, array &$mutexGroups): void
    {
        if (!isset($mutexGroups[$mutexGroup])) {
            $mutexGroups[$mutexGroup] = [
                'last_selected' => null,
                'selection_count' => 0,
                'last_execution' => null
            ];
        }

        $mutexGroups[$mutexGroup]['last_selected'] = $selectedUnitId;
        $mutexGroups[$mutexGroup]['selection_count']++;
        $mutexGroups[$mutexGroup]['last_execution'] = PerformanceTimer::now();
    }

    /**
     * Get mutex resolution statistics
     */
    public function getResolutionStats(array $mutexGroups): array
    {
        $totalGroups = count($mutexGroups);
        $activeGroups = 0;
        $totalSelections = 0;

        foreach ($mutexGroups as $groupData) {
            if ($groupData['last_execution'] !== null && 
                (PerformanceTimer::now() - $groupData['last_execution']) < 3600) { // Active in last hour
                $activeGroups++;
            }
            $totalSelections += $groupData['selection_count'];
        }

        return [
            'total_mutex_groups' => $totalGroups,
            'active_mutex_groups' => $activeGroups,
            'total_selections' => $totalSelections,
            'resolution_strategy' => $this->config['resolution_strategy'],
            'fairness_rotation_enabled' => $this->config['enable_fairness_rotation']
        ];
    }
}
