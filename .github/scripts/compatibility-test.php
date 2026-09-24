<?php

declare(strict_types=1);

namespace PulseCompatibility;

require __DIR__.'/compatibility.php';

function package(string $name, string $version, string $ref): array
{
    return [
        'name' => $name, 'version' => $version,
        'source' => ['type' => 'git', 'url' => "https://github.com/{$name}.git", 'reference' => $ref],
        'dist' => ['type' => 'zip', 'url' => "https://api.github.com/repos/{$name}/zipball/{$ref}", 'reference' => $ref],
    ];
}

function rejects(callable $callback, string $label): void
{
    try {
        $callback();
    } catch (\RuntimeException) {
        return;
    }
    throw new \RuntimeException("Guard accepted negative control: {$label}");
}

$root = readJson(__DIR__.'/../../composer.json');
$controls = 0;
foreach (LANES as $lane) {
    $native = str_starts_with($lane, 'native1-');
    $adoption = $native || str_starts_with($lane, 'adoption-');
    $candidate = ['name' => CORE, 'require' => ['laravel/ai' => $native ? '^1.0' : '^0.11.2']];
    $candidateRef = $native ? NATIVE_CANDIDATE_REF : CANDIDATE_REF;
    $minimum = in_array($lane, ['lowest', 'adoption-minimum', 'native1-minimum'], true);
    $set = packages([
        package(CORE, $adoption ? ($native ? '0.27.0' : '0.26.0') : 'v0.25.0', $adoption ? $candidateRef : PUBLISHED_REF),
        package('laravel/ai', $adoption ? ($native ? 'v1.0.0' : 'v0.11.2') : 'v0.10.0', $adoption ? ($native ? NATIVE_AI_MINIMUM_REF : AI_MINIMUM_REF) : str_repeat('a', 40)),
        package('laravel/framework', 'v13.16.0', str_repeat('b', 40)),
        package('livewire/livewire', $minimum ? 'v3.0.0' : 'v4.1.0', str_repeat('c', 40)),
    ]);
    if ($native) {
        $set[CORE]['require']['laravel/ai'] = '^1.0';
    }
    verify($set, $set, $lane);
    $prepared = prepare($root, $lane, $candidate);
    check($lane !== 'lowest' || $prepared === $root, 'Lowest lane must keep original constraints.');
    check(! $adoption || $prepared['repositories'][0]['package']['source']['reference'] === $candidateRef, 'Candidate must be immutable.');
    check(! $adoption || $prepared['require-dev']['laravel/ai'] === ($minimum ? ($native ? '1.0.0' : '0.11.2') : ($native ? '^1.0' : '^0.11.2')), 'Wrong AI lane pin.');
    check($lane !== 'published-0.25' || $prepared['require'][CORE] === '0.25.0', 'Published lane must stay pinned.');

    // Mutate the Composer evidence shape, both independently and in agreement.
    foreach (array_keys($set) as $name) {
        foreach (['version', 'source', 'dist'] as $field) {
            $bad = $set;
            unset($bad[$name][$field]);
            rejects(fn () => verify($set, $bad, $lane), "missing installed {$field}");
            rejects(fn () => verify($bad, $set, $lane), "missing locked {$field}");
            $controls += 2;
        }
        foreach (['dev-main', 'v99.0.0'] as $version) {
            if ($name === 'laravel/ai' && ! $adoption && $version === 'v99.0.0') {
                continue; // Legacy lanes retain their own transitive AI constraints.
            }
            $bad = $set;
            $bad[$name]['version'] = $version;
            rejects(fn () => verify($bad, $bad, $lane), "incorrect {$name} version");
            $controls++;
        }
        $bad = $set;
        $bad[$name]['source']['url'] = 'https://github.com/example/fork.git';
        rejects(fn () => verify($bad, $bad, $lane), 'fork source');
        $bad = $set;
        $bad[$name]['dist']['url'] = 'https://example.com/patched.zip';
        rejects(fn () => verify($bad, $bad, $lane), 'replaced archive');
        $bad = $set;
        $bad[$name]['dist']['reference'] = str_repeat('d', 40);
        rejects(fn () => verify($bad, $bad, $lane), 'archive ref mismatch');
        $bad = $set;
        $bad[$name]['source']['reference'] = 'main';
        $bad[$name]['dist']['reference'] = 'main';
        $bad[$name]['dist']['url'] = "https://api.github.com/repos/{$name}/zipball/main";
        rejects(fn () => verify($bad, $bad, $lane), 'moving source ref');
        $bad = $set;
        $bad[$name]['source']['type'] = 'svn';
        rejects(fn () => verify($bad, $bad, $lane), 'wrong source type');
        $controls += 5;
    }
    $differentInstalled = $set;
    $differentInstalled['laravel/framework'] = package('laravel/framework', 'v13.17.0', str_repeat('e', 40));
    rejects(fn () => verify($set, $differentInstalled, $lane), 'valid installed package differs from lock');
    $controls++;
    foreach ([CORE, 'laravel/ai'] as $name) {
        if (($name === CORE && $lane === 'lowest') || ($name === 'laravel/ai' && ! in_array($lane, ['adoption-minimum', 'native1-minimum'], true))) {
            continue;
        }
        $bad = $set;
        $bad[$name] = array_replace($set[$name], package($name, $set[$name]['version'], str_repeat('d', 40)));
        rejects(fn () => verify($bad, $bad, $lane), 'wrong pinned commit in otherwise consistent evidence');
        $controls++;
    }
    if ($native) {
        $bad = $set;
        $bad['laravel/ai'] = package('laravel/ai', 'v0.11.2', AI_MINIMUM_REF);
        rejects(fn () => verify($bad, $bad, $lane), 'stale AI generation');
        $bad = $set;
        $bad[CORE] = package(CORE, '0.26.0', CANDIDATE_REF);
        rejects(fn () => verify($bad, $bad, $lane), 'stale core generation');
        $bad = $set;
        $bad[CORE]['require']['laravel/ai'] = '^0.11.2';
        rejects(fn () => verify($bad, $bad, $lane), 'stale core AI contract');
        rejects(fn () => prepare($root, $lane, ['name' => CORE, 'require' => ['laravel/ai' => '^0.11.2']]), 'stale candidate manifest contract');
        $controls += 4;
    }
}
rejects(fn () => prepare($root, 'adoption-current', ['name' => 'example/fork', 'require' => ['laravel/ai' => '^0.11.2']]), 'wrong manifest identity');
rejects(fn () => prepare($root, 'adoption-current', ['name' => CORE, 'require' => ['laravel/ai' => '^0.10']]), 'wrong manifest contract');
$badRoot = $root;
$badRoot['require'][CORE] = '^0.26';
rejects(fn () => prepare($badRoot, 'adoption-current', $candidate), 'dropped older ranges');
rejects(fn () => verify([], [], 'unknown'), 'unknown lane');
echo 'Six positive lanes and '.($controls + 4)." negative dependency controls passed.\n";
