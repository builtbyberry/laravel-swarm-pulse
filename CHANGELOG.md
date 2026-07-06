# Changelog

All notable changes to `builtbyberry/laravel-swarm-pulse` are documented here.

## [Unreleased]

### Fixed

- Corrected the documented extraction version from v0.17.0 to v0.17.1 in the
  README and this changelog. v0.17.0 was a mistagged no-op release; the Pulse
  extraction actually shipped in `builtbyberry/laravel-swarm` v0.17.1.
- Removed the redundant `composer require laravel/pulse` step from the README
  install instructions — `laravel/pulse` is a hard dependency in
  `composer.json`, so it is installed transitively with this package.

## v0.1.1 - 2026-07-06

### Fixed

- Added `phpunit.xml` and `phpstan.neon` so CI can run the test suite and
  static analysis.

## v0.1.0 - 2026-07-05

### Added

- Initial release. Extracted from `builtbyberry/laravel-swarm` core v0.17.1:
  the `SwarmRuns`, `SwarmStepDurations`, and `SwarmMemoryMetrics` Pulse
  recorders, the `swarm.runs` / `swarm.steps` / `swarm.audit-outbox` /
  `swarm.memory` dashboard cards, and the `swarm:install:pulse` Artisan
  command. Namespace changed from `BuiltByBerry\LaravelSwarm\Pulse\*` to
  `BuiltByBerry\LaravelSwarmPulse\*`.
