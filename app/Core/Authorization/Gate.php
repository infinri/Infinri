<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Authorization;

use App\Core\Contracts\Auth\AuthorizableInterface;
use Closure;

/**
 * Authorization Gate
 * 
 * Central authorization service that determines if a user can perform actions.
 * 
 * Usage:
 *   $gate->define('edit-post', fn($user, $post) => $user->id === $post->user_id);
 *   $gate->allows('edit-post', $post);
 *   $gate->authorize('edit-post', $post); // throws if denied
 * 
 * With Policies:
 *   $gate->policy(Post::class, PostPolicy::class);
 *   $gate->allows('update', $post); // calls PostPolicy::update($user, $post)
 */
final class Gate
{
    /**
     * The user resolver callback
     * @var Closure|null
     */
    private ?Closure $userResolver = null;

    /**
     * Registered ability callbacks
     * @var array<string, Closure>
     */
    private array $abilities = [];

    /**
     * Registered policy classes
     * @var array<class-string, class-string>
     */
    private array $policies = [];

    /**
     * Policy instances cache
     * @var array<class-string, object>
     */
    private array $policyInstances = [];

    /**
     * Before callbacks (run before any check)
     * @var Closure[]
     */
    private array $beforeCallbacks = [];

    /**
     * After callbacks (run after any check)
     * @var Closure[]
     */
    private array $afterCallbacks = [];

    /**
     * Set the user resolver
     * 
     * @param Closure $resolver Returns the current user or null
     */
    public function setUserResolver(Closure $resolver): self
    {
        $this->userResolver = $resolver;
        return $this;
    }

    /**
     * Resolve the current user
     */
    public function resolveUser(): ?AuthorizableInterface
    {
        if ($this->userResolver === null) {
            return null;
        }

        return ($this->userResolver)();
    }

    /**
     * Define a new ability
     * 
     * @param string $ability The ability name
     * @param Closure $callback Receives (user, ...args) and returns bool|Response
     */
    public function define(string $ability, Closure $callback): self
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }

    /**
     * Register a policy for a model class
     * 
     * @param class-string $model The model class
     * @param class-string $policy The policy class
     */
    public function policy(string $model, string $policy): self
    {
        $this->policies[$model] = $policy;
        return $this;
    }

    /**
     * Register multiple policies at once
     * 
     * @param array<class-string, class-string> $policies
     */
    public function policies(array $policies): self
    {
        foreach ($policies as $model => $policy) {
            $this->policy($model, $policy);
        }
        return $this;
    }

    /**
     * Register a "before" callback
     * 
     * If this returns a non-null value, it short-circuits the check.
     * Return true to allow, false to deny, null to continue.
     */
    public function before(Closure $callback): self
    {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }

    /**
     * Register an "after" callback
     */
    public function after(Closure $callback): self
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    /**
     * Check if the user can perform an ability
     * 
     * @param string $ability The ability to check
     * @param mixed ...$arguments Arguments (typically the model instance)
     */
    public function allows(string $ability, mixed ...$arguments): bool
    {
        return $this->check($ability, ...$arguments)->allowed();
    }

    /**
     * Check if the user cannot perform an ability
     */
    public function denies(string $ability, mixed ...$arguments): bool
    {
        return $this->check($ability, ...$arguments)->denied();
    }

    /**
     * Check an ability and return the Response
     */
    public function check(string $ability, mixed ...$arguments): Response
    {
        $user = $this->resolveUser();

        // Run before callbacks
        foreach ($this->beforeCallbacks as $callback) {
            $result = $callback($user, $ability, $arguments);
            if ($result !== null) {
                return $this->toResponse($result);
            }
        }

        // Get the raw result
        $result = $this->raw($ability, $user, $arguments);

        // Run after callbacks
        foreach ($this->afterCallbacks as $callback) {
            $afterResult = $callback($user, $ability, $result, $arguments);
            if ($afterResult !== null) {
                $result = $afterResult;
            }
        }

        return $this->toResponse($result);
    }

    /**
     * Authorize an ability (throws if denied)
     * 
     * @throws AuthorizationException
     */
    public function authorize(string $ability, mixed ...$arguments): Response
    {
        return $this->check($ability, ...$arguments)->authorize();
    }

    /**
     * Check any of multiple abilities
     */
    public function any(array $abilities, mixed ...$arguments): bool
    {
        foreach ($abilities as $ability) {
            if ($this->allows($ability, ...$arguments)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check all of multiple abilities
     */
    public function all(array $abilities, mixed ...$arguments): bool
    {
        foreach ($abilities as $ability) {
            if ($this->denies($ability, ...$arguments)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check ability for a specific user (not the current user)
     */
    public function forUser(?AuthorizableInterface $user): GateForUser
    {
        return new GateForUser($this, $user);
    }

    /**
     * Get the policy instance for a model
     */
    public function getPolicyFor(object|string $model): ?object
    {
        $class = is_object($model) ? get_class($model) : $model;

        // Check for exact match
        if (isset($this->policies[$class])) {
            return $this->resolvePolicyInstance($this->policies[$class]);
        }

        // Check for parent class match
        foreach ($this->policies as $modelClass => $policyClass) {
            if (is_a($class, $modelClass, true)) {
                return $this->resolvePolicyInstance($policyClass);
            }
        }

        return null;
    }

    /**
     * Check if a policy is defined for a model
     */
    public function hasPolicyFor(object|string $model): bool
    {
        return $this->getPolicyFor($model) !== null;
    }

    /**
     * Perform the raw authorization check
     */
    private function raw(string $ability, ?AuthorizableInterface $user, array $arguments): bool|Response|null
    {
        // First, try to find a policy for the first argument (if it's an object)
        if (!empty($arguments) && is_object($arguments[0])) {
            $policy = $this->getPolicyFor($arguments[0]);

            if ($policy !== null) {
                $method = $this->formatAbilityMethod($ability);

                if (method_exists($policy, 'before')) {
                    $beforeResult = $policy->before($user, $ability);
                    if ($beforeResult !== null) {
                        return $beforeResult;
                    }
                }

                if (method_exists($policy, $method)) {
                    return $policy->$method($user, ...$arguments);
                }
            }
        }

        // Fall back to defined abilities
        if (isset($this->abilities[$ability])) {
            return ($this->abilities[$ability])($user, ...$arguments);
        }

        // No ability defined
        return null;
    }

    /**
     * Convert a method name to ability format (update -> update, viewAny -> view-any)
     */
    private function formatAbilityMethod(string $ability): string
    {
        // Convert kebab-case to camelCase
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $ability))));
    }

    /**
     * Resolve a policy class to an instance
     */
    private function resolvePolicyInstance(string $policyClass): object
    {
        if (!isset($this->policyInstances[$policyClass])) {
            $this->policyInstances[$policyClass] = new $policyClass();
        }

        return $this->policyInstances[$policyClass];
    }

    /**
     * Convert any result to a Response
     */
    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result === true) {
            return Response::allow();
        }

        if ($result === false || $result === null) {
            return Response::deny();
        }

        // Treat any other truthy value as allow
        return $result ? Response::allow() : Response::deny();
    }

    /**
     * Get all defined abilities
     * 
     * @return string[]
     */
    public function abilities(): array
    {
        return array_keys($this->abilities);
    }

    /**
     * Check if an ability is defined
     */
    public function has(string $ability): bool
    {
        return isset($this->abilities[$ability]);
    }
}
