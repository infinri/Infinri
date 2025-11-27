<?php

declare(strict_types=1);

namespace App\Core\Compiler;

use App\Core\Module\ModuleRegistry;

/**
 * Config Compiler
 *
 * Merges module configs into a single cached config file.
 */
class ConfigCompiler
{
    protected string $basePath;
    protected string $cachePath;
    protected ModuleRegistry $registry;

    public function __construct(
        ?string $basePath = null,
        ?string $cachePath = null,
        ?ModuleRegistry $registry = null
    ) {
        $this->basePath = $basePath ?? $this->getDefaultBasePath();
        $this->cachePath = $cachePath ?? $this->basePath . '/var/cache/config.php';
        $this->registry = $registry ?? new ModuleRegistry();
    }

    public function compile(): array
    {
        $config = [];
        $config = $this->loadModuleConfigs($config);
        $config = $this->loadAppConfig($config);
        $this->saveToCache($config);
        return $config;
    }

    public function load(): array
    {
        if ($this->isCached()) {
            return $this->loadFromCache();
        }
        return $this->compile();
    }

    public function isCached(): bool
    {
        return file_exists($this->cachePath);
    }

    public function clear(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    protected function loadModuleConfigs(array $config): array
    {
        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            $moduleConfig = $module->loadConfig();

            if (!empty($moduleConfig)) {
                $config['modules'][$module->name] = $moduleConfig;

                if (isset($moduleConfig['_global'])) {
                    $config = $this->mergeDeep($config, $moduleConfig['_global']);
                    unset($config['modules'][$module->name]['_global']);
                }
            }
        }

        return $config;
    }

    protected function loadAppConfig(array $config): array
    {
        $appConfigPath = $this->basePath . '/app/config.php';

        if (file_exists($appConfigPath)) {
            $appConfig = require $appConfigPath;

            if (is_array($appConfig)) {
                $config = $this->mergeDeep($config, $appConfig);
            }
        }

        return $config;
    }

    protected function mergeDeep(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeDeep($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    protected function saveToCache(array $config): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $content = "<?php\n\n// Compiled Config\n// Generated: " . date('Y-m-d H:i:s') . "\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($this->cachePath, $content);
    }

    protected function loadFromCache(): array
    {
        return require $this->cachePath;
    }

    protected function getDefaultBasePath(): string
    {
        if (function_exists('app')) {
            try {
                return app()->basePath();
            } catch (\Throwable $e) {
                // Fall through
            }
        }
        return dirname(__DIR__, 3);
    }
}
