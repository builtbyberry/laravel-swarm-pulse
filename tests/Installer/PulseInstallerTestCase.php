<?php

declare(strict_types=1);

namespace BuiltByBerry\LaravelSwarmPulse\Tests\Installer;

use BuiltByBerry\LaravelSwarmInstallerTestkit\InstallerTestCase;
use BuiltByBerry\LaravelSwarmPulse\PulseServiceProvider;
use Illuminate\Foundation\Application;
use Laravel\Pulse\PulseServiceProvider as VendorPulseServiceProvider;
use Livewire\LivewireServiceProvider;

/**
 * Pulse-specific specialization of the shared installer-test harness.
 *
 * Supplies the service providers `swarm:install:pulse` needs to boot. See
 * `builtbyberry/laravel-swarm-installer-testkit`'s own README for the
 * harness mechanics (skeleton materialization, assertions, idempotency
 * helper).
 */
abstract class PulseInstallerTestCase extends InstallerTestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            VendorPulseServiceProvider::class,
            PulseServiceProvider::class,
        ];
    }
}
