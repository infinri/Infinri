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
namespace App\Core\Http;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Http\Concerns\InteractsWithContentTypes;
use App\Core\Http\Concerns\InteractsWithInput;
use App\Core\Support\Str;

/**
 * HTTP Request
 *
 * Represents an incoming HTTP request with convenient access methods.
 * Uses traits to separate concerns and reduce class size.
 */
class Request implements RequestInterface
{
    use InteractsWithInput;
    use InteractsWithContentTypes;

    public readonly ParameterBag $query;
    public readonly ParameterBag $request;
    public readonly ParameterBag $server;
    public readonly HeaderBag $headers;
    public readonly ParameterBag $cookies;

    protected array $routeParameters = [];
    protected string $method;
    protected string $pathInfo;
    protected ?string $content = null;

    /**
     * Create a new request instance
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $server = [],
        array $cookies = [],
        ?string $content = null
    ) {
        $this->query = new ParameterBag($query);
        $this->request = new ParameterBag($request);
        $this->server = new ParameterBag($server);
        $this->cookies = new ParameterBag($cookies);
        $this->headers = new HeaderBag($this->extractHeaders($server));
        $this->content = $content;

        $this->method = $this->resolveMethod();
        $this->pathInfo = $this->resolvePath();
    }

    /**
     * Create request from PHP globals
     */
    public static function capture(): static
    {
        return new static(
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIE,
            file_get_contents('php://input') ?: null
        );
    }

    /**
     * Create request from arrays (for testing)
     */
    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $server = [],
        ?string $content = null
    ): static {
        $server = array_merge(self::getDefaultServerVars(), $server);

        $parsedUrl = parse_url($uri);
        $path = $parsedUrl['path'] ?? '/';
        $queryString = $parsedUrl['query'] ?? '';

        $server['REQUEST_URI'] = $uri;
        $server['REQUEST_METHOD'] = strtoupper($method);
        $server['PATH_INFO'] = $path;
        $server['QUERY_STRING'] = $queryString;

        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        if (strtoupper($method) === 'GET') {
            $query = array_merge($query, $parameters);
            $request = [];
        } else {
            $request = $parameters;
        }

        return new static($query, $request, $server, $cookies, $content);
    }

    /**
     * Get default server variables for testing
     */
    private static function getDefaultServerVars(): array
    {
        return [
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Infinri/1.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ];
    }

    // ==================== Core HTTP Methods ====================

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->pathInfo;
    }

    public function url(): string
    {
        return rtrim($this->getSchemeAndHttpHost() . $this->path(), '/');
    }

    public function fullUrl(): string
    {
        $queryString = $this->server->get('QUERY_STRING', '');

        return $queryString !== '' ? $this->url() . '?' . $queryString : $this->url();
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function secure(): bool
    {
        $https = $this->server->get('HTTPS');

        return $https !== null && $https !== 'off';
    }

    // ==================== Headers ====================

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers->get($key, $default);
    }

    public function headers(): array
    {
        $headers = [];
        foreach ($this->headers->all() as $key => $values) {
            $headers[$key] = $values[0] ?? null;
        }

        return $headers;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization', '');

        return str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;
    }

    // ==================== Client Info ====================

    public function ip(): string
    {
        $forwardedFor = $this->server->get('HTTP_X_FORWARDED_FOR');
        if ($forwardedFor !== null) {
            return trim(explode(',', $forwardedFor)[0]);
        }

        return $this->server->get('REMOTE_ADDR', '127.0.0.1');
    }

    public function userAgent(): ?string
    {
        return $this->server->get('HTTP_USER_AGENT');
    }

    // ==================== Route Parameters ====================

    public function route(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->routeParameters;
        }

        return $this->routeParameters[$key] ?? $default;
    }

    public function setRouteParameters(array $parameters): static
    {
        $this->routeParameters = $parameters;

        return $this;
    }

    // ==================== Raw Content ====================

    public function getContent(): ?string
    {
        return $this->content;
    }

    // ==================== Protected Helpers ====================

    protected function getSchemeAndHttpHost(): string
    {
        $scheme = $this->secure() ? 'https' : 'http';

        return $scheme . '://' . $this->server->get('HTTP_HOST', 'localhost');
    }

    protected function extractHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[Str::serverKeyToHeader($key)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[Str::serverKeyToHeader($key)] = $value;
            }
        }

        return $headers;
    }

    protected function resolveMethod(): string
    {
        $method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));

        if ($method === 'POST') {
            $override = $this->server->get('HTTP_X_HTTP_METHOD_OVERRIDE')
                ?? $this->request->get('_method');
            if ($override !== null) {
                $method = strtoupper($override);
            }
        }

        return $method;
    }

    protected function resolvePath(): string
    {
        $requestUri = $this->server->get('REQUEST_URI', '/');
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        return $path === '' ? '/' : $path;
    }
}
