<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\DomainGenerator;
use Shahmy\LaravelDddToolkit\Support\ComposerUpdater;
use Shahmy\LaravelDddToolkit\Support\NamespaceGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * ddd:domain — Create a new DDD bounded context with the full layer structure.
 */
final class DddDomainCommand extends Command
{
    protected $signature = 'ddd:domain
                            {name : The domain name in PascalCase (e.g. Merchant)}
                            {--force : Overwrite an existing domain}
                            {--dry-run : Preview what would be created without making any changes}';

    protected $description = 'Create a new DDD domain with Application, Domain, Infrastructure, Presentation, and Tests layers';

    public function handle(
        DomainGenerator    $generator,
        ComposerUpdater    $composerUpdater,
        NamespaceGenerator $namespaceGen,
    ): int {
        $name    = (string) $this->argument('name');
        $force   = (bool) $this->option('force');
        $dryRun  = (bool) $this->option('dry-run');
        $srcPath = DomainGenerator::resolveSrcPath();
        $files   = new Filesystem();

        // ── Validate name ────────────────────────────────────────────────

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error("Invalid domain name: '{$name}'. Must be PascalCase (e.g. Merchant, UserManagement).");
            return self::FAILURE;
        }

        $domainPath = $srcPath . '/' . $name;

        if (! $dryRun && $files->isDirectory($domainPath) && ! $force) {
            $this->error("Domain '{$name}' already exists at {$domainPath}.");
            $this->line('  Use <fg=yellow>--force</> to overwrite.');
            return self::FAILURE;
        }

        // ── Scaffold ─────────────────────────────────────────────────────

        $this->info("Creating domain: <fg=cyan>{$name}</>");
        $this->newLine();

        if ($dryRun) {
            $this->comment('[DRY RUN] No files will be created.');
            $this->newLine();
        }

        try {
            $created = $generator->generate($name, $dryRun);
        } catch (\Throwable $e) {
            $this->error("Generation failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        foreach ($created as $path) {
            $relative = str_replace(base_path() . '/', '', $path);
            $prefix   = $dryRun ? '<fg=cyan>[dry-run]</> Would create:' : '<fg=green>✓</> Created:';
            $this->line("  {$prefix} {$relative}");
        }

        // ── Namespace registration ────────────────────────────────────────

        if (! $dryRun && (bool) config('ddd.auto_register_namespaces', true)) {
            $this->newLine();

            $entry = $namespaceGen->getSrcAutoloadEntry();

            try {
                $composerUpdater->addPsr4Namespace($entry['namespace'], $entry['path']);

                if ((bool) config('ddd.auto_dump_autoload', true)) {
                    $composerUpdater->runDumpAutoload();
                    $this->line('  <fg=green>✓</> Autoload regenerated');
                }
            } catch (\Throwable $e) {
                $this->warn("  Namespace registration failed: {$e->getMessage()}");
                $this->warn('  Run manually: composer dump-autoload');
            }
        }

        $this->newLine();

        if (! $dryRun) {
            $this->info("✅ Domain <fg=cyan>{$name}</> created successfully!");
            $this->newLine();
            $this->comment('Next steps:');
            $this->line("  <fg=cyan>php artisan ddd:entity</> <fg=yellow>{$name} {$name}Entity</>");
            $this->line("  <fg=cyan>php artisan ddd:repository</> <fg=yellow>{$name} {$name}Repository</>");
            $this->line("  <fg=cyan>php artisan ddd:usecase</> <fg=yellow>{$name} Create{$name}UseCase</>");
        } else {
            $this->comment('Dry run complete. Run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
