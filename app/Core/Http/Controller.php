<?php

declare(strict_types=1);


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

use App\Core\Application;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;

/**
 * Base Controller
 * 
 * Provides common functionality for all controllers.
 */
abstract class Controller
{
    protected Application $app;

    public function __construct()
    {
        $this->app = Application::getInstance();
    }

    /**
     * Create a JSON response
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Create a success JSON response
     */
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return JsonResponse::success($data, $message, $status);
    }

    /**
     * Create an error JSON response
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return JsonResponse::error($message, $status, $errors);
    }

    /**
     * Create a standard response
     */
    protected function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Create a redirect response
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Get a service from the container
     */
    protected function make(string $abstract, array $parameters = []): mixed
    {
        return $this->app->make($abstract, $parameters);
    }

    /**
     * Validate request data using the Validator
     * 
     * @throws \App\Core\Validation\ValidationException
     */
    protected function validate(RequestInterface $request, array $rules, array $messages = []): array
    {
        $validator = \App\Core\Validation\Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            throw new \App\Core\Validation\ValidationException($validator);
        }
        
        return $validator->validated();
    }
}
