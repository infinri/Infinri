# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Module Event System**: New PSR-14 compliant event system for module lifecycle management
  - `ModuleEvent` base class and specialized event classes for different lifecycle stages
  - `ModuleEventDispatcher` implementing PSR-14 `EventDispatcherInterface`
  - `ModuleListenerProvider` for managing event listeners
  - Comprehensive documentation in `docs/events/README.md`
  - Migration guide in `docs/events/MIGRATION.md`

### Changed
- **ModuleManager**: Updated to dispatch events during module lifecycle
  - Module registration events
  - Dependency resolution events
  - Module boot events
- **Dependency Injection**: Added `EventServiceProvider` for registering event services

### Deprecated
- Direct module method calls for lifecycle management in favor of event-driven approach
- Custom event dispatching systems should migrate to the new PSR-14 implementation

## [1.0.0] - YYYY-MM-DD

### Added
- Initial project structure
- Basic module system with registration and bootstrapping
- Dependency injection container configuration
- Core module interfaces and implementations

[Unreleased]: https://github.com/yourusername/yourproject/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/yourusername/yourproject/releases/tag/v1.0.0
