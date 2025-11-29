<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Container\ServiceProvider;
use App\Core\Redis\RedisManager;
use App\Core\Cache\CacheManager;
use App\Core\Cache\RedisStore;
use App\Core\Queue\RedisQueue;
use App\Core\Queue\QueueWorker;
use App\Core\Session\RedisSessionHandler;
use App\Core\Support\CircuitBreaker;
use App\Core\Contracts\Queue\QueueInterface;
use App\Core\Contracts\Cache\CacheInterface;

/**
 * Redis Service Provider
 * 
 * Registers Redis-based services when Redis is configured as the driver.
 */
class RedisServiceProvider extends ServiceProvider
{
    /**
     * Services provided by this provider
     */
    protected array $provides = [
        RedisManager::class,
        QueueInterface::class,
        CircuitBreaker::class,
    ];

    /**
     * Register services
     */
    public function register(): void
    {
        // Register Redis Manager as singleton
        $this->app->singleton(RedisManager::class, function () {
            $prefix = env('REDIS_PREFIX', 'infinri:');
            $host = env('REDIS_HOST', '127.0.0.1');
            $port = (int) env('REDIS_PORT', 6379);
            $password = env('REDIS_PASSWORD');
            $database = (int) env('REDIS_DB', 0);

            return new RedisManager([
                'default' => 'default',
                'connections' => [
                    'default' => [
                        'host' => $host,
                        'port' => $port,
                        'password' => $password,
                        'database' => $database,
                        'prefix' => $prefix,
                    ],
                    // Cache/queue/session use same connection but different logical DBs if needed
                    'cache' => [
                        'host' => $host,
                        'port' => $port,
                        'password' => $password,
                        'database' => (int) env('REDIS_CACHE_DB', $database),
                        'prefix' => $prefix,
                    ],
                    'queue' => [
                        'host' => $host,
                        'port' => $port,
                        'password' => $password,
                        'database' => (int) env('REDIS_QUEUE_DB', $database),
                        'prefix' => $prefix,
                    ],
                    'session' => [
                        'host' => $host,
                        'port' => $port,
                        'password' => $password,
                        'database' => (int) env('REDIS_SESSION_DB', $database),
                        'prefix' => $prefix,
                    ],
                ],
            ]);
        });

        // Register Queue based on configuration
        $this->app->singleton(QueueInterface::class, function () {
            $driver = env('QUEUE_CONNECTION', 'sync');

            if ($driver === 'redis') {
                $redis = $this->app->make(RedisManager::class);
                return new RedisQueue(
                    $redis,
                    'queue',
                    env('QUEUE_DEFAULT', 'default'),
                    'queue:',
                    (int) env('QUEUE_RETRY_AFTER', 3),
                    (int) env('QUEUE_RETRY_DELAY', 60)
                );
            }

            // Fall back to sync queue
            return new \App\Core\Queue\SyncQueue();
        });

        // Register Queue Worker
        $this->app->bind(QueueWorker::class, function () {
            return new QueueWorker(
                $this->app->make(QueueInterface::class),
                logger(),
                [
                    'sleep' => (int) env('QUEUE_SLEEP', 3),
                    'tries' => (int) env('QUEUE_TRIES', 3),
                    'timeout' => (int) env('QUEUE_TIMEOUT', 60),
                    'memory_limit' => (int) env('QUEUE_MEMORY', 128),
                ]
            );
        });

        // Register Circuit Breaker
        $this->app->singleton(CircuitBreaker::class, function () {
            $cache = $this->app->make(CacheInterface::class);
            return new CircuitBreaker($cache, [
                // Service-specific options can be configured here
                'brevo' => [
                    'failure_threshold' => 3,
                    'timeout' => 30,
                ],
                'external_api' => [
                    'failure_threshold' => 5,
                    'timeout' => 60,
                ],
            ]);
        });

        // Register Redis Session Handler
        $this->app->singleton(RedisSessionHandler::class, function () {
            $redis = $this->app->make(RedisManager::class);
            return new RedisSessionHandler(
                $redis,
                'session',
                'session:',
                (int) env('SESSION_LIFETIME', 7200)
            );
        });
    }

    /**
     * Boot services
     */
    public function boot(): void
    {
        // Register Redis session handler if configured
        if (env('SESSION_DRIVER') === 'redis') {
            $handler = $this->app->make(RedisSessionHandler::class);
            $handler->register();
        }

        // Inject Redis manager into CacheManager if using Redis cache
        if (env('CACHE_DRIVER') === 'redis') {
            $cache = $this->app->make(CacheInterface::class);
            if ($cache instanceof CacheManager) {
                $redis = $this->app->make(RedisManager::class);
                $cache->setRedisManager($redis);
            }
        }
    }
}
