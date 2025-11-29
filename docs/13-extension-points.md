# Extension Points Guide

This document describes how to extend the Infinri framework through its various extension mechanisms.

## Table of Contents

1. [Modules](#modules)
2. [Service Providers](#service-providers)
3. [Middleware](#middleware)
4. [Events](#events)
5. [Console Commands](#console-commands)
6. [Cache Drivers](#cache-drivers)
7. [Queue Drivers](#queue-drivers)

---

## Modules

Modules are the primary extension mechanism. Each module is a self-contained unit with its own routes, views, controllers, and services.

### Creating a Module

1. Create a directory in `app/modules/your_module/`
2. Add a `module.php` configuration file:

```php
<?php
// app/modules/your_module/module.php

return [
    'name' => 'your_module',
    'version' => '1.0.0',
    'description' => 'Description of your module',
    'enabled' => true,
    
    // Lazy loading (optional) - module loads only when needed
    'lazy' => false,
    
    // Route prefixes for lazy loading trigger
    'route_prefixes' => ['/your-module'],
    
    // Dependencies on other modules
    'dependencies' => [],
    
    // Service providers to register
    'providers' => [
        \App\Modules\YourModule\YourModuleServiceProvider::class,
    ],
    
    // Console commands
    'commands' => [],
    
    // Optional file paths
    'routes' => 'routes.php',
    'config' => 'config.php',
    'events' => 'events.php',
];
```

### Lazy Loading

For modules that aren't needed on every request (e.g., admin panels), enable lazy loading:

```php
return [
    'name' => 'admin',
    'lazy' => true,
    'route_prefixes' => ['/admin'],
    // ...
];
```

The module will only load when a request matches `/admin/*`.

### Module Structure

```
app/modules/your_module/
├── module.php           # Module configuration
├── routes.php           # Route definitions (optional)
├── config.php           # Module config (optional)
├── events.php           # Event subscriptions (optional)
├── YourModuleServiceProvider.php
├── Controllers/
│   └── YourController.php
├── Models/
│   └── YourModel.php
└── view/
    └── frontend/
        └── templates/
            └── page.html.twig
```

---

## Service Providers

Service providers register bindings and bootstrap services.

### Creating a Service Provider

```php
<?php

namespace App\Modules\YourModule;

use App\Core\Container\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    /**
     * Services provided (for deferred providers)
     */
    protected array $provides = [
        YourService::class,
    ];

    /**
     * Register services in the container
     */
    public function register(): void
    {
        // Bind interface to implementation
        $this->app->bind(YourInterface::class, YourImplementation::class);
        
        // Register singleton
        $this->app->singleton(YourService::class, function ($app) {
            return new YourService(
                $app->make(DependencyClass::class)
            );
        });
    }

    /**
     * Bootstrap services (called after all providers registered)
     */
    public function boot(): void
    {
        // Register event listeners
        // Add middleware
        // Publish assets
    }
}
```

### Deferred Providers

For services only needed occasionally, use deferred loading:

```php
class YourServiceProvider extends ServiceProvider
{
    protected bool $defer = true;
    
    protected array $provides = [
        YourService::class,
    ];
    
    // Provider only loads when YourService is requested
}
```

---

## Middleware

Middleware intercepts HTTP requests/responses.

### Creating Middleware

```php
<?php

namespace App\Modules\YourModule\Middleware;

use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use Closure;

class YourMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        // Before request processing
        if ($this->shouldBlock($request)) {
            return new Response('Forbidden', 403);
        }
        
        // Pass to next middleware
        $response = $next($request);
        
        // After response (can modify response)
        return $response->withHeader('X-Custom-Header', 'value');
    }
    
    protected function shouldBlock(RequestInterface $request): bool
    {
        // Your logic here
        return false;
    }
}
```

### Registering Middleware

In your service provider's `boot()` method:

```php
public function boot(): void
{
    $kernel = $this->app->make(KernelInterface::class);
    
    // Global middleware (runs on all requests)
    $kernel->pushMiddleware(YourMiddleware::class);
    
    // Route middleware (use in routes)
    $kernel->addRouteMiddleware('your_alias', YourMiddleware::class);
    
    // Middleware group
    $kernel->addMiddlewareGroup('your_group', [
        FirstMiddleware::class,
        SecondMiddleware::class,
    ]);
}
```

---

## Events

The event system enables decoupled communication between components.

### Dispatching Events

```php
use App\Core\Contracts\Events\EventDispatcherInterface;

class YourService
{
    public function __construct(
        private EventDispatcherInterface $events
    ) {}
    
    public function doSomething(): void
    {
        // ... perform action ...
        
        $this->events->dispatch(new SomethingHappened($data));
    }
}
```

### Creating Events

```php
<?php

namespace App\Modules\YourModule\Events;

use App\Core\Events\Event;

class SomethingHappened extends Event
{
    public function __construct(
        public readonly array $data
    ) {}
}
```

### Subscribing to Events

**Option 1: In events.php**

```php
<?php
// app/modules/your_module/events.php

return [
    \App\Core\Events\RequestReceived::class => [
        [\App\Modules\YourModule\Listeners\LogRequest::class, 'handle'],
    ],
    
    \App\Modules\OtherModule\Events\UserCreated::class => [
        [\App\Modules\YourModule\Listeners\SendWelcomeEmail::class, 'handle'],
    ],
];
```

**Option 2: In Service Provider**

```php
public function boot(): void
{
    $events = $this->app->make(EventDispatcherInterface::class);
    
    $events->listen(
        SomethingHappened::class,
        fn($event) => $this->handleEvent($event)
    );
}
```

### Creating Listeners

```php
<?php

namespace App\Modules\YourModule\Listeners;

class SendWelcomeEmail
{
    public function handle(UserCreated $event): void
    {
        // Send email to $event->user
    }
}
```

---

## Console Commands

Add CLI commands for maintenance, jobs, or utilities.

### Creating a Command

```php
<?php

namespace App\Modules\YourModule\Commands;

use App\Core\Console\Command;

class YourCommand extends Command
{
    protected string $name = 'your:command';
    protected string $description = 'Description of what this command does';

    protected function configure(): void
    {
        $this->addArgument('name', 'The name argument');
        $this->addOption('force', 'f', 'Force the operation');
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $force = $this->option('force');
        
        $this->info("Processing: {$name}");
        
        if ($this->confirm('Continue?')) {
            // Do work
            $this->success('Done!');
        }
        
        return 0; // Exit code
    }
}
```

### Registering Commands

In your `module.php`:

```php
return [
    'commands' => [
        \App\Modules\YourModule\Commands\YourCommand::class,
    ],
];
```

Run with: `php bin/console your:command argument --force`

---

## Cache Drivers

Extend the caching system with custom drivers.

### Creating a Cache Driver

```php
<?php

namespace App\Modules\YourModule\Cache;

use App\Core\Contracts\Cache\CacheInterface;

class MemcachedStore implements CacheInterface
{
    private \Memcached $memcached;
    
    public function __construct(array $servers)
    {
        $this->memcached = new \Memcached();
        $this->memcached->addServers($servers);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->memcached->get($key);
        return $value !== false ? $value : $default;
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->memcached->set($key, $value, $ttl ?? 0);
    }

    public function forget(string $key): bool
    {
        return $this->memcached->delete($key);
    }

    public function has(string $key): bool
    {
        $this->memcached->get($key);
        return $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND;
    }

    public function flush(): bool
    {
        return $this->memcached->flush();
    }

    // Implement remaining interface methods...
}
```

### Registering the Driver

```php
public function register(): void
{
    $this->app->singleton('cache.memcached', function () {
        return new MemcachedStore([
            ['localhost', 11211],
        ]);
    });
}
```

---

## Queue Drivers

Extend the queue system for different backends.

### Creating a Queue Driver

```php
<?php

namespace App\Modules\YourModule\Queue;

use App\Core\Contracts\Queue\QueueInterface;
use App\Core\Contracts\Queue\JobInterface;

class DatabaseQueue implements QueueInterface
{
    public function push(string|object $job, array $data = [], ?string $queue = null): string|int
    {
        // Insert job into database
        return $jobId;
    }

    public function later(int $delay, string|object $job, array $data = [], ?string $queue = null): string|int
    {
        // Insert with available_at timestamp
    }

    public function pop(?string $queue = null): ?JobInterface
    {
        // Fetch and reserve next available job
    }

    public function size(?string $queue = null): int
    {
        // Count pending jobs
    }

    public function clear(?string $queue = null): bool
    {
        // Delete all jobs
    }
}
```

---

## Best Practices

### 1. Use Interfaces

Always depend on interfaces, not concrete implementations:

```php
// Good
public function __construct(CacheInterface $cache) {}

// Avoid
public function __construct(RedisStore $cache) {}
```

### 2. Keep Modules Independent

Modules should not directly reference other modules' internal classes. Use events or shared interfaces instead.

### 3. Document Extension Points

When creating extension points in your module, document them clearly:

```php
/**
 * Event dispatched when a widget is created.
 * 
 * Listeners can modify the widget data before it's saved.
 * 
 * @event
 */
class WidgetCreating extends Event
{
    public function __construct(
        public array &$data // Passed by reference for modification
    ) {}
}
```

### 4. Version Your APIs

If your module exposes APIs for other modules, version them:

```php
namespace App\Modules\YourModule\Api\V1;
```

### 5. Test Your Extensions

Create tests for your extensions in `tests/Unit/Modules/YourModule/`:

```php
test('middleware blocks unauthorized requests', function () {
    $middleware = new YourMiddleware();
    $request = createMockRequest(['Authorization' => null]);
    
    $response = $middleware->handle($request, fn() => new Response());
    
    expect($response->getStatusCode())->toBe(403);
});
```
