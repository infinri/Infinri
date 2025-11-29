<?php

declare(strict_types=1);

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable
     *
     * @param string $key The environment variable key
     * @param mixed $default Default value if key doesn't exist
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

if (!function_exists('app')) {
    /**
     * Get the available container instance
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\App\Core\Application
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        if ($abstract === null) {
            return \App\Core\Application::getInstance();
        }

        return \App\Core\Application::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value
     *
     * @param array|string|null $key
     * @param mixed $default
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

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        
        return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('ensure_directory')) {
    /**
     * Ensure a directory exists, creating it if necessary
     *
     * @param string $path Directory path
     * @param int $mode Directory permissions
     * @return bool True if directory exists or was created
     */
    function ensure_directory(string $path, int $mode = 0755): bool
    {
        if (is_dir($path)) {
            return true;
        }
        return mkdir($path, $mode, true);
    }
}

if (!function_exists('save_php_array')) {
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

if (!function_exists('clear_directory')) {
    /**
     * Recursively clear a directory's contents
     */
    function clear_directory(string $dir, bool $preserve = false): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $items = new \FilesystemIterator($dir);
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                clear_directory($item->getPathname());
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        if (!$preserve) {
            @rmdir($dir);
        }
        return true;
    }
}

if (!function_exists('logger')) {
    /**
     * Get the logger instance or log a message
     *
     * @param string|null $message
     * @param array $context
     * @return \App\Core\Log\LogManager|void
     */
    function logger(?string $message = null, array $context = [])
    {
        $logger = app(\App\Core\Contracts\Log\LoggerInterface::class);

        if ($message === null) {
            return $logger;
        }

        $logger->info($message, $context);
    }
}

if (!function_exists('log_system')) {
    /**
     * Log a system event
     *
     * @param string $message The system message
     * @param array $context Additional context
     * @return void
     */
    function log_system(string $message, array $context = []): void
    {
        $logger = logger();
        
        if ($logger instanceof \App\Core\Log\LogManager) {
            $logger->system($message, $context);
        } else {
            $logger->info('[SYSTEM] ' . $message, $context);
        }
    }
}

if (!function_exists('db')) {
    /**
     * Get the database manager or a specific connection
     *
     * @param string|null $connection
     * @return \App\Core\Database\DatabaseManager|\App\Core\Contracts\Database\ConnectionInterface
     */
    function db(?string $connection = null)
    {
        $manager = app(\App\Core\Database\DatabaseManager::class);
        
        if ($connection === null) {
            return $manager;
        }
        
        return $manager->connection($connection);
    }
}

if (!function_exists('cache')) {
    /**
     * Get the cache manager or retrieve/store a value
     *
     * @param string|null $key
     * @param mixed $default
     * @return \App\Core\Cache\CacheManager|mixed
     */
    function cache(?string $key = null, mixed $default = null): mixed
    {
        static $manager = null;
        
        if ($manager === null) {
            $manager = new \App\Core\Cache\CacheManager([
                'default' => 'file',
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => app()->storagePath('cache'),
                    ],
                    'array' => [
                        'driver' => 'array',
                    ],
                ],
            ]);
        }
        
        if ($key === null) {
            return $manager;
        }
        
        return $manager->get($key, $default);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters (shorthand for sanitize)
     *
     * @param string $value
     * @return string
     */
    function e(string $value): string
    {
        return \App\Core\Security\Sanitizer::html($value);
    }
}

if (!function_exists('session')) {
    /**
     * Get the session manager or a session value
     *
     * @param string|null $key
     * @param mixed $default
     * @return \App\Core\Session\SessionManager|mixed
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        static $manager = null;
        
        if ($manager === null) {
            $manager = new \App\Core\Session\SessionManager();
        }
        
        if ($key === null) {
            return $manager;
        }
        
        return $manager->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token
     *
     * @return string
     */
    function csrf_token(): string
    {
        static $csrf = null;
        
        if ($csrf === null) {
            $csrf = new \App\Core\Security\Csrf();
        }
        
        return $csrf->token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Get the CSRF hidden input field
     *
     * @return string
     */
    function csrf_field(): string
    {
        static $csrf = null;
        
        if ($csrf === null) {
            $csrf = new \App\Core\Security\Csrf();
        }
        
        return $csrf->field();
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Verify a CSRF token
     *
     * @param string $token
     * @return bool
     */
    function csrf_verify(string $token): bool
    {
        static $csrf = null;
        
        if ($csrf === null) {
            $csrf = new \App\Core\Security\Csrf();
        }
        
        return $csrf->verify($token);
    }
}

if (!function_exists('rate_limit')) {
    /**
     * Check rate limit for a key
     *
     * @param string $key Unique identifier (e.g., IP address)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    function rate_limit(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool
    {
        static $limiter = null;
        
        if ($limiter === null) {
            $limiter = new \App\Core\Security\RateLimiter(
                new \App\Core\Cache\FileStore(
                    (function_exists('app') ? app()->storagePath('cache/rate_limits') : sys_get_temp_dir() . '/rate_limits')
                )
            );
        }
        
        return !$limiter->tooManyAttempts($key, $maxAttempts);
    }
}

if (!function_exists('rate_limit_hit')) {
    /**
     * Record a rate limit hit
     *
     * @param string $key
     * @param int $decaySeconds
     * @return int Current attempt count
     */
    function rate_limit_hit(string $key, int $decaySeconds = 300): int
    {
        static $limiter = null;
        
        if ($limiter === null) {
            $limiter = new \App\Core\Security\RateLimiter(
                new \App\Core\Cache\FileStore(
                    (function_exists('app') ? app()->storagePath('cache/rate_limits') : sys_get_temp_dir() . '/rate_limits')
                )
            );
        }
        
        return $limiter->hit($key, $decaySeconds);
    }
}

if (!function_exists('validator')) {
    /**
     * Create a new validator instance
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return \App\Core\Validation\Validator
     */
    function validator(array $data, array $rules = [], array $messages = []): \App\Core\Validation\Validator
    {
        return \App\Core\Validation\Validator::make($data, $rules, $messages);
    }
}

if (!function_exists('csp_nonce')) {
    /**
     * Get the CSP nonce for inline scripts/styles
     *
     * @return string|null
     */
    function csp_nonce(): ?string
    {
        // Check if nonce is already stored in app
        $app = app();
        
        if ($app->bound('csp.nonce')) {
            return $app->make('csp.nonce');
        }
        
        // Fallback to GLOBALS for backwards compatibility
        return $GLOBALS['cspNonce'] ?? null;
    }
}

if (!function_exists('csp_nonce_attr')) {
    /**
     * Get the CSP nonce as an HTML attribute string
     *
     * @return string Empty string if no nonce, or ' nonce="..."' 
     */
    function csp_nonce_attr(): string
    {
        $nonce = csp_nonce();
        return $nonce ? ' nonce="' . e($nonce) . '"' : '';
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the path to the app directory
     *
     * @param string $path
     * @return string
     */
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public directory
     *
     * @param string $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return base_path('pub' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}
