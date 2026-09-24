# Laravel Swarm Pulse

[Laravel Pulse](https://laravel.com/docs/pulse) integration for
[Laravel Swarm](https://github.com/builtbyberry/laravel-swarm) — recorders and
dashboard cards for swarm runs, step durations, memory growth, and audit
outbox observability.

This package was extracted from `builtbyberry/laravel-swarm` core in v0.17.1
to keep the core package's dependency footprint small. If you were using
Pulse recorders or cards from `laravel-swarm` before v0.17.1, install this
package and update your imports — see
[UPGRADING.md](https://github.com/builtbyberry/laravel-swarm/blob/main/UPGRADING.md)
in core for the migration steps.

## Requirements

- PHP 8.4+
- `builtbyberry/laravel-swarm` ^0.22 through ^0.27
- Livewire ^3.0 or ^4.1

## Installation

```bash
composer require builtbyberry/laravel-swarm-pulse
```

This pulls in `laravel/pulse` as a dependency. Then publish and migrate
Pulse, and run the swarm installer:

```bash
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"

php artisan migrate

php artisan swarm:install:pulse
```

`swarm:install:pulse` registers the `SwarmRuns`, `SwarmStepDurations`, and
`SwarmMemoryMetrics` recorders in `config/pulse.php` and injects the swarm
cards into `resources/views/vendor/pulse/dashboard.blade.php`. Both edits are
sentinel-fenced and safe to re-run; the original files are backed up to
`<file>.bak` before the first mutation.

Pick which cards to enable with `--cards` (default: all four):

```bash
php artisan swarm:install:pulse --no-interaction --cards=runs,steps
```

If Pulse is not installed, the command refuses with a copy-paste hint and
exits non-zero.

## Cards

| Card | Livewire tag | What it shows |
| --- | --- | --- |
| Swarm Runs | `<livewire:swarm.runs />` | Per-swarm totals, failures, failure rate, average run duration, topology mix |
| Swarm Steps | `<livewire:swarm.steps />` | Slowest average step durations by swarm + agent |
| Swarm Audit Outbox | `<livewire:swarm.audit-outbox />` | Live operational state of the audit outbox (pending, dead-letter, stale) |
| Swarm Memory | `<livewire:swarm.memory />` | Memory growth + snapshot size per scope |

See core's `docs/pulse.md` for the full aggregate-type reference, tuning
knobs, and troubleshooting guide — this package's behavior is unchanged from
the pre-extraction integration, only the namespace and package boundary moved.

## License

MIT.

## Compatibility checks

CI retains a published Laravel Swarm v0.25.0 baseline and lowest-dependency coverage on PHP 8.4 and 8.5, including Livewire 3 and 4. The v0.26 adoption lanes exercise the recorders, dashboard cards, audit outbox, memory metrics, and installer against the pinned core candidate `e25842cab4291837dcce2ff6f4815e58feab9079`, with official Laravel AI v0.11.2 and current stable 0.11.x dependencies.

These adoption lanes use temporary Composer package metadata for the candidate; they are not proof that v0.26 is published or installable from released packages. The committed package manifest retains ordinary version constraints. Published ecosystem installability and the core post-main moving-dev gate remain separate follow-up checks.

The v0.27 / Laravel AI 1 lanes use the frozen core candidate
`48ad4ef690363ca40ba7d3bd50e63e7fbe76ba4b`, with official AI 1.0.0 minimum
and current stable 1.x dependencies on PHP 8.4 and 8.5. They run the same
recorders, aggregate, rendered-card and installer tests. The eight earlier
compatibility jobs remain, for twelve jobs overall. Core v0.27 owns the
application upgrade and native conversation migration contract; follow its
upgrade guide when moving an application from an earlier core line.

Pulse's integration remains aggregate observability of runs, steps, memory
and audit outbox. This additive dependency update introduces no token billing,
new schema or configuration setting. The candidate jobs use temporary package
metadata and do not establish published installation of the five-package ecosystem.
