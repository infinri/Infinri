<?php declare(strict_types=1);

namespace App\Modules\Shared\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    private $redis;
    private $config;
    private $logger;

    public function __construct(
        $redis, 
        array $config = [],
        ?LoggerInterface $logger = null
    ) {
        $this->redis = $redis;
        $this->config = array_merge([
            'requests' => 100,          // Number of requests
            'window' => 3600,            // Time window in seconds
            'ip_based' => true,          // Enable IP-based rate limiting
            'user_based' => false,       // Enable user-based rate limiting
            'user_id_field' => 'user_id' // Field to get user ID from request
        ], $config);
        
        $this->logger = $logger;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $key = $this->generateKey($request);
        
        if (!$this->isAllowed($key)) {
            $this->logger?->warning('Rate limit exceeded', [
                'key' => $key,
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $response = new \Slim\Psr7\Response(429);
            $response->getBody()->write(json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', $this->config['window']);
        }
        
        return $handler->handle($request);
    }
    
    private function generateKey(Request $request): string
    {
        $keyParts = ['ratelimit'];
        
        if ($this->config['ip_based']) {
            $keyParts[] = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown-ip';
        }
        
        if ($this->config['user_based']) {
            $user = $request->getAttribute($this->config['user_id_field']);
            if ($user) {
                $keyParts[] = 'user:' . $user;
            }
        }
        
        return implode(':', $keyParts);
    }
    
    private function isAllowed(string $key): bool
    {
        if (!$this->redis) {
            return true; // Fail open if Redis is not available
        }
        
        $current = $this->redis->incr($key);
        
        if ($current === 1) {
            // Set expiration on first request
            $this->redis->expire($key, $this->config['window']);
        }
        
        return $current <= $this->config['requests'];
    }
}
