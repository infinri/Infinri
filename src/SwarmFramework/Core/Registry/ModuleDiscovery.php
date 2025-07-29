<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Registry;

use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Mesh\ModuleManifest;
use Psr\Log\LoggerInterface;

/**
 * Module Discovery - Automated Module and Unit Detection
 * 
 * Discovers modules and SwarmUnits across configured paths using
 * PSR-4 autoloading patterns and manifest scanning.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class ModuleDiscovery
{
    use LoggerTrait;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = ConfigManager::getConfig('ModuleDiscovery', $config);
    }

    /**
     * Discover all modules in configured paths
     */
    public function discoverAllModules(): array
    {
        $discoveredModules = [];
        
        foreach ($this->config['module_paths'] as $basePath) {
            if (!is_dir($basePath)) {
                $this->logger->warning('Module path does not exist', ['path' => $basePath]);
                continue;
            }

            $modules = $this->scanDirectoryForModules($basePath);
            $discoveredModules = array_merge($discoveredModules, $modules);
        }

        $this->logger->info('Module discovery completed', [
            'discovered_modules' => count($discoveredModules),
            'scanned_paths' => $this->config['module_paths']
        ]);

        return $discoveredModules;
    }

    /**
     * Discover all SwarmUnits across registered modules
     */
    public function discoverAllUnits(array $registeredModules): array
    {
        $allUnits = [];
        
        foreach ($registeredModules as $moduleName => $manifest) {
            $moduleUnits = $this->discoverModuleUnits($this->getModulePath($moduleName), $manifest);
            $allUnits[$moduleName] = $moduleUnits;
        }

        $totalUnits = array_sum(array_map('count', $allUnits));
        
        $this->logger->info('Unit discovery completed', [
            'total_units' => $totalUnits,
            'modules_scanned' => count($registeredModules)
        ]);

        return $allUnits;
    }

    /**
     * Discover units within a specific module
     */
    public function discoverModuleUnits(string $modulePath, ModuleManifest $manifest): array
    {
        $discoveredUnits = [];
        
        // First, check manifest-declared units
        foreach ($manifest->getUnits() as $unitClass) {
            if (class_exists($unitClass) && $this->implementsSwarmUnitInterface($unitClass)) {
                $discoveredUnits[] = $unitClass;
            }
        }

        // Then, scan for additional units in the module directory
        $scannedUnits = $this->scanDirectoryForUnits($modulePath);
        
        // Merge and deduplicate
        $allUnits = array_unique(array_merge($discoveredUnits, $scannedUnits));

        $this->logger->debug('Module units discovered', [
            'module' => $manifest->getName(),
            'manifest_units' => count($manifest->getUnits()),
            'scanned_units' => count($scannedUnits),
            'total_units' => count($allUnits)
        ]);

        return $allUnits;
    }

    /**
     * Scan directory for modules
     */
    private function scanDirectoryForModules(string $basePath, int $depth = 0): array
    {
        $modules = [];
        
        if ($depth > $this->config['max_scan_depth']) {
            return $modules;
        }

        $iterator = new \DirectoryIterator($basePath);
        
        foreach ($iterator as $item) {
            if ($item->isDot() || $this->isExcluded($item->getFilename())) {
                continue;
            }

            if ($item->isDir()) {
                $modulePath = $item->getPathname();
                $manifestPath = $modulePath . '/' . $this->config['manifest_filename'];
                
                if (file_exists($manifestPath)) {
                    // Found a module
                    try {
                        $manifest = ModuleManifest::fromJsonFile($manifestPath);
                        $modules[$manifest->getName()] = [
                            'path' => $modulePath,
                            'manifest' => $manifest
                        ];
                        
                        $this->logger->debug('Module discovered', [
                            'module' => $manifest->getName(),
                            'path' => $modulePath,
                            'version' => $manifest->getVersion()
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->warning('Invalid module manifest', [
                            'path' => $manifestPath,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    // Recursively scan subdirectories
                    $subModules = $this->scanDirectoryForModules($modulePath, $depth + 1);
                    $modules = array_merge($modules, $subModules);
                }
            }
        }

        return $modules;
    }

    /**
     * Scan directory for SwarmUnit files
     */
    private function scanDirectoryForUnits(string $modulePath): array
    {
        $units = [];
        
        if (!is_dir($modulePath)) {
            return $units;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modulePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->extractClassName($file->getPathname());
                
                if ($className && $this->implementsSwarmUnitInterface($className)) {
                    $units[] = $className;
                    
                    $this->logger->debug('SwarmUnit discovered', [
                        'class' => $className,
                        'file' => $file->getPathname()
                    ]);
                }
            }
        }

        return $units;
    }

    /**
     * Extract class name from PHP file
     */
    private function extractClassName(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        
        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        // Extract class name
        $className = null;
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = trim($matches[1]);
        }

        if ($namespace && $className) {
            return $namespace . '\\' . $className;
        } elseif ($className) {
            return $className;
        }

        return null;
    }

    /**
     * Check if class implements SwarmUnitInterface
     */
    private function implementsSwarmUnitInterface(string $className): bool
    {
        try {
            if (!class_exists($className)) {
                return false;
            }

            $reflection = new \ReflectionClass($className);
            return $reflection->implementsInterface(SwarmUnitInterface::class);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to check SwarmUnit interface', [
                'class' => $className,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if filename should be excluded
     */
    private function isExcluded(string $filename): bool
    {
        foreach ($this->config['exclude_patterns'] as $pattern) {
            if (fnmatch($pattern, $filename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get module path by name
     */
    private function getModulePath(string $moduleName): string
    {
        // Simplified - in production would track actual paths
        foreach ($this->config['module_paths'] as $basePath) {
            $modulePath = $basePath . '/' . $moduleName;
            if (is_dir($modulePath)) {
                return $modulePath;
            }
        }
        return 'modules/' . $moduleName;
    }

    /**
     * Find specific module version
     */
    public function findModuleVersion(string $moduleName, string $version): ?string
    {
        foreach ($this->config['module_paths'] as $basePath) {
            $versionPath = $basePath . '/' . $moduleName . '-' . $version;
            if (is_dir($versionPath)) {
                $manifestPath = $versionPath . '/' . $this->config['manifest_filename'];
                if (file_exists($manifestPath)) {
                    return $versionPath;
                }
            }
        }
        return null;
    }

    /**
     * Get discovery statistics
     */
    public function getDiscoveryStats(): array
    {
        return [
            'configured_paths' => $this->config['module_paths'],
            'manifest_filename' => $this->config['manifest_filename'],
            'max_scan_depth' => $this->config['max_scan_depth'],
            'exclude_patterns' => $this->config['exclude_patterns']
        ];
    }

    /**
     * Validate discovered module structure
     */
    public function validateModuleStructure(string $modulePath): array
    {
        $issues = [];
        
        // Check if directory exists
        if (!is_dir($modulePath)) {
            $issues[] = "Module directory does not exist: {$modulePath}";
            return $issues;
        }

        // Check for manifest file
        $manifestPath = $modulePath . '/' . $this->config['manifest_filename'];
        if (!file_exists($manifestPath)) {
            $issues[] = "Module manifest not found: {$manifestPath}";
        } else {
            try {
                $manifest = ModuleManifest::fromJsonFile($manifestPath);
                $manifestErrors = $manifest->getValidationErrors();
                $issues = array_merge($issues, $manifestErrors);
            } catch (\Exception $e) {
                $issues[] = "Invalid manifest file: {$e->getMessage()}";
            }
        }

        // Check for common module structure
        $expectedDirs = ['src', 'units', 'config'];
        foreach ($expectedDirs as $dir) {
            $dirPath = $modulePath . '/' . $dir;
            if (!is_dir($dirPath)) {
                $issues[] = "Recommended directory missing: {$dir}";
            }
        }

        return $issues;
    }
}
