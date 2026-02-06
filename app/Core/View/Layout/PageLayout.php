<?php declare(strict_types=1);
/**
 * Page Layout
 *
 * Defines the page structure with named containers.
 * Blocks are injected into these containers via layout updates.
 *
 * Containers are injection points - named locations in the page
 * where blocks can be placed via layout update files.
 *
 * @package App\Core\View\Layout
 */
namespace App\Core\View\Layout;

final class PageLayout
{
    /**
     * Frontend page containers
     *
     * These are the valid container names for frontend layouts.
     * Modules inject blocks into these containers.
     */
    public const FRONTEND_CONTAINERS = [
        // Head section
        'head.meta',           // Meta tags
        'head.css',            // CSS files
        'head.scripts',        // Head JS (external APIs, analytics)

        // Body start
        'body.start',          // Right after <body> (skip links, etc.)

        // Header area
        'header.before',       // Before header (announcements)
        'header',              // Main header/navigation
        'header.after',        // After header (banners, callouts)

        // Breadcrumbs
        'breadcrumbs',         // Breadcrumb navigation

        // Main content area
        'content.before',      // Before main content
        'content.top',         // Top of content area
        'content',             // Main content
        'content.bottom',      // Bottom of content area
        'content.after',       // After main content

        // Sidebars
        'sidebar.left',        // Left sidebar
        'sidebar.right',       // Right sidebar

        // Footer area
        'footer.before',       // Before footer
        'footer',              // Main footer
        'footer.after',        // After footer (bottom bar)

        // Body end
        'body.end',            // Before </body>
        'body.js',             // JS files (end of body)
    ];

    /**
     * Admin page containers
     *
     * These are the valid container names for admin layouts.
     * Admin has different structure (sidebar, etc.)
     */
    public const ADMIN_CONTAINERS = [
        // Head section
        'head.meta',           // Meta tags (includes noindex)
        'head.css',            // CSS files
        'head.scripts',        // Head JS

        // Body start
        'body.start',          // Right after <body>

        // Admin structure
        'sidebar',             // Admin sidebar navigation
        'header',              // Admin header bar

        // Page content
        'page.title',          // Page title area
        'page.actions',        // Action buttons (New, Save, etc.)
        'messages',            // Flash messages/alerts

        // Main content
        'content.before',      // Before main content
        'content',             // Main content area
        'content.after',       // After main content

        // Body end
        'body.end',            // Before </body>
        'body.js',             // JS files
    ];

    /**
     * Check if container name is valid for area
     *
     * @param string $container Container name
     * @param string $area Area name (frontend/admin)
     *
     * @return bool
     */
    public static function isValidContainer(string $container, string $area = 'frontend'): bool
    {
        $containers = $area === 'admin' ? self::ADMIN_CONTAINERS : self::FRONTEND_CONTAINERS;

        return in_array($container, $containers, true);
    }

    /**
     * Get all containers for area
     *
     * @param string $area Area name
     *
     * @return array<string>
     */
    public static function getContainers(string $area = 'frontend'): array
    {
        return $area === 'admin' ? self::ADMIN_CONTAINERS : self::FRONTEND_CONTAINERS;
    }
}
