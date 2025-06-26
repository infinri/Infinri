<?php declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\RouteParserInterface;

/**
 * Base controller class providing common functionality for all controllers
 */
class Controller
{
    protected ContainerInterface $container;
    protected ?RouteParserInterface $router = null;
    protected LoggerInterface $logger;

    public function __construct(
        protected \League\Plates\Engine $view,
        ContainerInterface $container,
        LoggerInterface $logger
    ) {
        $this->container = $container;
        $this->logger = $logger;
        
        // Set router if available in container
        if ($container->has(RouteParserInterface::class)) {
            $this->router = $container->get(RouteParserInterface::class);
        }
    }

    /**
     * Render a template with the given data
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return string Rendered template
     */
    protected function render(string $template, array $data = []): string
    {
        return $this->view->render($template, $data);
    }

    /**
     * Return a JSON response
     *
     * @param Response $response PSR-7 response object
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return Response
     */
    protected function json(Response $response, $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Set the router instance (can be used for testing or manual injection)
     */
    public function setRouter(RouteParserInterface $router): void
    {
        $this->router = $router;
    }

    /**
     * Generate a URL for a named route
     * 
     * @param string $routeName The name of the route
     * @param array $routeParams Route parameters
     * @param array $queryParams Query string parameters
     * @return string The generated URL
     * @throws \RuntimeException If the router is not available
     */
    protected function urlFor(
        string $routeName, 
        array $routeParams = [], 
        array $queryParams = []
    ): string {
        if (!$this->router) {
            throw new \RuntimeException('Router is not available. Make sure the container provides ' . RouteParserInterface::class);
        }

        $url = $this->router->urlFor($routeName, $routeParams);
        
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        return $url;
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
        $url = $this->urlFor($routeName, $routeParams, $queryParams);
        
        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}