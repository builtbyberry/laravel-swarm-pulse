<?php

declare(strict_types=1);

use BuiltByBerry\LaravelSwarm\Events\SwarmCompleted;
use BuiltByBerry\LaravelSwarm\Events\SwarmFailed;
use BuiltByBerry\LaravelSwarm\Events\SwarmStepCompleted;
use BuiltByBerry\LaravelSwarmPulse\Livewire\SwarmRuns as SwarmRunsCard;
use BuiltByBerry\LaravelSwarmPulse\Recorders\SwarmRuns as SwarmRunsRecorder;
use BuiltByBerry\LaravelSwarmPulse\Recorders\SwarmStepDurations;
use BuiltByBerry\LaravelSwarmPulse\Support\SwarmPulseKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Facades\Pulse as PulseFacade;
use Laravel\Pulse\Pulse;
use Livewire\Livewire;

const EXAMPLE_SWARM_CLASS = 'App\\Ai\\Swarms\\ExampleSwarm';

const EXAMPLE_AGENT_CLASS = 'App\\Ai\\Agents\\ExampleWriter';

beforeEach(function () {
    config()->set('database.default', 'testing');

    PulseFacade::purge();
    PulseFacade::flush();
    app(Pulse::class)->register([
        SwarmRunsRecorder::class => ['enabled' => true],
        SwarmStepDurations::class => ['enabled' => true],
    ]);
});

test('pulse recorders store stable swarm entry keys', function () {
    event(new SwarmCompleted(
        runId: 'pulse-task-run',
        swarmClass: EXAMPLE_SWARM_CLASS,
        topology: 'sequential',
        output: 'done',
        durationMs: 42,
    ));

    event(new SwarmStepCompleted(
        runId: 'pulse-task-run',
        swarmClass: EXAMPLE_SWARM_CLASS,
        topology: 'sequential',
        index: 0,
        agentClass: EXAMPLE_AGENT_CLASS,
        input: 'writer-task',
        output: 'writer-out',
        durationMs: 10,
    ));

    PulseFacade::ingest();

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_run')->pluck('key')->all())
        ->toContain('swarm_run|'.EXAMPLE_SWARM_CLASS.'|sequential|completed');

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_run_duration')->pluck('key')->all())
        ->toContain('swarm_run_duration|'.EXAMPLE_SWARM_CLASS.'|sequential');

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_run_duration_total')->pluck('key')->all())
        ->toContain(EXAMPLE_SWARM_CLASS);

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_run_total')->pluck('key')->all())
        ->toContain(EXAMPLE_SWARM_CLASS);

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_run_duration_total_ms')->pluck('key')->all())
        ->toContain(EXAMPLE_SWARM_CLASS);

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_run_duration_samples')->pluck('key')->all())
        ->toContain(EXAMPLE_SWARM_CLASS);

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_step_duration')->pluck('key')->all())
        ->toContain('swarm_step_duration|'.EXAMPLE_SWARM_CLASS.'|sequential|'.EXAMPLE_AGENT_CLASS);
});

test('swarm runs card keeps per swarm metrics accurate when raw pulse keys exceed the old regrouping limit', function () {
    $timestamp = now()->getTimestamp();

    for ($i = 1; $i <= 600; $i++) {
        PulseFacade::record('swarm_run_total', EXAMPLE_SWARM_CLASS, value: 1, timestamp: $timestamp)->sum();
        PulseFacade::record('swarm_topology_sequential', EXAMPLE_SWARM_CLASS, value: 1, timestamp: $timestamp)->sum();
        PulseFacade::record('swarm_run_duration_total', EXAMPLE_SWARM_CLASS, value: 10, timestamp: $timestamp)->avg()->count();
        PulseFacade::record('swarm_run_duration_total_ms', EXAMPLE_SWARM_CLASS, value: 10, timestamp: $timestamp)->sum();
        PulseFacade::record('swarm_run_duration_samples', EXAMPLE_SWARM_CLASS, value: 1, timestamp: $timestamp)->sum();
        PulseFacade::record('swarm_run', SwarmPulseKey::runStatus(EXAMPLE_SWARM_CLASS, 'sequential', 'completed'), timestamp: $timestamp)->count()->onlyBuckets();
        PulseFacade::record('swarm_run_duration', SwarmPulseKey::runDuration(EXAMPLE_SWARM_CLASS, 'sequential'), value: 10, timestamp: $timestamp)->avg()->count()->onlyBuckets();
    }

    PulseFacade::record('swarm_run_total', EXAMPLE_SWARM_CLASS, value: 1, timestamp: $timestamp)->sum();
    PulseFacade::record('swarm_run_failed', EXAMPLE_SWARM_CLASS, value: 1, timestamp: $timestamp)->sum();
    PulseFacade::record('swarm_topology_parallel', EXAMPLE_SWARM_CLASS, value: 1, timestamp: $timestamp)->sum();
    PulseFacade::record('swarm_run_duration_total', EXAMPLE_SWARM_CLASS, value: 1000, timestamp: $timestamp)->avg()->count();
    PulseFacade::record('swarm_run_duration_total_ms', EXAMPLE_SWARM_CLASS, value: 1000, timestamp: $timestamp)->sum();
    PulseFacade::record('swarm_run_duration_samples', EXAMPLE_SWARM_CLASS, value: 1, timestamp: $timestamp)->sum();
    PulseFacade::record('swarm_run', SwarmPulseKey::runStatus(EXAMPLE_SWARM_CLASS, 'parallel', 'failed'), timestamp: $timestamp)->count()->onlyBuckets();
    PulseFacade::record('swarm_run_duration', SwarmPulseKey::runDuration(EXAMPLE_SWARM_CLASS, 'parallel'), value: 1000, timestamp: $timestamp)->avg()->count()->onlyBuckets();

    for ($i = 1; $i <= 500; $i++) {
        $swarmClass = "App\\Ai\\Swarms\\DistractorSwarm{$i}";

        for ($count = 1; $count <= 2; $count++) {
            PulseFacade::record('swarm_run', SwarmPulseKey::runStatus($swarmClass, 'sequential', 'completed'), timestamp: $timestamp)->count()->onlyBuckets();
            PulseFacade::record('swarm_run_duration', SwarmPulseKey::runDuration($swarmClass, 'sequential'), value: 50, timestamp: $timestamp)->avg()->count()->onlyBuckets();
        }
    }

    PulseFacade::ingest();

    $card = new class extends SwarmRunsCard
    {
        public function snapshot(): Collection
        {
            return $this->resolveRuns();
        }
    };

    $run = $card->snapshot()->firstWhere('swarmClass', EXAMPLE_SWARM_CLASS);

    expect($run)->not()->toBeNull()
        ->and($run->totalRuns)->toBe(601)
        ->and($run->failures)->toBe(1)
        ->and($run->failureRate)->toBe(0.2)
        ->and($run->averageRunDurationMs)->toBe(12)
        ->and($run->topologyMix->mapWithKeys(fn (object $topology) => [$topology->topology => $topology->count])->all())
        ->toBe(['sequential' => 600, 'parallel' => 1]);
});

test('pulse cards render through the registered livewire aliases', function () {
    event(new SwarmCompleted(
        runId: 'pulse-task-run',
        swarmClass: EXAMPLE_SWARM_CLASS,
        topology: 'sequential',
        output: 'done',
        durationMs: 42,
    ));

    event(new SwarmStepCompleted(
        runId: 'pulse-task-run',
        swarmClass: EXAMPLE_SWARM_CLASS,
        topology: 'sequential',
        index: 0,
        agentClass: EXAMPLE_AGENT_CLASS,
        input: 'writer-task',
        output: 'writer-out',
        durationMs: 10,
    ));

    PulseFacade::ingest();

    Livewire::withoutLazyLoading();

    Livewire::test('swarm.runs')
        ->assertSee('Swarm Runs');

    Livewire::withoutLazyLoading();

    Livewire::test('swarm.steps')
        ->assertSee('Swarm Steps');
});

test('pulse recorders capture failed swarm runs with the failed status key', function () {
    event(new SwarmFailed(
        runId: 'pulse-failed-run',
        swarmClass: EXAMPLE_SWARM_CLASS,
        topology: 'sequential',
        exception: new RuntimeException('Queued swarm failed.'),
        durationMs: 15,
    ));

    PulseFacade::ingest();

    expect(DB::table('pulse_aggregates')->where('type', 'swarm_run')->pluck('key')->all())
        ->toContain('swarm_run|'.EXAMPLE_SWARM_CLASS.'|sequential|failed');
});
