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

if (!function_exists('app_path')) {
    /**
     * Get the path to the app folder
     *
     * @param string $path
     * @return string
     */
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('var' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('logger')) {
    /**
     * Log a message to the logs
     *
     * @param string|null $message
     * @param array $context
     * @return \App\Core\Contracts\Log\LoggerInterface|void
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
