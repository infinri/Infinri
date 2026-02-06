<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Contracts\View;

/**
 * Template Resolver Interface
 *
 * Resolves template paths with theme fallback chain.
 *
 * Resolution order (first found wins):
 * 1. Theme override: Theme/view/{area}/templates/{module}/{template}
 * 2. Module template: {Module}/view/{area}/templates/{template}
 * 3. Core fallback: Core/View/view/{area}/templates/{template}
 */
interface TemplateResolverInterface
{
    /**
     * Resolve a template path
     *
     * @param string $template Template identifier (e.g., 'contact/form.phtml')
     * @param string $module Module name (e.g., 'Contact')
     * @param string $area Area name ('frontend' or 'admin')
     *
     * @return string|null Resolved absolute path or null if not found
     */
    public function resolve(string $template, string $module, string $area = 'frontend'): ?string;

    /**
     * Check if a template exists
     *
     * @param string $template Template identifier
     * @param string $module Module name
     * @param string $area Area name
     */
    public function exists(string $template, string $module, string $area = 'frontend'): bool;

    /**
     * Set the active theme
     *
     * @param string|null $theme Theme name or null for no theme
     */
    public function setTheme(?string $theme): void;

    /**
     * Get the active theme
     */
    public function getTheme(): ?string;

    /**
     * Get all resolution paths for debugging
     *
     * @param string $template Template identifier
     * @param string $module Module name
     * @param string $area Area name
     *
     * @return string[] Array of paths that would be checked
     */
    public function getResolutionPaths(string $template, string $module, string $area = 'frontend'): array;
}
