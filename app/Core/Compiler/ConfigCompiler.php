<?php

declare(strict_types=1);

namespace App\Core\Compiler;

/**
 * Config Compiler
 *
 * Merges module configs into a single cached config file.
 */
class ConfigCompiler extends AbstractCompiler
{
    protected function getDefaultCachePath(): string
    {
        return $this->basePath . '/var/cache/config.php';
    }

    public function compile(): array
    {
        $config = [];
        $config = $this->loadModuleConfigs($config);
        $config = $this->loadAppConfig($config);
        $this->saveToCache($config, 'Compiled Config');
        return $config;
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
}
