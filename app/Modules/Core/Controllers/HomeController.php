<?php declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends Controller
{
    /**
     * Display the home page
     */
    public function index(Request $request, Response $response): Response
    {
        $data = [
            'title' => 'Welcome to Infinri',
            'description' => 'We build fast, modern, and reliable web applications that help businesses grow.'
        ];
        
        return $this->render($response, 'core/home', $data);
    }
}
