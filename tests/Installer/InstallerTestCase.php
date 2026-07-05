<?php

declare(strict_types=1);

namespace BuiltByBerry\LaravelSwarmPulse\Tests\Installer;

use BuiltByBerry\LaravelSwarmPulse\PulseServiceProvider;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Laravel\Pulse\PulseServiceProvider as VendorPulseServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PHPUnit\Framework\Assert;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Base test case for `swarm:install:pulse` command tests.
 *
 * Materializes a temp directory shaped like a minimal Laravel skeleton, points
 * the booted testbench application at it, runs the installer under test, and
 * exposes ergonomic assertions over the resulting filesystem state. Trimmed
 * copy of the equivalent harness in `builtbyberry/laravel-swarm` — see that
 * package's tests/Installer/README.md for the full rationale.
 *
 * @see InstallerRunResult
 * @see DoubleRunResult
 */
abstract class InstallerTestCase extends Orchestra
{
    /** Absolute path to this test's scratch skeleton. */
    protected string $skeletonPath;

    /** Filesystem helper, instantiated lazily. */
    private ?Filesystem $files = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skeletonPath = $this->makeSkeletonDirectory();
        $this->materializeSkeleton($this->skeletonPath);

        $this->app->setBasePath($this->skeletonPath);
    }

    protected function tearDown(): void
    {
        if (isset($this->skeletonPath) && is_dir($this->skeletonPath)) {
            $this->filesystem()->deleteDirectory($this->skeletonPath);
        }

        parent::tearDown();
    }

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

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    /**
     * Run an installer command once against the scratch skeleton.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function runInstaller(string $command, array $arguments = []): InstallerRunResult
    {
        return $this->runInstallerOnce($command, $arguments);
    }

    /**
     * @internal
     *
     * @param  array<string, mixed>  $arguments
     */
    public function runInstallerOnce(string $command, array $arguments = []): InstallerRunResult
    {
        /** @var ConsoleKernel $kernel */
        $kernel = $this->app->make(ConsoleKernel::class);
        $output = new BufferedOutput;

        $exitCode = $kernel->call($command, $arguments, $output);

        return new InstallerRunResult(
            command: $command,
            arguments: $arguments,
            exitCode: $exitCode,
            output: $output->fetch(),
            skeletonSnapshot: $this->snapshotSkeleton(),
            testCase: $this,
        );
    }

    /**
     * Refusal-path helper: assert the installer exits non-zero AND its output
     * contains the given error fragment.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function assertInstallerFailsWith(string $command, array $arguments, string $expectedErrorFragment): InstallerRunResult
    {
        $result = $this->runInstallerOnce($command, $arguments);

        Assert::assertNotSame(
            0,
            $result->exitCode,
            "Installer [{$command}] was expected to fail but exited 0.\nOutput:\n{$result->output}",
        );

        Assert::assertStringContainsString(
            $expectedErrorFragment,
            $result->output,
            "Installer [{$command}] failed (exit {$result->exitCode}) but its output did not contain the expected error fragment.",
        );

        return $result;
    }

    /**
     * Assert a skeleton file (relative to the skeleton root) contains the
     * given fragment.
     */
    public function assertFileContains(string $relativePath, string $needle): void
    {
        $absolute = $this->skeletonPath($relativePath);

        Assert::assertFileExists(
            $absolute,
            "Expected installer to create [{$relativePath}] but the file does not exist.",
        );

        $contents = (string) file_get_contents($absolute);

        Assert::assertStringContainsString(
            $needle,
            $contents,
            "File [{$relativePath}] exists but does not contain expected fragment.",
        );
    }

    /**
     * Resolve a path inside the scratch skeleton.
     */
    public function skeletonPath(string $relative = ''): string
    {
        return $relative === ''
            ? $this->skeletonPath
            : $this->skeletonPath.DIRECTORY_SEPARATOR.ltrim($relative, '/\\');
    }

    /**
     * Drop a file into the skeleton at a relative path (parents created on
     * demand).
     */
    public function writeSkeletonFile(string $relative, string $contents): void
    {
        $absolute = $this->skeletonPath($relative);
        $directory = dirname($absolute);

        if (! is_dir($directory)) {
            $this->filesystem()->makeDirectory($directory, recursive: true);
        }

        file_put_contents($absolute, $contents);
    }

    /**
     * Build the minimal Laravel-like skeleton at the given path.
     */
    protected function materializeSkeleton(string $base): void
    {
        $fs = $this->filesystem();

        foreach ([
            'config',
            'resources/views',
            'storage/app',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ] as $subdir) {
            $fs->ensureDirectoryExists($base.DIRECTORY_SEPARATOR.$subdir);
        }
    }

    /**
     * Capture a sha256-per-file snapshot of every file in the skeleton.
     *
     * @return array<string, string> relative path => sha256
     */
    public function snapshotSkeleton(): array
    {
        if (! is_dir($this->skeletonPath)) {
            return [];
        }

        $snapshot = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->skeletonPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $prefixLength = strlen($this->skeletonPath) + 1;

        foreach ($iterator as $info) {
            /** @var \SplFileInfo $info */
            if (! $info->isFile()) {
                continue;
            }

            $absolute = $info->getPathname();
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($absolute, $prefixLength));
            $hash = hash_file('sha256', $absolute);

            if ($hash !== false) {
                $snapshot[$relative] = $hash;
            }
        }

        ksort($snapshot);

        return $snapshot;
    }

    private function makeSkeletonDirectory(): string
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-swarm-pulse-installer-'.bin2hex(random_bytes(8));

        if (is_dir($base)) {
            $this->filesystem()->deleteDirectory($base);
        }

        if (! mkdir($base, 0o755, true) && ! is_dir($base)) {
            throw new RuntimeException("Unable to create installer skeleton directory at [{$base}].");
        }

        return $base;
    }

    private function filesystem(): Filesystem
    {
        return $this->files ??= new Filesystem;
    }
}
