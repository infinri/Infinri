# Migration Guide: Upgrading to the New Event System

This guide will help you migrate your application to use the new event system for module lifecycle management.

## Table of Contents

- [Overview of Changes](#overview-of-changes)
- [Migration Steps](#migration-steps)
  - [1. Update Dependencies](#1-update-dependencies)
  - [2. Update Service Providers](#2-update-service-providers)
  - [3. Replace Custom Event Dispatching](#3-replace-custom-event-dispatching)
  - [4. Update Event Listeners](#4-update-event-listeners)
  - [5. Update Module Classes](#5-update-module-classes)
- [Backward Compatibility](#backward-compatibility)
- [Troubleshooting](#troubleshooting)

## Overview of Changes

The new event system introduces several key changes:

1. **PSR-14 Compliance**: The system now uses the PSR-14 standard for event dispatching.
2. **Standardized Event Classes**: New event classes for different module lifecycle stages.
3. **Dependency Injection**: Better integration with the DI container.
4. **Improved Error Handling**: Better error reporting through events.
5. **Type Safety**: Strict typing and better type hints.

## Migration Steps

### 1. Update Dependencies

Ensure you have the required PSR-14 implementation in your `composer.json`:

```bash
composer require psr/event-dispatcher
```

### 2. Update Service Providers

Update your service providers to use the new `EventServiceProvider`:

```php
// In your main service provider or bootstrap file
use App\Modules\Events\EventServiceProvider;

// Register the event service provider
$container->addServiceProvider(new EventServiceProvider());
```

### 3. Replace Custom Event Dispatching

If you were using a custom event dispatching system, replace it with the new PSR-14 implementation:

```php
// Old way (example)
$event = new CustomModuleEvent($module);
$dispatcher->dispatch('module.event', $event);

// New way
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Modules\Events\ModuleEvent;

$event = new ModuleEvent($module);
$dispatcher = $container->get(EventDispatcherInterface::class);
$dispatcher->dispatch($event);
```

### 4. Update Event Listeners

Update your event listeners to work with the new system:

```php
// Old way (example)
$dispatcher->addListener('module.boot', function (array $args) {
    $module = $args['module'];
    // Handle event
});

// New way
use App\Modules\Events\ModuleBootEvent;
use Psr\EventDispatcher\ListenerProviderInterface;

$listenerProvider = $container->get(ListenerProviderInterface::class);
$listenerProvider->addListener(ModuleBootEvent::class, function (ModuleBootEvent $event) {
    $module = $event->getModule();
    // Handle event
});
```

### 5. Update Module Classes

Update your module classes to work with the new event system:

```php
// Old way (example)
class MyModule implements ModuleInterface
{
    public function boot()
    {
        // Old boot code
    }
}

// New way
class MyModule extends \App\Modules\Module
{
    public function boot(): void
    {
        parent::boot(); // Important: Call parent to ensure events are dispatched
        // Your boot code
    }
}
```

## Backward Compatibility

The new system includes some backward compatibility layers, but you should still test your application thoroughly:

1. **Legacy Event Support**: Some legacy event dispatching may still work, but it's deprecated.
2. **Module Interface**: The `ModuleInterface` remains backward compatible.
3. **Error Handling**: The new system provides better error handling through events.

## Troubleshooting

### Events Not Being Dispatched

1. Ensure the `EventServiceProvider` is registered in your container.
2. Verify that your listeners are registered with the correct event class.
3. Check that the event propagation isn't being stopped by another listener.

### Listener Not Being Called

1. Verify the event class in your listener matches exactly.
2. Check that your listener is registered with sufficient priority.
3. Ensure the module is properly registered and booted.

### Dependency Injection Issues

If you're having issues with dependency injection in your listeners:

```php
// Instead of injecting dependencies directly in the listener
$listenerProvider->addListener(ModuleBootEvent::class, function (ModuleBootEvent $event) use ($container) {
    $service = $container->get(MyService::class);
    // ...
});

// Consider using a factory or service
class MyEventListener
{
    public function __construct(private MyService $service) {}
    
    public function __invoke(ModuleBootEvent $event): void
    {
        // Use $this->service
    }
}

// Register with container
$listenerProvider->addListener(
    ModuleBootEvent::class, 
    $container->get(MyEventListener::class)
);
```

## Example Migration

### Before Migration

```php
// Old module class
class OldModule implements ModuleInterface
{
    private $dispatcher;
    
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    public function boot()
    {
        $this->dispatcher->dispatch('module.booting', ['module' => $this]);
        
        try {
            // Boot logic
            $this->dispatcher->dispatch('module.booted', ['module' => $this]);
        } catch (\Exception $e) {
            $this->dispatcher->dispatch('module.boot_failed', [
                'module' => $this,
                'error' => $e
            ]);
            throw $e;
        }
    }
}

// Old listener
$dispatcher->addListener('module.booted', function ($args) {
    $module = $args['module'];
    $logger->info("Module booted: " . get_class($module));
});
```

### After Migration

```php
// New module class
class NewModule extends \App\Modules\Module
{
    // Inherits boot() method that dispatches events
    
    protected function doBoot(): void
    {
        // Your boot logic here
        // No need to dispatch events manually
    }
}

// New listener
use App\Modules\Events\ModuleBootEvent;
use Psr\EventDispatcher\ListenerProviderInterface;

$listenerProvider->addListener(ModuleBootEvent::class, function (ModuleBootEvent $event) {
    if ($event->isSuccessful()) {
        $logger->info(sprintf(
            'Module booted: %s', 
            get_class($event->getModule())
        ));
    }
});
```

## Performance Considerations

The new event system is designed to be efficient, but keep these points in mind:

1. **Listener Registration**: Register listeners during application bootstrap, not on every request.
2. **Event Objects**: Events are now objects, which might use slightly more memory than arrays.
3. **Propagation**: Stopping event propagation can improve performance by preventing unnecessary listener calls.

## Conclusion

This migration guide covers the essential steps to upgrade to the new event system. The new system provides better type safety, improved error handling, and follows PSR standards, making your code more maintainable and robust.

If you encounter any issues during migration, refer to the [documentation](./README.md) or file an issue in the issue tracker.
