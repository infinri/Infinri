<?php declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\AdminUser;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Respect\Validation\Validator as v;

class UserController extends BaseAdminController
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->logger = $container->get(LoggerInterface::class);
    }

    /**
     * List all admin users
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $perPage = 20;
        
        $filters = [
            'search' => $request->getQueryParams()['search'] ?? null,
            'is_active' => $request->getQueryParams()['status'] ?? null,
            'page' => $page,
        ];

        $users = $this->getUserRepository()->getPaginated($perPage, $filters);

        return $this->view->render($response, 'admin/users/index', [
            'title' => 'Manage Admin Users',
            'users' => $users['items'],
            'pagination' => [
                'total' => $users['total'],
                'per_page' => $users['per_page'],
                'current_page' => $users['current_page'],
                'last_page' => $users['last_page'],
                'base_url' => $this->router->pathFor('admin.users.index'),
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Show create user form
     */
    public function create(Request $request, Response $response, array $args): Response
    {
        return $this->view->render($response, 'admin/users/form', [
            'title' => 'Create Admin User',
            'user' => new AdminUser($this->container),
            'isNew' => true,
        ]);
    }

    /**
     * Store new user
     */
    public function store(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        $validation = $this->validateUserData($data, null);
        
        if (!$validation['valid']) {
            $this->flash->addMessage('error', 'Please correct the errors below.');
            $this->flash->addMessage('form_errors', $validation['errors']);
            $this->flash->addMessage('form_data', $data);
            return $this->redirect('admin.users.create');
        }

        // Create user
        $user = new AdminUser($this->container);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->setPassword($data['password']);
        $user->is_active = isset($data['is_active']);
        $user->save();

        $this->logger->info('Admin user created', ['user_id' => $user->id, 'by' => $this->getCurrentUser($request)->id]);
        $this->flash->addMessage('success', 'User created successfully.');
        
        return $this->redirect('admin.users.index');
    }

    /**
     * Show edit user form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->getUserRepository()->findByPK($id);
        
        if (!$user) {
            $this->flash->addMessage('error', 'User not found.');
            return $this->redirect('admin.users.index');
        }

        return $this->view->render($response, 'admin/users/form', [
            'title' => 'Edit Admin User',
            'user' => $user,
            'isNew' => false,
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->getUserRepository()->findByPK($id);
        
        if (!$user) {
            $this->flash->addMessage('error', 'User not found.');
            return $this->redirect('admin.users.index');
        }
        
        $data = $request->getParsedBody();
        
        // Validate input
        $validation = $this->validateUserData($data, $user->id);
        
        if (!$validation['valid']) {
            $this->flash->addMessage('error', 'Please correct the errors below.');
            $this->flash->addMessage('form_errors', $validation['errors']);
            return $this->redirect('admin.users.edit', ['id' => $user->id]);
        }

        // Update user
        $user->name = $data['name'];
        $user->email = $data['email'];
        
        if (!empty($data['password'])) {
            $user->setPassword($data['password']);
        }
        
        $user->is_active = isset($data['is_active']);
        $user->save();

        $this->logger->info('Admin user updated', ['user_id' => $user->id, 'by' => $this->getCurrentUser($request)->id]);
        $this->flash->addMessage('success', 'User updated successfully.');
        
        return $this->redirect('admin.users.index');
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->getUserRepository()->findByPK($id);
        
        if (!$user) {
            return $this->json($response, ['error' => 'User not found.'], 404);
        }
        
        // Prevent deactivating self
        $currentUser = $this->getCurrentUser($request);
        if ($user->id === $currentUser->id) {
            return $this->json($response, ['error' => 'You cannot deactivate your own account.'], 422);
        }
        
        $user->is_active = !$user->is_active;
        $user->save();
        
        $action = $user->is_active ? 'activated' : 'deactivated';
        $this->logger->info("Admin user {$action}", ['user_id' => $user->id, 'by' => $currentUser->id]);
        
        return $this->json($response, [
            'success' => true,
            'is_active' => $user->is_active,
            'message' => "User {$action} successfully."
        ]);
    }

    /**
     * Validate user data
     */
    private function validateUserData(array $data, ?int $userId = null): array
    {
        $errors = [];
        $valid = true;
        
        $validator = v::key('name', v::stringType()->notEmpty())
            ->key('email', v::email())
            ->key('email', $this->uniqueEmailRule($userId));
        
        // Only validate password if it's a new user or password is being changed
        if ($userId === null || !empty($data['password'])) {
            $validator->key('password', v::stringType()->length(8, null));
        }
        
        try {
            $validator->assert($data);
        } catch (\Respect\Validation\Exceptions\NestedValidationException $e) {
            $errors = $e->getMessages();
            $valid = false;
        }
        
        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }
    
    /**
     * Get unique email validation rule
     */
    private function uniqueEmailRule(?int $userId = null): v
    {
        return v::callback(function($value) use ($userId) {
            return !$this->getUserRepository()->emailExists($value, $userId);
        })->setTemplate('Email is already in use');
    }
    
    /**
     * Get user repository
     */
    private function getUserRepository()
    {
        return $this->container->get('orm')->getRepository(AdminUser::class);
    }
}
