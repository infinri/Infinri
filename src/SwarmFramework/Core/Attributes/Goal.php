<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Attributes;

use Attribute;
use Infinri\SwarmFramework\Exceptions\InvalidGoalException;

/**
 * Goal Annotation - Describes the unit's purpose
 * 
 * @reference infinri_blueprint.md → Functional Requirements
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Goal
{
    /**
     * @param string $description Human-readable goal description
     * @param array $requirements List of functional requirement IDs (FR-XXX-XXX)
     * @param int $priority Goal priority (1-10, higher is more important)
     */
    public function __construct(
        public readonly string $description,
        public readonly array $requirements = [],
        public readonly int $priority = 5
    ) {
        if (empty($this->description)) {
            throw new InvalidGoalException('Goal description cannot be empty');
        }

        if ($this->priority < 1 || $this->priority > 10) {
            throw new InvalidGoalException('Goal priority must be between 1 and 10');
        }

        foreach ($this->requirements as $requirement) {
            if (!preg_match('/^FR-[A-Z]+-\d{3}$/', $requirement)) {
                throw new InvalidGoalException("Invalid requirement format: {$requirement}");
            }
        }
    }
}
