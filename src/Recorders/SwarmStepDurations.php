<?php

declare(strict_types=1);

namespace BuiltByBerry\LaravelSwarmPulse\Recorders;

use BuiltByBerry\LaravelSwarm\Events\SwarmStepCompleted;
use BuiltByBerry\LaravelSwarmPulse\Support\SwarmPulseKey;
use Carbon\CarbonImmutable;
use Laravel\Pulse\Pulse;

/**
 * Pulse recorder for average swarm step duration by swarm, topology, and
 * agent.
 *
 * Registered by class string in config/pulse.php.
 */
class SwarmStepDurations
{
    /**
     * @var list<class-string>
     */
    public array $listen = [
        SwarmStepCompleted::class,
    ];

    public function __construct(
        protected Pulse $pulse,
    ) {}

    public function record(SwarmStepCompleted $event): void
    {
        $timestamp = CarbonImmutable::now()->getTimestamp();

        $this->pulse->lazy(function () use ($event, $timestamp): void {
            $this->pulse->record(
                type: 'swarm_step_duration',
                key: SwarmPulseKey::stepDuration($event->swarmClass, $event->topology, $event->agentClass),
                value: $event->durationMs,
                timestamp: $timestamp,
            )->avg()->count();
        });
    }
}
