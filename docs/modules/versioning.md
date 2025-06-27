# Module Versioning & Dependency Constraints

This document explains **how to declare module versions and dependency constraints** in Infinri and how the `DependencyResolver` enforces them.

## Why version modules?

* Provide **backwards-compatible evolution** of features.
* Allow modules to express which versions of their dependencies they support.
* Let the runtime refuse to boot when an **incompatible** combination is detected.

> Infinri re-uses Composer’s [Semver](https://getcomposer.org/doc/articles/versions.md) implementation for constraint parsing.

---

## Declaring a version

In your module class (extending `BaseModule`) implement `getMetadata()` and set your version:

```php
use App\Modules\ValueObject\VersionConstraint;

public function getMetadata(): ModuleMetadata
{
    return new ModuleMetadata(
        name: 'Blog',
        version: '1.3.0',
        requires: [
            'Core' => new VersionConstraint('^2.0'),          // caret range
            'User' => new VersionConstraint('~1.1 || ^1.2'), // multi-range
        ],
        optional: [
            'Search' => new VersionConstraint('>=0.5'),
        ],
        conflicts: [
            'Analytics' => new VersionConstraint('<2.0'),
        ],
    );
}
```

### Constraint formats

| Example            | Meaning                                    |
|--------------------|--------------------------------------------|
| `1.2.3`            | **Exact** version                          |
| `^1.2`             | `>=1.2.0 <2.0.0`                           |
| `~1.2`             | `>=1.2.0 <1.3.0`                           |
| `>=1.4 <2.0`       | range                                      |
| `1.*` or `1.x`     | wildcard                                   |
| `dev-main`         | branch alias                               |
| `^1.0 || ^2.0`     | logical OR of constraints                  |

See Composer docs for the full grammar.

### Optional dependencies

If a module is _nice to have_ but not required, declare it in the `optional` array. The resolver will:

1. Load the module if present **and** satisfies the constraint.
2. Skip it silently if missing.
3. Fail boot if present but **incompatible**.

### Conflicts

A `conflicts` entry prevents two modules from loading **together** when their versions overlap.

```php
'LegacyAuth' => new VersionConstraint('*') // conflicts with any version
```

## Runtime enforcement

During bootstrap the `DependencyResolver`:

1. Builds the module graph.
2. Evaluates `requires` / `optional` against installed versions.
3. Detects and throws `DependencyException` on unsatisfied constraints or conflicts.
4. Caches the resolved graph (coming soon).

## Best practices

* Bump the **major** version for breaking changes.
* Keep the public API stable within a major series.
* Use **conflicts** to prevent two competing modules from loading.
* Document your module’s version history in its README.

---

_Last updated: 2025-06-26_
