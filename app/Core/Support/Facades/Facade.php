<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Support\Facades;

use App\Core\Application;
use RuntimeException;

/**
 * Facade Base Class
 *
 * Provides static access to services in the container
 */
abstract class Facade
{
    /**
     * The application instance
     *
     * @var Application|null
     */
    protected static ?Application $app = null;

    /**
     * The resolved object instances
     *
     * @var array<string, object>
     */
    protected static array $resolvedInstances = [];

    /**
     * Get the registered name of the component
     *
     * @throws RuntimeException
     *
     * @return string
     */
    abstract protected static function getFacadeAccessor(): string;

    /**
     * Set the application instance
     *
     * @param Application $app
     *
     * @return void
     */
    public static function setFacadeApplication(Application $app): void
    {
        static::$app = $app;
    }

    /**
     * Clear all resolved instances
     *
     * @return void
     */
    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstances = [];
    }

    /**
     * Get the application instance
     *
     * @throws RuntimeException
     *
     * @return Application
     */
    protected static function getFacadeApplication(): Application
    {
        if (static::$app === null) {
            static::$app = Application::getInstance();
        }

        return static::$app;
    }

    /**
     * Get the root object behind the facade
     *
     * @return mixed
     */
    protected static function getFacadeRoot(): mixed
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Resolve the facade root instance from the container
     *
     * @param string $name
     *
     * @return mixed
     */
    protected static function resolveFacadeInstance(string $name): mixed
    {
        if (isset(static::$resolvedInstances[$name])) {
            return static::$resolvedInstances[$name];
        }

        $app = static::getFacadeApplication();

        return static::$resolvedInstances[$name] = $app->make($name);
    }

    /**
     * Handle dynamic, static calls to the object
     *
     * @param string $method
     * @param array $args
     *
     * @throws RuntimeException
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            $accessor = static::getFacadeAccessor();
            if (function_exists('logger')) {
                logger()->error('Facade root not set', [
                    'facade' => static::class,
                    'accessor' => $accessor,
                    'method' => $method,
                ]);
            }
            throw new RuntimeException("A facade root has not been set for [{$accessor}].");
        }

        return $instance->$method(...$args);
    }
}
