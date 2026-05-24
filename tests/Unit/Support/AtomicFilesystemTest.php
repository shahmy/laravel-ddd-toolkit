<?php

declare(strict_types=1);

use Shahmy\LaravelDddToolkit\Support\AtomicFilesystem;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->fs         = new AtomicFilesystem();
    $this->files      = new Filesystem();
    $this->testRoot   = sys_get_temp_dir() . '/ddd-test-' . uniqid('', true);
    $this->files->makeDirectory($this->testRoot, 0755, true);
});

afterEach(function (): void {
    if ($this->files->isDirectory($this->testRoot)) {
        $this->files->deleteDirectory($this->testRoot);
    }
});

describe('AtomicFilesystem', function (): void {

    describe('beginTransaction', function (): void {
        it('creates a temp directory', function (): void {
            $tx = $this->fs->beginTransaction();

            expect($tx['id'])->toBeString()->not->toBeEmpty();
            expect($tx['tempDir'])->toBeString();
            expect(is_dir($tx['tempDir']))->toBeTrue();
            expect($tx['committed'])->toBeFalse();
            expect($tx['rolledBack'])->toBeFalse();

            // Cleanup
            $this->fs->rollback($tx);
        });

        it('generates unique IDs for concurrent transactions', function (): void {
            $tx1 = $this->fs->beginTransaction();
            $tx2 = $this->fs->beginTransaction();

            expect($tx1['id'])->not->toBe($tx2['id']);
            expect($tx1['tempDir'])->not->toBe($tx2['tempDir']);

            $this->fs->rollback($tx1);
            $this->fs->rollback($tx2);
        });
    });

    describe('commit — directory creation', function (): void {
        it('creates staged directories on commit', function (): void {
            $targetDir = $this->testRoot . '/Merchant/Domain/Entities';

            $tx = $this->fs->beginTransaction();
            $this->fs->stageDirectory($tx, $targetDir);
            $this->fs->commit($tx);

            expect(is_dir($targetDir))->toBeTrue();
            expect($tx['committed'])->toBeTrue();
        });

        it('creates nested directories atomically', function (): void {
            $paths = [
                $this->testRoot . '/src',
                $this->testRoot . '/src/Merchant',
                $this->testRoot . '/src/Merchant/Application',
                $this->testRoot . '/src/Merchant/Domain',
            ];

            $tx = $this->fs->beginTransaction();
            foreach ($paths as $path) {
                $this->fs->stageDirectory($tx, $path);
            }
            $this->fs->commit($tx);

            foreach ($paths as $path) {
                expect(is_dir($path))->toBeTrue("Expected directory: {$path}");
            }
        });
    });

    describe('commit — file creation', function (): void {
        it('creates staged files on commit', function (): void {
            $targetPath = $this->testRoot . '/README.md';

            $tx = $this->fs->beginTransaction();
            $this->fs->stageFile($tx, $targetPath, '# Test README');
            $this->fs->commit($tx);

            expect(file_exists($targetPath))->toBeTrue();
            expect(file_get_contents($targetPath))->toBe('# Test README');
        });
    });

    describe('rollback', function (): void {
        it('discards the temp directory', function (): void {
            $tx = $this->fs->beginTransaction();
            $tempDir = $tx['tempDir'];

            expect(is_dir($tempDir))->toBeTrue();

            $this->fs->rollback($tx);

            expect(is_dir($tempDir))->toBeFalse();
            expect($tx['rolledBack'])->toBeTrue();
        });
    });

    describe('ensureGitkeep', function (): void {
        it('adds .gitkeep to an empty directory', function (): void {
            $dir = $this->testRoot . '/empty-dir';
            mkdir($dir, 0755, true);

            $this->fs->ensureGitkeep($dir);

            expect(file_exists($dir . '/.gitkeep'))->toBeTrue();
        });

        it('does not add .gitkeep when directory has real files', function (): void {
            $dir = $this->testRoot . '/non-empty-dir';
            mkdir($dir, 0755, true);
            file_put_contents($dir . '/SomeClass.php', '<?php');

            $this->fs->ensureGitkeep($dir);

            expect(file_exists($dir . '/.gitkeep'))->toBeFalse();
        });

        it('is idempotent — does not create duplicate .gitkeep', function (): void {
            $dir = $this->testRoot . '/idempotent-dir';
            mkdir($dir, 0755, true);

            $this->fs->ensureGitkeep($dir);
            $this->fs->ensureGitkeep($dir); // Second call should not error

            $count = count(glob($dir . '/.gitkeep') ?: []);
            expect($count)->toBe(1);
        });
    });

});
