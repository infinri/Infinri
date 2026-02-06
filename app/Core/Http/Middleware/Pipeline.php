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
namespace App\Core\Http\Middleware;

use App\Core\Application;
use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * Middleware Pipeline
 *
 * Executes middleware in order, allowing each to process the request
 * before passing to the next, and process the response on the way back
 */
class Pipeline
{
    /**
     * The application instance
     */
    protected Application $app;

    /**
     * The object being passed through the pipeline
     */
    protected RequestInterface $passable;

    /**
     * The array of middleware pipes
     *
     * @var array<int, string|MiddlewareInterface|Closure>
     */
    protected array $pipes = [];

    /**
     * The method to call on each middleware
     */
    protected string $method = 'handle';

    /**
     * Create a new pipeline instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Set the object being sent through the pipeline
     */
    public function send(RequestInterface $passable): static
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of middleware pipes
     */
    public function through(array $pipes): static
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Set the method to call on each middleware
     */
    public function via(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback
     */
    public function then(Closure $destination): ResponseInterface
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Run the pipeline and return the result
     */
    public function thenReturn(): ResponseInterface
    {
        return $this->then(function (RequestInterface $request) {
            return new \App\Core\Http\Response('');
        });
    }

    /**
     * Get a Closure that represents a slice of the middleware onion
     */
    protected function carry(): Closure
    {
        return function (Closure $stack, mixed $pipe): Closure {
            return function (RequestInterface $passable) use ($stack, $pipe): ResponseInterface {
                // Resolve the middleware
                $middleware = $this->resolvePipe($pipe);

                if ($middleware instanceof Closure) {
                    return $middleware($passable, $stack);
                }

                if ($middleware instanceof MiddlewareInterface) {
                    return $middleware->handle($passable, $stack);
                }

                // Call custom method if specified
                if (method_exists($middleware, $this->method)) {
                    return $middleware->{$this->method}($passable, $stack);
                }

                $error = sprintf('Middleware %s does not have a %s method', get_class($middleware), $this->method);
                if (function_exists('logger')) {
                    logger()->error('Middleware execution failed', [
                        'middleware' => get_class($middleware),
                        'method' => $this->method,
                        'error' => $error,
                    ]);
                }
                throw new RuntimeException($error);
            };
        };
    }

    /**
     * Prepare the final destination callback
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function (RequestInterface $passable) use ($destination): ResponseInterface {
            return $destination($passable);
        };
    }

    /**
     * Resolve a middleware pipe
     *
     * @return MiddlewareInterface|Closure|object
     */
    protected function resolvePipe(mixed $pipe): mixed
    {
        if ($pipe instanceof Closure) {
            return $pipe;
        }

        if ($pipe instanceof MiddlewareInterface) {
            return $pipe;
        }

        if (is_string($pipe)) {
            // Check for parameters (middleware:param1,param2)
            [$class, $parameters] = $this->parsePipeString($pipe);

            // Resolve from container
            $instance = $this->app->make($class);

            // If parameters were provided, wrap in a closure
            if (! empty($parameters)) {
                return function (RequestInterface $request, Closure $next) use ($instance, $parameters): ResponseInterface {
                    return $instance->handle($request, $next, ...$parameters);
                };
            }

            return $instance;
        }

        if (is_object($pipe)) {
            return $pipe;
        }

        $error = 'Invalid middleware pipe type: ' . gettype($pipe);
        if (function_exists('logger')) {
            logger()->error('Invalid middleware configuration', [
                'pipe_type' => gettype($pipe),
                'error' => $error,
            ]);
        }
        throw new InvalidArgumentException($error);
    }

    /**
     * Parse a middleware string for class and parameters
     *
     * @return array{0: string, 1: array}
     */
    protected function parsePipeString(string $pipe): array
    {
        [$class, $parameters] = array_pad(explode(':', $pipe, 2), 2, '');

        $parameters = $parameters !== '' ? explode(',', $parameters) : [];

        return [$class, $parameters];
    }
}
