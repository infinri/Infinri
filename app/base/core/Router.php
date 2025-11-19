<?php
declare(strict_types=1);
/**
 * Router
 *
 * Simple, secure HTTP router with method-based routing
 *
 * @package App\Core
 */

namespace App\Core;

final class Router
{
    private array $routes = [];
    private string $method;
    private string $path;
    private static ?string $modulesDir = null;
    private static ?string $layoutPath = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    }

    /**
     * Register route
     *
     * @param string $method HTTP method
     * @param string $path Route path
     * @param callable|string $handler Handler function or module name
     * @return self
     */
    private function route(string $method, string $path, $handler): self
    {
        $this->routes[$method][$path] = $handler;
        return $this;
    }

    /**
     * Register GET route
     *
     * @param string $path Route path
     * @param callable|string $handler Handler function or module name
     * @return self
     */
    public function get(string $path, $handler): self
    {
        return $this->route('GET', $path, $handler);
    }

    /**
     * Register POST route
     *
     * @param string $path Route path
     * @param callable|string $handler Handler function or module name
     * @return self
     */
    public function post(string $path, $handler): self
    {
        return $this->route('POST', $path, $handler);
    }

    /**
     * Dispatch route and execute handler
     *
     * @param string|callable $notFoundHandler 404 handler (module name or callable)
     * @return void
     */
    public function dispatch($notFoundHandler = 'error'): void
    {
        $handler = $this->routes[$this->method][$this->path] ?? null;

        if (! $handler) {
            http_response_code(404);
            $this->executeHandler($notFoundHandler);
            return;
        }

        $this->executeHandler($handler);
    }

    /**
     * Execute handler (callable or module string)
     *
     * @param callable|string $handler Handler to execute
     * @return void
     */
    private function executeHandler($handler): void
    {
        if (is_callable($handler)) {
            // Callable handlers render their own output (e.g., API endpoints, JSON responses)
            $handler();
        } else {
            // Module strings get automatic layout (head + content + footer)
            $this->renderModule($handler);
        }
    }

    /**
     * Render module with layout (head + content + footer)
     *
     * @param string $module Module name
     * @return void
     */
    private function renderModule(string $module): void
    {
        // Security: Validate module name
        if (! preg_match('/^[a-z0-9_-]+$/', $module)) {
            $this->renderError(500);
            return;
        }

        // Cache modules directory path (computed once per request)
        if (self::$modulesDir === null) {
            self::$modulesDir = dirname(__DIR__, 2) . '/modules';
        }
        $modulePath = self::$modulesDir . "/{$module}/index.php";

        if (! file_exists($modulePath)) {
            $this->renderError(500);
            return;
        }

        // Capture module content
        ob_start();
        require $modulePath;
        $content = ob_get_clean();

        // Cache layout path (computed once per request)
        if (self::$layoutPath === null) {
            self::$layoutPath = dirname(__DIR__) . '/view/layouts/default.php';
        }
        if (file_exists(self::$layoutPath)) {
            require self::$layoutPath;
        } else {
            // Fallback if layout doesn't exist
            echo $content;
        }
    }

    /**
     * Render error page using error module
     *
     * @param int $code HTTP status code (400, 404, 500)
     * @param string|null $type Error type ('400', '404', '500', 'maintenance')
     * @return void
     */
    public function renderError(int $code, string $type = null): void
    {
        // Set HTTP response code
        http_response_code($code);

        // Determine error type from code if not specified
        if ($type === null) {
            $type = match($code) {
                400 => '400',
                404 => '404',
                500, 503 => '500',
                default => '404'
            };
        }

        // Set error type for error module
        $errorType = $type;

        // Use cached modules directory path
        if (self::$modulesDir === null) {
            self::$modulesDir = dirname(__DIR__, 2) . '/modules';
        }
        $errorModulePath = self::$modulesDir . "/error/index.php";

        if (! file_exists($errorModulePath)) {
            // Fallback if error module doesn't exist
            exit("Error {$code}: " . http_response_code());
        }

        // Capture error module content
        ob_start();
        require $errorModulePath;
        $content = ob_get_clean();

        // Use cached layout path
        if (self::$layoutPath === null) {
            self::$layoutPath = dirname(__DIR__) . '/view/layouts/default.php';
        }
        if (file_exists(self::$layoutPath)) {
            require self::$layoutPath;
        } else {
            echo $content;
        }

        exit;
    }

    /**
     * Render maintenance page
     *
     * @return void
     */
    public function renderMaintenance(): void
    {
        http_response_code(503);
        $this->renderError(503, 'maintenance');
    }
}
