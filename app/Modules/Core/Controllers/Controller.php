<?php declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PlatesRenderer;

abstract class Controller
{
    protected PlatesRenderer $view;

    public function __construct(PlatesRenderer $view)
    {
        $this->view = $view;
    }

    /**
     * Render a view with the given data
     */
    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->view->render($response, $template, $data);
    }

    /**
     * Return a JSON response
     */
    protected function json(Response $response, $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Redirect to a named route
     */
    protected function redirect(
        Response $response, 
        string $routeName, 
        array $routeParams = [], 
        array $queryParams = []
    ): Response {
        // This will be implemented when we set up the router
        return $response
            ->withHeader('Location', $routeName) // TODO: Generate URL from route name
            ->withStatus(302);
    }
}
