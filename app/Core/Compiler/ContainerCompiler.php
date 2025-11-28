<?php

declare(strict_types=1);

namespace App\Core\Compiler;

/**
 * Container Compiler
 * 
 * Compiles service provider bindings for faster container resolution.
 */
class ContainerCompiler extends AbstractCompiler
{
    protected function getDefaultCachePath(): string
    {
        return $this->basePath . '/var/cache/container.php';
    }

    public function compile(): array
    {
        $compiled = [
            'providers' => [],
            'deferred' => [],
            'provides' => [],
        ];

        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            foreach ($module->providers as $providerClass) {
                if (!class_exists($providerClass)) {
                    continue;
                }

                $compiled['providers'][] = [
                    'class' => $providerClass,
                    'module' => $module->name,
                ];

                try {
                    $reflection = new \ReflectionClass($providerClass);
                    
                    if ($reflection->hasMethod('isDeferred')) {
                        $instance = $reflection->newInstanceWithoutConstructor();
                        if ($instance->isDeferred()) {
                            $compiled['deferred'][] = $providerClass;
                            
                            if ($reflection->hasMethod('provides')) {
                                $provides = $instance->provides();
                                foreach ($provides as $service) {
                                    $compiled['provides'][$service] = $providerClass;
                                }
                            }
                        }
                    }
                } catch (\Throwable) {
                    // Skip if can't reflect
                }
            }
        }

        $this->saveToCache($compiled, 'Compiled Container');

        return $compiled;
    }

    public function getStats(): array
    {
        $data = $this->load();
        
        return [
            'total_providers' => count($data['providers']),
            'deferred_providers' => count($data['deferred']),
            'deferred_services' => count($data['provides']),
        ];
    }
}
