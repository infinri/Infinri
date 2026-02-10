<?php declare(strict_types=1);

/**
 * Infinri Framework - Core Helpers
 *
 * Core application helpers: environment, container, config, paths, logging, database, cache, session.
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 *
 * @see security_helpers.php - CSRF, rate limiting, CSP, safe redirects
 * @see cookie_helpers.php - Cookie management helpers
 * @see auth_helpers.php - Authorization (gate, can, cannot, authorize)
 */

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable
     *
     * @param string $key The environment variable key
     * @param mixed $default Default value if key doesn't exist
     *
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert string boolean values
        return match (strtolower($value)) {
            'true', '(true)', '1', 'yes', 'on' => true,
            'false', '(false)', '0', 'no', 'off' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (! function_exists('app')) {
    /**
     * Get the available container instance
     *
     * @param string|null $abstract
     * @param array $parameters
     *
     * @return mixed|\App\Core\Application
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if ($abstract === null) {
            return \App\Core\Application::getInstance();
        }

        return \App\Core\Application::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value
     *
     * @param array|string|null $key
     * @param mixed $default
     *
     * @return mixed|\App\Core\Contracts\Config\ConfigInterface
     */
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $config = app(\App\Core\Contracts\Config\ConfigInterface::class);

        if ($key === null) {
            return $config;
        }

        if (is_array($key)) {
            $config->set($key);

            return null;
        }

        return $config->get($key, $default);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install
     *
     * @param string $path
     *
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

        return $basePath . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (! function_exists('ensure_directory')) {
    /**
     * Ensure a directory exists, creating it if necessary
     *
     * @param string $path Directory path
     * @param int $mode Directory permissions
     *
     * @return bool True if directory exists or was created
     */
    function ensure_directory(string $path, int $mode = 0o755): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, $mode, true);
    }
}

if (! function_exists('save_php_array')) {
    /**
     * Save an array to a PHP file that can be required
     */
    function save_php_array(string $path, array $data, string $header = 'Cached Data', int $flags = 0): bool
    {
        ensure_directory(dirname($path));
        $content = "<?php\n\n// {$header}\n// Generated: " . date('Y-m-d H:i:s') . "\n\n"
            . "return " . var_export($data, true) . ";\n";

        return file_put_contents($path, $content, $flags) !== false;
    }
}

if (! function_exists('clear_directory')) {
    /**
     * Recursively clear a directory's contents
     *
     * @param string $dir Directory to clear
     * @param bool $preserve If true, keep the directory itself
     *
     * @return bool True if successful
     */
    function clear_directory(string $dir, bool $preserve = false): bool
    {
        if (! is_dir($dir)) {
            return false;
        }

        $success = true;
        $items = new \FilesystemIterator($dir);

        foreach ($items as $item) {
            $path = $item->getPathname();

            if ($item->isDir() && ! $item->isLink()) {
                if (! clear_directory($path, false)) {
                    $success = false;
                }
            } else {
                if (! unlink($path) && file_exists($path)) {
                    $success = false;
                }
            }
        }

        if (! $preserve && ! rmdir($dir) && is_dir($dir)) {
            $success = false;
        }

        return $success;
    }
}

if (! function_exists('logger')) {
    /**
     * Get the logger instance or log a message
     *
     * @param string|null $message Message to log (null to get logger instance)
     * @param array $context Log context
     *
     * @return \App\Core\Log\LogManager|null Logger instance when $message is null, null after logging
     */
    function logger(?string $message = null, array $context = []): ?\App\Core\Log\LogManager
    {
        $logger = app(\App\Core\Contracts\Log\LoggerInterface::class);

        if ($message === null) {
            return $logger;
        }

        $logger->info($message, $context);

        return null;
    }
}

if (! function_exists('safe_log')) {
    /**
     * Log a message only if the logger is available
     *
     * Centralizes the function_exists('logger') guard pattern
     * used throughout the codebase for code that may run before bootstrap.
     *
     * @param string $level Log level (info, warning, error, debug)
     * @param string $message Log message
     * @param array $context Log context
     */
    function safe_log(string $level, string $message, array $context = []): void
    {
        if (! function_exists('logger')) {
            return;
        }

        $log = logger();

        match ($level) {
            'emergency' => $log->emergency($message, $context),
            'alert' => $log->alert($message, $context),
            'critical' => $log->critical($message, $context),
            'error' => $log->error($message, $context),
            'warning' => $log->warning($message, $context),
            'notice' => $log->notice($message, $context),
            'info' => $log->info($message, $context),
            'debug' => $log->debug($message, $context),
            default => $log->info($message, $context),
        };
    }
}

if (! function_exists('log_system')) {
    /**
     * Log a system event
     *
     * @param string $message The system message
     * @param array $context Additional context
     *
     * @return void
     */
    function log_system(string $message, array $context = []): void
    {
        $logger = logger();

        if ($logger === null) {
            return;
        }

        $logger->system($message, $context);
    }
}

if (! function_exists('db')) {
    /**
     * Get the database manager or a specific connection
     *
     * @param string|null $connection
     *
     * @return \App\Core\Database\DatabaseManager|\App\Core\Contracts\Database\ConnectionInterface
     */
    function db(?string $connection = null): \App\Core\Database\DatabaseManager|\App\Core\Contracts\Database\ConnectionInterface
    {
        $manager = app(\App\Core\Database\DatabaseManager::class);

        if ($connection === null) {
            return $manager;
        }

        return $manager->connection($connection);
    }
}

if (! function_exists('cache')) {
    /**
     * Get the cache manager or retrieve/store a value
     *
     * @param string|null $key
     * @param mixed $default
     *
     * @return \App\Core\Cache\CacheManager|mixed
     */
    function cache(?string $key = null, mixed $default = null): mixed
    {
        $manager = app(\App\Core\Cache\CacheManager::class);

        if ($key === null) {
            return $manager;
        }

        return $manager->get($key, $default);
    }
}

if (! function_exists('session')) {
    /**
     * Get the session manager or a session value
     *
     * @param string|null $key
     * @param mixed $default
     *
     * @return \App\Core\Session\SessionManager|mixed
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        $manager = app(\App\Core\Session\SessionManager::class);

        if ($key === null) {
            return $manager;
        }

        return $manager->get($key, $default);
    }
}

if (! function_exists('validator')) {
    /**
     * Create a new validator instance
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return \App\Core\Validation\Validator
     */
    function validator(array $data, array $rules = [], array $messages = []): \App\Core\Validation\Validator
    {
        return \App\Core\Validation\Validator::make($data, $rules, $messages);
    }
}

if (! function_exists('app_path')) {
    /**
     * Get the path to the app directory
     *
     * @param string $path
     *
     * @return string
     */
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public directory
     *
     * @param string $path
     *
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return base_path('pub' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (! function_exists('meta')) {
    /**
     * Get the MetaManager instance
     *
     * @return \App\Core\View\Meta\MetaManager
     */
    function meta(): \App\Core\View\Meta\MetaManager
    {
        return app(\App\Core\View\Meta\MetaManager::class);
    }
}
