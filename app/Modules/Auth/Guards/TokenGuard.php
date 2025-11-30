<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Guards;

use App\Core\Contracts\Auth\AuthorizableInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Contracts\GuardInterface;
use App\Modules\Auth\Contracts\UserRepositoryInterface;

/**
 * Token Guard
 * 
 * Token-based authentication guard for API requests.
 * Supports Bearer tokens and query parameter tokens.
 */
class TokenGuard implements GuardInterface
{
    /**
     * Guard name
     */
    protected string $name;

    /**
     * User repository
     */
    protected UserRepositoryInterface $provider;

    /**
     * The request instance
     */
    protected ?RequestInterface $request;

    /**
     * The name of the query string item for the API token
     */
    protected string $inputKey;

    /**
     * The name of the token column in storage
     */
    protected string $storageKey;

    /**
     * Whether to hash the token before comparison
     */
    protected bool $hash;

    /**
     * The currently authenticated user
     */
    protected ?AuthorizableInterface $user = null;

    /**
     * Whether the user has been retrieved
     */
    protected bool $userRetrieved = false;

    /**
     * Create a new token guard instance
     */
    public function __construct(
        string $name,
        UserRepositoryInterface $provider,
        ?RequestInterface $request,
        string $inputKey = 'api_token',
        string $storageKey = 'api_token',
        bool $hash = false
    ) {
        $this->name = $name;
        $this->provider = $provider;
        $this->request = $request;
        $this->inputKey = $inputKey;
        $this->storageKey = $storageKey;
        $this->hash = $hash;
    }

    /**
     * {@inheritDoc}
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * {@inheritDoc}
     */
    public function user(): ?AuthorizableInterface
    {
        if ($this->userRetrieved) {
            return $this->user;
        }

        $this->userRetrieved = true;

        $token = $this->getTokenFromRequest();

        if ($token !== null) {
            $this->user = $this->retrieveUserByToken($token);
        }

        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function id(): int|string|null
    {
        $user = $this->user();
        return $user?->getAuthIdentifier();
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials[$this->inputKey])) {
            return false;
        }

        $user = $this->retrieveUserByToken($credentials[$this->inputKey]);

        return $user !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        // Token guard doesn't support password-based authentication
        // Use validate() for token validation
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function once(array $credentials = []): bool
    {
        if ($this->validate($credentials)) {
            $this->user = $this->retrieveUserByToken($credentials[$this->inputKey]);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function login(AuthorizableInterface $user, bool $remember = false): void
    {
        // Token guard doesn't maintain session state
        $this->setUser($user);
    }

    /**
     * {@inheritDoc}
     */
    public function loginUsingId(int|string $id, bool $remember = false): ?AuthorizableInterface
    {
        $user = $this->provider->findById($id);

        if ($user !== null) {
            $this->setUser($user);
            return $user;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function onceUsingId(int|string $id): ?AuthorizableInterface
    {
        return $this->loginUsingId($id);
    }

    /**
     * {@inheritDoc}
     */
    public function viaRemember(): bool
    {
        // Token guard doesn't use remember functionality
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function logout(): void
    {
        $this->user = null;
        $this->userRetrieved = false;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the currently authenticated user
     */
    public function setUser(AuthorizableInterface $user): static
    {
        $this->user = $user;
        $this->userRetrieved = true;

        return $this;
    }

    /**
     * Set the current request instance
     */
    public function setRequest(RequestInterface $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the token from the request
     */
    protected function getTokenFromRequest(): ?string
    {
        if ($this->request === null) {
            return null;
        }

        // Try Bearer token from Authorization header first
        $token = $this->getBearerToken();

        if ($token !== null) {
            return $token;
        }

        // Fall back to query parameter or POST data
        return $this->request->input($this->inputKey);
    }

    /**
     * Get Bearer token from Authorization header
     */
    protected function getBearerToken(): ?string
    {
        $header = $this->request?->header('Authorization') ?? '';

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    /**
     * Retrieve user by token
     */
    protected function retrieveUserByToken(string $token): ?AuthorizableInterface
    {
        $token = $this->hash ? hash('sha256', $token) : $token;

        return $this->provider->findByToken($token, 'api_token');
    }
}
