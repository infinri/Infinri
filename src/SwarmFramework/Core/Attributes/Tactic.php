<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Attributes;

use Attribute;
use Infinri\SwarmFramework\Exceptions\InvalidTacticException;

/**
 * Tactic Annotation - Links units to architectural tactics
 * 
 * @reference infinri_blueprint.md → Tactic Tags (TAC-XXX-XXX)
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Tactic
{
    public readonly array $tactics;
    
    /**
     * @param string ...$tactics List of tactic identifiers (e.g., 'TAC-PERF-001')
     */
    public function __construct(string ...$tactics)
    {
        foreach ($tactics as $tactic) {
            if (!preg_match('/^TAC-[A-Z]+-\d{3}$/', $tactic)) {
                throw new InvalidTacticException("Invalid tactic format: {$tactic}");
            }
        }
        
        $this->tactics = $tactics;
    }

    /**
     * Get all tactics as array
     * 
     * @return array List of tactic identifiers
     */
    public function getTactics(): array
    {
        return $this->tactics;
    }
}
