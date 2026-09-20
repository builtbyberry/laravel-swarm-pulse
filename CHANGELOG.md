# Changelog

All notable changes to `builtbyberry/laravel-swarm-pulse` are documented here.

## Unreleased

- Register the Pulse service provider in the development Testbench configuration so Larastan can discover the package views during analysis.

- Allow Laravel Swarm `^0.26` alongside the existing `^0.22`–`^0.25` ranges without changing PHP, Laravel, Pulse, or Livewire requirements.
- Preserve lowest-dependency and published v0.25.0 CI coverage; add pinned v0.26 candidate checks with official Laravel AI v0.11.2 and current stable 0.11.x dependencies. Candidate checks do not establish published-package installability.

## v0.1.6 - 2026-09-03

### Changed

- Widens the Livewire compatibility range from `^3.0` to `^3.0 || ^4.1`.
  Laravel 13 applications can therefore install this package alongside
  Filament 5.7, which requires Livewire 4.1. The Pulse cards use APIs shared by
  both supported Livewire majors; CI runs the complete suite on PHP 8.4 and
  8.5 against Livewire 4 in the stable-latest lanes and Livewire 3 in the
  lowest-dependency lanes.
- Removes the temporary pre-tag Composer VCS bootstrap now that Laravel Swarm
  v0.25.0 is published. Stable-latest CI resolves the Packagist release and
  verifies its exact source commit.

## v0.1.5 - 2026-09-03

### Changed

- Supports PHP `^8.4` and extends the verified Laravel Swarm range through
  `^0.25`, retaining the v0.1.4 floor of `^0.22`.
- CI covers PHP 8.4 and 8.5 against latest and lowest dependency sets. The
  recorders, cards, and installer behavior are unchanged.

## v0.1.4 - 2026-07-21

### Fixed

- Widened the `builtbyberry/laravel-swarm` constraint to `^0.22 || ^0.23` so the
  package installs alongside core v0.23.0 ("Compatibility and Correctness"). The
  previous `^0.17 || … || ^0.22` capped at 0.22 and blocked any application on
  current core. The supported floor is now 0.22: an application still on core
  0.17–0.21 keeps resolving to v0.1.3 (Composer picks the highest release
  satisfying both constraints), so nothing regresses — it simply does not
  receive this release.
- No behavioural change. v0.23 adds no public API, and its one breaking change
  (custom `MemoryPropagationPolicy` re-typing its `present()` agent parameter)
  is not a surface this package touches. The full suite (23 tests, 123
  assertions) passes against core v0.23.0.

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
