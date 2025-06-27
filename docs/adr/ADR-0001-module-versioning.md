# ADR-0001: Introduce Module Versioning & Dependency Constraints

Date: 2025-06-26

## Status
Accepted

## Context

Infinri’s module system originally allowed modules to declare dependencies but lacked a formal concept of **module versions** or **version constraints**. This made backward-compatible evolution difficult and offered no protection against loading incompatible modules at runtime.

## Decision

1. **VersionConstraint Value Object** based on Composer SemVer is added to parse & evaluate constraints.
2. `ModuleMetadata` now contains:
   * `version: string` – module’s own version
   * `requires: array<string,VersionConstraint>` – hard dependencies
   * `optional: array<string,VersionConstraint>` – soft dependencies
   * `conflicts: array<string,VersionConstraint>` – mutually-exclusive modules
3. `DependencyResolver` enforces constraints during bootstrap and aborts with a descriptive exception when unsatisfied.
4. Tests cover positive & negative scenarios; email tests mock external HTTP calls to remain CI-safe.
5. Documentation updated (`README`, `docs/modules/versioning.md`, dependency-resolution doc) to guide adopters.

## Consequences

* Modules can evolve independently following Semantic Versioning.
* Breaking changes require a major version bump; downstream modules specify compatible ranges.
* Resolver performance impact is minimal (Composer SemVer library is highly optimised).
* Future work: cache resolved graphs; tooling to visualise version compatibility.

## Alternatives Considered

* **No versioning** – rejected; doesn’t scale.
* **Custom constraint syntax** – would duplicate Composer semantics.

## References
* [docs/modules/versioning.md](../modules/versioning.md)
* [Composer SemVer docs](https://getcomposer.org/doc/articles/versions.md)
