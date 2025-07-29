<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Attributes;

use Attribute;

/**
 * Injectable Annotation - Marks classes for dependency injection
 * 
 * @reference .windsurfrules → SwarmUnit Standards
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Injectable
{
    /**
     * @param array $dependencies List of dependency class names
     * @param bool $singleton Whether to treat as singleton
     */
    public function __construct(
        public readonly array $dependencies = [],
        public readonly bool $singleton = false
    ) {}
}
