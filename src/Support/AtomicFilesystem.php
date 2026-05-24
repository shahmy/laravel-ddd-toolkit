<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * AtomicFilesystem — transactional filesystem operations for domain scaffolding.
 *
 * All domain and class generation goes through this class to guarantee that
 * filesystem failures never leave partial structures on disk.
 *
 * Transaction lifecycle:
 *   1. beginTransaction() — creates a temp staging directory
 *   2. stageDirectory() / stageFile() — record intended operations
 *   3. commit() — execute all staged ops; rollback on any failure
 *   4. rollback() — discard staging dir without touching the real filesystem
 */
final class AtomicFilesystem
{
    private readonly Filesystem $files;

    public function __construct()
    {
        $this->files = new Filesystem();
    }

    // ─── Transaction Lifecycle ────────────────────────────────────────────

    /**
     * Open a new transaction and return its token array.
     * The token is a mutable array passed by reference to all stage/commit calls.
     *
     * @return array{id: string, tempDir: string, operations: array<int, array<string, mixed>>, committed: bool, rolledBack: bool}
     */
    public function beginTransaction(): array
    {
        $id = bin2hex(random_bytes(16));
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laravel-ddd-' . $id;

        $this->files->makeDirectory($tempDir, 0755, true);

        return [
            'id'         => $id,
            'tempDir'    => $tempDir,
            'operations' => [],
            'committed'  => false,
            'rolledBack' => false,
        ];
    }

    /**
     * Stage a directory creation in the transaction.
     *
     * @param array<string, mixed> $tx
     */
    public function stageDirectory(array &$tx, string $targetPath): void
    {
        $tx['operations'][] = ['type' => 'create_dir', 'target' => $targetPath];
    }

    /**
     * Stage a file creation in the transaction.
     * The content is written to the staging temp dir immediately.
     *
     * @param array<string, mixed> $tx
     */
    public function stageFile(array &$tx, string $targetPath, string $content): void
    {
        $stageName = basename($targetPath) . '_' . bin2hex(random_bytes(4));
        $stagePath = $tx['tempDir'] . DIRECTORY_SEPARATOR . $stageName;

        $this->files->put($stagePath, $content);

        $tx['operations'][] = [
            'type'    => 'create_file',
            'source'  => $stagePath,
            'target'  => $targetPath,
            'content' => $content,
        ];
    }

    /**
     * Commit the transaction — execute all staged operations.
     * On any failure: undo completed operations and throw.
     *
     * @param array<string, mixed> $tx
     *
     * @throws RuntimeException If any operation fails (all changes rolled back)
     */
    public function commit(array &$tx): void
    {
        if ($tx['committed'] || $tx['rolledBack']) {
            throw new RuntimeException("Transaction {$tx['id']} is already finalized.");
        }

        /** @var array<int, array<string, mixed>> $executedOps */
        $executedOps = [];

        try {
            foreach ($tx['operations'] as $op) {
                $this->executeOperation($op);
                $executedOps[] = $op;
            }
            $tx['committed'] = true;
        } catch (\Throwable $e) {
            $this->rollbackOperations(array_reverse($executedOps));
            $tx['rolledBack'] = true;
            throw new RuntimeException(
                "Atomic operation failed (tx: {$tx['id']}). All changes rolled back. Reason: {$e->getMessage()}",
                0,
                $e,
            );
        } finally {
            $this->cleanupTempDir($tx['tempDir']);
        }
    }

    /**
     * Discard the transaction — clean up the temp dir without touching the real FS.
     *
     * @param array<string, mixed> $tx
     */
    public function rollback(array &$tx): void
    {
        $tx['rolledBack'] = true;
        $this->cleanupTempDir($tx['tempDir']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Add a .gitkeep file to a directory if and only if it is empty
     * (contains no files or subdirectories other than an existing .gitkeep).
     */
    public function ensureGitkeep(string $dirPath): void
    {
        if (! $this->files->isDirectory($dirPath)) {
            return;
        }

        $files = $this->files->files($dirPath);
        $dirs  = $this->files->directories($dirPath);

        $hasRealFiles = count(array_filter($files, fn($f) => $f->getFilename() !== '.gitkeep')) > 0;

        if (! $hasRealFiles && count($dirs) === 0) {
            $this->files->put($dirPath . '/.gitkeep', '');
        }
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $op
     */
    private function executeOperation(array $op): void
    {
        match ($op['type']) {
            'create_dir'  => $this->files->makeDirectory((string) $op['target'], 0755, true, true),
            'create_file' => (function () use ($op): void {
                $this->files->ensureDirectoryExists(dirname((string) $op['target']));
                $this->files->put((string) $op['target'], (string) $op['content']);
            })(),
            default => throw new RuntimeException("Unknown operation type: {$op['type']}"),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $ops
     */
    private function rollbackOperations(array $ops): void
    {
        foreach ($ops as $op) {
            try {
                if ($op['type'] === 'create_file' && $this->files->exists((string) $op['target'])) {
                    $this->files->delete((string) $op['target']);
                } elseif ($op['type'] === 'create_dir' && $this->files->isDirectory((string) $op['target'])) {
                    // Only remove if empty to avoid destroying pre-existing content
                    if (count($this->files->allFiles((string) $op['target'])) === 0) {
                        $this->files->deleteDirectory((string) $op['target']);
                    }
                }
            } catch (\Throwable) {
                // Best-effort rollback — log if a logger were available, but continue
            }
        }
    }

    private function cleanupTempDir(string $tempDir): void
    {
        try {
            if ($this->files->isDirectory($tempDir)) {
                $this->files->deleteDirectory($tempDir);
            }
        } catch (\Throwable) {
            // Non-fatal — OS will clean up tmp eventually
        }
    }
}
