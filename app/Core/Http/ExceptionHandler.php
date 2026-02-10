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
namespace App\Core\Http;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Error\Handler as ErrorHandler;
use App\Core\Routing\Exceptions\MethodNotAllowedException;
use App\Core\Routing\Exceptions\RouteNotFoundException;
use Throwable;

/**
 * HTTP Exception Handler
 *
 * Converts exceptions to appropriate HTTP responses.
 * Delegates reporting to Core\Error\Handler.
 */
class ExceptionHandler
{
    public function __construct(
        protected bool $debug = false,
        protected ?ErrorHandler $errorHandler = null
    ) {
    }

    /**
     * Handle an exception and return a response
     */
    public function handle(RequestInterface $request, Throwable $e): ResponseInterface
    {
        return match (true) {
            $e instanceof RouteNotFoundException => $this->handleNotFound($request, $e),
            $e instanceof MethodNotAllowedException => $this->handleMethodNotAllowed($request, $e),
            default => $this->handleGeneric($request, $e),
        };
    }

    /**
     * Handle 404 Not Found
     */
    public function handleNotFound(RequestInterface $request, RouteNotFoundException $e): ResponseInterface
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'error' => 'Not Found',
                'message' => $e->getMessage(),
            ], HttpStatus::NOT_FOUND);
        }

        return $this->htmlResponse(
            'Not Found',
            $e->getMessage(),
            HttpStatus::NOT_FOUND
        );
    }

    /**
     * Handle 405 Method Not Allowed
     */
    public function handleMethodNotAllowed(RequestInterface $request, MethodNotAllowedException $e): ResponseInterface
    {
        $response = $request->expectsJson()
            ? new JsonResponse([
                'error' => 'Method Not Allowed',
                'message' => $e->getMessage(),
                'allowed_methods' => $e->getAllowedMethods(),
            ], HttpStatus::METHOD_NOT_ALLOWED)
            : $this->htmlResponse(
                'Method Not Allowed',
                $e->getMessage(),
                HttpStatus::METHOD_NOT_ALLOWED
            );

        return $response->header('Allow', implode(', ', $e->getAllowedMethods()));
    }

    /**
     * Handle generic exceptions
     */
    public function handleGeneric(RequestInterface $request, Throwable $e): ResponseInterface
    {
        $this->logException($e, $request);

        if ($request->expectsJson()) {
            return $this->jsonErrorResponse($e);
        }

        return $this->htmlErrorResponse($e);
    }

    /**
     * Create JSON error response
     */
    protected function jsonErrorResponse(Throwable $e): JsonResponse
    {
        $data = [
            'error' => 'Internal Server Error',
            'message' => $this->debug ? $e->getMessage() : 'An unexpected error occurred.',
        ];

        if ($this->debug) {
            $data['exception'] = get_class($e);
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
        }

        return new JsonResponse($data, HttpStatus::INTERNAL_SERVER_ERROR);
    }

    /**
     * Create HTML error response
     */
    protected function htmlErrorResponse(Throwable $e): Response
    {
        $message = $this->debug
            ? sprintf(
                '%s<pre>%s</pre>',
                htmlspecialchars($e->getMessage()),
                htmlspecialchars($e->getTraceAsString())
            )
            : 'An unexpected error occurred.';

        return $this->htmlResponse('Internal Server Error', $message, HttpStatus::INTERNAL_SERVER_ERROR);
    }

    /**
     * Create HTML response
     */
    protected function htmlResponse(string $title, string $message, int $status): Response
    {
        $content = sprintf('<h1>%d %s</h1><p>%s</p>', $status, $title, $message);

        return new Response($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Report the exception via Error Handler
     */
    protected function logException(Throwable $e, ?RequestInterface $request = null): void
    {
        $context = [
            'request' => $request !== null ? [
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ] : null,
        ];

        // Use Core Error Handler if available
        if ($this->errorHandler !== null) {
            $this->errorHandler->report($e, $context);

            return;
        }

        // Fallback to direct logging
        try {
            safe_log('error', $e->getMessage(), array_merge([
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $context));
        } catch (Throwable $logError) {
            error_log(sprintf(
                '[EXCEPTION] %s: %s in %s:%d',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }

    /**
     * Set debug mode
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
}
