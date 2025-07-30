<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\AccessControl;

/**
 * Mesh ACL Manager - Access Control with Namespace Awareness
 * 
 * Manages mesh access control with role-based permissions, namespace isolation,
 * and comprehensive audit logging for security compliance.
 * 
 * @architecture Access control and security management
 * @reference infinri_blueprint.md → FR-CORE-006 (Mesh Access Control)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class MeshACLManager
{
    private array $config;
    private array $permissions = [];
    private array $auditLog = [];

    /**
     * Initialize ACL manager
     * 
     * @param array $config ACL configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'default_policy' => 'deny',
            'admin_bypass' => false,
            'audit_enabled' => true,
            'cache_permissions' => true,
            'permission_ttl' => 300,
            'namespace_isolation' => true
        ], $config);
        
        $this->loadDefaultPermissions();
    }

    /**
     * Validate access to mesh key
     * 
     * @param string $meshKey Mesh key to access
     * @param string $operation Operation type (read/write/delete)
     * @param array $capabilities Unit capabilities
     * @return bool True if access allowed
     */
    public function validateAccess(string $meshKey, string $operation, array $capabilities): bool
    {
        $this->auditAccess($meshKey, $operation, $capabilities);
        
        // Admin bypass check
        if ($this->config['admin_bypass'] && in_array('admin', $capabilities)) {
            return true;
        }
        
        // Check namespace isolation
        if ($this->config['namespace_isolation']) {
            $namespace = $this->extractNamespace($meshKey);
            if (!$this->hasNamespaceAccess($namespace, $capabilities)) {
                return false;
            }
        }
        
        // Check specific permission
        return $this->hasPermission($meshKey, $operation, $capabilities);
    }

    /**
     * Grant permission for mesh key
     * 
     * @param string $meshKey Mesh key
     * @param string $operation Operation type
     * @param array $capabilities Required capabilities
     * @return void
     */
    public function grantPermission(string $meshKey, string $operation, array $capabilities): void
    {
        $permissionKey = $this->getPermissionKey($meshKey, $operation);
        
        $this->permissions[$permissionKey] = [
            'mesh_key' => $meshKey,
            'operation' => $operation,
            'capabilities' => $capabilities,
            'granted_at' => microtime(true),
            'ttl' => $this->config['permission_ttl']
        ];
    }

    /**
     * Revoke permission for mesh key
     * 
     * @param string $meshKey Mesh key
     * @param string $operation Operation type
     * @return void
     */
    public function revokePermission(string $meshKey, string $operation): void
    {
        $permissionKey = $this->getPermissionKey($meshKey, $operation);
        unset($this->permissions[$permissionKey]);
    }

    /**
     * Get audit log
     * 
     * @return array Audit log entries
     */
    public function getAuditLog(): array
    {
        return $this->auditLog;
    }

    /**
     * Clear expired permissions
     * 
     * @return void
     */
    public function clearExpiredPermissions(): void
    {
        $currentTime = microtime(true);
        
        $this->permissions = array_filter(
            $this->permissions,
            fn($permission) => 
                ($permission['granted_at'] + $permission['ttl']) > $currentTime
        );
    }

    /**
     * Check if has specific permission
     * 
     * @param string $meshKey Mesh key
     * @param string $operation Operation type
     * @param array $capabilities Unit capabilities
     * @return bool True if has permission
     */
    private function hasPermission(string $meshKey, string $operation, array $capabilities): bool
    {
        $permissionKey = $this->getPermissionKey($meshKey, $operation);
        
        if (!isset($this->permissions[$permissionKey])) {
            return $this->config['default_policy'] === 'allow';
        }
        
        $permission = $this->permissions[$permissionKey];
        
        // Check if permission is expired
        if (($permission['granted_at'] + $permission['ttl']) <= microtime(true)) {
            unset($this->permissions[$permissionKey]);
            return $this->config['default_policy'] === 'allow';
        }
        
        // Check if capabilities match
        $requiredCapabilities = $permission['capabilities'];
        return !empty(array_intersect($capabilities, $requiredCapabilities));
    }

    /**
     * Check namespace access
     * 
     * @param string $namespace Namespace to check
     * @param array $capabilities Unit capabilities
     * @return bool True if has namespace access
     */
    private function hasNamespaceAccess(string $namespace, array $capabilities): bool
    {
        // Check for namespace-specific capabilities
        $namespaceCapability = "namespace:{$namespace}";
        
        if (in_array($namespaceCapability, $capabilities)) {
            return true;
        }
        
        // Check for wildcard namespace access
        if (in_array('namespace:*', $capabilities)) {
            return true;
        }
        
        // Default namespace access for common namespaces
        $publicNamespaces = ['public', 'shared', 'common'];
        if (in_array($namespace, $publicNamespaces)) {
            return true;
        }
        
        return false;
    }

    /**
     * Extract namespace from mesh key
     * 
     * @param string $meshKey Mesh key
     * @return string Namespace
     */
    private function extractNamespace(string $meshKey): string
    {
        $parts = explode(':', $meshKey);
        return $parts[0] ?? 'default';
    }

    /**
     * Generate permission key
     * 
     * @param string $meshKey Mesh key
     * @param string $operation Operation type
     * @return string Permission key
     */
    private function getPermissionKey(string $meshKey, string $operation): string
    {
        return md5($meshKey . ':' . $operation);
    }

    /**
     * Audit access attempt
     * 
     * @param string $meshKey Mesh key
     * @param string $operation Operation type
     * @param array $capabilities Unit capabilities
     * @return void
     */
    private function auditAccess(string $meshKey, string $operation, array $capabilities): void
    {
        if (!$this->config['audit_enabled']) {
            return;
        }
        
        $this->auditLog[] = [
            'timestamp' => microtime(true),
            'mesh_key' => $meshKey,
            'operation' => $operation,
            'capabilities' => $capabilities,
            'namespace' => $this->extractNamespace($meshKey)
        ];
        
        // Limit audit log size
        if (count($this->auditLog) > 10000) {
            array_shift($this->auditLog);
        }
    }

    /**
     * Check if unit has required capabilities
     * 
     * @param array $unitCapabilities Unit's capabilities
     * @param array $requiredCapabilities Required capabilities
     * @return bool True if unit has required capabilities
     */
    public function hasCapabilities(array $unitCapabilities, array $requiredCapabilities): bool
    {
        return !empty(array_intersect($unitCapabilities, $requiredCapabilities));
    }

    /**
     * Check if unit can perform operation
     * 
     * @param array $unitCapabilities Unit's capabilities
     * @param string $operation Operation to check
     * @return bool True if unit can perform operation
     */
    public function unitCanPerform(array $unitCapabilities, string $operation): bool
    {
        // Check for operation-specific capabilities
        $operationCapability = "operation:{$operation}";
        if (in_array($operationCapability, $unitCapabilities)) {
            return true;
        }
        
        // Check for wildcard operation access
        if (in_array('operation:*', $unitCapabilities)) {
            return true;
        }
        
        // Check for admin access
        if (in_array('admin', $unitCapabilities)) {
            return true;
        }
        
        return false;
    }

    /**
     * Validate namespace access
     * 
     * @param string $namespace Namespace to validate
     * @param array $unitCapabilities Unit's capabilities
     * @return bool True if access is valid
     */
    public function validateNamespaceAccess(string $namespace, array $unitCapabilities): bool
    {
        return $this->hasNamespaceAccess($namespace, $unitCapabilities);
    }

    /**
     * Get effective permissions for unit
     * 
     * @param array $unitCapabilities Unit's capabilities
     * @return array Effective permissions
     */
    public function getEffectivePermissions(array $unitCapabilities): array
    {
        $effectivePermissions = [];
        
        foreach ($this->permissions as $permissionKey => $permission) {
            if ($this->hasCapabilities($unitCapabilities, $permission['capabilities'])) {
                $effectivePermissions[$permissionKey] = [
                    'mesh_key' => $permission['mesh_key'],
                    'operation' => $permission['operation'],
                    'granted_at' => $permission['granted_at'],
                    'expires_at' => $permission['granted_at'] + $permission['ttl']
                ];
            }
        }
        
        return $effectivePermissions;
    }

    /**
     * Get audit trail for specific criteria
     * 
     * @param array $filters Audit trail filters
     * @return array Filtered audit trail
     */
    public function getAuditTrail(array $filters = []): array
    {
        $trail = $this->auditLog;
        
        // Apply filters
        if (isset($filters['mesh_key'])) {
            $trail = array_filter($trail, fn($entry) => 
                str_contains($entry['mesh_key'], $filters['mesh_key']));
        }
        
        if (isset($filters['operation'])) {
            $trail = array_filter($trail, fn($entry) => 
                $entry['operation'] === $filters['operation']);
        }
        
        if (isset($filters['namespace'])) {
            $trail = array_filter($trail, fn($entry) => 
                $entry['namespace'] === $filters['namespace']);
        }
        
        if (isset($filters['time_range'])) {
            $start = $filters['time_range']['start'] ?? 0;
            $end = $filters['time_range']['end'] ?? PHP_FLOAT_MAX;
            $trail = array_filter($trail, fn($entry) => 
                $entry['timestamp'] >= $start && $entry['timestamp'] <= $end);
        }
        
        return array_values($trail);
    }

    /**
     * Record access attempt
     * 
     * @param string $meshKey Mesh key accessed
     * @param string $operation Operation performed
     * @param array $unitCapabilities Unit's capabilities
     * @param bool $granted Whether access was granted
     * @return void
     */
    public function recordAccess(string $meshKey, string $operation, array $unitCapabilities, bool $granted): void
    {
        if (!$this->config['audit_enabled']) {
            return;
        }
        
        $this->auditLog[] = [
            'timestamp' => microtime(true),
            'mesh_key' => $meshKey,
            'operation' => $operation,
            'capabilities' => $unitCapabilities,
            'namespace' => $this->extractNamespace($meshKey),
            'access_granted' => $granted,
            'result' => $granted ? 'allowed' : 'denied'
        ];
        
        // Limit audit log size
        if (count($this->auditLog) > 10000) {
            array_shift($this->auditLog);
        }
    }

    /**
     * Load default permissions
     * 
     * @return void
     */
    private function loadDefaultPermissions(): void
    {
        // Grant basic read access to public namespaces
        $this->grantPermission('public:*', 'read', ['public']);
        $this->grantPermission('shared:*', 'read', ['shared']);
        $this->grantPermission('common:*', 'read', ['common']);
        
        // Grant admin full access
        $this->grantPermission('*', '*', ['admin']);
    }
}
