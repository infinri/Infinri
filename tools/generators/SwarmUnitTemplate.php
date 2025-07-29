<?php declare(strict_types=1);

namespace Infinri\Tools\Generators;

use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Infinri\SwarmFramework\Core\Attributes\Tactic;
use Infinri\SwarmFramework\Core\Attributes\Goal;
use Infinri\SwarmFramework\Core\Attributes\Injectable;

/**
 * SwarmUnit Template Generator - Creates Properly Annotated SwarmUnits
 * 
 * Generates SwarmUnit classes with proper annotations, ethical validation hooks,
 * and architectural compliance. Ensures all generated units follow the
 * Swarm Pattern™ and digital consciousness principles.
 * 
 * @reference infinri_blueprint.md → Unit Generation Guidelines
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: [])]
final class SwarmUnitTemplate
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'namespace_prefix' => 'Infinri\\Modules',
            'output_path' => 'modules',
            'include_ethical_validation' => true,
            'include_performance_monitoring' => true,
            'default_priority' => 50,
            'default_cooldown_ms' => 0,
            'default_timeout_ms' => 5000
        ], $config);
    }

    /**
     * Generate a SwarmUnit class from specification
     * 
     * @param array $spec Unit specification
     * @return string Generated PHP class code
     */
    public function generateUnit(array $spec): string
    {
        $this->validateSpecification($spec);
        
        $className = $spec['class_name'];
        $namespace = $this->buildNamespace($spec['module']);
        $identity = $this->buildIdentity($spec);
        $tactics = $this->buildTactics($spec['tactics'] ?? []);
        $goals = $this->buildGoals($spec['goals'] ?? []);
        
        $template = $this->getDefaultUnitTemplate();
        
        $replacements = [
            '{{NAMESPACE}}' => $namespace,
            '{{CLASS_NAME}}' => $className,
            '{{IDENTITY_ANNOTATION}}' => $identity,
            '{{TACTICS_ANNOTATIONS}}' => $tactics,
            '{{GOALS_ANNOTATION}}' => $goals,
            '{{DESCRIPTION}}' => $spec['description'] ?? "Generated SwarmUnit: {$className}",
            '{{AUTHOR}}' => $spec['author'] ?? 'Infinri Framework',
            '{{VERSION}}' => $spec['version'] ?? '1.0.0',
            '{{ID}}' => $spec['id'],
            '{{HASH}}' => $spec['hash'],
            '{{CAPABILITIES}}' => $this->formatArray($spec['capabilities'] ?? []),
            '{{DEPENDENCIES}}' => $this->formatArray($spec['dependencies'] ?? []),
            '{{MESH_KEYS}}' => $this->formatArray($spec['mesh_keys'] ?? []),
            '{{TRIGGER_CONDITION}}' => $this->generateTriggerCondition($spec),
            '{{ACT_METHOD}}' => $this->generateActMethod($spec),
            '{{PRIORITY}}' => $spec['priority'] ?? $this->config['default_priority'],
            '{{COOLDOWN}}' => $spec['cooldown_ms'] ?? $this->config['default_cooldown_ms'],
            '{{TIMEOUT}}' => $spec['timeout_ms'] ?? $this->config['default_timeout_ms'],
            '{{MUTEX_GROUP}}' => $this->formatMutexGroup($spec['mutex_group'] ?? null),
            '{{RESOURCE_REQUIREMENTS}}' => $this->generateResourceRequirements($spec),
            '{{HEALTH_CHECKS}}' => $this->generateHealthChecks($spec),
            '{{INITIALIZATION}}' => $this->generateInitialization($spec),
            '{{SHUTDOWN}}' => $this->generateShutdown($spec),
            '{{VALIDATION}}' => $this->generateValidation($spec),
            '{{ETHICAL_VALIDATION}}' => $this->generateEthicalValidation($spec),
            '{{IMPORTS}}' => $this->generateImports($spec)
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Generate a complete module with multiple units
     * 
     * @param array $moduleSpec Module specification
     * @return array Generated files mapping
     */
    public function generateModule(array $moduleSpec): array
    {
        $files = [];
        $moduleName = $moduleSpec['name'];
        $basePath = $this->config['output_path'] . '/' . $moduleName;
        
        // Generate module manifest
        $manifest = $this->generateModuleManifest($moduleSpec);
        $files[$basePath . '/swarm-module.json'] = $manifest;
        
        // Generate README
        $readme = $this->generateModuleReadme($moduleSpec);
        $files[$basePath . '/README.md'] = $readme;
        
        // Generate units
        foreach ($moduleSpec['units'] as $unitSpec) {
            $unitSpec['module'] = $moduleName;
            $unitCode = $this->generateUnit($unitSpec);
            $unitPath = $basePath . '/SwarmUnits/' . $unitSpec['class_name'] . '.php';
            $files[$unitPath] = $unitCode;
        }
        
        return $files;
    }

    /**
     * Validate unit specification
     */
    private function validateSpecification(array $spec): void
    {
        $required = ['class_name', 'id', 'version', 'hash'];
        
        foreach ($required as $field) {
            if (!isset($spec[$field]) || empty($spec[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty");
            }
        }
        
        // Validate formats
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*Unit$/', $spec['class_name'])) {
            throw new \InvalidArgumentException("Class name must end with 'Unit' and follow PascalCase");
        }
        
        if (!preg_match('/^[a-z][a-z0-9-]*[a-z0-9]$/', $spec['id'])) {
            throw new \InvalidArgumentException("Unit ID must be in kebab-case format");
        }
        
        if (!preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9]+)?$/', $spec['version'])) {
            throw new \InvalidArgumentException("Version must follow semantic versioning");
        }
        
        if (!preg_match('/^sha256:[a-f0-9]{64}$/', $spec['hash'])) {
            throw new \InvalidArgumentException("Hash must be SHA-256 format");
        }
    }

    /**
     * Build namespace from module name
     */
    private function buildNamespace(string $module): string
    {
        $moduleParts = explode('-', $module);
        $moduleParts = array_map('ucfirst', $moduleParts);
        $moduleNamespace = implode('', $moduleParts);
        
        return $this->config['namespace_prefix'] . '\\' . $moduleNamespace . '\\SwarmUnits';
    }

    /**
     * Build UnitIdentity annotation
     */
    private function buildIdentity(array $spec): string
    {
        $capabilities = $this->formatArrayForAnnotation($spec['capabilities'] ?? []);
        $dependencies = $this->formatArrayForAnnotation($spec['dependencies'] ?? []);
        $meshKeys = $this->formatArrayForAnnotation($spec['mesh_keys'] ?? []);
        
        return sprintf(
            "#[UnitIdentity(\n    id: '%s',\n    version: '%s',\n    hash: '%s',\n    capabilities: [%s],\n    dependencies: [%s],\n    meshKeys: [%s],\n    description: '%s'\n)]",
            $spec['id'],
            $spec['version'],
            $spec['hash'],
            $capabilities,
            $dependencies,
            $meshKeys,
            $spec['description'] ?? ''
        );
    }

    /**
     * Build Tactic annotations
     */
    private function buildTactics(array $tactics): string
    {
        if (empty($tactics)) {
            return '';
        }
        
        $annotations = [];
        foreach ($tactics as $tactic) {
            $annotations[] = "#[Tactic('{$tactic}')]";
        }
        
        return implode("\n", $annotations);
    }

    /**
     * Build Goal annotation
     */
    private function buildGoals(array $goals): string
    {
        if (empty($goals)) {
            return '';
        }
        
        $description = $goals['description'] ?? 'Generated unit goal';
        $requirements = $this->formatArrayForAnnotation($goals['requirements'] ?? []);
        $priority = $goals['priority'] ?? 5;
        
        return sprintf(
            "#[Goal(\n    description: '%s',\n    requirements: [%s],\n    priority: %d\n)]",
            $description,
            $requirements,
            $priority
        );
    }

    /**
     * Format array for PHP code
     */
    private function formatArray(array $items): string
    {
        if (empty($items)) {
            return '[]';
        }
        
        $formatted = array_map(fn($item) => "'{$item}'", $items);
        return '[' . implode(', ', $formatted) . ']';
    }

    /**
     * Format array for annotation
     */
    private function formatArrayForAnnotation(array $items): string
    {
        if (empty($items)) {
            return '';
        }
        
        return "'" . implode("', '", $items) . "'";
    }

    /**
     * Generate trigger condition method
     */
    private function generateTriggerCondition(array $spec): string
    {
        if (isset($spec['trigger_condition'])) {
            return $spec['trigger_condition'];
        }
        
        return "        // TODO: Implement trigger condition logic\n        return false;";
    }

    /**
     * Generate act method
     */
    private function generateActMethod(array $spec): string
    {
        if (isset($spec['act_method'])) {
            return $spec['act_method'];
        }
        
        $template = "        // TODO: Implement unit action logic\n";
        
        if ($this->config['include_ethical_validation']) {
            $template .= "        \$this->validateEthicalConstraints(\$mesh);\n\n";
        }
        
        if ($this->config['include_performance_monitoring']) {
            $template .= "        \$startTime = microtime(true);\n";
            $template .= "        \$this->recordMetric('execution_start', \$startTime);\n\n";
        }
        
        $template .= "        // Your implementation here\n\n";
        
        if ($this->config['include_performance_monitoring']) {
            $template .= "        \$duration = (microtime(true) - \$startTime) * 1000;\n";
            $template .= "        \$this->recordMetric('execution_duration_ms', \$duration);";
        }
        
        return $template;
    }

    /**
     * Format mutex group
     */
    private function formatMutexGroup(?string $mutexGroup): string
    {
        return $mutexGroup ? "'{$mutexGroup}'" : 'null';
    }

    /**
     * Generate resource requirements
     */
    private function generateResourceRequirements(array $spec): string
    {
        $requirements = $spec['resource_requirements'] ?? [];
        
        $defaults = [
            'memory_mb' => 64,
            'cpu_percentage' => 10,
            'io_operations_per_sec' => 100
        ];
        
        $requirements = array_merge($defaults, $requirements);
        
        return "        return [\n" .
               "            'memory_mb' => {$requirements['memory_mb']},\n" .
               "            'cpu_percentage' => {$requirements['cpu_percentage']},\n" .
               "            'io_operations_per_sec' => {$requirements['io_operations_per_sec']}\n" .
               "        ];";
    }

    /**
     * Generate health checks
     */
    private function generateHealthChecks(array $spec): string
    {
        return "        return \$this->isInitialized;";
    }

    /**
     * Generate initialization method
     */
    private function generateInitialization(array $spec): string
    {
        return "        \$this->logger->info('Initializing unit', ['unit_id' => \$this->getIdentity()->id]);\n        \$this->isInitialized = true;";
    }

    /**
     * Generate shutdown method
     */
    private function generateShutdown(array $spec): string
    {
        return "        \$this->logger->info('Shutting down unit');\n        \$this->isInitialized = false;";
    }

    /**
     * Generate validation method
     */
    private function generateValidation(array $spec): string
    {
        return "        return new ValidationResult(true, [], []);";
    }

    /**
     * Generate ethical validation
     */
    private function generateEthicalValidation(array $spec): string
    {
        if (!$this->config['include_ethical_validation']) {
            return '';
        }
        
        return "\n    private function validateEthicalConstraints(SemanticMeshInterface \$mesh): void\n    {\n        // TODO: Implement ethical validation\n    }";
    }

    /**
     * Generate imports
     */
    private function generateImports(array $spec): string
    {
        $imports = [
            'use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;',
            'use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;',
            'use Infinri\SwarmFramework\Core\UnitIdentity;',
            'use Infinri\SwarmFramework\Core\Tactic;',
            'use Infinri\SwarmFramework\Core\Goal;',
            'use Infinri\SwarmFramework\Core\ValidationResult;',
            'use Psr\Log\LoggerInterface;'
        ];
        
        return implode("\n", $imports);
    }

    /**
     * Get default unit template
     */
    private function getDefaultUnitTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace {{NAMESPACE}};

{{IMPORTS}}

/**
 * {{CLASS_NAME}} - {{DESCRIPTION}}
 * 
 * @author {{AUTHOR}}
 * @version {{VERSION}}
 */
{{IDENTITY_ANNOTATION}}
{{TACTICS_ANNOTATIONS}}
{{GOALS_ANNOTATION}}
class {{CLASS_NAME}} implements SwarmUnitInterface
{
    private LoggerInterface $logger;
    private bool $isInitialized = false;
    private array $metrics = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function triggerCondition(SemanticMeshInterface $mesh): bool
    {
{{TRIGGER_CONDITION}}
    }

    public function act(SemanticMeshInterface $mesh): void
    {
{{ACT_METHOD}}
    }

    public function getIdentity(): UnitIdentity
    {
        return new UnitIdentity(
            id: \'{{ID}}\',
            version: \'{{VERSION}}\',
            hash: \'{{HASH}}\',
            capabilities: {{CAPABILITIES}},
            dependencies: {{DEPENDENCIES}},
            meshKeys: {{MESH_KEYS}},
            description: \'{{DESCRIPTION}}\'
        );
    }

    public function getPriority(): int
    {
        return {{PRIORITY}};
    }

    public function getCooldown(): int
    {
        return {{COOLDOWN}};
    }

    public function getTimeout(): int
    {
        return {{TIMEOUT}};
    }

    public function getMutexGroup(): ?string
    {
        return {{MUTEX_GROUP}};
    }

    public function getResourceRequirements(): array
    {
{{RESOURCE_REQUIREMENTS}}
    }

    public function isHealthy(): bool
    {
{{HEALTH_CHECKS}}
    }

    public function getHealthMetrics(): array
    {
        return $this->metrics;
    }

    public function initialize(SemanticMeshInterface $mesh): void
    {
{{INITIALIZATION}}
    }

    public function shutdown(SemanticMeshInterface $mesh): void
    {
{{SHUTDOWN}}
    }

    public function validate(): ValidationResult
    {
{{VALIDATION}}
    }

    private function recordMetric(string $name, mixed $value): void
    {
        $this->metrics[$name] = [
            \'value\' => $value,
            \'timestamp\' => microtime(true)
        ];
    }
{{ETHICAL_VALIDATION}}
}';
    }

    /**
     * Generate module manifest
     */
    private function generateModuleManifest(array $moduleSpec): string
    {
        $manifest = [
            'manifest_version' => '1.0',
            'name' => $moduleSpec['name'],
            'version' => $moduleSpec['version'],
            'description' => $moduleSpec['description'] ?? '',
            'units' => [],
            'dependencies' => $moduleSpec['dependencies'] ?? [],
            'capabilities' => $moduleSpec['capabilities'] ?? []
        ];
        
        foreach ($moduleSpec['units'] as $unitSpec) {
            $manifest['units'][] = [
                'class' => $unitSpec['class_name'],
                'id' => $unitSpec['id'],
                'version' => $unitSpec['version'],
                'hash' => $unitSpec['hash']
            ];
        }
        
        return json_encode($manifest, JSON_PRETTY_PRINT);
    }

    /**
     * Generate module README
     */
    private function generateModuleReadme(array $moduleSpec): string
    {
        $name = $moduleSpec['name'];
        $description = $moduleSpec['description'] ?? "Generated module: {$name}";
        
        return "# {$name} Module\n\n{$description}\n\n## SwarmUnits\n\nThis module contains " . count($moduleSpec['units']) . " SwarmUnits.\n";
    }
}
