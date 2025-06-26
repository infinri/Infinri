<?php declare(strict_types=1);

namespace App\Modules\Pages\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Modules\Core\Controllers\Controller;

class PageController extends Controller
{
    /**
     * Display the about page
     */
    public function about(Request $request, Response $response): Response
    {
        $data = [
            'title' => 'About Us',
            'description' => 'Learn more about our company and our mission.'
        ];
        
        return $this->render($response, 'pages/about.php', $data);
    }

    /**
     * Display the services page
     */
    public function services(Request $request, Response $response): Response
    {
        $services = [
            [
                'title' => 'Web Development',
                'description' => 'Custom web applications built with modern technologies.',
                'icon' => 'code'
            ],
            [
                'title' => 'Responsive Design',
                'description' => 'Beautiful, responsive designs that work on all devices.',
                'icon' => 'devices'
            ],
            [
                'title' => 'Performance',
                'description' => 'Lightning-fast websites optimized for speed and efficiency.',
                'icon' => 'speed'
            ]
        ];

        $data = [
            'title' => 'Our Services',
            'description' => 'Explore the services we offer to help your business grow.',
            'services' => $services
        ];
        
        return $this->render($response, 'pages/services.php', $data);
    }
}
