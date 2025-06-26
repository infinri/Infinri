# Module Development Quick Start

This guide will walk you through creating a new module from scratch.

## 1. Create the Module Structure

Create the following directory structure:

```
app/Modules/HelloWorld/
├── Controllers/
├── Models/
├── Services/
├── Views/
│   └── hello.twig
└── HelloWorldModule.php
```

## 2. Create the Module Class

Edit `HelloWorldModule.php`:

```php
<?php declare(strict_types=1);

namespace App\Modules\HelloWorld;

use App\Modules\Module;
use App\Modules\HelloWorld\Controllers\HelloController;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;

class HelloWorldModule extends Module
{
    public function register(): void
    {
        $this->registerViewPaths();
        $this->registerControllers();
    }
    
    private function registerViewPaths(): void
    {
        $this->container->extend('view.paths', function (array $paths, ContainerInterface $c) {
            $paths[] = $this->getViewsPath();
            return $paths;
        });
    }
    
    private function registerControllers(): void
    {
        $this->container->set(HelloController::class, function(ContainerInterface $c) {
            return new HelloController(
                $c->get(Engine::class),
                $c
            );
        });
    }
    
    public function boot(): void
    {
        // Boot logic here
    }
    
    public function getBasePath(): string
    {
        return __DIR__;
    }
    
    public function getViewsPath(): string
    {
        return $this->getBasePath() . '/Views';
    }
    
    public function getNamespace(): string
    {
        return __NAMESPACE__;
    }
}
```

## 3. Create a Controller

Create `Controllers/HelloController.php`:

```php
<?php declare(strict_types=1);

namespace App\Modules\HelloWorld\Controllers;

use App\Modules\Core\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HelloController extends Controller
{
    public function hello(Request $request, Response $response, array $args): Response
    {
        $name = $args['name'] ?? 'World';
        return $this->render($response, 'hello.twig', ['name' => $name]);
    }
}
```

## 4. Create a View

Create `Views/hello.twig`:

```twig
<!DOCTYPE html>
<html>
<head>
    <title>Hello {{ name }}</title>
</head>
<body>
    <h1>Hello, {{ name }}!</h1>
</body>
</html>
```

## 5. Register Routes

Create `routes.php` in your module directory:

```php
<?php declare(strict_types=1);

use App\Modules\HelloWorld\Controllers\HelloController;
use Slim\Routing\RouteCollectorProxy;

return function (RouteCollectorProxy $group) {
    $group->get('/hello[/{name}]', [HelloController::class, 'hello'])
         ->setName('hello');
};
```

## 6. Register the Module

In your application bootstrap (e.g., `bootstrap/app.php`), register the module:

```php
$modules = [
    // ... other modules
    new App\Modules\HelloWorld\HelloWorldModule($container)
];

foreach ($modules as $module) {
    $module->register();
}

// After all modules are registered, boot them
foreach ($modules as $module) {
    $module->boot();
}
```

## 7. Test Your Module

Visit `/hello` in your browser or run:

```bash
curl http://localhost:8080/hello/YourName
```

You should see "Hello, YourName!" in your browser or terminal.

## Next Steps

- Add tests in `tests/Unit/Modules/HelloWorld/`
- Add configuration in `config/hello_world.php`
- Add translations in `resources/lang/`
- Add database migrations if needed
