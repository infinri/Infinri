<?php declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\AdminUser;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AuthController extends BaseAdminController
{
    private const SESSION_KEY = 'admin_auth';
    private const REMEMBER_ME_DURATION = 60 * 60 * 24 * 30; // 30 days

    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->logger = $container->get(LoggerInterface::class);
        $this->dispatcher = $container->has(EventDispatcherInterface::class) 
            ? $container->get(EventDispatcherInterface::class) 
            : null;
    }

    /**
     * Show login form
     */
    public function showLoginForm(Request $request, Response $response): Response
    {
        if ($this->isLoggedIn($request)) {
            return $this->redirect('admin.dashboard');
        }

        return $this->view->render($response, 'admin/auth/login', [
            'title' => 'Admin Login',
            'email' => $request->getQueryParams()['email'] ?? '',
            'remember' => !empty($request->getQueryParams()['remember']),
        ]);
    }

    /**
     * Handle login form submission
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $remember = !empty($data['remember']);

        // Basic validation
        if (empty($email) || empty($password)) {
            $this->flash->addMessage('error', 'Email and password are required');
            return $this->redirect('admin.login', [], ['email' => $email, 'remember' => $remember]);
        }

        // Find user
        $user = $this->getUserRepository()->findByEmail($email);
        
        // Verify user and password
        if (!$user || !$user->isActive() || !$user->verifyPassword($password)) {
            $this->logger->warning('Failed login attempt', ['email' => $email, 'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? '']);
            $this->flash->addMessage('error', 'Invalid email or password');
            return $this->redirect('admin.login', [], ['email' => $email, 'remember' => $remember]);
        }

        // Update last login
        $user->recordLogin();

        // Set session
        $session = $request->getAttribute('session');
        $session->set(self::SESSION_KEY, [
            'user_id' => $user->id,
            'logged_in_at' => time(),
        ]);

        // Set remember me cookie if requested
        if ($remember) {
            $token = $user->generateRememberToken();
            $user->save();
            
            $this->setRememberCookie(
                $response,
                $user->id,
                $token,
                time() + self::REMEMBER_ME_DURATION
            );
        }

        // Log successful login
        $this->logger->info('User logged in', ['user_id' => $user->id, 'email' => $email]);
        
        // Redirect to intended URL or dashboard
        $redirectTo = $session->get('redirect_after_login', $this->router->pathFor('admin.dashboard'));
        $session->delete('redirect_after_login');
        
        return $this->redirect($redirectTo);
    }

    /**
     * Logout the user
     */
    public function logout(Request $request, Response $response): Response
    {
        $session = $request->getAttribute('session');
        
        // Clear session
        $session->delete(self::SESSION_KEY);
        
        // Clear remember me cookie
        $response = $this->setRememberCookie($response, '', '', time() - 3600);
        
        // Add logout message
        $this->flash->addMessage('success', 'You have been logged out successfully.');
        
        return $this->redirect('admin.login');
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(Request $request): bool
    {
        $session = $request->getAttribute('session');
        
        // Check session
        if ($session->has(self::SESSION_KEY)) {
            $auth = $session->get(self::SESSION_KEY);
            return !empty($auth['user_id']) && $this->getUserRepository()->findByPK($auth['user_id']) !== null;
        }
        
        // Check remember me cookie
        $cookies = $request->getCookieParams();
        $rememberToken = $cookies['admin_remember'] ?? null;
        
        if ($rememberToken) {
            $parts = explode('|', $rememberToken);
            if (count($parts) === 2) {
                $userId = (int) $parts[0];
                $token = $parts[1];
                
                $user = $this->getUserRepository()->findByPK($userId);
                if ($user && $user->remember_token === $token && $user->isActive()) {
                    // Update session
                    $session->set(self::SESSION_KEY, [
                        'user_id' => $user->id,
                        'logged_in_at' => time(),
                    ]);
                    
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get currently logged in user
     */
    public function getCurrentUser(Request $request): ?AdminUser
    {
        if (!$this->isLoggedIn($request)) {
            return null;
        }
        
        $session = $request->getAttribute('session');
        $auth = $session->get(self::SESSION_KEY);
        
        return $this->getUserRepository()->findByPK($auth['user_id']);
    }

    /**
     * Require authentication for a route
     */
    public function requireAuth(Request $request, Response $response, callable $next): Response
    {
        if (!$this->isLoggedIn($request)) {
            $session = $request->getAttribute('session');
            $session->set('redirect_after_login', $request->getUri()->getPath());
            
            $this->flash->addMessage('error', 'Please log in to access this page.');
            return $this->redirect('admin.login');
        }
        
        return $next($request, $response);
    }

    /**
     * Require guest (non-authenticated) for a route
     */
    public function requireGuest(Request $request, Response $response, callable $next): Response
    {
        if ($this->isLoggedIn($request)) {
            return $this->redirect('admin.dashboard');
        }
        
        return $next($request, $response);
    }

    /**
     * Set remember me cookie
     */
    private function setRememberCookie(Response $response, int $userId, string $token, int $expires): Response
    {
        $value = "{$userId}|{$token}";
        
        return $response->withHeader('Set-Cookie', sprintf(
            'admin_remember=%s; Path=/; HttpOnly; SameSite=Strict; Expires=%s; %s',
            urlencode($value),
            gmdate('D, d M Y H:i:s T', $expires),
            $this->container->get('settings')['session']['secure'] ? 'Secure' : ''
        ));
    }

    /**
     * Get user repository
     */
    private function getUserRepository()
    {
        return $this->container->get('orm')->getRepository(AdminUser::class);
    }
}
