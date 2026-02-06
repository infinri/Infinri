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
namespace App\Core\Http\Concerns;

/**
 * Provides content type detection methods for Request
 *
 * Centralizes logic for determining request/response content types
 * and content negotiation.
 */
trait InteractsWithContentTypes
{
    /**
     * Check if request content is JSON
     */
    public function isJson(): bool
    {
        return $this->hasJsonContentType($this->headers->getContentType() ?? '');
    }

    /**
     * Check if request expects JSON response
     */
    public function expectsJson(): bool
    {
        return $this->hasJsonContentType($this->header('accept', ''));
    }

    /**
     * Check if request wants JSON response
     */
    public function wantsJson(): bool
    {
        return $this->expectsJson() || $this->isJson();
    }

    /**
     * Check if request is AJAX
     */
    public function ajax(): bool
    {
        return $this->server->get('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Check if request is PJAX
     */
    public function pjax(): bool
    {
        return $this->header('X-PJAX') === 'true';
    }

    /**
     * Check if request accepts HTML
     */
    public function acceptsHtml(): bool
    {
        return str_contains($this->header('accept', ''), 'text/html');
    }

    /**
     * Check if request accepts any content type
     */
    public function acceptsAny(): bool
    {
        return str_contains($this->header('accept', ''), '*/*');
    }

    /**
     * Get the preferred content type from Accept header
     */
    public function prefers(array $contentTypes): ?string
    {
        $accept = $this->header('accept', '');

        foreach ($contentTypes as $type) {
            if (str_contains($accept, $type)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Check if content type contains JSON indicator
     */
    protected function hasJsonContentType(string $contentType): bool
    {
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }
}
