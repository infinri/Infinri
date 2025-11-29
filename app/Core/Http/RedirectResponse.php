<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Http;

/**
 * Redirect Response
 * 
 * Represents an HTTP redirect response
 */
class RedirectResponse extends Response
{
    /**
     * Target URL for redirect
     */
    protected string $targetUrl;

    /**
     * Create a new redirect response instance
     */
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct('', $status, $headers);
        
        $this->setTargetUrl($url);
    }

    /**
     * Create a redirect response
     */
    public static function to(string $url, int $status = 302): static
    {
        return new static($url, $status);
    }

    /**
     * Create a permanent redirect (301)
     */
    public static function permanent(string $url): static
    {
        return new static($url, 301);
    }

    /**
     * Create a temporary redirect (302)
     */
    public static function temporary(string $url): static
    {
        return new static($url, 302);
    }

    /**
     * Create a "See Other" redirect (303)
     * Used after POST to redirect to GET
     */
    public static function seeOther(string $url): static
    {
        return new static($url, 303);
    }

    /**
     * Set target URL
     */
    public function setTargetUrl(string $url): static
    {
        if ($url === '') {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }
        
        $this->targetUrl = $url;
        $this->header('Location', $url);
        
        // Set content for browsers that don't follow redirects
        $this->setContent(sprintf(
            '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="refresh" content="0;url=\'%1$s\'" />
    <title>Redirecting to %1$s</title>
</head>
<body>
    Redirecting to <a href="%1$s">%1$s</a>.
</body>
</html>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        ));
        
        $this->header('Content-Type', 'text/html; charset=UTF-8');
        
        return $this;
    }

    /**
     * Get target URL
     */
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    /**
     * Add query parameters to redirect URL
     */
    public function withQuery(array $query): static
    {
        $separator = str_contains($this->targetUrl, '?') ? '&' : '?';
        
        return $this->setTargetUrl($this->targetUrl . $separator . http_build_query($query));
    }

    /**
     * Add fragment to redirect URL
     */
    public function withFragment(string $fragment): static
    {
        return $this->setTargetUrl($this->targetUrl . '#' . $fragment);
    }
}
