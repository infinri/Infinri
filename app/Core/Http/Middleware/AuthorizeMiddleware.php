<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Http\Middleware;

use App\Core\Authorization\AuthorizationException;
use App\Core\Authorization\Gate;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\HttpStatus;
use App\Core\Http\JsonResponse;

/**
 * Authorize Middleware
 *
 * Apply authorization checks at the route level.
 *
 * Usage in routes:
 *   $router->get('/posts/{id}/edit', [PostController::class, 'edit'])
 *       ->middleware(AuthorizeMiddleware::class . ':update,post');
 *
 * Or with custom ability:
 *   ->middleware('can:admin-access')
 */
class AuthorizeMiddleware
{
    protected Gate $gate;
    protected string $ability;
    protected ?string $modelParam;

    public function __construct(?Gate $gate = null, string $ability = '', ?string $modelParam = null)
    {
        $this->gate = $gate ?? gate();
        $this->ability = $ability;
        $this->modelParam = $modelParam;
    }

    /**
     * Handle the request
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        try {
            $arguments = $this->getArguments($request);
            $this->gate->authorize($this->ability, ...$arguments);
        } catch (AuthorizationException $e) {
            return $this->unauthorized($request, $e);
        }

        return $next($request);
    }

    /**
     * Get the arguments for the authorization check
     */
    protected function getArguments(RequestInterface $request): array
    {
        if ($this->modelParam === null) {
            return [];
        }

        // Get model from route parameters
        $model = $request->route($this->modelParam);

        if ($model === null) {
            return [];
        }

        return [$model];
    }

    /**
     * Handle unauthorized response
     */
    protected function unauthorized(RequestInterface $request, AuthorizationException $e): ResponseInterface
    {
        // Check if request expects JSON
        $acceptsJson = str_contains($request->header('Accept', ''), 'application/json')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($acceptsJson) {
            return JsonResponse::error($e->getMessage(), HttpStatus::FORBIDDEN);
        }

        // For web requests, return HTML 403 page
        return new \App\Core\Http\Response(
            $this->render403Page($e->getMessage()),
            HttpStatus::FORBIDDEN,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Render a simple 403 page
     */
    protected function render403Page(string $message): string
    {
        $escapedMessage = e($message); // Uses Core\Security\Sanitizer::html()

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>403 Forbidden</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                           display: flex; justify-content: center; align-items: center; 
                           min-height: 100vh; margin: 0; background: #f5f5f5; }
                    .container { text-align: center; padding: 40px; }
                    h1 { font-size: 72px; margin: 0; color: #333; }
                    p { color: #666; margin: 20px 0; }
                    a { color: #0066cc; text-decoration: none; }
                    a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>403</h1>
                    <p>{$escapedMessage}</p>
                    <a href="/">Return Home</a>
                </div>
            </body>
            </html>
            HTML;
    }

    /**
     * Create middleware instance with parameters
     *
     * @param string $ability The ability to check
     * @param string|null $model Route parameter name for the model
     */
    public static function can(string $ability, ?string $model = null): self
    {
        return new self(gate(), $ability, $model);
    }
}
