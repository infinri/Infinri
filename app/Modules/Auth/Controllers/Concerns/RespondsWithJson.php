<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Controllers\Concerns;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;

/**
 * Responds With JSON Trait
 * 
 * Common response handling for Auth controllers.
 */
trait RespondsWithJson
{
    /**
     * Check if request expects JSON response
     */
    protected function expectsJson(RequestInterface $request): bool
    {
        return str_contains($request->header('Accept', ''), 'application/json')
            || str_starts_with($request->getUri(), '/api/');
    }

    /**
     * Render a view template
     * 
     * View paths (dots = directory separator):
     * - 'auth.login' → Auth::login → view/base/templates/login.phtml
     * - 'auth.two-factor.challenge' → Auth::two-factor/challenge → view/base/templates/two-factor/challenge.phtml
     */
    protected function view(string $view, array $data = []): ResponseInterface
    {
        // Convert dot notation to path
        // 'auth.login' → 'login'
        // 'auth.two-factor.challenge' → 'two-factor/challenge'
        // 'auth.forgot-password' → 'forgot-password'
        $viewPath = str_replace('auth.', '', $view);
        $viewPath = str_replace('.', '/', $viewPath);
        
        // Use the view() helper if available, otherwise return placeholder
        if (function_exists('view')) {
            return view("Auth::{$viewPath}", $data);
        }
        
        // Fallback: render basic HTML response
        // This allows the module to work even without full view system
        return $this->response($this->renderFallbackView($viewPath, $data));
    }

    /**
     * Render a basic fallback view when view system is not available
     */
    protected function renderFallbackView(string $viewPath, array $data): string
    {
        $templatePath = __DIR__ . '/../../view/base/templates/' . $viewPath . '.phtml';
        
        if (file_exists($templatePath)) {
            // Extract data to local scope
            extract($data);
            
            // Capture output
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        
        return "View not found: {$viewPath}";
    }
}
