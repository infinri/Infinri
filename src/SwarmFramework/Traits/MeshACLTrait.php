<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Traits;

use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Core\AccessControl\MeshACLManager;

/**
 * MeshACLTrait - Mesh Access Control Consciousness
 * 
 * Provides consciousness-level mesh access control capabilities to SwarmUnits.
 * Implements the Access Guard Pattern with role-based security awareness.
 * 
 * @architecture Reusable mesh access control consciousness
 * @reference swarm_framework_pattern_blueprint.md → Access Guard Pattern
 * @reference infinri_blueprint.md → FR-CORE-006 (Mesh Access Control)
 * @author Infinri Framework
 * @version 1.0.0
 */
trait MeshACLTrait
{
    protected MeshACLManager $aclManager;

    /**
     * Guard check with consciousness-level validation
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @param array $requiredCapabilities Required capabilities for operation
     * @param string $operation The operation being performed
     * @return bool True if access granted
     */
    protected function guardCheck(SemanticMeshInterface $mesh, array $requiredCapabilities, string $operation = 'read'): bool
    {
        $userRole = $mesh->get('auth.user.role');
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        
        // Check user role capabilities
        $userHasAccess = $this->aclManager->hasCapabilities($userRole, $requiredCapabilities);
        
        // Check unit capabilities - check if unit can perform the operation
        $unitHasAccess = $this->aclManager->unitCanPerform($unitCapabilities, $operation);
        
        // Both user and unit must have required capabilities
        return $userHasAccess && $unitHasAccess;
    }

    /**
     * Check namespace access with consciousness awareness
     * 
     * @param string $namespace Namespace to check
     * @param string $operation Operation type (read/write/delete)
     * @return bool True if access granted
     */
    protected function checkNamespaceAccess(string $namespace, string $operation): bool
    {
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        return $this->aclManager->validateNamespaceAccess($namespace, $unitCapabilities);
    }

    /**
     * Get effective permissions for current unit
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return array Effective permissions
     */
    protected function getEffectivePermissions(SemanticMeshInterface $mesh): array
    {
        $userRole = $mesh->get('auth.user.role');
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        
        return $this->aclManager->getEffectivePermissions($userRole, $unitCapabilities);
    }

    /**
     * Check if unit can access specific mesh key
     * 
     * @param string $meshKey Mesh key to check
     * @param string $operation Operation type
     * @return bool True if access granted
     */
    protected function canAccessMeshKey(string $meshKey, string $operation): bool
    {
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        return $this->aclManager->validateAccess($meshKey, $operation, $unitCapabilities);
    }

    /**
     * Get access audit trail for consciousness monitoring
     * 
     * @return array Access audit trail
     */
    protected function getAccessAuditTrail(): array
    {
        return $this->aclManager->getAuditTrail(['unit_id' => $this->getIdentity()->id]);
    }

    /**
     * Record access attempt for consciousness tracking
     * 
     * @param string $resource Resource being accessed
     * @param string $operation Operation attempted
     * @param bool $granted Whether access was granted
     * @return void
     */
    protected function recordAccessAttempt(string $resource, string $operation, bool $granted): void
    {
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        $this->aclManager->recordAccess(
            $resource,
            $operation,
            $unitCapabilities,
            $granted
        );
    }

    /**
     * Check if unit has admin privileges
     * 
     * @return bool True if unit has admin privileges
     */
    protected function hasAdminPrivileges(): bool
    {
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        return in_array('admin', $unitCapabilities) || in_array('super_admin', $unitCapabilities);
    }

    /**
     * Check if operation requires elevated privileges
     * 
     * @param string $operation Operation to check
     * @return bool True if elevated privileges required
     */
    protected function requiresElevatedPrivileges(string $operation): bool
    {
        $elevatedOperations = ['delete', 'clear', 'admin', 'system', 'config'];
        return in_array($operation, $elevatedOperations);
    }

    /**
     * Get security context for current unit
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return array Security context
     */
    protected function getSecurityContext(SemanticMeshInterface $mesh): array
    {
        return [
            'unit_id' => $this->getIdentity()->id,
            'unit_capabilities' => $this->getIdentity()->capabilities ?? [],
            'user_role' => $mesh->get('auth.user.role'),
            'session_id' => $mesh->get('auth.session.id'),
            'timestamp' => microtime(true),
            'has_admin' => $this->hasAdminPrivileges()
        ];
    }
}
