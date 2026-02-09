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
use App\Core\Module\ModuleRenderer;
use App\Core\Routing\Exceptions\RouteNotFoundException;
use Throwable;

/**
 * Web Exception Handler
 *
 * Extends the base ExceptionHandler to render error pages through
 * ModuleRenderer with the full site layout, instead of bare HTML.
 */
class WebExceptionHandler extends ExceptionHandler
{
    protected ?ModuleRenderer $renderer = null;

    public function setRenderer(ModuleRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }

    public function handleNotFound(RequestInterface $request, RouteNotFoundException $e): ResponseInterface
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'error' => 'Not Found',
                'message' => $e->getMessage(),
            ], HttpStatus::NOT_FOUND);
        }

        if ($this->renderer) {
            $content = $this->renderer->renderError(404);

            return Response::make($content, HttpStatus::NOT_FOUND)->asHtml();
        }

        return parent::handleNotFound($request, $e);
    }

    protected function htmlErrorResponse(Throwable $e): Response
    {
        if ($this->renderer && ! $this->debug) {
            $content = $this->renderer->renderError(500);

            return Response::make($content, HttpStatus::INTERNAL_SERVER_ERROR)->asHtml();
        }

        return parent::htmlErrorResponse($e);
    }
}
