<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\DomainGenerator;
use Shahmy\LaravelDddToolkit\Support\ComposerUpdater;
use Shahmy\LaravelDddToolkit\Support\NamespaceGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * ddd:init — Initialize the Laravel DDD project structure.
 *
 * Creates the src/ directory, scaffolds the Shared domain,
 * registers the Src\\ namespace in composer.json, and runs
 * composer dump-autoload.
 */
final class DddInitCommand extends Command
{
    protected $signature = 'ddd:init
                            {--dry-run : Preview what would be created without making any changes}';

    protected $description = 'Initialize the Laravel DDD project structure with src/ and the Shared domain';

    public function handle(
        DomainGenerator   $generator,
        ComposerUpdater   $composerUpdater,
        NamespaceGenerator $namespaceGen,
    ): int {
        $dryRun  = (bool) $this->option('dry-run');
        $srcPath = DomainGenerator::resolveSrcPath();
        $srcDir  = (string) config('ddd.src_directory', 'src');
        $files   = new Filesystem();

        $this->info('🚀 Initializing Laravel DDD project structure…');
        $this->newLine();

        if ($dryRun) {
            $this->comment('[DRY RUN] No files will be created.');
            $this->newLine();
        }

        // ── Step 1: Create src/ ──────────────────────────────────────────

        if ($dryRun) {
            $this->line("  <fg=cyan>[dry-run]</> Would create: {$srcDir}/");
        } elseif ($files->isDirectory($srcPath)) {
            $this->line("  <fg=yellow>→</> Already exists: {$srcDir}/");
        } else {
            $files->makeDirectory($srcPath, 0755, true);
            $this->line("  <fg=green>✓</> Created: {$srcDir}/");
        }

        // ── Step 2: Scaffold Shared domain ───────────────────────────────

        $sharedDomain = (string) config('ddd.shared_domain', 'Shared');
        $sharedPath   = $srcPath . '/' . $sharedDomain;

        $this->newLine();
        $this->info("Creating '{$sharedDomain}' domain…");

        if (! $dryRun && $files->isDirectory($sharedPath)) {
            $this->line("  <fg=yellow>→</> Shared domain already exists. Skipping.");
        } else {
            $created = $generator->generate($sharedDomain, $dryRun);

            foreach ($created as $path) {
                $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
                $prefix   = $dryRun ? '<fg=cyan>[dry-run]</> Would create:' : '<fg=green>✓</> Created:';
                $this->line("  {$prefix} {$relative}");
            }
        }

        // ── Step 3: Update composer.json ─────────────────────────────────

        if (! $dryRun) {
            $this->newLine();
            $this->info('Updating composer.json…');

            $entry = $namespaceGen->getSrcAutoloadEntry();

            try {
                $composerUpdater->addPsr4Namespace($entry['namespace'], $entry['path']);
                $this->line("  <fg=green>✓</> Registered: \"{$entry['namespace']}\" → \"{$entry['path']}\"");
            } catch (\Throwable $e) {
                $this->error("  Failed to update composer.json: {$e->getMessage()}");
                return self::FAILURE;
            }

            // ── Step 4: composer dump-autoload ────────────────────────────

            if ((bool) config('ddd.auto_dump_autoload', true)) {
                $this->newLine();
                $this->info('Running composer dump-autoload…');

                try {
                    $composerUpdater->runDumpAutoload();
                    $this->line('  <fg=green>✓</> Autoload files regenerated');
                } catch (\Throwable $e) {
                    $this->warn("  Could not run composer dump-autoload automatically: {$e->getMessage()}");
                    $this->warn('  Please run it manually: composer dump-autoload');
                }
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->comment('Dry run complete. Run without --dry-run to apply changes.');
        } else {
            $this->info('✅ Laravel DDD project initialized successfully!');
            $this->newLine();
            $this->comment('Next steps:');
            $this->line('  <fg=cyan>php artisan ddd:domain</> <fg=yellow>Merchant</>');
            $this->line('  <fg=cyan>php artisan ddd:entity</> <fg=yellow>Merchant Product</>');
        }

        return self::SUCCESS;
    }
}
