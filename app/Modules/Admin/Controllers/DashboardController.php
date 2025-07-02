<?php declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Core\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index(Request $request, Response $response): Response
    {
        $data = [
            'title' => 'Admin Dashboard',
            'stats' => [
                'users' => 0, // Will be replaced with actual data
                'pages' => 0, // Will be replaced with actual data
                'contacts' => 0, // Will be replaced with actual data
            ],
            'recent_activity' => [], // Will be replaced with actual data
        ];

        return $this->render($response, 'admin/dashboard', $data);
    }
}
