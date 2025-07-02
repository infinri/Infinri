<?php declare(strict_types=1);

namespace App\Modules\Admin\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class GuestMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $session = $request->getAttribute('session');
        
        // If user is already authenticated, redirect to dashboard
        if (!empty($session['admin_user_id'])) {
            $response = new SlimResponse();
            return $response
                ->withHeader('Location', '/admin')
                ->withStatus(302);
        }
        
        // User is a guest, proceed with the next middleware
        return $handler->handle($request);
    }
}
