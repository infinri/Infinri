<?php declare(strict_types=1);

namespace App\Modules;

use InvalidArgumentException;

/**
 * Represents a module version with semantic versioning support
 */
class ModuleVersion implements \Stringable
{
    private const VERSION_PATTERN = '/^v?(\d+)\.(\d+)\.(\d+)(?:-([0-9A-Za-z-\.]+))?(?:\+([0-9A-Za-z-\.]+))?$/';
    
    private int $major;
    private int $minor;
    private int $patch;
    private ?string $preRelease = null;
    private ?string $build = null;

    public function __construct(string $version)
    {
        if (!preg_match(self::VERSION_PATTERN, $version, $matches)) {
            throw new InvalidArgumentException("Invalid version string: {$version}");
        }

        $this->major = (int)$matches[1];
        $this->minor = (int)$matches[2];
        $this->patch = (int)$matches[3];
        $this->preRelease = $matches[4] ?? null;
        $this->build = $matches[5] ?? null;
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    public function getPreRelease(): ?string
    {
        return $this->preRelease;
    }

    public function getBuild(): ?string
    {
        return $this->build;
    }

    public function __toString(): string
    {
        $version = "{$this->major}.{$this->minor}.{$this->patch}";
        
        if ($this->preRelease !== null) {
            $version .= "-{$this->preRelease}";
        }
        
        if ($this->build !== null) {
            $version .= "+{$this->build}";
        }
        
        return $version;
    }

    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function satisfies(string $constraint): bool
    {
        // Handle simple version constraints
        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        // Handle comparison operators
        $firstChar = $constraint[0];
        if (in_array($firstChar, ['>', '<', '=', '~', '^'], true)) {
            $operator = $firstChar;
            $base = substr($constraint, 1);
            $version = new self(self::normaliseVersionString($base));
            
            return match ($operator) {
                '>' => $this->greaterThan($version),
                '>=' => $this->greaterThan($version) || $this->equals($version),
                '<' => $this->lessThan($version),
                '<=' => $this->lessThan($version) || $this->equals($version),
                '=' => $this->equals($version),
                '~' => $this->satisfiesTilde($version),
                '^' => $this->satisfiesCaret($version),
            default => false,
            };
        }

        // Handle version range
        if (str_contains($constraint, ' - ')) {
            [$min, $max] = explode(' - ', $constraint, 2);
            return $this->satisfies(">={$min}") && $this->satisfies("<={$max}");
        }

        // Handle OR conditions
        if (str_contains($constraint, ' || ')) {
            foreach (explode(' || ', $constraint) as $c) {
                if ($this->satisfies(trim($c))) {
                    return true;
                }
            }
            return false;
        }

        // Default to exact match
        return $this->equals(new self($constraint));
    }

    private function compareTo(self $other): int
    {
        if ($this->major !== $other->major) {
            return $this->major <=> $other->major;
        }
        
        if ($this->minor !== $other->minor) {
            return $this->minor <=> $other->minor;
        }
        
        if ($this->patch !== $other->patch) {
            return $this->patch <=> $other->patch;
        }
        
        // Pre-release versions have lower precedence
        if ($this->preRelease !== $other->preRelease) {
            if ($this->preRelease === null) {
                return 1; // This version is greater (no pre-release)
            }
            if ($other->preRelease === null) {
                return -1; // Other version is greater (no pre-release)
            }
            
            // Compare pre-release identifiers
            return strcmp($this->preRelease, $other->preRelease);
        }
        
        return 0;
    }

    /**
     * Normalize a version string to full MAJOR.MINOR.PATCH format.
     * 1      -> 1.0.0
     * 1.2    -> 1.2.0
     * v1.2.3 -> 1.2.3
     */
    private static function normaliseVersionString(string $version): string
    {
        $version = ltrim($version, 'v');
        $parts = explode('.', $version);
        return match (count($parts)) {
            1 => $parts[0] . '.0.0',
            2 => $parts[0] . '.' . $parts[1] . '.0',
            default => $version,
        };
    }

    private function satisfiesTilde(self $version): bool
    {
        // ~1.2.3 is >=1.2.3 <1.3.0
        // ~1.2 is >=1.2.0 <2.0.0
        // ~1 is >=1.0.0 <2.0.0
        
        $min = clone $version;
        
        if ($version->patch !== 0) {
            // ~1.2.3 → >=1.2.3 <1.3.0
            $max = new self(sprintf('%d.%d.0', $version->major, $version->minor + 1));
        } elseif ($version->minor !== 0) {
            // ~1.2 → >=1.2.0 <2.0.0
            $max = new self(sprintf('%d.0.0', $version->major + 1));
        } else {
            // ~1 → >=1.0.0 <2.0.0
            $max = new self(sprintf('%d.0.0', $version->major + 1));
        }
        
        return ($this->greaterThan($min) || $this->equals($min)) && $this->lessThan($max);
    }

    private function satisfiesCaret(self $version): bool
    {
        // ^1.2.3 is >=1.2.3 <2.0.0
        // ^0.2.3 is >=0.2.3 <0.3.0
        // ^0.0.3 is >=0.0.3 <0.0.4
        
        $min = clone $version;
        
        if ($version->major > 0) {
            // ^1.2.3 → >=1.2.3 <2.0.0
            $max = new self(sprintf('%d.0.0', $version->major + 1));
        } elseif ($version->minor > 0) {
            // ^0.2.3 → >=0.2.3 <0.3.0
            $max = new self(sprintf('0.%d.0', $version->minor + 1));
        } else {
            // ^0.0.3 → >=0.0.3 <0.0.4
            $max = new self(sprintf('0.0.%d', $version->patch + 1));
        }
        
        return ($this->greaterThan($min) || $this->equals($min)) && $this->lessThan($max);
    }
}
