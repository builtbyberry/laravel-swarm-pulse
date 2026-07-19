# Changelog

All notable changes to `builtbyberry/laravel-swarm-pulse` are documented here.

## v0.1.3 - 2026-07-19

### Fixed

- Widened the `builtbyberry/laravel-swarm` constraint to `^0.17 || ^0.18 ||
  ^0.19 || ^0.20 || ^0.21 || ^0.22`. The package was pinned to `^0.17`, so it
  could not be installed alongside core releases 0.18 through 0.22 —
  `composer require builtbyberry/laravel-swarm-pulse` failed outright for
  anyone on a current core, including the install command published in the
  documentation.
- No behavioural change. Every core symbol this package uses is unchanged, and
  the full suite (23 tests, 123 assertions) passes against core v0.22.0.

## v0.1.2 - 2026-07-06

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
