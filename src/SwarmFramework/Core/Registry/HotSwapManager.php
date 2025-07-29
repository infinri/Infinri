<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Registry;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Infinri\SwarmFramework\Core\Registry\SwapOrchestrator;
use Psr\Log\LoggerInterface;

/**
 * Hot Swap Manager - Clean facade for hot-swap operations
 * 
 * Delegates to specialized components following single responsibility.
 * Reduced from 504 lines to focused coordination logic.
 */
#[Injectable(dependencies: ['LoggerInterface', 'SwapOrchestrator'])]
final class HotSwapManager
{
    use LoggerTrait;
    private SwapOrchestrator $orchestrator;

    public function __construct(LoggerInterface $logger, SwapOrchestrator $orchestrator)
    {
        $this->logger = $logger;
        $this->orchestrator = $orchestrator;
    }

    /**
     * Perform hot swap of a module
     */
    public function swapModule(string $moduleName, string $newModulePath, array &$registeredModules): ValidationResult
    {
        return $this->orchestrator->executeSwap($moduleName, $newModulePath, $registeredModules);
    }

    /**
     * Get swap history
     */
    public function getSwapHistory(): array
    {
        return $this->orchestrator->getSwapHistory();
    }

    /**
     * Get swap statistics
     */
    public function getSwapStats(): array
    {
        return $this->orchestrator->getSwapStats();
    }

    /**
     * Check for active swaps
     */
    public function hasActiveSwaps(): bool
    {
        return $this->orchestrator->hasActiveSwaps();
    }
}
