<?php declare(strict_types=1);

namespace App\Modules\ValueObject;

use App\Modules\Validation\VersionValidator;
use InvalidArgumentException;

/**
 * Immutable value object representing a version constraint (semver range).
 *
 * This is syntactic sugar around a raw constraint string. It validates the
 * string on construction (using VersionValidator) and provides a convenient
 * `matches()` helper for checking if a concrete version is allowed.
 *
 * The syntax follows Composer-style SemVer constraints, e.g.:
 *   ^1.2    >=1.2.0 <2.0.0
 *   ~2.1    >=2.1.0 <2.2.0
 *   >=1.0 <2.0
 *   *       any version
 */
final class VersionConstraint implements \Stringable
{
    private string $constraint;
    private VersionValidator $validator;

    public function __construct(string $constraint, ?VersionValidator $validator = null)
    {
        $this->validator = $validator ?? new VersionValidator();

        // Basic sanity check (throws on invalid pattern)
        $this->validator->validateVersionConstraint($constraint);
        $this->constraint = $constraint;
    }

    /**
     * Check whether the given version satisfies this constraint.
     */
    public function matches(string $version): bool
    {
        return $this->validator->satisfies($version, $this->constraint);
    }

    public function __toString(): string
    {
        return $this->constraint;
    }
}
