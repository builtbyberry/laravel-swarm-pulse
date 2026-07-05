<?php

declare(strict_types=1);

namespace BuiltByBerry\LaravelSwarmPulse;

use BuiltByBerry\LaravelSwarmPulse\Commands\InstallPulseCommand;
use BuiltByBerry\LaravelSwarmPulse\Livewire\AuditOutbox as AuditOutboxCard;
use BuiltByBerry\LaravelSwarmPulse\Livewire\SwarmMemory as SwarmMemoryCard;
use BuiltByBerry\LaravelSwarmPulse\Livewire\SwarmRuns;
use BuiltByBerry\LaravelSwarmPulse\Livewire\SwarmSteps;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireManager;

class PulseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'swarm-pulse');

        $this->callAfterResolving('livewire', function (LivewireManager $livewire): void {
            $livewire->component('swarm.runs', SwarmRuns::class);
            $livewire->component('swarm.steps', SwarmSteps::class);
            $livewire->component('swarm.audit-outbox', AuditOutboxCard::class);
            $livewire->component('swarm.memory', SwarmMemoryCard::class);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPulseCommand::class,
            ]);
        }
    }
}
