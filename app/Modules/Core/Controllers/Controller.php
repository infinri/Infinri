<?php declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine;
use Slim\Psr7\Response as SlimResponse;

abstract class Controller
{
    protected Engine $view;
    protected array $data = [];

    public function __construct(Engine $view)
    {
        $this->view = $view;
    }

    /**
     * Render a view with the given data
     */
    protected function render(Response $response, string $template, array $data = []): Response
    {
        // Merge with any existing data
        $data = array_merge($this->data, $data);
        
        // Create a new response if none was provided
        if (!$response instanceof SlimResponse) {
            $response = new SlimResponse();
        }
        
        // Render the template
        $output = $this->view->render($template, $data);
        
        // Write the output to the response
        $response->getBody()->write($output);
        
        return $response->withHeader('Content-Type', 'text/html');
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
