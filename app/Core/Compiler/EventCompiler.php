<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Compiler;

/**
 * Event Compiler
 * 
 * Scans module event listeners and compiles them into a cached file.
 * 
 * Module events.php format:
 * return [
 *     UserRegistered::class => [
 *         SendWelcomeEmail::class,
 *         AddToNewsletter::class,
 *     ],
 *     OrderPlaced::class => [
 *         [NotifyAdmin::class, 'handle', 10], // with priority
 *     ],
 * ];
 */
class EventCompiler extends AbstractCompiler
{
    protected function getDefaultCachePath(): string
    {
        return $this->basePath . '/var/cache/events.php';
    }

    public function compile(): array
    {
        $listeners = [];

        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            $moduleEvents = $module->loadEvents();
            
            foreach ($moduleEvents as $event => $eventListeners) {
                if (!isset($listeners[$event])) {
                    $listeners[$event] = [];
                }
                
                foreach ((array) $eventListeners as $listener) {
                    $listeners[$event][] = $this->normalizeListener($listener, $module->name);
                }
            }
        }

        // Sort listeners by priority
        foreach ($listeners as $event => &$eventListeners) {
            usort($eventListeners, fn($a, $b) => $b['priority'] <=> $a['priority']);
        }

        $this->saveToCache($listeners, 'Compiled Event Listeners');

        return $listeners;
    }

    protected function normalizeListener(mixed $listener, string $moduleName): array
    {
        if (is_string($listener)) {
            return [
                'class' => $listener,
                'method' => 'handle',
                'priority' => 0,
                'module' => $moduleName,
            ];
        }

        if (is_array($listener)) {
            return [
                'class' => $listener[0],
                'method' => $listener[1] ?? 'handle',
                'priority' => $listener[2] ?? 0,
                'module' => $moduleName,
            ];
        }

        throw new \InvalidArgumentException("Invalid listener format in module: {$moduleName}");
    }

    public function getStats(): array
    {
        $listeners = $this->load();
        $stats = [];
        
        foreach ($listeners as $event => $eventListeners) {
            $stats[$event] = count($eventListeners);
        }
        
        return $stats;
    }
}
