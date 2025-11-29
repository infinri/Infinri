<?php
declare(strict_types=1);
/**
 * Handle Generator
 * 
 * Generates layout handles from routes using hybrid approach:
 * - Auto-generates default handles based on route
 * - Allows controllers to add explicit handles
 * 
 * @package App\Core\View
 */

namespace App\Core\View;

final class HandleGenerator
{
    /**
     * Generate handles from route information
     * 
     * Pattern: {module}_{action}
     * Examples:
     *   GET /           → home_index
     *   GET /blog       → blog_index
     *   GET /blog/123   → blog_view
     *   GET /contact    → contact_index
     *   POST /contact   → contact_post
     *   GET /admin/users → admin_users_index
     * 
     * @param string $module Module name (e.g., 'Home', 'Blog', 'Contact')
     * @param string $action Action name (e.g., 'index', 'view', 'create')
     * @param string $area Area name ('frontend' or 'admin')
     * @return array<string> Ordered list of handles
     */
    public function generate(string $module, string $action, string $area = 'frontend'): array
    {
        $handles = [];
        
        // 1. Universal default - applies to every page
        $handles[] = 'default';
        
        // 2. Area default - applies to all pages in this area
        $handles[] = "{$area}_default";
        
        // 3. Route-based handle - specific to this page
        $moduleHandle = strtolower($module);
        $actionHandle = strtolower($action);
        $handles[] = "{$moduleHandle}_{$actionHandle}";
        
        // 4. Admin-prefixed handle for admin area (additional specificity)
        if ($area === 'admin') {
            $handles[] = "admin_{$moduleHandle}_{$actionHandle}";
        }
        
        return $handles;
    }
    
    /**
     * Generate handles from a route path
     * 
     * Parses URL path to extract module and action:
     *   /           → module: home, action: index
     *   /blog       → module: blog, action: index
     *   /blog/123   → module: blog, action: view
     *   /blog/create → module: blog, action: create
     *   /admin/users → module: users, action: index (admin area)
     *   /admin/users/5/edit → module: users, action: edit (admin area)
     * 
     * @param string $path URL path
     * @param string $method HTTP method
     * @return array<string> Ordered list of handles
     */
    public function generateFromPath(string $path, string $method = 'GET'): array
    {
        $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);
        
        // Determine area
        $area = 'frontend';
        if (!empty($segments) && $segments[0] === 'admin') {
            $area = 'admin';
            array_shift($segments); // Remove 'admin' prefix
        }
        
        // Parse module and action
        if (empty($segments)) {
            // Root path: /
            $module = 'home';
            $action = 'index';
        } elseif (count($segments) === 1) {
            // Single segment: /blog, /contact
            $module = $segments[0];
            $action = 'index';
        } else {
            // Multiple segments: /blog/123, /blog/create, /users/5/edit
            $module = $segments[0];
            $lastSegment = end($segments);
            
            // Check if last segment is an action word
            $actionWords = ['index', 'view', 'show', 'create', 'new', 'edit', 'update', 'delete', 'list'];
            
            if (in_array($lastSegment, $actionWords, true)) {
                $action = $lastSegment;
            } elseif (is_numeric($lastSegment)) {
                // Numeric ID implies view action
                $action = 'view';
            } else {
                // Default to index for unknown patterns
                $action = 'index';
            }
        }
        
        // Map HTTP method to action override for REST patterns
        if ($method === 'POST' && $action === 'index') {
            $action = 'post';
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $action = 'update';
        } elseif ($method === 'DELETE') {
            $action = 'delete';
        }
        
        return $this->generate($module, $action, $area);
    }
    
    /**
     * Normalize a handle name
     * 
     * @param string $handle Raw handle name
     * @return string Normalized handle (lowercase, underscores)
     */
    public function normalize(string $handle): string
    {
        // Convert to lowercase
        $handle = strtolower($handle);
        
        // Replace non-alphanumeric with underscores
        $handle = preg_replace('/[^a-z0-9_]/', '_', $handle);
        
        // Remove consecutive underscores
        $handle = preg_replace('/_+/', '_', $handle);
        
        // Trim underscores from ends
        return trim($handle, '_');
    }
}
