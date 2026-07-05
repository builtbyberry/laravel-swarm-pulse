# Laravel Swarm Pulse

[Laravel Pulse](https://laravel.com/docs/pulse) integration for
[Laravel Swarm](https://github.com/builtbyberry/laravel-swarm) — recorders and
dashboard cards for swarm runs, step durations, memory growth, and audit
outbox observability.

This package was extracted from `builtbyberry/laravel-swarm` core in v0.17.0
to keep the core package's dependency footprint small. If you were using
Pulse recorders or cards from `laravel-swarm` before v0.17.0, install this
package and update your imports — see
[UPGRADING.md](https://github.com/builtbyberry/laravel-swarm/blob/main/UPGRADING.md)
in core for the migration steps.

## Installation

```bash
composer require builtbyberry/laravel-swarm-pulse
```

Then, once Pulse itself is installed and published:

```bash
composer require laravel/pulse

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
