<?php declare(strict_types=1);

namespace App\Modules\Admin;

use App\Modules\BaseModule;
use App\Modules\Traits\RegistersControllers;
use App\Modules\Traits\RegistersViewPaths;
use Psr\Container\ContainerInterface;

class AdminModule extends BaseModule
{
    use RegistersViewPaths, RegistersControllers;

    protected string $name = 'Admin';
    protected string $description = 'Administration interface for managing the application';
    protected string $version = '1.0.0';

    /**
     * Register admin module services and configurations
     */
    /**
     * Get the module's base directory path
     */
    private function getModulePath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }
    
    public function register(): void
    {
        $modulePath = $this->getModulePath();
        $this->registerViewPaths($this->container, $modulePath);
        $this->registerControllers($this->container, $modulePath);
        $this->registerRoutes();
        $this->registerNavigation();
    }

    /**
     * Register admin routes
     */
    private function registerRoutes(): void
    {
        $router = $this->container->get('router');
        
        // Admin routes (protected)
        $router->group('/admin', function ($group) {
            // Dashboard
            $group->get('', 'App\\Modules\\Admin\\Controllers\\DashboardController:index')
                ->setName('admin.dashboard');
                
            // User management
            $group->group('/users', function ($group) {
                $group->get('', 'App\\Modules\\Admin\\Controllers\\UserController:index')
                    ->setName('admin.users.index');
                $group->get('/create', 'App\\Modules\\Admin\\Controllers\\UserController:create')
                    ->setName('admin.users.create');
                $group->post('', 'App\\Modules\\Admin\\Controllers\\UserController:store')
                    ->setName('admin.users.store');
                $group->get('/{id}/edit', 'App\\Modules\\Admin\\Controllers\\UserController:edit')
                    ->setName('admin.users.edit');
                $group->put('/{id}', 'App\\Modules\\Admin\\Controllers\\UserController:update')
                    ->setName('admin.users.update');
                $group->post('/{id}/toggle-status', 'App\\Modules\\Admin\\Controllers\\UserController:toggleStatus')
                    ->setName('admin.users.toggle-status');
            });
        })->add('App\\Modules\\Admin\\Middleware\\AdminAuthMiddleware');
        
        // Authentication routes (public)
        $router->group('/admin', function ($group) {
            // Login
            $group->get('/login', 'App\\Modules\\Admin\\Controllers\\AuthController:showLoginForm')
                ->setName('admin.login');
            $group->post('/login', 'App\\Modules\\Admin\\Controllers\\AuthController:login')
                ->setName('admin.auth.login');
            $group->post('/logout', 'App\\Modules\\Admin\\Controllers\\AuthController:logout')
                ->setName('admin.logout');
                
            // Password reset
            $group->get('/forgot-password', 'App\\Modules\\Admin\\Controllers\\ForgotPasswordController:showLinkRequestForm')
                ->setName('admin.password.request');
            $group->post('/forgot-password', 'App\\Modules\\Admin\\Controllers\\ForgotPasswordController:sendResetLinkEmail')
                ->setName('admin.password.email');
            $group->get('/reset-password/{token}', 'App\\Modules\\Admin\\Controllers\\ResetPasswordController:showResetForm')
                ->setName('admin.password.reset');
            $group->post('/reset-password', 'App\\Modules\\Admin\\Controllers\\ResetPasswordController:reset')
                ->setName('admin.password.update');
        })->add('App\\Modules\\Admin\\Middleware\\GuestMiddleware');
    }

    /**
     * Register admin navigation
     */
    private function registerNavigation(): void
    {
        $navigation = $this->container->get('navigation');
        
        $navigation->addItem('admin', [
            'label' => 'Admin',
            'icon' => 'settings',
            'route' => 'admin.dashboard',
            'order' => 1000,
            'children' => [
                'dashboard' => [
                    'label' => 'Dashboard',
                    'route' => 'admin.dashboard',
                ],
                // Add more navigation items here
            ]
        ]);
    }

    /**
     * Boot the admin module
     */
    public function boot(): void
    {
        // Module bootstrapping logic here
    }

    /**
     * Check if the module is compatible with the current environment
     */
    public function isCompatible(): bool
    {
        return true;
    }
}
