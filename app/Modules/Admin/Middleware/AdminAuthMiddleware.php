<?php declare(strict_types=1);

namespace App\Modules\Admin\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Slim\Routing\RouteContext;

class AdminAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $session = $request->getAttribute('session');
        
        // Check if user is authenticated
        if (empty($session['admin_user_id'])) {
            // Store the intended URL for redirecting after login
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            
            if ($route) {
                $session['intended_url'] = $route->getPattern();
            }
            
            // Redirect to login page
            $response = new SlimResponse();
            return $response
                ->withHeader('Location', '/admin/login')
                ->withStatus(302);
        }
        
        // User is authenticated, proceed with the next middleware
        return $handler->handle($request);
    }
}
