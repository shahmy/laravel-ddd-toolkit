<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * StubEngine — renders PHP stub templates by replacing {{ VARIABLE }} placeholders.
 *
 * Template resolution order:
 *   1. Custom path from config('ddd.stubs_path')
 *   2. Published stubs in project-root/stubs/ddd/
 *   3. Built-in stubs shipped with the package
 *
 * No external templating library is required — pure str_replace() keeps
 * the package lean and avoids unnecessary dependencies.
 */
final class StubEngine
{
    private readonly Filesystem $files;

    public function __construct(
        private readonly string $stubsPath,
        private readonly AtomicFilesystem $atomicFs,
    ) {
        $this->files = new Filesystem();
    }

    // ─── Public API ───────────────────────────────────────────────────────

    /**
     * Render a stub template with the given variable map.
     *
     * @param array<string, string> $variables
     *
     * @throws RuntimeException If the stub file is not found
     */
    public function render(string $stubName, array $variables): string
    {
        $stubPath = $this->resolveStubPath($stubName);
        $content  = $this->files->get($stubPath);

        return $this->replaceVariables($content, $variables);
    }

    /**
     * Render a stub and write it to a target path atomically.
     *
     * @param array<string, string> $variables
     *
     * @throws RuntimeException If the target file already exists or write fails
     */
    public function renderToFile(string $stubName, array $variables, string $targetPath, bool $force = false): void
    {
        if ($this->files->exists($targetPath) && ! $force) {
            throw new RuntimeException("File already exists: {$targetPath}. Use --force to overwrite.");
        }

        $content = $this->render($stubName, $variables);

        $tx = $this->atomicFs->beginTransaction();
        try {
            $this->atomicFs->stageFile($tx, $targetPath, $content);
            $this->atomicFs->commit($tx);
        } catch (\Throwable $e) {
            $this->atomicFs->rollback($tx);
            throw $e;
        }
    }

    /**
     * Return the names of all available stubs (without .stub extension).
     *
     * @return array<int, string>
     */
    public function getAvailableStubs(): array
    {
        if (! $this->files->isDirectory($this->stubsPath)) {
            return [];
        }

        return collect($this->files->files($this->stubsPath))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.stub'))
            ->map(fn($f) => pathinfo($f->getFilename(), PATHINFO_FILENAME))
            ->values()
            ->sort()
            ->all();
    }

    /**
     * Check whether a stub with the given name is available.
     */
    public function stubExists(string $stubName): bool
    {
        try {
            $this->resolveStubPath($stubName);
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function resolveStubPath(string $stubName): string
    {
        $stubFile = $stubName . '.stub';
        $path     = rtrim($this->stubsPath, '/\\') . DIRECTORY_SEPARATOR . $stubFile;

        if ($this->files->exists($path)) {
            return $path;
        }

        throw new RuntimeException(
            "Stub not found: '{$stubName}' at {$path}. " .
            "Run `php artisan vendor:publish --tag=ddd-stubs` to publish stubs.",
        );
    }

    /**
     * Replace all {{ KEY }} and {{KEY}} occurrences with their values.
     *
     * @param array<string, string> $vars
     */
    private function replaceVariables(string $content, array $vars): string
    {
        foreach ($vars as $key => $value) {
            // Handle {{ KEY }}, {{KEY}}, and {{ key }} variants
            $content = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/i', $value, $content) ?? $content;
        }
        return $content;
    }
}
