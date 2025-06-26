# Module Event System

The Module Event System provides a powerful way to hook into the module lifecycle and respond to various events that occur during the execution of your application. It implements the PSR-14 standard for event dispatching.

## Table of Contents

- [Overview](#overview)
- [Available Events](#available-events)
  - [ModuleEvent](#moduleevent)
  - [ModuleRegistrationEvent](#moduleregistrationevent)
  - [ModuleDependencyEvent](#moduledependencyevent)
  - [ModuleBootEvent](#modulebootevent)
  - [ModuleDiscoveryEvent](#modulediscoveryevent)
- [Usage](#usage)
  - [Listening to Events](#listening-to-events)
  - [Stopping Event Propagation](#stopping-event-propagation)
  - [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [Examples](#examples)

## Overview

The event system allows you to:

1. React to module lifecycle events (registration, boot, etc.)
2. Modify module behavior without changing the core code
3. Add cross-cutting concerns (logging, validation, etc.)
4. Implement plugin systems

All events implement `Psr\EventDispatcher\StoppableEventInterface`, allowing you to stop event propagation when needed.

## Available Events

### ModuleEvent

Base event class that all module events extend.

**Properties:**
- `ModuleInterface $module`: The module instance
- `array $arguments`: Additional event arguments
- `?\Throwable $error`: Error if one occurred
- `bool $propagationStopped`: Whether event propagation is stopped

**Methods:**
- `getModule(): ModuleInterface`: Get the module instance
- `getArgument(string $key, $default = null)`: Get an argument by key
- `getError(): ?\Throwable`: Get the error if one occurred
- `stopPropagation()`: Stop event propagation
- `isPropagationStopped(): bool`: Check if propagation is stopped

### ModuleRegistrationEvent

Dispatched when a module is being registered.

**Properties (in addition to ModuleEvent):**
- `bool $successful`: Whether the registration was successful

**Methods:**
- `isSuccessful(): bool`: Check if registration was successful

### ModuleDependencyEvent

Dispatched when module dependencies are being resolved.

**Properties (in addition to ModuleEvent):**
- `array $dependencies`: List of module dependencies
- `array $missingDependencies`: Missing dependencies
- `array $versionConflicts`: Version conflicts

**Methods:**
- `getDependencies(): array`: Get all dependencies
- `getMissingDependencies(): array`: Get missing dependencies
- `getVersionConflicts(): array`: Get version conflicts
- `hasErrors(): bool`: Check if there are any dependency errors

### ModuleBootEvent

Dispatched when a module is being booted.

**Properties (in addition to ModuleEvent):**
- `bool $successful`: Whether the boot was successful

**Methods:**
- `isSuccessful(): bool`: Check if boot was successful

### ModuleDiscoveryEvent

Dispatched when modules are being discovered.

**Properties:**
- `array $discoveredModules`: List of discovered module classes
- `bool $fromCache`: Whether the discovery was loaded from cache

**Methods:**
- `getDiscoveredModules(): array`: Get discovered module classes
- `isFromCache(): bool`: Check if discovery was from cache

## Usage

### Listening to Events

To listen to events, you need to register an event listener with the `ModuleListenerProvider`:

```php
use App\Modules\Events\ModuleBootEvent;
use Psr\EventDispatcher\ListenerProviderInterface;

// In your service provider or bootstrap code
$listenerProvider = $container->get(ListenerProviderInterface::class);

// Add a listener for ModuleBootEvent
$listenerProvider->addListener(ModuleBootEvent::class, function (ModuleBootEvent $event) {
    $module = $event->getModule();
    if ($event->isSuccessful()) {
        // Module was successfully booted
        $logger->info(sprintf('Module %s was booted', get_class($module)));
    } else {
        // Handle boot failure
        $error = $event->getError();
        $logger->error(sprintf('Failed to boot module %s: %s', 
            get_class($module), 
            $error ? $error->getMessage() : 'Unknown error'
        ));
    }
});
```

### Stopping Event Propagation

You can stop event propagation to prevent other listeners from being called:

```php
use App\Modules\Events\ModuleRegistrationEvent;

$listenerProvider->addListener(ModuleRegistrationEvent::class, function (ModuleRegistrationEvent $event) {
    $module = $event->getModule();
    
    // Example: Prevent registration of modules from a specific namespace
    if (str_starts_with(get_class($module), 'Vendor\\Forbidden\\')) {
        $event->stopPropagation();
        $event->setError(new \RuntimeException('Module from forbidden namespace'));
    }
});
```

### Error Handling

Events can include error information when something goes wrong:

```php
use App\Modules\Events\ModuleBootEvent;

$listenerProvider->addListener(ModuleBootEvent::class, function (ModuleBootEvent $event) {
    if (($error = $event->getError()) !== null) {
        // Handle the error
        $this->logger->error('Module boot error: ' . $error->getMessage(), [
            'module' => get_class($event->getModule()),
            'exception' => $error
        ]);
    }
});
```

## Best Practices

1. **Keep listeners small and focused**: Each listener should do one thing well.
2. **Handle errors gracefully**: Always check for errors in your listeners.
3. **Be careful with propagation**: Only stop propagation when absolutely necessary.
4. **Use dependency injection**: Inject dependencies into your listeners rather than using global state.
5. **Document your events**: Clearly document what each event is for and when it's dispatched.

## Examples

### Logging Module Lifecycle

```php
use App\Modules\Events\{
    ModuleRegistrationEvent,
    ModuleDependencyEvent,
    ModuleBootEvent
};
use Psr\Log\LoggerInterface;

class ModuleLifecycleLogger
{
    public function __construct(
        private ListenerProviderInterface $listenerProvider,
        private LoggerInterface $logger
    ) {
        $this->registerListeners();
    }
    
    private function registerListeners(): void
    {
        $this->listenerProvider->addListener(
            ModuleRegistrationEvent::class, 
            [$this, 'onModuleRegistration']
        );
        
        $this->listenerProvider->addListener(
            ModuleDependencyEvent::class, 
            [$this, 'onDependencyResolution']
        );
        
        $this->listenerProvider->addListener(
            ModuleBootEvent::class, 
            [$this, 'onModuleBoot']
        );
    }
    
    public function onModuleRegistration(ModuleRegistrationEvent $event): void
    {
        $module = $event->getModule();
        $level = $event->isSuccessful() ? 'info' : 'error';
        
        $this->logger->$level(sprintf(
            'Module %s %s',
            get_class($module),
            $event->isSuccessful() ? 'registered successfully' : 'registration failed: ' . $event->getError()?->getMessage()
        ));
    }
    
    public function onDependencyResolution(ModuleDependencyEvent $event): void
    {
        if ($event->hasErrors()) {
            $this->logger->error('Dependency resolution errors', [
                'missing' => $event->getMissingDependencies(),
                'conflicts' => $event->getVersionConflicts()
            ]);
        }
    }
    
    public function onModuleBoot(ModuleBootEvent $event): void
    {
        $module = $event->getModule();
        $level = $event->isSuccessful() ? 'info' : 'error';
        
        $this->logger->$level(sprintf(
            'Module %s %s',
            get_class($module),
            $event->isSuccessful() ? 'booted successfully' : 'failed to boot: ' . $event->getError()?->getMessage()
        ));
    }
}
```

### Module Feature Flags

```php
use App\Modules\Events\ModuleRegistrationEvent;

class FeatureFlagListener
{
    public function __construct(
        private ListenerProviderInterface $listenerProvider,
        private FeatureFlagService $featureFlags
    ) {
        $this->listenerProvider->addListener(
            ModuleRegistrationEvent::class,
            [$this, 'onModuleRegistration']
        );
    }
    
    public function onModuleRegistration(ModuleRegistrationEvent $event): void
    {
        $module = $event->getModule();
        $moduleClass = get_class($module);
        
        // Check if module is enabled via feature flag
        $featureName = 'module.' . str_replace('\\', '.', strtolower($moduleClass));
        
        if (!$this->featureFlags->isEnabled($featureName)) {
            $event->stopPropagation();
            $event->setError(new \RuntimeException("Module $moduleClass is disabled by feature flag"));
        }
    }
}
```

### Module Configuration Validation

```php
use App\Modules\Events\ModuleBootEvent;

class ModuleConfigValidator
{
    public function __construct(
        private ListenerProviderInterface $listenerProvider,
        private ConfigValidator $validator
    ) {
        $this->listenerProvider->addListener(
            ModuleBootEvent::class,
            [$this, 'validateModuleConfig'],
            // Run early with high priority
            100
        );
    }
    
    public function validateModuleConfig(ModuleBootEvent $event): void
    {
        if ($event->isPropagationStopped()) {
            return;
        }
        
        $module = $event->getModule();
        
        try {
            // Validate module configuration
            $config = $module->getConfig() ?? [];
            $this->validator->validate($config, $this->getValidationRules($module));
            
        } catch (ValidationException $e) {
            $event->stopPropagation();
            $event->setError(new \RuntimeException(
                sprintf('Invalid configuration for module %s: %s', 
                    get_class($module),
                    $e->getMessage()
                ),
                0,
                $e
            ));
        }
    }
    
    private function getValidationRules(ModuleInterface $module): array
    {
        // Return validation rules based on module class
        $rules = [
            // Default rules
            'enabled' => 'boolean',
            'priority' => 'integer|min:0|max:100'
        ];
        
        // Add module-specific rules
        if ($module instanceof DatabaseModule) {
            $rules += [
                'connection' => 'required|string',
                'migrations' => 'boolean'
            ];
        }
        
        return $rules;
    }
}
```

### Performance Monitoring

```php
use App\Modules\Events\{
    ModuleBootEvent,
    ModuleRegistrationEvent
};

class ModulePerformanceMonitor
{
    private array $timers = [];
    
    public function __construct(
        private ListenerProviderInterface $listenerProvider,
        private MetricsCollector $metrics
    ) {
        // Register for module registration start/end
        $this->listenerProvider->addListener(
            ModuleRegistrationEvent::class,
            [$this, 'onModuleRegistration'],
            // High priority to run first
            100
        );
        
        // Register for module boot start/end
        $this->listenerProvider->addListener(
            ModuleBootEvent::class,
            [$this, 'onModuleBoot'],
            // High priority to run first
            100
        );
    }
    
    public function onModuleRegistration(ModuleRegistrationEvent $event): void
    {
        $moduleClass = get_class($event->getModule());
        $this->timers['register.' . $moduleClass] = microtime(true);
        
        // Register a post-listener with low priority to run last
        $this->listenerProvider->addListener(
            ModuleRegistrationEvent::class,
            function (ModuleRegistrationEvent $e) use ($moduleClass) {
                if ($e->getModule() instanceof $moduleClass) {
                    $duration = microtime(true) - $this->timers['register.' . $moduleClass];
                    $this->metrics->histogram('module.registration.duration', $duration, [
                        'module' => $moduleClass,
                        'success' => $e->isSuccessful()
                    ]);
                    unset($this->timers['register.' . $moduleClass]);
                }
            },
            // Low priority to run last
            -100
        );
    }
    
    public function onModuleBoot(ModuleBootEvent $event): void
    {
        if ($event->isPropagationStopped()) {
            return;
        }
        
        $moduleClass = get_class($event->getModule());
        $this->timers['boot.' . $moduleClass] = microtime(true);
        
        // Register a post-listener with low priority to run last
        $this->listenerProvider->addListener(
            ModuleBootEvent::class,
            function (ModuleBootEvent $e) use ($moduleClass) {
                if ($e->getModule() instanceof $moduleClass) {
                    $duration = microtime(true) - $this->timers['boot.' . $moduleClass];
                    $this->metrics->histogram('module.boot.duration', $duration, [
                        'module' => $moduleClass,
                        'success' => $e->isSuccessful()
                    ]);
                    unset($this->timers['boot.' . $moduleClass]);
                }
            },
            // Low priority to run last
            -100
        );
    }
}
```

This documentation provides a comprehensive guide to using the module event system, including detailed information about available events, usage examples, and best practices. The examples cover common use cases like logging, feature flags, configuration validation, and performance monitoring.
