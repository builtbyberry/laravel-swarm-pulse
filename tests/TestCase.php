<?php

declare(strict_types=1);

namespace BuiltByBerry\LaravelSwarmPulse\Tests;

use BuiltByBerry\LaravelSwarm\SwarmServiceProvider;
use BuiltByBerry\LaravelSwarmPulse\PulseServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Laravel\Ai\AiServiceProvider;
use Laravel\Pulse\PulseServiceProvider as VendorPulseServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'testing');
        $this->app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        Artisan::call('migrate:fresh', ['--database' => 'testing']);
        Artisan::call('migrate', [
            '--database' => 'testing',
            '--path' => __DIR__.'/../vendor/laravel/pulse/database/migrations',
            '--realpath' => true,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
            SwarmServiceProvider::class,
            LivewireServiceProvider::class,
            VendorPulseServiceProvider::class,
            PulseServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('pulse.enabled', true);
        $app['config']->set('pulse.storage.database.connection', 'testing');
        $app['config']->set('pulse.ingest.driver', 'storage');
    }
}
