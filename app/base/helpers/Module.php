<?php
declare(strict_types=1);
/**
 * Module Helper
 *
 * Module discovery and management utilities
 *
 * @package App\Helpers
 */

namespace App\Helpers;

class Module
{
    /**
     * Auto-discover all available modules
     *
     * @return array List of module names
     */
    public static function discover(): array
    {
        return Cache::remember('modules.discovered', function () {
            $modulesDir = dirname(__DIR__, 2) . '/modules';
            $modules = [];

            $dirs = Path::scanDir($modulesDir);

            foreach ($dirs as $dir) {
                $path = Path::join($modulesDir, $dir);

                // Must be a directory
                if (! is_dir($path)) {
                    continue;
                }

                // Validate module name
                if (! preg_match('/^[a-z0-9_-]+$/', $dir)) {
                    continue;
                }

                // Must contain {ModuleName}Module.php
                $moduleFile = self::getClassFile($dir);

                if (file_exists($moduleFile)) {
                    $modules[] = $dir;
                }
            }

            return $modules;
        });
    }


    /**
     * Get module class file path
     *
     * @param string $name Module name
     * @return string
     */
    public static function getClassFile(string $name): string
    {
        $className = ucfirst($name) . 'Module.php';
        return Path::module($name, $className);
    }

    /**
     * Get module class name (with namespace)
     *
     * @param string $name Module name
     * @return string
     */
    public static function getClassName(string $name): string
    {
        $moduleName = ucfirst($name);
        return "\\App\\Modules\\{$moduleName}\\{$moduleName}Module";
    }

    /**
     * Check if module has assets
     *
     * @param string $name Module name
     * @param string $context Context (frontend/admin)
     * @return bool
     */
    public static function hasAssets(string $name, string $context = 'frontend'): bool
    {
        $assetsPath = Path::module($name, "view/{$context}");
        return is_dir($assetsPath);
    }
}
