<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Exceptions;

/**
 * MeshACLViolationException - Mesh Access Control Violation
 * 
 * Thrown when a SwarmUnit attempts unauthorized access to mesh resources.
 * Implements consciousness-level security response with detailed context.
 * 
 * @architecture Self-protective mesh access control
 * @reference infinri_blueprint.md → FR-CORE-006 (Mesh Access Control)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class MeshACLViolationException extends SwarmException
{
    /**
     * Create mesh ACL violation exception
     * 
     * @param string $meshKey Mesh key that was accessed
     * @param string $operation Operation that was attempted
     * @param array $unitCapabilities Unit's current capabilities
     * @param string $unitId Unit ID that triggered the violation
     */
    public function __construct(
        string $meshKey, 
        string $operation, 
        array $unitCapabilities,
        string $unitId = ''
    ) {
        $message = "Unit attempted unauthorized {$operation} on {$meshKey}";
        
        $context = [
            'mesh_key' => $meshKey,
            'operation' => $operation,
            'unit_capabilities' => $unitCapabilities,
            'security_level' => $this->determineSecurityLevel($operation),
            'requires_escalation' => $this->requiresEscalation($operation)
        ];
        
        parent::__construct($message, 403, null, $context, $unitId);
    }

    /**
     * Get the mesh key that was accessed
     * 
     * @return string Mesh key
     */
    public function getMeshKey(): string
    {
        return $this->context['mesh_key'];
    }

    /**
     * Get the operation that was attempted
     * 
     * @return string Operation
     */
    public function getOperation(): string
    {
        return $this->context['operation'];
    }

    /**
     * Get the unit's capabilities
     * 
     * @return array Unit capabilities
     */
    public function getUnitCapabilities(): array
    {
        return $this->context['unit_capabilities'];
    }



    /**
     * Determine security level based on operation
     * 
     * @param string $operation Operation type
     * @return string Security level
     */
    private function determineSecurityLevel(string $operation): string
    {
        return match($operation) {
            'delete', 'clear' => 'critical',
            'write', 'set' => 'high',
            'read', 'get' => 'medium',
            default => 'low'
        };
    }

    /**
     * Check if operation requires escalation
     * 
     * @param string $operation Operation type
     * @return bool True if escalation required
     */
    public function requiresEscalation(string $operation): bool
    {
        return in_array($operation, ['delete', 'clear', 'admin']);
    }
}
