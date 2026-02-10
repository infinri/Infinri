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
namespace App\Core\Container;

use App\Core\Error\Concerns\LogsExceptions;
use Exception;

/**
 * Binding Resolution Exception
 */
class BindingResolutionException extends Exception
{
    use LogsExceptions;

    protected string $abstract;
    protected ?string $reason;

    public function __construct(string $message, string $abstract = '', ?string $reason = null)
    {
        parent::__construct($message);
        $this->abstract = $abstract;
        $this->reason = $reason;

        $this->logException('error', 'Container binding resolution failed', [
            'abstract' => $abstract,
            'reason' => $reason,
            ...$this->getExceptionContext(),
        ]);
    }

    public static function unresolvable(string $abstract, ?string $message = null): static
    {
        return new static($message ?? "Unable to resolve binding for [{$abstract}]", $abstract, $message);
    }

    public static function circularDependency(string $abstract): static
    {
        return new static("Circular dependency detected while resolving [{$abstract}]", $abstract, 'circular_dependency');
    }

    public static function uninstantiable(string $abstract, ?string $reason = null): static
    {
        $message = "Target [{$abstract}] is not instantiable" . ($reason !== null ? ": {$reason}" : '');

        return new static($message, $abstract, $reason);
    }

    public function getAbstract(): string
    {
        return $this->abstract;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
