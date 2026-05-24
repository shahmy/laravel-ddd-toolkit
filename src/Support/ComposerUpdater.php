<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * ComposerUpdater — manages composer.json PSR-4 namespace registration.
 *
 * Safety guarantees:
 *   • Reads the current file before every write (no stale in-memory state)
 *   • Writes atomically: writes to a .tmp file first, then renames atomically
 *   • Skips already-registered namespaces (idempotent)
 *   • Runs `composer dump-autoload` only after a successful write
 *   • Cleans up temp files on write failure
 */
final class ComposerUpdater
{
    private readonly Filesystem $files;

    public function __construct(
        private readonly string $composerJsonPath,
        private readonly AtomicFilesystem $atomicFs,
    ) {
        $this->files = new Filesystem();
    }

    // ─── Public API ───────────────────────────────────────────────────────

    /**
     * Add a PSR-4 namespace to the autoload section.
     * Skips silently if the namespace is already registered.
     *
     * @throws RuntimeException On read or write failure
     */
    public function addPsr4Namespace(string $namespace, string $path): void
    {
        $composer = $this->read();

        if ($this->hasNamespace($composer, $namespace)) {
            return; // Already registered — idempotent
        }

        $composer['autoload']           ??= [];
        $composer['autoload']['psr-4']  ??= [];
        $composer['autoload']['psr-4'][$namespace] = $path;

        $this->write($composer);
    }

    /**
     * Read and parse composer.json.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException If the file is missing or contains invalid JSON
     */
    public function read(): array
    {
        if (! $this->files->exists($this->composerJsonPath)) {
            throw new RuntimeException("composer.json not found at: {$this->composerJsonPath}");
        }

        $content = $this->files->get($this->composerJsonPath);
        $data    = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new RuntimeException('composer.json does not contain a JSON object.');
        }

        return $data;
    }

    /**
     * Write composer.json atomically.
     *
     * @param array<string, mixed> $data
     *
     * @throws RuntimeException On write or rename failure
     */
    public function write(array $data): void
    {
        $tmpPath = $this->composerJsonPath . '.' . bin2hex(random_bytes(4)) . '.tmp';

        try {
            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->files->put($tmpPath, $content . PHP_EOL);
            rename($tmpPath, $this->composerJsonPath);
        } catch (\Throwable $e) {
            // Clean up the temp file if it exists
            if ($this->files->exists($tmpPath)) {
                $this->files->delete($tmpPath);
            }
            throw new RuntimeException("Failed to write composer.json: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Execute `composer dump-autoload --optimize` in the project root.
     *
     * @throws RuntimeException If Composer exits with a non-zero code
     */
    public function runDumpAutoload(): void
    {
        $composerBin = $this->detectComposerBin();
        $projectRoot = dirname($this->composerJsonPath);
        $isWindows   = PHP_OS_FAMILY === 'Windows';

        // On Windows, `cd` alone cannot switch drives — `cd /d` is required.
        $cdCmd = $isWindows
            ? 'cd /d ' . escapeshellarg($projectRoot)
            : 'cd ' . escapeshellarg($projectRoot);

        $command = sprintf('%s && %s dump-autoload --optimize 2>&1', $cdCmd, escapeshellarg($composerBin));

        $output   = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'composer dump-autoload failed (exit ' . $exitCode . '): ' . implode("\n", $output),
            );
        }
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $composer
     */
    private function hasNamespace(array $composer, string $namespace): bool
    {
        /** @var array<string, string>|null $psr4 */
        $psr4 = $composer['autoload']['psr-4'] ?? null;
        return isset($psr4[$namespace]);
    }

    private function detectComposerBin(): string
    {
        // Config override takes priority (laravelDdd.composerPath in VSCode maps to ddd.composer_path)
        /** @var string $configured */
        $configured = (string) config('ddd.composer_path', '');
        if ($configured !== '') {
            return $configured;
        }

        $isWindows  = PHP_OS_FAMILY === 'Windows';
        $devNull    = $isWindows ? '2>NUL' : '2>/dev/null';
        $lookupCmd  = $isWindows ? 'where' : 'which';

        // On Windows prefer .bat/.cmd wrappers; on Unix prefer bare binary then fallback paths
        $candidates = $isWindows
            ? ['composer', 'composer.bat', 'composer.cmd']
            : ['composer', '/usr/local/bin/composer', '/usr/bin/composer', 'composer.phar'];

        foreach ($candidates as $candidate) {
            $output = [];
            $code   = 0;
            exec("{$lookupCmd} " . escapeshellarg($candidate) . " {$devNull}", $output, $code);

            if ($code === 0 && ! empty($output)) {
                $found = trim($output[0]);
                // Skip bare .phar files returned by `where` — they are not directly executable
                if ($isWindows && str_ends_with(strtolower($found), '.phar')) {
                    continue;
                }
                return $found;
            }
        }

        return 'composer'; // Last resort — rely on PATH
    }
}
