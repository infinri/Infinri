<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\AccessControl;

use Infinri\SwarmFramework\Exceptions\MeshAccessException;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;

/**
 * Mesh Access Controller - ACL and Security Management
 * 
 * Manages access control, permissions, and security policies
 * for the semantic mesh operations.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class MeshAccessController
{
    use LoggerTrait;
    private array $aclRules = [];
    private array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('MeshAccessController', $config);
        
        $this->loadAclRules();
    }

    /**
     * Check if operation is allowed for a key
     */
    public function checkAccess(string $key, string $operation, ?string $context = null): bool
    {
        if (!$this->config['enable_acl']) {
            return true;
        }

        try {
            // Check global deny rules first
            if ($this->isGloballyDenied($key, $operation)) {
                $this->logger->warning('Access denied by global rule', $this->buildSecurityContext('access_denied', [
                    'key' => $key,
                    'operation' => $operation,
                    'context' => $context
                ]));
                return false;
            }

            // Check specific ACL rules
            foreach ($this->aclRules as $rule) {
                if ($this->matchesRule($key, $rule)) {
                    $allowed = $this->evaluateRule($rule, $operation, $context);
                    
                    if (!$allowed) {
                        $this->logger->warning('Access denied by ACL rule', $this->buildSecurityContext('acl_denied', [
                            'key' => $key,
                            'operation' => $operation,
                            'rule' => $rule['name'] ?? 'unnamed',
                            'context' => $context
                        ]));
                    }
                    
                    return $allowed;
                }
            }

            // Check default permissions
            return $this->checkDefaultPermissions($key, $operation);

        } catch (\Exception $e) {
            $this->logger->error('ACL check failed', $this->buildErrorContext('acl_check', $e, [
                'key' => $key,
                'operation' => $operation
            ]));
            
            // Fail secure - deny access on error
            return false;
        }
    }

    /**
     * Check if key matches admin pattern
     */
    public function isAdminKey(string $key): bool
    {
        foreach ($this->config['admin_keys'] as $pattern) {
            if (fnmatch($pattern, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if key is read-only
     */
    public function isReadOnlyKey(string $key): bool
    {
        foreach ($this->config['readonly_keys'] as $pattern) {
            if (fnmatch($pattern, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if key is public access
     */
    public function isPublicKey(string $key): bool
    {
        foreach ($this->config['public_keys'] as $pattern) {
            if (fnmatch($pattern, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add ACL rule
     */
    public function addRule(array $rule): void
    {
        if (!$this->validateRule($rule)) {
            throw ExceptionFactory::invalidArgument('Invalid ACL rule structure', [
                'rule' => $rule,
                'required_fields' => ['name', 'pattern', 'operations', 'allow']
            ]);
        }

        $this->aclRules[] = $rule;
        
        $this->logger->debug('ACL rule added', $this->buildOperationContext('add_acl_rule', [
            'rule_name' => $rule['name'],
            'pattern' => $rule['pattern'],
            'operations' => $rule['operations']
        ]));
    }

    /**
     * Remove ACL rule by name
     */
    public function removeRule(string $ruleName): bool
    {
        $initialCount = count($this->aclRules);
        
        $this->aclRules = array_filter(
            $this->aclRules,
            fn($rule) => ($rule['name'] ?? '') !== $ruleName
        );

        $removed = count($this->aclRules) < $initialCount;
        
        if ($removed) {
            $this->logger->info('ACL rule removed', $this->buildOperationContext('remove_acl_rule', ['rule_name' => $ruleName]));
        }
        
        return $removed;
    }

    /**
     * Get all ACL rules
     */
    public function getRules(): array
    {
        return $this->aclRules;
    }

    /**
     * Load ACL rules from configuration
     */
    private function loadAclRules(): void
    {
        $configRules = $this->config['acl_rules'] ?? [];
        
        foreach ($configRules as $rule) {
            if ($this->validateRule($rule)) {
                $this->aclRules[] = $rule;
            } else {
                $this->logger->warning('ACL rule validation failed', $this->buildValidationContext(['acl_rule_validation'], [], ['rule' => $rule]));
            }
        }

        $this->logger->info('ACL rules loaded', $this->buildOperationContext('load_acl_rules', ['count' => count($this->aclRules)]));
    }

    /**
     * Check if globally denied
     */
    private function isGloballyDenied(string $key, string $operation): bool
    {
        // System protection rules
        if ($operation === 'delete' && $this->isAdminKey($key)) {
            return true; // Protect admin keys from deletion
        }

        if (in_array($operation, ['write', 'delete']) && $this->isReadOnlyKey($key)) {
            return true; // Protect read-only keys
        }

        return false;
    }

    /**
     * Check if key matches ACL rule pattern
     */
    private function matchesRule(string $key, array $rule): bool
    {
        $pattern = $rule['pattern'] ?? '';
        
        if (empty($pattern)) {
            return false;
        }

        // Support wildcards and regex patterns
        if (strpos($pattern, '*') !== false) {
            return fnmatch($pattern, $key);
        }

        if (strpos($pattern, '/') === 0) {
            // Regex pattern
            return preg_match($pattern, $key) === 1;
        }

        // Exact match
        return $key === $pattern;
    }

    /**
     * Evaluate ACL rule for operation
     */
    private function evaluateRule(array $rule, string $operation, ?string $context): bool
    {
        $permissions = $rule['permissions'] ?? [];
        
        // Check if operation is explicitly allowed
        if (in_array($operation, $permissions)) {
            return true;
        }

        // Check if operation is explicitly denied
        $deniedOps = $rule['denied_operations'] ?? [];
        if (in_array($operation, $deniedOps)) {
            return false;
        }

        // Check context-based permissions
        if ($context && isset($rule['context_permissions'][$context])) {
            return in_array($operation, $rule['context_permissions'][$context]);
        }

        // Check time-based restrictions
        if (isset($rule['time_restrictions'])) {
            return $this->checkTimeRestrictions($rule['time_restrictions']);
        }

        // Default to rule's default permission
        return $rule['default_allow'] ?? false;
    }

    /**
     * Check default permissions
     */
    private function checkDefaultPermissions(string $key, string $operation): bool
    {
        // Public keys allow all operations
        if ($this->isPublicKey($key)) {
            return true;
        }

        // Read-only keys only allow read
        if ($this->isReadOnlyKey($key)) {
            return $operation === 'read';
        }

        // Admin keys require special handling (should be caught by rules)
        if ($this->isAdminKey($key)) {
            return false; // Deny by default, require explicit rules
        }

        // Default permissions for other keys
        return in_array($operation, $this->config['default_permissions']);
    }

    /**
     * Check time-based restrictions
     */
    private function checkTimeRestrictions(array $restrictions): bool
    {
        $currentTime = time();
        $currentHour = (int)date('H', $currentTime);
        $currentDay = (int)date('w', $currentTime); // 0 = Sunday

        // Check hour restrictions
        if (isset($restrictions['allowed_hours'])) {
            if (!in_array($currentHour, $restrictions['allowed_hours'])) {
                return false;
            }
        }

        // Check day restrictions
        if (isset($restrictions['allowed_days'])) {
            if (!in_array($currentDay, $restrictions['allowed_days'])) {
                return false;
            }
        }

        // Check date range restrictions
        if (isset($restrictions['start_date']) && isset($restrictions['end_date'])) {
            $startDate = strtotime($restrictions['start_date']);
            $endDate = strtotime($restrictions['end_date']);
            
            if ($currentTime < $startDate || $currentTime > $endDate) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate ACL rule format
     */
    private function validateRule(array $rule): bool
    {
        // Required fields
        if (!isset($rule['pattern'])) {
            return false;
        }

        // Validate permissions array
        if (isset($rule['permissions']) && !is_array($rule['permissions'])) {
            return false;
        }

        // Validate context permissions
        if (isset($rule['context_permissions']) && !is_array($rule['context_permissions'])) {
            return false;
        }

        return true;
    }

    /**
     * Get access statistics
     */
    public function getAccessStats(): array
    {
        return [
            'total_rules' => count($this->aclRules),
            'admin_keys_pattern' => $this->config['admin_keys'],
            'readonly_keys_pattern' => $this->config['readonly_keys'],
            'public_keys_pattern' => $this->config['public_keys'],
            'acl_enabled' => $this->config['enable_acl']
        ];
    }

    /**
     * Reset ACL rules to defaults
     */
    public function resetToDefaults(): void
    {
        $this->aclRules = [];
        $this->loadAclRules();
        
        $this->logger->info('ACL rules reset to defaults');
    }
}
