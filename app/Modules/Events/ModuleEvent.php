<?php declare(strict_types=1);

namespace App\Modules\Events;

use App\Modules\ModuleInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base class for module-related events
 */
class ModuleEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;
    private ?string $error = null;

    public function __construct(
        private ModuleInterface $module,
        private array $arguments = []
    ) {}

    public function getModule(): ModuleInterface
    {
        return $this->module;
    }

    public function getArgument(string $key, mixed $default = null): mixed
    {
        return $this->arguments[$key] ?? $default;
    }

    public function setArgument(string $key, mixed $value): void
    {
        $this->arguments[$key] = $value;
    }

    public function hasArgument(string $key): bool
    {
        return array_key_exists($key, $this->arguments);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
