<?php declare(strict_types=1);

namespace App\Modules\Validation;

use App\Modules\ModuleVersion;
use InvalidArgumentException;
use RuntimeException;

/**
 * Handles validation of module versions and requirements.
 */
class VersionValidator
{
    /**
     * Validate PHP version requirements.
     *
     * @param string $requiredVersion Required PHP version constraint
     * @throws RuntimeException If the PHP version requirement is not met
     */
    public function validatePhpVersion(string $requiredVersion): void
    {
        if (!version_compare(PHP_VERSION, $requiredVersion, '>=')) {
            throw new RuntimeException(
                sprintf('PHP %s or higher is required (current: %s)', $requiredVersion, PHP_VERSION)
            );
        }
    }

    /**
     * Validate PHP extension requirements.
     *
     * @param array<string,string> $requirements Map of requirements (e.g., ['ext-pdo' => '*'])
     * @throws RuntimeException If any extension requirement is not met
     */
    public function validateExtensions(array $requirements): void
    {
        foreach ($requirements as $requirement => $constraint) {
            if (!str_starts_with($requirement, 'ext-')) {
                continue;
            }

            $ext = substr($requirement, 4);
            if (!extension_loaded($ext)) {
                throw new RuntimeException(sprintf('Required PHP extension "%s" is not installed', $ext));
            }

            // Skip version check if no constraint is specified
            if ($constraint === '*' || $constraint === '') {
                continue;
            }

            $extVersion = phpversion($ext) ?: '0';
            if (!version_compare($extVersion, $constraint, '>=')) {
                throw new RuntimeException(
                    sprintf(
                        'Extension %s version %s or higher is required (installed: %s)',
                        $ext,
                        $constraint,
                        $extVersion
                    )
                );
            }
        }
    }

    /**
     * Validate a version constraint string.
     *
     * @param string $constraint Version constraint (e.g., '^1.0', '>=2.0 <3.0')
     * @throws InvalidArgumentException If the constraint is invalid
     */
    public function validateVersionConstraint(string $constraint): void
    {
        if ($constraint === '' || $constraint === '*') {
            return;
        }

        // Simple validation - could be enhanced with a proper parser
        $pattern = '/^(?:[<>=~^]|>=?|<=?|!=|\|\|?|\s*-\s*|\s*\|\|?\s*|\s*,\s*|\d+(?:\.\d+)*(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?(?:\+[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?)+$/';
        
        if (!preg_match($pattern, $constraint)) {
            throw new InvalidArgumentException(sprintf('Invalid version constraint: %s', $constraint));
        }
    }

    /**
     * Check if a version satisfies a constraint.
     *
     * @param string $version Version to check
     * @param string $constraint Version constraint
     * @return bool True if the version satisfies the constraint
     */
    public function satisfies(string $version, string $constraint): bool
    {
        try {
            $version = new ModuleVersion($version);
            return $version->satisfies($constraint);
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
